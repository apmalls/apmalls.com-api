<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Helpers\NumberHelper;
use App\Models\Payment\Payment;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected SaleRepositoryInterface $saleRepository,
        protected PurchaseRepositoryInterface $purchaseRepository,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($filters);
    }

    public function trashedPaginate(array $filters = []): LengthAwarePaginator
    {
        return $this->paymentRepository->trashedPaginate($filters);
    }

    public function all(): Collection
    {
        return $this->paymentRepository->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(int $id): Payment
    {
        return $this->paymentRepository->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            $data['payment_no'] ??= NumberHelper::generate(
                Payment::class,
                'payment_no',
                'PAY'
            );

            return $this->paymentRepository->create($data);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(int $id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {

            $payment = $this->find($id);

            if ($payment->isRefunded()) {
                throw new \Exception('Refunded payment cannot be updated.');
            }

            return $this->paymentRepository->update($id, $data);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $payment = $this->find($id);

            if ($payment->isCompleted()) {
                throw new \Exception('Completed payment cannot be deleted.');
            }

            return $this->paymentRepository->delete($id);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    */

    public function restore(int $id): bool
    {
        return $this->paymentRepository->restore($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete
    |--------------------------------------------------------------------------
    */

    public function forceDelete(int $id): bool
    {
        return $this->paymentRepository->forceDelete($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(int $id, string $status): Payment
    {
        return DB::transaction(function () use ($id, $status) {

            return $this->paymentRepository
                ->changeStatus($id, $status);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Pay
    |--------------------------------------------------------------------------
    */

    public function pay(array $data): Payment
    {
        return $this->create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Refund
    |--------------------------------------------------------------------------
    */

    public function refund(int $id, array $data = []): Payment
    {
        return DB::transaction(function () use ($id) {

            return $this->paymentRepository->changeStatus(
                $id,
                Payment::STATUS_REFUNDED
            );

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Verify
    |--------------------------------------------------------------------------
    */

    public function verifyPayment(int $id): Payment
    {
        return $this->paymentRepository
            ->changeStatus(
                $id,
                Payment::STATUS_COMPLETED
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function totalPaid(
        string $paymentableType,
        int $paymentableId
    ): float {

        return $this->paymentRepository
            ->totalPaid(
                $paymentableType,
                $paymentableId
            );
    }

    public function completedPayments(): Collection
    {
        return $this->paymentRepository->completedPayments();
    }

    public function pendingPayments(): Collection
    {
        return $this->paymentRepository->pendingPayments();
    }

    public function failedPayments(): Collection
    {
        return $this->paymentRepository->failedPayments();
    }

    public function refundedPayments(): Collection
    {
        return $this->paymentRepository->refundedPayments();
    }

    public function cancelledPayments(): Collection
    {
        return $this->paymentRepository->cancelledPayments();
    }

    public function todayPayments(): Collection
    {
        return $this->paymentRepository->todayPayments();
    }

    public function betweenDates(
        string $fromDate,
        string $toDate
    ): Collection {
        return $this->paymentRepository
            ->betweenDates($fromDate, $toDate);
    }

    // Remove these for now

    public function createPurchasePayment(
        int $purchaseId,
        array $data
    ): Payment {

        return DB::transaction(function () use ($purchaseId, $data) {

            $purchase = $this->purchaseRepository->findOrFail($purchaseId);

            $data['payment_no'] ??= NumberHelper::generate(
                Payment::class,
                'payment_no',
                'PAY'
            );

            $data['paymentable_type'] = PurchaseOrder::class;
            $data['paymentable_id'] = $purchase->id;
            $data['supplier_id'] = $purchase->supplier_id;

            $payment = $this->paymentRepository->create($data);

            $this->updatePurchasePaymentSummary($purchaseId);

            return $payment;
        });
    }

    public function createSalePayment(
        int $saleId,
        array $data
    ): Payment {

        return DB::transaction(function () use ($saleId, $data) {

            $sale = $this->saleRepository->findOrFail($saleId);

            $data['payment_no'] ??= NumberHelper::generate(
                Payment::class,
                'payment_no',
                'PAY'
            );

            $data['paymentable_type'] = SaleOrder::class;
            $data['paymentable_id'] = $sale->id;
            $data['customer_id'] = $sale->customer_id;

            $payment = $this->paymentRepository->create($data);

            $this->updateSalePaymentSummary($saleId);

            return $payment;
        });
    }

    private function updateSalePaymentSummary(
        int $saleId
    ): void {

        $sale = $this->saleRepository->findOrFail($saleId);

        $paid = $this->paymentRepository->totalPaid(
            SaleOrder::class,
            $saleId
        );

        $due = max(
            0,
            $sale->grand_total - $paid
        );

        if ($paid <= 0) {

            $status = SaleOrder::PAYMENT_PENDING;

        } elseif ($paid < $sale->grand_total) {

            $status = SaleOrder::PAYMENT_PARTIAL;

        } else {

            $status = SaleOrder::PAYMENT_COMPLETED;
        }

        $this->saleRepository->update($saleId, [

            'paid_amount' => $paid,

            'due_amount' => $due,

            'payment_status' => $status,

        ]);
    }

    private function updatePurchasePaymentSummary(
        int $purchaseId
    ): void {

        $purchase = $this->purchaseRepository->findOrFail($purchaseId);

        $paid = $this->paymentRepository->totalPaid(
            PurchaseOrder::class,
            $purchaseId
        );

        $due = max(
            0,
            $purchase->grand_total - $paid
        );

        if ($paid <= 0) {

            $status = PurchaseOrder::PAYMENT_PENDING;

        } elseif ($paid < $purchase->grand_total) {

            $status = PurchaseOrder::PAYMENT_PARTIAL;

        } else {

            $status = PurchaseOrder::PAYMENT_COMPLETED;
        }

        $this->purchaseRepository->update($purchaseId, [

            'paid_amount' => $paid,

            'due_amount' => $due,

            'payment_status' => $status,

        ]);
    }

    public function createAdvancePayment(
        array $data
    ): Payment {
        throw new \BadMethodCallException('Not implemented yet.');
    }

    public function createGatewayOrder(
        int $paymentId
    ): array {
        throw new \BadMethodCallException('Not implemented yet.');
    }

    public function verifyGatewayPayment(
        array $payload
    ): Payment {
        throw new \BadMethodCallException('Not implemented yet.');
    }

    public function webhook(
        array $payload
    ): bool {
        throw new \BadMethodCallException('Not implemented yet.');
    }



}
