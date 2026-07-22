<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment\PaymentMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentModeRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function trashedPaginate(array $filters = []): LengthAwarePaginator;

    public function all(): Collection;

    public function active(): Collection;

    public function find(int $id): ?PaymentMode;

    public function findOrFail(int $id): PaymentMode;

    public function create(array $data): PaymentMode;

    public function update(int $id, array $data): PaymentMode;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function exists(int $id): bool;

    public function existsByCode(string $code): bool;

    public function existsByName(string $name): bool;
}
