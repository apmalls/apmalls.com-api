<?php

namespace App\Services\Sale;

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

                StockHelper::decrease(
                    productId: $item['product_id'],
                    quantity: $item['quantity'],
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale'
                );
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

            $items = $data['items'] ?? [];

            unset($data['items']);

            $sale = $this->saleRepository
                ->update($id, $data);

            foreach ($sale->items as $oldItem) {

                StockHelper::increase(
                    productId: $oldItem->product_id,
                    quantity: $oldItem->quantity,
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale Update Rollback'
                );
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

                StockHelper::decrease(
                    productId: $item['product_id'],
                    quantity: $item['quantity'],
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale Updated'
                );
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

                StockHelper::increase(
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    referenceType: SaleOrder::class,
                    referenceId: $sale->id,
                    remarks: 'Sale Deleted'
                );
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

        return DB::transaction(
            fn() =>
            $this->saleRepository->restore($id)
        );
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

        return DB::transaction(
            fn() =>
            $this->saleRepository
                ->changeStatus($id, $status)
        );
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
