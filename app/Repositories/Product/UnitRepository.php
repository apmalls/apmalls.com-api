<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Models\Product\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository implements UnitRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Unit::query()->latest();

        if (isset($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('short_name', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('is_active', (bool) $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function all(): Collection
    {
        return Unit::all();
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        $query = Unit::onlyTrashed()->latest('deleted_at');

        if (isset($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('short_name', 'ILIKE', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id): ?Unit
    {
        return Unit::find($id);
    }

    public function findOrFail(int $id): Unit
    {
        return Unit::findOrFail($id);
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(int $id, array $data): Unit
    {
        $unit = Unit::findOrFail($id);
        $unit->update($data);

        return $unit;
    }

    public function delete(int $id): bool
    {
        return (bool) Unit::findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return (bool) Unit::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) Unit::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function changeStatus(int $id, bool $isActive): Unit
    {
        $unit = Unit::findOrFail($id);
        $unit->update(['is_active' => $isActive]);

        return $unit;
    }


    /*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

    /**
     * Website unit listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return Unit::query()

            ->withCount('products')

            ->where(
                'is_active',
                true
            )

            ->when(

                !empty($filters['search']),

                fn($query) =>

                $query->where(
                    'name',
                    'ILIKE',
                    '%' . trim($filters['search']) . '%'
                )

            )

            ->orderBy('name')

            ->paginate(
                $filters['per_page'] ?? 20
            );

    }

    /**
     * Find unit by slug.
     */
    public function findBySlug(
        string $slug
    ): Unit {

        return Unit::query()

            ->with([

                'products' => function ($query) {

                    $query

                        ->with([
                            'category',
                            'brand',
                            'unit',
                            'images',
                        ])

                        ->where(
                            'is_active',
                            true
                        );

                },

            ])

            ->withCount('products')

            ->where(
                'slug',
                $slug
            )

            ->where(
                'is_active',
                true
            )

            ->firstOrFail();

    }

    /**
     * Featured units.
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return Unit::query()

            ->withCount('products')

            ->where(
                'is_featured',
                true
            )

            ->where(
                'is_active',
                true
            )

            ->orderBy('name')

            ->limit($limit)

            ->get();

    }
}
