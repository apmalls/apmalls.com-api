<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Product\ProductImage;
use Illuminate\Database\Eloquent\Collection;

interface ProductImageServiceInterface
{
    /**
     * Get all images for a product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Find product image by id.
     */
    public function find(int $id): ProductImage;

    /**
     * Create a product image.
     */
    public function create(array $data): ProductImage;

    /**
     * Create multiple product images.
     */
    public function createMany(array $images): Collection;

    /**
     * Update a product image.
     */
    public function update(int $id, array $data): ProductImage;

    /**
     * Delete a product image.
     */
    public function delete(int $id): bool;

    /**
     * Delete multiple product images.
     */
    public function deleteMany(array $ids): int;

    /**
     * Update sort order.
     */
    public function updateSortOrder(int $id, int $sortOrder): ProductImage;
}
