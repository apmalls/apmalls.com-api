<?php

namespace App\Services\Purchase;

use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Models\Purchase\PurchaseOrder;

use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Services\Contracts\PurchaseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService implements PurchaseServiceInterface
{
    public function __construct(
        protected PurchaseRepositoryInterface $purchaseRepository
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
        return $this->purchaseRepository
            ->paginate($perPage, $filters);
    }

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->purchaseRepository
            ->trashedPaginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {
        return $this->purchaseRepository
            ->all($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?PurchaseOrder {
        return $this->purchaseRepository
            ->find($id);
    }

    public function findOrFail(
        int $id
    ): PurchaseOrder {
        return $this->purchaseRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    // public function create(
    //     array $data
    // ): PurchaseOrder {

    //     return DB::transaction(function () use ($data) {

    //         if (empty($data['purchase_no'])) {

    //             $data['purchase_no'] = NumberHelper::generate(
    //                 PurchaseOrder::class,
    //                 'purchase_no',
    //                 'PO'
    //             );

    //         }

    //         return $this->purchaseRepository
    //             ->create($data);

    //     });
    // }

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {

            $items = $data['items'] ?? [];

            unset($data['items']);

            if (empty($data['purchase_no'])) {
                $data['purchase_no'] = NumberHelper::generate(
                    PurchaseOrder::class,
                    'purchase_no',
                    'PO'
                );
            }

            $purchase = $this->purchaseRepository->create($data);

            foreach ($items as $item) {

                $purchase->items()->create([

                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],

                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'free_quantity' => $item['free_quantity'] ?? 0,

                    'unit_cost' => $item['unit_cost'],

                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],
                ]);

                StockHelper::increase(
                    productId: $item['product_id'],
                    quantity: $item['quantity'] + ($item['free_quantity'] ?? 0),
                    referenceType: PurchaseOrder::class,
                    referenceId: $purchase->id,
                    remarks: 'Purchase Stock'
                );
            }

            return $purchase->load([
                'supplier',
                'items.product',
                'items.unit',
            ]);
        });
    }

    // public function update(
    //     int $id,
    //     array $data
    // ): PurchaseOrder {

    //     return DB::transaction(function () use ($id, $data) {

    //         return $this->purchaseRepository
    //             ->update($id, $data);

    //     });
    // }

    public function update(
        int $id,
        array $data
    ): PurchaseOrder {

        return DB::transaction(function () use ($id, $data) {

            $purchase = $this->purchaseRepository->findOrFail($id);

            $items = $data['items'] ?? [];

            unset($data['items']);

            $purchase = $this->purchaseRepository->update($id, $data);

            foreach ($purchase->items as $oldItem) {

                StockHelper::decrease(
                    productId: $oldItem->product_id,
                    quantity: $oldItem->quantity + $oldItem->free_quantity,
                    referenceType: PurchaseOrder::class,
                    referenceId: $purchase->id,
                    remarks: 'Purchase Update Rollback'
                );
            }

            $purchase->items()->delete();

            foreach ($items as $item) {

                $purchase->items()->create([

                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],

                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'free_quantity' => $item['free_quantity'] ?? 0,

                    'unit_cost' => $item['unit_cost'],

                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,

                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,

                    'line_total' => $item['line_total'],
                ]);

                StockHelper::increase(
                    productId: $item['product_id'],
                    quantity: $item['quantity'] + ($item['free_quantity'] ?? 0),
                    referenceType: PurchaseOrder::class,
                    referenceId: $purchase->id,
                    remarks: 'Purchase Updated'
                );
            }

            return $purchase->load([
                'supplier',
                'items.product',
                'items.unit',
            ]);
        });
    }

    // public function delete(
    //     int $id
    // ): bool {

    //     return DB::transaction(function () use ($id) {

    //         return $this->purchaseRepository
    //             ->delete($id);

    //     });
    // }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            $purchase = $this->purchaseRepository->findOrFail($id);

            if ($purchase->payments()->exists()) {

                throw ValidationException::withMessages([
                    'purchase' => 'Purchase has payment(s). Delete them first.'
                ]);
            }

            if ($purchase->purchaseReturns()->exists()) {

                throw ValidationException::withMessages([
                    'purchase' => 'Purchase has return(s). Delete them first.'
                ]);
            }

            foreach ($purchase->items as $item) {

                StockHelper::decrease(
                    productId: $item->product_id,
                    quantity: $item->quantity + $item->free_quantity,
                    referenceType: PurchaseOrder::class,
                    referenceId: $purchase->id,
                    remarks: 'Purchase Deleted'
                );
            }

            return $this->purchaseRepository->delete($id);
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

            return $this->purchaseRepository
                ->restore($id);

        });
    }

    public function forceDelete(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            return $this->purchaseRepository
                ->forceDelete($id);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): PurchaseOrder {

        return DB::transaction(function () use ($id, $status) {

            return $this->purchaseRepository
                ->changeStatus($id, $status);

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

        return $this->purchaseRepository
            ->count($filters);
    }

    public function totalAmount(
        array $filters = []
    ): float {

        return $this->purchaseRepository
            ->totalAmount($filters);
    }
}
