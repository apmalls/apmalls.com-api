<?php

namespace Tests\Feature;

use App\Http\Resources\Banner\WebsiteBannerResource;
use App\Models\Banner\WebsiteBanner;
use App\Models\User;
use App\Repositories\Contracts\WebsiteBannerRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebsiteBannerDisplayModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $user = $this->user();
        foreach (['create', 'update', 'view', 'list'] as $action) {
            $permission = Permission::firstOrCreate(['name' => "website-banner.{$action}", 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }
        Sanctum::actingAs($user);
    }

    public function test_migration_defaults_existing_records_and_rolls_back_without_losing_banners(): void
    {
        $migration = require database_path('migrations/2026_10_03_000004_add_display_mode_to_website_banners_table.php');
        $migration->down();
        $id = DB::table('website_banners')->insertGetId($this->attributes('legacy'));
        $migration->up();

        $this->assertSame('image_with_text', DB::table('website_banners')->where('id', $id)->value('display_mode'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('website_banners', 'display_mode'));
        $this->assertDatabaseHas('website_banners', ['id' => $id, 'title' => 'Banner legacy']);
        $migration->up();
    }

    public function test_create_defaults_to_text_mode_and_accepts_both_image_modes(): void
    {
        foreach ([null, 'full_image', 'image_with_text'] as $index => $mode) {
            $data = $this->attributes("create-{$index}");
            $data['desktop_image'] = UploadedFile::fake()->image('banner.png', 900, 300);
            if ($mode !== null) $data['display_mode'] = $mode;
            $response = $this->postJson('/api/v1/admin/website-banners', $data);
            $response->assertCreated()->assertJsonPath('data.display_mode', $mode ?? 'image_with_text');
            $this->assertDatabaseHas('website_banners', ['id' => $response->json('data.id'), 'display_mode' => $mode ?? 'image_with_text']);
        }
    }

    public function test_mode_switches_and_omitted_updates_preserve_media_copy_links_and_schedules(): void
    {
        foreach (['slider'] as $placement) {
            $banner = WebsiteBanner::create(array_merge($this->attributes($placement), [
                'banner_type' => $placement,
                'sub_title' => 'Preserved subtitle',
                'description' => 'Preserved description',
                'button_text' => 'Browse',
                'button_url' => 'https://apmalls.com/products',
                'open_new_tab' => true,
                'mobile_image' => 'banners/mobile.png',
                'start_date' => '2026-10-03 01:20:00',
                'end_date' => '2027-10-03 01:20:00',
            ]));
            $before = $banner->fresh()->getAttributes();
            $data = array_intersect_key($before, array_flip(['title', 'slug', 'type', 'banner_type', 'position']));
            foreach (['full_image', null, 'image_with_text', 'full_image'] as $mode) {
                $response = $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $mode === null ? $data : $data + ['display_mode' => $mode]);
                $response->assertOk()->assertJsonPath('data.display_mode', $mode ?? 'full_image');
                foreach (['desktop_image', 'mobile_image', 'sub_title', 'description', 'button_text', 'button_url', 'open_new_tab', 'start_date', 'end_date'] as $key) {
                    $this->assertSame($before[$key], $banner->fresh()->getRawOriginal($key), $key);
                }
            }
        }
    }

    public function test_invalid_modes_and_full_image_videos_are_rejected(): void
    {
        $banner = WebsiteBanner::create($this->attributes('invalid'));
        $data = $this->attributes('invalid');
        unset($data['desktop_image']);
        foreach (['unknown', null, ''] as $mode) {
            $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $data + ['display_mode' => $mode])
                ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        }
        $video = array_merge($data, ['type' => 'video', 'video_url' => 'https://example.com/banner.mp4']);
        $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $video + ['display_mode' => 'full_image'])
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $banner->update(['display_mode' => 'full_image']);
        $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $video)
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $video + ['display_mode' => 'image_with_text'])
            ->assertOk()->assertJsonPath('data.display_mode', 'image_with_text');
        $video['slug'] = 'new-video';
        $video['desktop_image'] = UploadedFile::fake()->image('video-poster.png');
        $this->postJson('/api/v1/admin/website-banners', $video + ['display_mode' => 'full_image'])
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $this->postJson('/api/v1/admin/website-banners', $video)
            ->assertCreated()->assertJsonPath('data.display_mode', 'image_with_text');
    }

    public function test_admin_and_homepage_payloads_serialize_saved_modes_for_both_placements(): void
    {
        $repository = app(WebsiteBannerRepositoryInterface::class);
        foreach (['slider'] as $placement) {
            $banner = WebsiteBanner::create(array_merge($this->attributes("resource-{$placement}"), [
                'banner_type' => $placement,
                'display_mode' => 'full_image',
            ]));
            $this->assertSame('full_image', (new WebsiteBannerResource($banner))->resolve()['display_mode']);
            $items = $repository->sliders();
            $this->assertSame('full_image', $items->toArray()[0]['display_mode']);
            $this->getJson("/api/v1/admin/website-banners/{$banner->id}")->assertOk()->assertJsonPath('data.display_mode', 'full_image');
        }
        $this->getJson('/api/v1/admin/website-banners')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/website/home')->assertOk()
            ->assertJsonPath('data.sliders.0.display_mode', 'full_image');
    }

    public function test_display_mode_does_not_bypass_banner_permissions(): void
    {
        $banner = WebsiteBanner::create($this->attributes('restricted'));
        Sanctum::actingAs($this->user());
        $this->putJson("/api/v1/admin/website-banners/{$banner->id}", $this->attributes('restricted') + ['display_mode' => 'full_image'])
            ->assertForbidden();
    }

    private function user(): User
    {
        $key = uniqid('banner-');

        return User::create([
            'first_name' => 'Banner', 'last_name' => 'Manager',
            'username' => $key, 'email' => "{$key}@example.com",
            'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function attributes(string $slug): array
    {
        return [
            'title' => "Banner {$slug}", 'slug' => $slug,
            'desktop_image' => 'banners/desktop.png',
            'type' => 'image', 'banner_type' => 'slider', 'position' => 'home_hero',
            'status' => true, 'sort_order' => 0,
        ];
    }
}
