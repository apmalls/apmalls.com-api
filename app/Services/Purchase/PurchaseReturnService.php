<?php

namespace App\Services\Purchase;

use App\Helpers\NumberHelper;
use App\Helpers\StockHelper;
use App\Models\Purchase\PurchaseReturn;
use App\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Services\Contracts\PurchaseReturnServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnService implements PurchaseReturnServiceInterface
{
    public function __construct(
        protected PurchaseReturnRepositoryInterface $purchaseReturnRepository
    ) {
    }

    /**
     * Paginated listing.
     */
    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->purchaseReturnRepository->paginate(
            $perPage,
            $filters
        );
    }

    /**
     * Trashed listing.
     */
    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return $this->purchaseReturnRepository->trashedPaginate(
            $perPage
        );
    }

    /**
     * Get all purchase returns.
     */
    public function all(
        array $filters = []
    ): Collection {

        return $this->purchaseReturnRepository->all(
            $filters
        );
    }

    /**
     * Find purchase return.
     */
    public function find(
        int $id
    ): ?PurchaseReturn {

        return $this->purchaseReturnRepository->find($id);
    }

    /**
     * Find purchase return or fail.
     */
    public function findOrFail(
        int $id
    ): PurchaseReturn {

        return $this->purchaseReturnRepository->findOrFail($id);
    }

    /**
     * Create purchase return.
     */
    public function create(
        array $data
    ): PurchaseReturn {

        return DB::transaction(function () use ($data) {

            $data['return_no'] ??= NumberHelper::generate(
                PurchaseReturn::class,
                'return_no',
                'PR'
            );

            $purchaseReturn = $this->purchaseReturnRepository->create($data);

            if (!empty($data['items'])) {

                $purchaseReturn->items()->createMany(
                    $data['items']
                );

            }

            return $this->purchaseReturnRepository->findOrFail(
                $purchaseReturn->id
            );
        });
    }

    /**
     * Update purchase return.
     */
    public function update(
        int $id,
        array $data
    ): PurchaseReturn {

        return DB::transaction(function () use ($id, $data) {

            $purchaseReturn = $this->purchaseReturnRepository->findOrFail($id);

            if ($purchaseReturn->status === PurchaseReturn::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Completed purchase returns are immutable.',
                ]);
            }

            if (isset($data['status']) && $data['status'] !== $purchaseReturn->status) {
                throw ValidationException::withMessages([
                    'status' => 'Use the purchase return status action to change status.',
                ]);
            }

            $purchaseReturn = $this->purchaseReturnRepository->update(
                $id,
                $data
            );

            if (array_key_exists('items', $data)) {

                $purchaseReturn->items()->delete();

                if (!empty($data['items'])) {

                    $purchaseReturn->items()->createMany(
                        $data['items']
                    );

                }
            }

            return $this->purchaseReturnRepository->findOrFail(
                $purchaseReturn->id
            );
        });
    }

    /**
     * Soft delete purchase return.
     */
    public function delete(
        int $id
    ): bool {

        $purchaseReturn = $this->purchaseReturnRepository->findOrFail($id);

        if (
            $purchaseReturn->status === PurchaseReturn::STATUS_COMPLETED
        ) {
            throw ValidationException::withMessages([
                'status' => 'Completed purchase return cannot be deleted.',
            ]);
        }

        return $this->purchaseReturnRepository->delete($id);
    }

    /**
     * Restore purchase return.
     */
    public function restore(
        int $id
    ): bool {

        return $this->purchaseReturnRepository->restore($id);
    }

    /**
     * Permanently delete purchase return.
     */
    public function forceDelete(
        int $id
    ): bool {

        return $this->purchaseReturnRepository->forceDelete($id);
    }

    /**
     * Change purchase return status.
     */
    public function changeStatus(
        int $id,
        string $status
    ): PurchaseReturn {
        return DB::transaction(function () use ($id, $status) {
            $purchaseReturn = $this->purchaseReturnRepository->findOrFail($id);

            if ($status === $purchaseReturn->status) {
                return $purchaseReturn;
            }

            $allowedTransitions = [
                PurchaseReturn::STATUS_DRAFT => [PurchaseReturn::STATUS_APPROVED, PurchaseReturn::STATUS_CANCELLED],
                PurchaseReturn::STATUS_APPROVED => [PurchaseReturn::STATUS_COMPLETED, PurchaseReturn::STATUS_CANCELLED],
                PurchaseReturn::STATUS_COMPLETED => [],
                PurchaseReturn::STATUS_CANCELLED => [],
            ];

            if (! in_array($status, $allowedTransitions[$purchaseReturn->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Purchase return status cannot change from {$purchaseReturn->status} to {$status}.",
                ]);
            }

            if ($purchaseReturn->status !== PurchaseReturn::STATUS_COMPLETED
                && $status === PurchaseReturn::STATUS_COMPLETED) {
                foreach ($purchaseReturn->items as $item) {
                    StockHelper::decrease(
                        $item->product_id,
                        (int) $item->quantity,
                        PurchaseReturn::class,
                        $purchaseReturn->id,
                        'Purchase return completed',
                        "purchase-return:{$purchaseReturn->id}:product:{$item->product_id}:completed"
                    );
                }
            }

            return $this->purchaseReturnRepository->changeStatus($id, $status);
        });
    }

    /**
     * Total purchase returns.
     */
    public function count(
        array $filters = []
    ): int {

        return $this->purchaseReturnRepository->count(
            $filters
        );
    }

    /**
     * Total returned amount.
     */
    public function totalAmount(
        array $filters = []
    ): float|int {

        return $this->purchaseReturnRepository->totalAmount(
            $filters
        );
    }
}
