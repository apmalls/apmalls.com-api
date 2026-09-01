<?php

namespace Tests\Feature;

use App\Helpers\StockHelper;
use App\Models\Category\Category;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Product\Product;
use App\Models\Product\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StoreManagerFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'dashboard.statistics',
            'product.list',
            'product.view',
            'stock.list',
            'stock.view',
            'stock-movement.list',
            'stock-movement.view',
            'stock-adjustment.list',
            'stock-adjustment.view',
            'stock-adjustment.create',
        ];
        foreach ($permissions as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::create(['name' => 'Store Manager', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
        $this->manager = User::create([
            'first_name' => 'Store',
            'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->manager->assignRole($role);
    }

    public function test_manager_can_view_inventory_but_cannot_access_pos(): void
    {
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/admin/inventory/stocks')->assertOk();
        $this->getJson('/api/v1/admin/pos/session-context')->assertForbidden();
        $this->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_stock_mutation_is_locked_mirrored_and_idempotent(): void
    {
        $product = $this->product();

        StockHelper::increase($product->id, 5, Product::class, $product->id, 'Opening stock', 'test-opening-stock');
        StockHelper::increase($product->id, 5, Product::class, $product->id, 'Opening stock', 'test-opening-stock');

        $this->assertSame(5, Stock::where('product_id', $product->id)->value('available_stock'));
        $this->assertSame(5, Product::findOrFail($product->id)->stock);
        $this->assertSame(1, StockMovement::where('idempotency_key', 'test-opening-stock')->count());
    }

    public function test_adjustment_requires_a_reason_and_history_cannot_be_mutated(): void
    {
        $product = $this->product();
        Sanctum::actingAs($this->manager);

        $this->postJson('/api/v1/admin/inventory/stock-adjustments', [
            'product_id' => $product->id,
            'physical_stock' => 7,
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->putJson('/api/v1/admin/inventory/stock-adjustments/1', [])->assertMethodNotAllowed();
        $this->deleteJson('/api/v1/admin/inventory/stock-adjustments/1')->assertMethodNotAllowed();
    }

    public function test_manager_dashboard_uses_ledger_stock_exceptions(): void
    {
        $product = $this->product();
        Stock::create([
            'product_id' => $product->id,
            'current_stock' => 2,
            'reserved_stock' => 0,
            'available_stock' => 2,
            'minimum_stock' => 3,
            'maximum_stock' => 20,
        ]);
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.cards.low_stock', 1)
            ->assertJsonPath('data.cards.out_of_stock', 0)
            ->assertJsonCount(1, 'data.low_stock_products');
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Leaf', 'slug' => 'leaf', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'purchase_price' => 50,
            'selling_price' => 75,
            'mrp' => 80,
            'stock' => 0,
            'minimum_stock' => 3,
            'is_active' => true,
        ]);
    }
}
