<?php

namespace Tests\Feature;

use App\Models\Product\Brand;
use App\Models\User;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Services\Brand\BrandService;
use App\Services\Contracts\BrandServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BrandFeaturedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['logging.default' => 'null']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['brand.list', 'brand.view', 'brand.update'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function actor(array $permissions = ['brand.list', 'brand.update']): User
    {
        $user = User::create([
            'first_name' => 'Fixture', 'last_name' => 'Manager', 'username' => 'fixture',
            'email' => 'fixture@example.com', 'mobile' => '9999999999',
            'password' => bcrypt('fixture-password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $user->givePermissionTo($permissions);
        Sanctum::actingAs($user);
        return $user;
    }

    private function brand(string $slug, array $values = []): Brand
    {
        return Brand::create(array_merge([
            'name' => $slug, 'slug' => $slug, 'description' => 'Preserve description',
            'logo' => 'brands/'.$slug.'.png', 'is_active' => true, 'featured' => false,
        ], $values));
    }

    public function test_single_selection_saves_both_states_and_preserves_brand_details(): void
    {
        $actor = $this->actor();
        $brand = $this->brand('sample', ['is_active' => false]);
        $before = $brand->fresh()->getAttributes();
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => true])
            ->assertOk()->assertJsonPath('data.featured', true)
            ->assertJsonPath('featured_summary.featured_count', 1);
        $after = $brand->fresh()->getAttributes();
        $this->assertSame($actor->id, $after['updated_by']);
        foreach (['featured', 'updated_by', 'updated_at'] as $key) {
            unset($before[$key], $after[$key]);
        }
        $this->assertSame($before, $after);
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => false])
            ->assertOk()->assertJsonPath('data.featured', false);
    }

    public function test_bulk_updates_all_pages_ignores_filters_and_excludes_trash(): void
    {
        $actor = $this->actor();
        for ($i = 0; $i < 25; $i++) {
            $this->brand('brand-'.$i, ['featured' => $i < 3, 'is_active' => $i % 2 === 0]);
        }
        $trash = $this->brand('trash');
        $trash->delete();
        $before = DB::table('brands')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $this->patchJson('/api/v1/admin/brands/featured?search=brand-1&status=1&page=2', ['featured' => true])
            ->assertOk()->assertJsonPath('data.updated_count', 22)
            ->assertJsonPath('featured_summary', ['total' => 25, 'featured_count' => 25]);
        $this->assertFalse($trash->fresh()->featured);
        $after = DB::table('brands')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        foreach ($before as $index => $row) {
            foreach (['featured', 'updated_by', 'updated_at'] as $key) {
                unset($row[$key], $after[$index][$key]);
            }
            $this->assertSame($row, $after[$index]);
        }
        $this->assertSame($actor->id, Brand::where('slug', 'brand-4')->first()->updated_by);
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => false])
            ->assertOk()->assertJsonPath('data.updated_count', 25)
            ->assertJsonPath('featured_summary.featured_count', 0);
    }

    public function test_repeated_sets_do_not_rewrite_audit_fields(): void
    {
        $this->actor();
        $brand = $this->brand('already', ['featured' => true]);
        $before = $brand->fresh()->getAttributes();
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => true])->assertOk();
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])
            ->assertOk()->assertJsonPath('data.updated_count', 0);
        $this->assertSame($before, $brand->fresh()->getAttributes());
    }

    public function test_global_summary_is_independent_of_status_and_pagination(): void
    {
        $this->actor();
        $this->brand('active', ['featured' => true]);
        $this->brand('inactive', ['is_active' => false]);
        $trash = $this->brand('trash', ['featured' => true]);
        $trash->delete();
        $this->getJson('/api/v1/admin/brands?status=0&per_page=1')
            ->assertOk()->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.slug', 'inactive')
            ->assertJsonPath('featured_summary', ['total' => 2, 'featured_count' => 1]);
        $this->getJson('/api/v1/admin/brands?page=3&per_page=1')
            ->assertOk()->assertJsonPath('data.data', [])
            ->assertJsonPath('featured_summary.total', 2);
    }

    public function test_bulk_failure_rolls_back_all_flags_and_audit_fields(): void
    {
        $this->actor();
        $this->brand('one');
        $this->brand('two');
        $before = DB::table('brands')->orderBy('id')->get()->toJson();
        $service = \Mockery::mock(BrandService::class, [app(BrandRepositoryInterface::class)])->makePartial();
        $service->shouldReceive('featuredSummary')->once()->andThrow(new \RuntimeException('Fixture failure'));
        $this->app->instance(BrandServiceInterface::class, $service);
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])->assertStatus(500);
        $this->assertSame($before, DB::table('brands')->orderBy('id')->get()->toJson());
    }

    public function test_validation_missing_ids_and_trashed_ids(): void
    {
        $this->actor();
        $brand = $this->brand('sample');
        foreach (['/api/v1/admin/brands/featured', "/api/v1/admin/brands/{$brand->id}/featured"] as $endpoint) {
            foreach ([[], ['featured' => null], ['featured' => 'yes'], ['featured' => []]] as $body) {
                $this->patchJson($endpoint, $body)->assertUnprocessable()->assertJsonValidationErrors('featured');
            }
        }
        $this->assertFalse($brand->fresh()->featured);
        $this->patchJson('/api/v1/admin/brands/9999/featured', ['featured' => true])->assertNotFound();
        $brand->delete();
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => true])->assertNotFound();
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])
            ->assertOk()->assertJsonPath('featured_summary', ['total' => 0, 'featured_count' => 0]);
    }

    public function test_mutations_require_brand_update_and_guests_cannot_read_or_write(): void
    {
        $brand = $this->brand('sample');
        $this->getJson('/api/v1/admin/brands')->assertUnauthorized();
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])->assertUnauthorized();
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => true])->assertUnauthorized();
        $this->actor(['brand.list']);
        $this->getJson('/api/v1/admin/brands')->assertOk();
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])->assertForbidden();
        $this->patchJson("/api/v1/admin/brands/{$brand->id}/featured", ['featured' => true])->assertForbidden();
        $this->assertFalse($brand->fresh()->featured);
    }

    public function test_super_admin_keeps_access_without_new_permissions(): void
    {
        $actor = $this->actor([]);
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('brand.update');
        $actor->assignRole($role);
        $this->brand('sample');
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => true])->assertOk();
    }

    public function test_regular_brand_edit_preserves_featured_selection(): void
    {
        $this->actor();
        $brand = $this->brand('sample', ['featured' => true]);
        $this->putJson("/api/v1/admin/brands/{$brand->id}", [
            'name' => 'Edited', 'slug' => 'sample', 'description' => 'Edited description', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.featured', true);
        $this->assertSame('brands/sample.png', $brand->fresh()->logo);
    }

    public function test_homepage_returns_all_eligible_featured_brands_and_correct_alias(): void
    {
        for ($i = 0; $i < 14; $i++) {
            $this->brand(sprintf('brand-%02d', $i), ['featured' => true]);
        }
        $this->brand('inactive', ['featured' => true, 'is_active' => false]);
        $this->brand('unfeatured');
        $trash = $this->brand('trash', ['featured' => true]);
        $trash->delete();
        $response = $this->getJson('/api/v1/website/home')->assertOk()->assertJsonCount(14, 'data.featured_brands');
        $brands = $response->json('data.featured_brands');
        $this->assertSame('brand-00', $brands[0]['slug']);
        $this->assertSame('brand-13', $brands[13]['slug']);
        foreach ($brands as $brand) {
            $this->assertTrue($brand['featured']);
            $this->assertTrue($brand['is_featured']);
        }
        $repository = app(BrandRepositoryInterface::class);
        $this->assertCount(10, $repository->featured(), 'Explicit/default limits remain compatible elsewhere.');
        $this->actor();
        $this->patchJson('/api/v1/admin/brands/featured', ['featured' => false])->assertOk();
        $this->getJson('/api/v1/website/home')->assertOk()->assertJsonPath('data.featured_brands', []);
    }
}
