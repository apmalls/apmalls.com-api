<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CarouselAnimationTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_and_migration_preserve_existing_settings(): void
    {
        $settings = app(GeneralSettingRepositoryInterface::class)->get();
        $this->assertFalse($settings->banner_autoplay_enabled);
        $this->assertFalse($settings->offer_autoplay_enabled);
        $settings->update(['company_name' => 'Preserved company']);
        $migration = require database_path('migrations/2026_10_04_000001_add_carousel_animation_settings.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('general_settings', 'banner_autoplay_enabled'));
        $migration->up();
        $fresh = $settings->fresh();
        $this->assertSame('Preserved company', $fresh->company_name);
        $this->assertFalse($fresh->banner_autoplay_enabled);
        $this->assertFalse($fresh->offer_autoplay_enabled);
    }

    public function test_independent_updates_require_explicit_booleans(): void
    {
        $this->signIn(['website-banner.list', 'website-banner.update', 'website-offer.view', 'website-offer.update']);
        foreach (['banners', 'offers'] as $section) {
            $url = "/api/v1/admin/website-{$section}/animation";
            $this->getJson($url)->assertOk()->assertJsonPath('data.enabled', false);
            foreach ([[], ['enabled' => null], ['enabled' => 'true'], ['enabled' => 1], ['enabled' => '0']] as $invalid) {
                $this->putJson($url, $invalid)->assertUnprocessable()->assertJsonValidationErrors('enabled');
            }
            $this->putJson($url, ['enabled' => true])->assertOk()->assertJsonPath('data.enabled', true);
            $this->putJson($url, ['enabled' => true])->assertOk()->assertJsonPath('data.enabled', true);
        }
        $settings = app(GeneralSettingRepositoryInterface::class)->get();
        $company = $settings->company_name;
        $this->putJson('/api/v1/admin/website-banners/animation', ['enabled' => false, 'offer_autoplay_enabled' => false, 'company_name' => 'Ignored'])
            ->assertOk()->assertJsonPath('data.enabled', false);
        $this->assertTrue($settings->fresh()->offer_autoplay_enabled);
        $this->assertSame($company, $settings->fresh()->company_name);
    }

    public function test_permissions_are_independent_and_read_only_users_cannot_save(): void
    {
        $this->signIn(['website-banner.list']);
        $this->getJson('/api/v1/admin/website-banners/animation')->assertOk();
        $this->putJson('/api/v1/admin/website-banners/animation', ['enabled' => true])->assertForbidden();
        $this->getJson('/api/v1/admin/website-offers/animation')->assertForbidden();
        $this->signIn(['website-offer.view', 'website-offer.update']);
        $this->getJson('/api/v1/admin/website-offers/animation')->assertOk();
        $this->putJson('/api/v1/admin/website-offers/animation', ['enabled' => true])->assertOk();
        $this->putJson('/api/v1/admin/website-banners/animation', ['enabled' => true])->assertForbidden();
    }

    public function test_public_home_exposes_only_the_two_carousel_settings(): void
    {
        app(GeneralSettingRepositoryInterface::class)->update([
            'banner_autoplay_enabled' => true, 'offer_autoplay_enabled' => false,
            'company_email' => 'private@example.com',
        ]);
        $response = $this->getJson('/api/v1/website/home')->assertOk();
        $this->assertSame([
            'banner_autoplay_enabled' => true, 'offer_autoplay_enabled' => false,
        ], $response->json('data.carousel_settings'));
        $this->assertStringNotContainsString('private@example.com', $response->getContent());
    }

    public function test_settings_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/website-banners/animation')->assertUnauthorized();
        $this->putJson('/api/v1/admin/website-offers/animation', ['enabled' => true])->assertUnauthorized();
    }

    private function signIn(array $permissions): void
    {
        $key = uniqid('carousel-');
        $user = User::create([
            'first_name' => 'Carousel', 'last_name' => 'Editor', 'username' => $key,
            'email' => "{$key}@example.com", 'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        Sanctum::actingAs($user);
    }
}
