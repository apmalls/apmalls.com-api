<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Product\ProductImage;
use App\Repositories\Contracts\ProductImageRepositoryInterface;
use App\Services\Contracts\ProductImageServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductImageService implements ProductImageServiceInterface
{
    public function __construct(
        protected ProductImageRepositoryInterface $productImageRepository,
    ) {
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->productImageRepository->getByProduct($productId);
    }

    public function find(int $id): ProductImage
    {
        return $this->productImageRepository->findOrFail($id);
    }

    public function create(array $data): ProductImage
    {
        return $this->productImageRepository->create($data);
    }

    public function createMany(array $images): Collection
    {
        $createdImages = [];

        foreach ($images as $imageData) {
            $createdImages[] = $this->productImageRepository->create($imageData);
        }

        return collect($createdImages);
    }

    public function update(int $id, array $data): ProductImage
    {
        return $this->productImageRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->productImageRepository->delete($id);
    }

    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->productImageRepository->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function updateSortOrder(int $id, int $sortOrder): ProductImage
    {
        return $this->productImageRepository->updateSortOrder($id, $sortOrder);
    }
}
