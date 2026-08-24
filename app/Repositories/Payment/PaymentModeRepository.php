<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Models\Payment\PaymentMode;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class PaymentModeRepository implements PaymentModeRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return PaymentMode::query()

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {

                    $query->where(function ($q) use ($search) {

                        $q->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('code', 'ILIKE', "%{$search}%");

                    });

                }
            )

            ->when(
                isset($filters['is_active']),
                fn($query) => $query->where('is_active', $filters['is_active'])
            )

            ->when(
                isset($filters['is_online']),
                fn($query) => $query->where('is_online', $filters['is_online'])
            )

            ->orderBy('sort_order')
            ->orderBy('name')

            ->paginate(
                $filters['per_page'] ?? config('constant.pagination_count')
            );
    }

    public function trashedPaginate(array $filters = []): LengthAwarePaginator
    {
        return PaymentMode::onlyTrashed()

            ->orderByDesc('id')

            ->paginate(
                $filters['per_page'] ?? config('constant.pagination_count')
            );
    }

    public function all(): Collection
    {
        return PaymentMode::orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function active(): Collection
    {
        return PaymentMode::active()

            ->orderBy('sort_order')

            ->orderBy('name')

            ->get();
    }

    public function find(int $id): ?PaymentMode
    {
        return PaymentMode::find($id);
    }

    public function findOrFail(int $id): PaymentMode
    {
        return PaymentMode::findOrFail($id);
    }

    public function create(array $data): PaymentMode
    {
        return PaymentMode::create($data);
    }

    public function update(int $id, array $data): PaymentMode
    {
        $paymentMode = PaymentMode::findOrFail($id);

        $paymentMode->update($data);

        return $paymentMode->refresh();
    }

    public function delete(int $id): bool
    {
        return (bool) PaymentMode::findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return (bool) PaymentMode::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) PaymentMode::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function exists(int $id): bool
    {
        return PaymentMode::whereKey($id)->exists();
    }

    public function existsByCode(string $code): bool
    {
        return PaymentMode::where('code', $code)->exists();
    }

    public function existsByName(string $name): bool
    {
        return PaymentMode::where('name', $name)->exists();
    }
}
