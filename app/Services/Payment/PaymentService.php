<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Helpers\NumberHelper;
use App\Models\Payment\Payment;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,

    ) {
    }

    /**
     * Payment Listing
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->paymentRepository->paginate($filters);
    }

    /**
     * Find Payment
     */
    public function find(int $id): Payment
    {
        return $this->paymentRepository->find($id);
    }

    /**
     * Store Payment
     */
    public function store(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Get Purchase / Sale
            |--------------------------------------------------------------------------
            */

            $module = $this->getModule(

                $data['module'],

                $data['module_id']

            );

            /*
            |--------------------------------------------------------------------------
            | Validate
            |--------------------------------------------------------------------------
            */

            $this->validatePayment(

                $module,

                $data['amount']

            );

            /*
            |--------------------------------------------------------------------------
            | Create Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentRepository->create([

                'payment_no' => $this->generatePaymentNumber(),

                'module' => $data['module'],

                'module_id' => $data['module_id'],

                'payment_mode_id' => $data['payment_mode_id'],

                'payment_date' => $data['payment_date'],

                'amount' => $data['amount'],

                'transaction_no' => $data['transaction_no'] ?? null,

                'reference_no' => $data['reference_no'] ?? null,

                'remarks' => $data['remarks'] ?? null,

                'status' => 'Completed',

                'created_by' => auth()->id(),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Purchase / Sale Amount
            |--------------------------------------------------------------------------
            */

            $this->updateModuleAmounts($module);

            return $payment->load('paymentMode');

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Private Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get Module
     */
    private function getModule(
        string $module,
        int $moduleId
    ) {
        return match ($module) {

            'purchase' => PurchaseOrder::findOrFail($moduleId),

            'sale' => SaleOrder::findOrFail($moduleId),

            default => throw new InvalidArgumentException(
                'Invalid payment module.'
            ),

        };
    }

    /**
     * Validate Payment
     */
    private function validatePayment(
        $module,
        float $amount
    ): void {

        if ($amount <= 0) {

            throw new InvalidArgumentException(
                'Payment amount must be greater than zero.'
            );

        }

        if ($module->status !== 'Completed') {

            throw new InvalidArgumentException(
                'Payment can be accepted only for completed documents.'
            );

        }

        if ($amount > $module->due_amount) {

            throw new InvalidArgumentException(
                'Payment amount exceeds due amount.'
            );

        }
    }

    /**
     * Update Paid & Due Amount
     */
    private function updateModuleAmounts($module): void
    {
        $paidAmount = $module
            ->payments()
            ->where('status', 'Completed')
            ->sum('amount');

        $module->update([

            'paid_amount' => $paidAmount,

            'due_amount' => $module->grand_total - $paidAmount,

        ]);
    }

    /**
     * Generate Payment Number
     */
    private function generatePaymentNumber(): string
    {
        return NumberHelper::generate(

            Payment::class,

            'payment_no',

            'PAY'

        );
    }


    /**
     * Update Payment
     */
    public function update(int $id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {

            $payment = $this->paymentRepository->find($id);

            if ($payment->status === 'Completed') {
                throw new InvalidArgumentException(
                    'Completed payment cannot be updated.'
                );
            }

            $module = $this->getModule(
                $payment->module,
                $payment->module_id
            );

            $this->validatePayment(
                $module,
                $data['amount']
            );

            $payment = $this->paymentRepository->update(
                $payment,
                [
                    'payment_mode_id' => $data['payment_mode_id'],
                    'payment_date' => $data['payment_date'],
                    'amount' => $data['amount'],
                    'transaction_no' => $data['transaction_no'] ?? null,
                    'reference_no' => $data['reference_no'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'updated_by' => auth()->id(),
                ]
            );

            $this->updateModuleAmounts($module);

            return $payment->load('paymentMode');
        });
    }

    /**
     * Change Payment Status
     */
    public function changeStatus(
        int $id,
        string $status
    ): Payment {

        return DB::transaction(function () use ($id, $status) {

            $payment = $this->paymentRepository->find($id);

            $payment = $this->paymentRepository->update(
                $payment,
                [
                    'status' => $status,
                    'updated_by' => auth()->id(),
                ]
            );

            $module = $this->getModule(
                $payment->module,
                $payment->module_id
            );

            $this->updateModuleAmounts($module);

            return $payment->refresh();
        });
    }

    /**
     * Soft Delete Payment
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $payment = $this->paymentRepository->find($id);

            if ($payment->status === 'Completed') {
                throw new InvalidArgumentException(
                    'Completed payment cannot be deleted.'
                );
            }

            return $this->paymentRepository->delete($payment);
        });
    }

    /**
     * Restore Payment
     */
    public function restore(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->paymentRepository->restore($id);

        });
    }

    /**
     * Permanently Delete Payment
     */
    public function forceDelete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->paymentRepository->forceDelete($id);

        });
    }


    /**
     * Get completed payment amount.
     */
    public function getCompletedAmount(
        string $module,
        int $moduleId
    ): float {

        return (float) Payment::query()

            ->where('module', $module)

            ->where('module_id', $moduleId)

            ->where('status', 'Completed')

            ->sum('amount');

    }

    /**
     * Get module payments.
     */
    public function getModulePayments(
        string $module,
        int $moduleId
    ): Collection {

        return Payment::query()

            ->with([
                'paymentMode',
                'creator',
                'updater',
            ])

            ->where('module', $module)

            ->where('module_id', $moduleId)

            ->latest()

            ->get();

    }
}
