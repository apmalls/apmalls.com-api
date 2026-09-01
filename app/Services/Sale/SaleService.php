<?php

namespace App\Services\Sale;

use App\Models\Delivery\DeliveryConfirmation;
use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\SaleOrderItemRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\Contracts\SaleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService implements SaleServiceInterface
{
    public function __construct(
        protected SaleRepositoryInterface $saleRepository,
        protected SaleOrderItemRepositoryInterface $saleOrderItemRepository
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->saleRepository
            ->paginate($perPage, $filters);
    }

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return $this->saleRepository
            ->trashedPaginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {

        return $this->saleRepository
            ->all($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?SaleOrder {

        return $this->saleRepository
            ->find($id);
    }

    public function findOrFail(
        int $id
    ): SaleOrder {

        return $this->saleRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleOrder {

        return DB::transaction(function () use ($data) {

            $items = $data['items'] ?? [];

            unset($data['items']);

            if (empty($data['sale_no'])) {

                $data['sale_no'] = NumberHelper::generate(
                    SaleOrder::class,
                    'sale_no',
                    'SO'
                );
            }

            $sale = $this->saleRepository->create($data);

            foreach ($items as $item) {

                $sale->items()->create([

                    'product_id' => $item['product_id'],

                    'unit_id' => $item['unit_id'],

                    'quantity' => $item['quantity'],

                    'returned_quantity' => 0,

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'],

                    'tax_percent' => $item['tax_percent'] ?? 0,

                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,

                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],
                ]);

                if (in_array($sale->status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true)) {
                    StockHelper::decrease(
                        productId: $item['product_id'],
                        quantity: $item['quantity'],
                        referenceType: SaleOrder::class,
                        referenceId: $sale->id,
                        remarks: 'Sale',
                        idempotencyKey: "sale:{$sale->id}:product:{$item['product_id']}:created"
                    );
                }
            }

            return $sale->load([
                'customer',
                'billingAddress',
                'shippingAddress',
                'items.product',
                'items.unit',
            ]);
        });
    }

    public function update(
        int $id,
        array $data
    ): SaleOrder {

        return DB::transaction(function () use ($id, $data) {

            $sale = $this->saleRepository->findOrFail($id);
            $oldStatus = $sale->status;

            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                throw ValidationException::withMessages([
                    'status' => 'Use the sale status action to change status.',
                ]);
            }

            $oldItems = $sale->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])->values()->all();

            $items = $data['items'] ?? [];

            unset($data['items']);

            $sale = $this->saleRepository
                ->update($id, $data);

            $oldStockApplied = in_array($oldStatus, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true);
            $newStockApplied = in_array($sale->status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true);
            $version = hash('sha256', json_encode([$oldStatus, $oldItems, $sale->status, $items]));

            foreach ($sale->items as $oldItem) {

                if ($oldStockApplied) {
                    StockHelper::increase(
                    productId: $oldItem->product_id,
                    quantity: $oldItem->quantity,
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale update rollback',
                    idempotencyKey: "sale:{$sale->id}:product:{$oldItem->product_id}:update:{$version}:rollback"
                    );
                }
            }

            $this->saleOrderItemRepository
                ->deleteBySaleOrder($sale->id);

            foreach ($items as $item) {

                $this->saleOrderItemRepository->create([

                    'sale_order_id' => $sale->id,

                    'product_id' => $item['product_id'],

                    'unit_id' => $item['unit_id'],

                    'quantity' => $item['quantity'],

                    'returned_quantity' => 0,

                    'purchase_price' => $item['purchase_price'],

                    'selling_price' => $item['selling_price'],

                    'tax_percent' => $item['tax_percent'] ?? 0,

                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,

                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],

                ]);

                if ($newStockApplied) {
                    StockHelper::decrease(
                    productId: $item['product_id'],
                    quantity: $item['quantity'],
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale updated',
                    idempotencyKey: "sale:{$sale->id}:product:{$item['product_id']}:update:{$version}:apply"
                    );
                }
            }

            return $sale->load([
                'customer',
                'billingAddress',
                'shippingAddress',
                'items.product',
                'items.unit',
            ]);
        });
    }

    public function delete(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            $sale = $this->saleRepository
                ->findOrFail($id);

            if ($sale->payments()->exists()) {

                throw ValidationException::withMessages([
                    'sale' => 'Sale has payment(s). Delete them first.'
                ]);
            }

            if ($sale->saleReturns()->exists()) {

                throw ValidationException::withMessages([
                    'sale' => 'Sale has return(s). Delete them first.'
                ]);
            }

            foreach ($sale->items as $item) {

                if (in_array($sale->status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true)) {
                    StockHelper::increase(
                        productId: $item->product_id,
                        quantity: $item->quantity,
                        referenceType: SaleOrder::class,
                        referenceId: $sale->id,
                        remarks: 'Sale deleted',
                        idempotencyKey: "sale:{$sale->id}:product:{$item->product_id}:deleted"
                    );
                }
            }

            return $this->saleRepository
                ->delete($id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {
            $restored = $this->saleRepository->restore($id);
            $sale = $this->saleRepository->findOrFail($id);

            if (in_array($sale->status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true)) {
                foreach ($sale->items as $item) {
                    StockHelper::decrease(
                        $item->product_id,
                        $item->quantity,
                        SaleOrder::class,
                        $sale->id,
                        'Sale restored',
                        "sale:{$sale->id}:product:{$item->product_id}:restored"
                    );
                }
            }

            return $restored;
        });
    }

    public function forceDelete(
        int $id
    ): bool {

        return DB::transaction(
            fn() =>
            $this->saleRepository->forceDelete($id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): SaleOrder {

        return DB::transaction(function () use ($id, $status) {
            $sale = $this->saleRepository->findOrFail($id);
            $oldStatus = $sale->status;
            $wasApplied = in_array($oldStatus, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true);
            $willApply = in_array($status, [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED], true);

            if ($status === $oldStatus) {
                return $sale;
            }

            $allowedTransitions = [
                SaleOrder::STATUS_DRAFT => [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_CANCELLED],
                SaleOrder::STATUS_CONFIRMED => [SaleOrder::STATUS_COMPLETED, SaleOrder::STATUS_CANCELLED],
                SaleOrder::STATUS_COMPLETED => [],
                SaleOrder::STATUS_CANCELLED => [],
            ];

            if (! in_array($status, $allowedTransitions[$oldStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Sale status cannot change from {$oldStatus} to {$status}.",
                ]);
            }

            $confirmationStatus = $sale->deliveryAssignment?->confirmation?->status;
            if (in_array($confirmationStatus, [
                DeliveryConfirmation::STATUS_AWAITING_CUSTOMER,
                DeliveryConfirmation::STATUS_DISPUTED,
            ], true) && in_array($status, [
                SaleOrder::STATUS_COMPLETED,
                SaleOrder::STATUS_CANCELLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Resolve the delivery confirmation before completing or cancelling this order.'],
                ]);
            }

            if ($status === SaleOrder::STATUS_COMPLETED && $sale->deliveryAssignment) {
                throw ValidationException::withMessages([
                    'status' => ['Delivered orders must be completed through customer or manager confirmation.'],
                ]);
            }

            if (! $wasApplied && $willApply) {
                foreach ($sale->items as $item) {
                    StockHelper::decrease(
                        $item->product_id,
                        $item->quantity,
                        SaleOrder::class,
                        $sale->id,
                        "Sale status changed to {$status}",
                        "sale:{$sale->id}:product:{$item->product_id}:status:stock-out"
                    );
                }
            } elseif ($wasApplied && $status === SaleOrder::STATUS_CANCELLED) {
                foreach ($sale->items as $item) {
                    StockHelper::increase(
                        $item->product_id,
                        $item->quantity,
                        SaleOrder::class,
                        $sale->id,
                        'Sale cancelled',
                        "sale:{$sale->id}:product:{$item->product_id}:status:cancelled"
                    );
                }
            }

            return $this->saleRepository->changeStatus($id, $status);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int {

        return $this->saleRepository
            ->count($filters);
    }

    public function totalAmount(
        array $filters = []
    ): float {

        return $this->saleRepository
            ->totalAmount($filters);
    }
}
