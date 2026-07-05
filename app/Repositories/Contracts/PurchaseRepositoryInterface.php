<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Purchase\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function all(): Collection;

    public function find(int $id): PurchaseOrder;

    public function create(array $data): PurchaseOrder;

    public function update(
        PurchaseOrder $purchase,
        array $data
    ): PurchaseOrder;

    public function delete(
        PurchaseOrder $purchase
    ): bool;

    public function restore(
        int $id
    ): bool;

    public function forceDelete(
        int $id
    ): bool;
}
