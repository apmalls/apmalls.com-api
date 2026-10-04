<?php

namespace Tests\Feature;

use App\Models\Banner\WebsiteBanner;
use App\Models\User;
use App\Services\Contracts\WebsiteBannerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebsiteBannerDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->manager = User::create([
            'first_name' => 'Banner', 'last_name' => 'Manager',
            'username' => 'banner-manager', 'email' => 'banner-manager@example.com',
            'mobile' => '9000000001', 'password' => Hash::make('password'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);
        foreach (['delete', 'restore', 'force-delete'] as $action) {
            $this->manager->givePermissionTo(Permission::firstOrCreate([
                'name' => "website-banner.{$action}", 'guard_name' => 'web',
            ]));
        }
        Sanctum::actingAs($this->manager);
    }

    public function test_permanent_deletion_removes_a_trashed_banner_and_both_images(): void
    {
        $banner = $this->banner();
        $banner->delete();

        $this->deleteJson($this->endpoint($banner))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Website banner permanently deleted.');

        $this->assertDatabaseMissing('website_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing([$banner->desktop_image, $banner->mobile_image]);
        $this->deleteJson($this->endpoint($banner))->assertNotFound();
    }

    public function test_active_banners_cannot_be_permanently_deleted(): void
    {
        $banner = $this->banner();

        $this->deleteJson($this->endpoint($banner))->assertNotFound();

        $this->assertDatabaseHas('website_banners', ['id' => $banner->id, 'deleted_at' => null]);
        Storage::disk('public')->assertExists([$banner->desktop_image, $banner->mobile_image]);
    }

    public function test_missing_banner_returns_not_found(): void
    {
        $this->deleteJson('/api/v1/admin/website-banners/999999/force-delete')->assertNotFound();
    }

    public function test_permanent_deletion_requires_its_own_permission(): void
    {
        $banner = $this->banner();
        $banner->delete();
        $this->manager->revokePermissionTo('website-banner.force-delete');
        Sanctum::actingAs($this->manager->fresh());

        $this->deleteJson($this->endpoint($banner))->assertForbidden();

        $this->assertSoftDeleted('website_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertExists([$banner->desktop_image, $banner->mobile_image]);
    }

    public function test_soft_delete_and_restore_preserve_uploaded_images(): void
    {
        $banner = $this->banner();

        $this->deleteJson("/api/v1/admin/website-banners/{$banner->id}")->assertOk();
        $this->assertSoftDeleted('website_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertExists([$banner->desktop_image, $banner->mobile_image]);
        $this->putJson("/api/v1/admin/website-banners/{$banner->id}/restore")->assertOk();
        $this->assertDatabaseHas('website_banners', ['id' => $banner->id, 'deleted_at' => null]);
        Storage::disk('public')->assertExists([$banner->desktop_image, $banner->mobile_image]);
    }

    public function test_failed_database_deletion_does_not_remove_images(): void
    {
        $banner = $this->banner();
        $banner->delete();
        $this->mock(WebsiteBannerServiceInterface::class, function (MockInterface $mock) use ($banner) {
            $mock->shouldReceive('findTrashedById')->once()->with($banner->id)->andReturn($banner);
            $mock->shouldReceive('forceDelete')->once()->with($banner->id)
                ->andThrow(new \RuntimeException('Simulated deletion failure'));
        });

        $this->deleteJson($this->endpoint($banner))->assertStatus(500);

        $this->assertSoftDeleted('website_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertExists([$banner->desktop_image, $banner->mobile_image]);
    }

    public function test_missing_or_unset_images_do_not_block_permanent_deletion(): void
    {
        foreach ([false, true] as $unset) {
            $banner = $this->banner();
            Storage::disk('public')->delete([$banner->desktop_image, $banner->mobile_image]);
            if ($unset) {
                $banner->update(['mobile_image' => null]);
            }
            $banner->delete();

            $this->deleteJson($this->endpoint($banner))->assertOk();
            $this->assertDatabaseMissing('website_banners', ['id' => $banner->id]);
        }
    }

    private function banner(): WebsiteBanner
    {
        $slug = uniqid('deletion-test-');
        $banner = WebsiteBanner::create([
            'title' => 'Deletion test banner', 'slug' => $slug,
            'desktop_image' => "banners/{$slug}.png", 'mobile_image' => "banners/{$slug}-mobile.png",
            'type' => 'image', 'banner_type' => 'slider', 'position' => 'home_hero',
            'status' => true, 'sort_order' => 0,
        ]);
        Storage::disk('public')->put($banner->desktop_image, 'desktop test image');
        Storage::disk('public')->put($banner->mobile_image, 'mobile test image');

        return $banner;
    }

    private function endpoint(WebsiteBanner $banner): string
    {
        return "/api/v1/admin/website-banners/{$banner->id}/force-delete";
    }
}
