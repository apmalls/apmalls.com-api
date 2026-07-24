<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Models\Product\ProductImage;
use App\Repositories\Contracts\ProductImageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductImageRepository implements ProductImageRepositoryInterface
{
    public function getByProduct(int $productId): Collection
    {
        return ProductImage::where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function find(int $id): ?ProductImage
    {
        return ProductImage::find($id);
    }

    public function findOrFail(int $id): ProductImage
    {
        return ProductImage::findOrFail($id);
    }

    public function create(array $data): ProductImage
    {
        return ProductImage::create($data);
    }

    public function update(int $id, array $data): ProductImage
    {
        $image = ProductImage::findOrFail($id);
        $image->update($data);

        return $image;
    }

    public function delete(int $id): bool
    {
        return (bool) ProductImage::findOrFail($id)->delete();
    }

    public function updateSortOrder(int $id, int $sortOrder): ProductImage
    {
        $image = ProductImage::findOrFail($id);
        $image->update(['sort_order' => $sortOrder]);

        return $image;
    }
}
