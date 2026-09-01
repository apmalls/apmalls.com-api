<?php

namespace App\Services\Sale;

use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Models\Sale\SaleReturn;
use App\Repositories\Contracts\SaleReturnRepositoryInterface;
use App\Services\Contracts\SaleReturnServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnService implements SaleReturnServiceInterface
{
    public function __construct(
        protected SaleReturnRepositoryInterface $saleReturnRepository
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

        return $this->saleReturnRepository
            ->paginate($perPage, $filters);
    }

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return $this->saleReturnRepository
            ->trashedPaginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {

        return $this->saleReturnRepository
            ->all($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?SaleReturn {

        return $this->saleReturnRepository
            ->find($id);
    }

    public function findOrFail(
        int $id
    ): SaleReturn {

        return $this->saleReturnRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleReturn {

        return DB::transaction(function () use ($data) {

            if (empty($data['return_no'])) {

                $data['return_no'] = NumberHelper::generate(
                    SaleReturn::class,
                    'return_no',
                    'SR'
                );
            }

            return $this->saleReturnRepository
                ->create($data);
        });
    }

    public function update(
        int $id,
        array $data
    ): SaleReturn {

        return DB::transaction(function () use ($id, $data) {

            $saleReturn = $this->saleReturnRepository->findOrFail($id);

            if ($saleReturn->status === SaleReturn::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Completed sale returns are immutable.',
                ]);
            }

            if (isset($data['status']) && $data['status'] !== $saleReturn->status) {
                throw ValidationException::withMessages([
                    'status' => 'Use the sale return status action to change status.',
                ]);
            }

            return $this->saleReturnRepository
                ->update($id, $data);
        });
    }

    public function delete(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            $saleReturn = $this->saleReturnRepository
                ->findOrFail($id);

            if ($saleReturn->status === SaleReturn::STATUS_COMPLETED) {

                throw ValidationException::withMessages([
                    'sale_return' => 'Completed sale return cannot be deleted.',
                ]);
            }

            return $this->saleReturnRepository
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
            $this->saleReturnRepository
                ->restore($id)
        );
    }

    public function forceDelete(
        int $id
    ): bool {

        return DB::transaction(
            fn() =>
            $this->saleReturnRepository
                ->forceDelete($id)
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
    ): SaleReturn {

        return DB::transaction(function () use ($id, $status) {
            $saleReturn = $this->saleReturnRepository->findOrFail($id);

            if ($status === $saleReturn->status) {
                return $saleReturn;
            }

            $allowedTransitions = [
                SaleReturn::STATUS_DRAFT => [SaleReturn::STATUS_APPROVED, SaleReturn::STATUS_CANCELLED],
                SaleReturn::STATUS_APPROVED => [SaleReturn::STATUS_COMPLETED, SaleReturn::STATUS_CANCELLED],
                SaleReturn::STATUS_COMPLETED => [],
                SaleReturn::STATUS_CANCELLED => [],
            ];

            if (! in_array($status, $allowedTransitions[$saleReturn->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Sale return status cannot change from {$saleReturn->status} to {$status}.",
                ]);
            }

            if ($saleReturn->status !== SaleReturn::STATUS_COMPLETED
                && $status === SaleReturn::STATUS_COMPLETED) {
                foreach ($saleReturn->items as $item) {
                    StockHelper::increase(
                        $item->product_id,
                        (int) $item->quantity,
                        SaleReturn::class,
                        $saleReturn->id,
                        'Sale return completed',
                        "sale-return:{$saleReturn->id}:product:{$item->product_id}:completed"
                    );
                }
            }

            return $this->saleReturnRepository->changeStatus($id, $status);
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

        return $this->saleReturnRepository
            ->count($filters);
    }

    public function totalAmount(
        array $filters = []
    ): float {

        return $this->saleReturnRepository
            ->totalAmount($filters);
    }
}
