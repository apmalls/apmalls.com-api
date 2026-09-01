<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Category\Category;
use App\Models\Product\Product;
use App\Models\Inventory\Stock;
use App\Helpers\StockHelper;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $this->validateProductCategory((int) $data['category_id']);

        return DB::transaction(function () use ($data) {

            $initialStock = max(0, (int) ($data['stock'] ?? 0));
            $minimumStock = max(0, (int) ($data['minimum_stock'] ?? 0));
            $data['stock'] = 0;

            if (blank($data['barcode'] ?? null)) {

                $setting = $this->generalSettingRepository->getForUpdate();

                $barcodeNumber = (int) $setting->barcode_start_number;

                do {
                    $barcode = $setting->barcode_prefix . $barcodeNumber;
                    $barcodeNumber++;
                } while (
                    Product::withTrashed()
                        ->where('barcode', $barcode)
                        ->exists()
                );

                $data['barcode'] = $barcode;

                $data['barcode_type'] = $setting->barcode_type;
                $data['is_barcode_auto'] = true;

                $setting->update([
                    'barcode_start_number' => $barcodeNumber,
                ]);

            } else {

                $data['is_barcode_auto'] = false;

                $data['barcode_type'] ??= $this->generalSettingRepository
                    ->get()
                    ->barcode_type;
            }

            $product = $this->productRepository->create($data);

            Stock::create([
                'product_id' => $product->id,
                'current_stock' => 0,
                'reserved_stock' => 0,
                'available_stock' => 0,
                'minimum_stock' => $minimumStock,
                'maximum_stock' => 0,
            ]);

            if ($initialStock > 0) {
                StockHelper::increase(
                    productId: $product->id,
                    quantity: $initialStock,
                    referenceType: Product::class,
                    referenceId: $product->id,
                    remarks: 'Initial product stock',
                    idempotencyKey: "product:{$product->id}:initial-stock"
                );
            }

            return $product->fresh(['inventoryStock']);
        });
    }

    public function update(int $id, array $data): Product
    {
        if (isset($data['category_id'])) {
            $this->validateProductCategory((int) $data['category_id']);
        }

        return DB::transaction(function () use ($id, $data) {
            unset($data['stock']);
            $product = $this->productRepository->update($id, $data);

            StockHelper::currentStock($product->id);
            Stock::query()
                ->where('product_id', $product->id)
                ->update([
                    'minimum_stock' => max(0, (int) ($data['minimum_stock'] ?? $product->minimum_stock)),
                ]);

            return $product->fresh(['inventoryStock']);
        });
    }

    private function validateProductCategory(int $categoryId): void
    {
        $category = Category::query()
            ->withCount('children')
            ->find($categoryId);

        if (!$category || !$category->is_active) {
            throw ValidationException::withMessages([
                'category_id' => ['Select an active category.'],
            ]);
        }

        if ($category->children_count > 0) {
            throw ValidationException::withMessages([
                'category_id' => ['Products must be assigned to a subcategory.'],
            ]);
        }

        if ($category->parent_id !== null && !$category->parent?->is_active) {
            throw ValidationException::withMessages([
                'category_id' => ['The parent category must be active.'],
            ]);
        }
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
