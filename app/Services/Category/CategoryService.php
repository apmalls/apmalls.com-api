<?php

declare(strict_types=1);

namespace App\Services\Category;

use App\Models\Category\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Contracts\CategoryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($filters);
    }

    public function all(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function tree(): Collection
    {
        return Category::with([
            'children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
            }
        ])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->trash($filters);
    }

    public function find(int $id): Category
    {
        return $this->categoryRepository->find($id);
    }

    public function create(array $data): Category
    {
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data): Category
    {
        return $this->categoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    public function changeStatus(int $id, bool $isActive): Category
    {
        return $this->categoryRepository->find($id);
    }

    public function restore(int $id): bool
    {
        return $this->categoryRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->categoryRepository->forceDelete($id);
    }

    public function bulkDelete(array $ids): array
    {
        $categories = Category::whereIn('id', $ids)->get();
        $deleted = 0;
        $failed = [];

        foreach ($categories as $category) {
            if ($category->children()->exists() || $category->products()->exists()) {
                $failed[] = $category->id;
                continue;
            }

            if (!empty($category->image)) {
                // This should be handled in controller or via event
            }

            $category->delete();
            $deleted++;
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    /*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

    /**
     * Website category listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->categoryRepository->websitePaginate($filters);

    }

    /**
     * Find category by slug.
     */
    public function findBySlug(
        string $slug
    ): Category {

        return $this->categoryRepository->findBySlug($slug);

    }

    /**
     * Featured categories.
     */
    public function featured(
        int $limit = 10
    ): Collection {

        return $this->categoryRepository->featured($limit);

    }
}
