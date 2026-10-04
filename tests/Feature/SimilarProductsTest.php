<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category\Category;
use App\Models\Inventory\Stock;
use App\Models\Product\Product;
use App\Models\Product\Unit;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimilarProductsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Soap', 'slug' => 'soap', 'is_active' => true]);
        $this->unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
    }

    public function test_only_other_visible_products_in_the_exact_category_are_selected(): void
    {
        $source = $this->product();
        $eligible = $this->product();
        $this->product(['is_active' => false]);
        $deleted = $this->product();
        $deleted->delete();
        $parent = Category::create(['name' => 'Personal Care', 'slug' => 'personal-care']);
        $this->category->update(['parent_id' => $parent->id]);
        $sibling = Category::create(['name' => 'Shampoo', 'slug' => 'shampoo', 'parent_id' => $parent->id]);
        $this->product(['category_id' => $sibling->id]);
        $this->product(['category_id' => $parent->id]);

        $this->assertSame([$eligible->id], $this->related($source));
    }

    public function test_inactive_or_deleted_categories_and_parents_remain_hidden(): void
    {
        $source = $this->product();
        $this->product();
        $this->category->update(['is_active' => false]);
        $this->assertSame([], $this->related($source));
        $this->category->update(['is_active' => true]);
        $parent = Category::create(['name' => 'Personal Care', 'slug' => 'personal-care', 'is_active' => false]);
        $this->category->update(['parent_id' => $parent->id]);
        $this->assertSame([], $this->related($source));
        $parent->update(['is_active' => true]);
        $parent->delete();
        $this->assertSame([], $this->related($source));
        $parent->restore();
        $this->category->delete();
        $this->assertSame([], $this->related($source));
    }

    public function test_inventory_availability_then_date_and_id_determine_order(): void
    {
        $source = $this->product();
        $olderAvailable = $this->product(['created_at' => '2026-01-01 00:00:00'], 1);
        $newerAvailable = $this->product(['created_at' => '2026-02-01 00:00:00'], 2);
        $tieAvailable = $this->product(['created_at' => '2026-02-01 00:00:00'], 1);
        $zero = $this->product(['created_at' => '2026-03-01 00:00:00', 'stock' => 100], 0);
        $negative = $this->product(['created_at' => '2026-04-01 00:00:00', 'stock' => 100], -1);
        $missing = $this->product(['created_at' => '2026-05-01 00:00:00', 'stock' => 100]);

        $this->assertSame([
            $tieAvailable->id, $newerAvailable->id, $olderAvailable->id,
            $missing->id, $negative->id, $zero->id,
        ], $this->related($source));
    }

    public function test_availability_is_ordered_before_the_eight_product_limit(): void
    {
        $source = $this->product();
        $available = $this->product(['created_at' => '2026-01-01 00:00:00'], 1);
        for ($i = 0; $i < 12; $i++) {
            $this->product(['created_at' => '2026-02-01 00:00:00'], 0);
        }

        $related = $this->related($source);
        $this->assertCount(8, $related);
        $this->assertSame($available->id, $related[0]);
        $this->assertCount(8, array_unique($related));
    }

    public function test_both_public_endpoints_return_the_same_order_and_existing_resource_shape(): void
    {
        $source = $this->product();
        $missing = $this->product(['stock' => 100]);
        $available = $this->product([], 2);
        $detail = $this->getJson("/api/v1/website/products/{$source->slug}")->assertOk();
        $related = $this->getJson("/api/v1/website/products/{$source->slug}/related")->assertOk();

        $this->assertSame($detail->json('data.related_products'), $related->json('data'));
        $this->assertSame([$available->id, $missing->id], array_column($related->json('data'), 'id'));
        $this->assertSame(2, $related->json('data.0.stock'));
        $this->assertNull($related->json('data.1.stock'));
        foreach ($related->json('data') as $item) {
            $this->assertArrayNotHasKey('has_available_inventory', $item);
            $this->assertArrayNotHasKey('manufacture_date', $item);
            $this->assertArrayNotHasKey('expiry_date', $item);
        }
    }

    public function test_no_alternatives_returns_an_empty_array_without_category_fallback(): void
    {
        $source = $this->product();

        $this->getJson("/api/v1/website/products/{$source->slug}")->assertOk()
            ->assertJsonPath('data.related_products', []);
        $this->getJson("/api/v1/website/products/{$source->slug}/related")->assertOk()
            ->assertJsonPath('data', []);
    }

    private function related(Product $source): array
    {
        return app(ProductRepositoryInterface::class)->relatedProducts($source->category_id, $source->id)->modelKeys();
    }

    private function product(array $overrides = [], ?int $availableStock = null): Product
    {
        $slug = uniqid('similar-soap-');
        $product = Product::create(array_merge([
            'name' => 'Similar Soap', 'slug' => $slug, 'sku' => $slug,
            'category_id' => $this->category->id, 'unit_id' => $this->unit->id,
            'selling_price' => 31, 'mrp' => 35, 'is_active' => true,
        ], $overrides));
        if ($availableStock !== null) {
            Stock::create(['product_id' => $product->id, 'current_stock' => 100, 'reserved_stock' => 99, 'available_stock' => $availableStock]);
        }

        return $product;
    }
}
