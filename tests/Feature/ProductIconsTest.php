<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category\Category;
use App\Models\Customer\Customer;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use App\Models\Product\Unit;
use App\Models\User;
use App\Http\Resources\Website\CartItemResource;
use App\Repositories\Cart\CartRepository;
use Database\Seeders\GeneralSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductIconsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Unit $unit;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['logging.default' => 'null']);
        $this->seed(GeneralSettingSeeder::class);
        $this->category = Category::create(['name' => 'Soap', 'slug' => 'soap', 'is_active' => true]);
        $this->unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc']);
        $this->actor = User::create(['first_name' => 'Icon', 'last_name' => 'Staff', 'username' => 'icon-staff',
            'email' => 'icons@example.com', 'mobile' => '9000000001', 'password' => 'password', 'is_active' => true]);
        foreach (['product.create', 'product.update', 'product.view', 'product.list'] as $name) {
            $this->actor->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        Sanctum::actingAs($this->actor);
    }

    public function test_create_defaults_to_null_and_accepts_a_selected_icon(): void
    {
        $this->postJson('/api/v1/admin/products', $this->payload('Automatic soap'))->assertCreated()->assertJsonPath('data.product_icon', null);
        $response = $this->postJson('/api/v1/admin/products', $this->payload('Selected soap', ['product_icon' => 'soap-dispenser-droplet']))
            ->assertCreated()->assertJsonPath('data.product_icon', 'soap-dispenser-droplet');
        $this->assertDatabaseHas('products', ['id' => $response->json('data.id'), 'product_icon' => 'soap-dispenser-droplet']);
    }

    public function test_update_preserves_replaces_and_clears_icons_without_affecting_photos(): void
    {
        $product = $this->product(['product_icon' => 'soap-dispenser-droplet', 'thumbnail' => 'products/soap.png']);
        $url = "/api/v1/admin/products/{$product->id}";
        $this->putJson($url, $this->payload($product->name))->assertOk()->assertJsonPath('data.product_icon', 'soap-dispenser-droplet');
        $this->putJson($url, $this->payload($product->name, ['product_icon' => 'milk']))->assertOk()->assertJsonPath('data.product_icon', 'milk');
        foreach ([null, ''] as $value) {
            $this->putJson($url, $this->payload($product->name, ['product_icon' => $value]))->assertOk()->assertJsonPath('data.product_icon', null);
        }
        $this->assertSame('products/soap.png', $product->fresh()->thumbnail);
    }

    public function test_unknown_identifiers_markup_urls_and_arrays_are_rejected(): void
    {
        $product = $this->product(['product_icon' => 'milk']);
        foreach (['not-an-icon', '<svg onload="alert(1)"></svg>', 'https://example.com/icon.svg', ['milk']] as $value) {
            $this->postJson('/api/v1/admin/products', $this->payload('Invalid icon', ['product_icon' => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors('product_icon');
            $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload($product->name, ['product_icon' => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors('product_icon');
        }
        $this->assertSame('milk', $product->fresh()->product_icon);
    }

    public function test_public_staff_and_wishlist_resources_include_the_same_optional_icon(): void
    {
        $product = $this->product(['product_icon' => 'cookie']);
        $this->getJson("/api/v1/admin/products/{$product->id}")->assertOk()->assertJsonPath('data.product_icon', 'cookie');
        $this->getJson('/api/v1/admin/products')->assertOk()->assertJsonPath('data.data.0.product_icon', 'cookie');
        $this->getJson("/api/v1/website/products/{$product->slug}")->assertOk()->assertJsonPath('data.product.product_icon', 'cookie');
        $wishlist = new \App\Models\Wishlist\Wishlist();
        $wishlist->setRelation('product', $product);
        $payload = (new \App\Http\Resources\Website\WishlistResource($wishlist))->resolve();
        $this->assertSame('cookie', $payload['product']->resolve()['product_icon']);
    }

    public function test_cart_media_is_eager_loaded_and_serialization_performs_no_queries(): void
    {
        $product = $this->product(['product_icon' => 'milk', 'thumbnail' => 'products/soap.png']);
        ProductImage::create(['product_id' => $product->id, 'image' => 'products/gallery.png', 'sort_order' => 1]);
        $customer = Customer::create(['user_id' => $this->actor->id, 'customer_code' => 'ICON-TEST', 'first_name' => 'Icon', 'mobile' => '9000000001']);
        $cart = Cart::create(['customer_id' => $customer->id, 'cart_no' => 'CART-ICON', 'status' => 'Active']);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 31, 'subtotal' => 31]);
        foreach ([(new CartRepository())->find($cart->id), (new CartRepository())->getActiveCart($customer->id)] as $loaded) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $payload = (new CartItemResource($loaded->items->first()))->resolve();
            $this->assertSame([], DB::getQueryLog());
            DB::disableQueryLog();
            $this->assertSame('milk', $payload['product_icon']);
            $this->assertSame('soap', $payload['product_category_slug']);
            $this->assertSame('Soap', $payload['product_category_name']);
            $this->assertSame($product->thumbnail_url, $payload['product_image']);
            $this->assertCount(1, $payload['product_images']);
        }
        $product->update(['thumbnail' => null]);
        $fallback = (new CartItemResource((new CartRepository())->find($cart->id)->items->first()))->resolve();
        $this->assertSame($fallback['product_images'][0], $fallback['product_image']);
    }

    public function test_icon_updates_cannot_bypass_existing_permissions(): void
    {
        $product = $this->product(['product_icon' => 'milk']);
        $this->actor->revokePermissionTo(['product.create', 'product.update']);
        Sanctum::actingAs($this->actor->fresh());
        $this->postJson('/api/v1/admin/products', $this->payload('Unauthorized icon'))->assertForbidden();
        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload($product->name, ['product_icon' => 'cookie']))->assertForbidden();
        $this->assertSame('milk', $product->fresh()->product_icon);
    }

    public function test_migration_round_trip_preserves_existing_products_and_defaults_to_null(): void
    {
        $product = $this->product();
        $migration = require database_path('migrations/2026_10_07_120000_add_product_icon_to_products_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('products', 'product_icon'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
        $migration->up();
        $this->assertNull($product->fresh()->product_icon);
    }

    private function payload(string $name, array $extra = []): array
    {
        return array_merge(['name' => $name, 'category_id' => $this->category->id, 'unit_id' => $this->unit->id,
            'purchase_price' => 20, 'selling_price' => 31, 'mrp' => 35, 'tax_percent' => 0,
            'discount_percent' => 0, 'minimum_stock' => 0, 'is_active' => true], $extra);
    }

    private function product(array $extra = []): Product
    {
        return Product::create(array_merge($this->payload('Icon Soap'), ['slug' => 'icon-soap', 'sku' => 'ICON-SOAP'], $extra));
    }
}
