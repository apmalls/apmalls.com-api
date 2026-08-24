<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product\ProductImage;
use Illuminate\Database\Eloquent\Collection;

interface ProductImageRepositoryInterface
{
    /**
     * Get all images for a product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Find product image by id.
     */
    public function find(int $id): ?ProductImage;

    /**
     * Find product image by id or fail.
     */
    public function findOrFail(int $id): ProductImage;

    /**
     * Create a product image.
     */
    public function create(array $data): ProductImage;

    /**
     * Update a product image.
     */
    public function update(int $id, array $data): ProductImage;

    /**
     * Delete a product image.
     */
    public function delete(int $id): bool;

    /**
     * Update sort order.
     */
    public function updateSortOrder(int $id, int $sortOrder): ProductImage;
}
