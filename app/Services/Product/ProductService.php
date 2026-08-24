<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Product\Product;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected GeneralSettingRepositoryInterface $generalSettingRepository
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters);
    }

    public function all(): Collection
    {
        return $this->productRepository->all();
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->trash($filters);
    }

    public function find(int $id): Product
    {
        return $this->productRepository->find($id);
    }

    // public function create(array $data): Product
    // {
    //     return $this->productRepository->create($data);
    // }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            if (blank($data['barcode'])) {

                $setting = $this->generalSettingRepository->getForUpdate();

                $data['barcode'] = $setting->barcode_prefix .
                    $setting->barcode_start_number;

                $data['barcode_type'] = $setting->barcode_type;
                $data['is_barcode_auto'] = true;

                $setting->increment('barcode_start_number');

            } else {

                $data['is_barcode_auto'] = false;

                $data['barcode_type'] ??= $this->generalSettingRepository
                    ->get()
                    ->barcode_type;
            }

            return $this->productRepository->create($data);
        });
    }

    public function update(int $id, array $data): Product
    {
        return $this->productRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function changeStatus(int $id, bool $isActive): Product
    {
        $product = $this->productRepository->find($id);

        $product->update([
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);

        return $product->refresh();
    }

    public function restore(int $id): bool
    {
        return $this->productRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->productRepository->forceDelete($id);
    }

    public function generateSku(): string
    {
        do {
            $nextId = (Product::max('id') ?? 0) + 1;
            $sku = 'SKU-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    public function generateBarcode(): string
    {
        do {
            $nextId = (Product::max('id') ?? 0) + 1;
            $barcode = '890' . str_pad((string) $nextId, 9, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /*
       |--------------------------------------------------------------------------
       | Website
       |--------------------------------------------------------------------------
       */

    /**
     * Website product listing.
     */
    public function websitePaginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->productRepository->websitePaginate($filters);

    }

    /**
     * Product details.
     */
    public function findBySlug(
        string $slug
    ): Product {

        return $this->productRepository->findBySlug($slug);

    }

    /**
     * Related products.
     */
    public function relatedProducts(
        int $categoryId,
        int $productId,
        int $limit = 8
    ): Collection {

        return $this->productRepository->relatedProducts(
            $categoryId,
            $productId,
            $limit
        );

    }

    /**
     * Featured products.
     */
    public function featuredProducts(
        int $limit = 12
    ): Collection {

        return $this->productRepository->featuredProducts($limit);

    }

    /**
     * New arrival products.
     */
    public function newArrivalProducts(
        int $limit = 12
    ): Collection {

        return $this->productRepository->newArrivalProducts($limit);

    }

    /**
     * Best seller products.
     */
    public function bestSellerProducts(
        int $limit = 12
    ): Collection {

        return $this->productRepository->bestSellerProducts($limit);

    }

    /**
     * Product search suggestions.
     */
    public function searchSuggestions(
        string $keyword,
        int $limit = 10
    ): Collection {

        return $this->productRepository->searchSuggestions(
            $keyword,
            $limit
        );

    }
}
