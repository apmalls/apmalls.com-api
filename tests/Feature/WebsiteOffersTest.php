<?php

namespace Tests\Feature;

use App\Models\Banner\WebsiteBanner;
use App\Models\Offer\WebsiteOffer;
use App\Models\User;
use App\Repositories\Contracts\WebsiteOfferRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsiteOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $user = $this->user();
        foreach (['list', 'view', 'create', 'update', 'delete', 'restore', 'force-delete', 'change-status'] as $action) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => "website-offer.{$action}", 'guard_name' => 'web']));
        }
        Sanctum::actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_migration_moves_live_and_trashed_offers_preserving_all_fields_and_grants(): void
    {
        $migration = $this->migration();
        $migration->down();
        $role = Role::create(['name' => 'Promotions Editor', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'website-banner.update', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = $this->user();
        $user->givePermissionTo($permission);
        $unrelated = Permission::firstOrCreate(['name' => 'product.view', 'guard_name' => 'web']);
        $role->givePermissionTo($unrelated);
        foreach ([1001, 1002] as $id) {
            DB::table('website_banners')->insert(array_merge($this->attributes("moved-{$id}"), [
                'id' => $id, 'banner_type' => 'offer', 'position' => 'offer',
                'desktop_image' => "existing/{$id}.png", 'mobile_image' => "existing/mobile-{$id}.png",
                'sub_title' => 'Preserved subtitle', 'description' => 'Preserved copy',
                'button_url' => 'https://apmalls.com/products', 'button_text' => 'Shop',
                'open_new_tab' => true, 'display_mode' => 'full_image',
                'start_date' => '2026-10-03 01:20:00', 'end_date' => '2027-10-03 01:20:00',
                'created_by' => $user->id, 'updated_by' => $user->id,
                'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-02 13:00:00',
                'deleted_at' => $id === 1002 ? '2026-10-03 14:00:00' : null,
            ]));
            Storage::disk('public')->put("existing/{$id}.png", 'image');
        }
        $before = DB::table('website_banners')->where('banner_type', 'offer')->orderBy('id')->get();
        $migration->up();
        foreach ($before as $record) {
            $original = (array) $record;
            unset($original['banner_type']);
            $actual = (array) DB::table('website_offers')->find($record->id);
            ksort($original); ksort($actual);
            $this->assertEquals($original, $actual);
            $this->assertDatabaseMissing('website_banners', ['id' => $record->id]);
            Storage::disk('public')->assertExists("existing/{$record->id}.png");
        }
        $this->assertFalse(Schema::hasColumn('website_offers', 'banner_type'));
        $this->assertTrue($role->fresh()->hasPermissionTo('website-offer.update'));
        $this->assertTrue($role->fresh()->hasPermissionTo('product.view'));
        $this->assertTrue($user->fresh()->hasDirectPermission('website-offer.update'));
        $new = WebsiteOffer::create($this->attributes('after-migration'));
        $this->assertGreaterThan(1002, $new->id);

        $migration->down();
        $this->assertFalse(Schema::hasTable('website_offers'));
        foreach ($before as $record) $this->assertDatabaseHas('website_banners', (array) $record);
        $this->assertDatabaseHas('website_banners', ['id' => $new->id, 'banner_type' => 'offer']);
        $migration->up();
    }

    public function test_rollback_stops_without_data_loss_on_id_or_slug_conflicts(): void
    {
        foreach (['id', 'slug'] as $conflict) {
            $offer = WebsiteOffer::create($this->attributes("conflict-{$conflict}"));
            $banner = $this->attributes("banner-{$conflict}") + ['banner_type' => 'slider'];
            $banner[$conflict] = $offer->{$conflict};
            $id = DB::table('website_banners')->insertGetId($banner);
            try {
                $this->migration()->down();
                $this->fail('Rollback should reject conflicting IDs or slugs.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('conflicts', $exception->getMessage());
            }
            $this->assertTrue(Schema::hasTable('website_offers'));
            $this->assertDatabaseHas('website_offers', ['id' => $offer->id]);
            $this->assertDatabaseHas('website_banners', ['id' => $id]);
            DB::table('website_banners')->where('id', $id)->delete();
            $offer->forceDelete();
        }
    }

    public function test_create_and_repeat_mode_changes_preserve_uploaded_media_and_hidden_copy(): void
    {
        $data = $this->attributes('new-offer') + [
            'sub_title' => 'Saved subtitle', 'description' => 'Saved description',
            'button_text' => 'Shop Now', 'button_url' => 'https://apmalls.com/products', 'open_new_tab' => true,
            'start_date' => '2026-10-03 01:20:00', 'end_date' => '2027-10-03 01:20:00',
        ];
        $data['desktop_image'] = UploadedFile::fake()->image('offer.png', 900, 300);
        $data['mobile_image'] = UploadedFile::fake()->image('mobile.png', 300, 400);
        $response = $this->postJson('/api/v1/admin/website-offers', $data);
        $response->assertCreated()->assertJsonPath('data.display_mode', 'image_with_text')
            ->assertJsonPath('data.schedule_timezone', 'Asia/Kolkata')->assertJsonMissingPath('data.banner_type');
        $id = $response->json('data.id');
        $before = WebsiteOffer::findOrFail($id)->getAttributes();
        $update = ['title' => $data['title'], 'slug' => $data['slug'], 'type' => 'image'];
        foreach (['full_image', null, 'image_with_text', 'full_image'] as $mode) {
            $this->putJson("/api/v1/admin/website-offers/{$id}", $mode === null ? $update : $update + ['display_mode' => $mode])
                ->assertOk()->assertJsonPath('data.display_mode', $mode ?? 'full_image');
            foreach (['desktop_image', 'mobile_image', 'sub_title', 'description', 'button_text', 'button_url', 'open_new_tab', 'start_date', 'end_date'] as $key) {
                $this->assertSame($before[$key], WebsiteOffer::find($id)->getRawOriginal($key), $key);
            }
        }
        $this->putJson("/api/v1/admin/website-offers/{$id}", $update + ['start_date' => null, 'end_date' => null])
            ->assertOk()->assertJsonPath('data.start_date', null)->assertJsonPath('data.end_date', null);
        Storage::disk('public')->assertExists($before['desktop_image']);
        Storage::disk('public')->assertExists($before['mobile_image']);
    }

    public function test_invalid_modes_video_restrictions_and_cross_module_requests(): void
    {
        $offer = WebsiteOffer::create($this->attributes('video'));
        $data = ['title' => $offer->title, 'slug' => $offer->slug, 'type' => 'video', 'video_url' => 'https://example.com/offer.mp4'];
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['display_mode' => 'full_image'])
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $offer->update(['display_mode' => 'full_image']);
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data)
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['display_mode' => 'image_with_text'])
            ->assertOk()->assertJsonPath('data.type', 'video');
        $data['display_mode'] = 'unknown';
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data)
            ->assertUnprocessable()->assertJsonValidationErrors('display_mode');
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", array_merge($data, ['type' => 'image', 'display_mode' => 'full_image', 'banner_type' => 'offer']))
            ->assertUnprocessable()->assertJsonValidationErrors('banner_type');

        $user = $this->user();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'website-banner.create', 'guard_name' => 'web']));
        Sanctum::actingAs($user);
        $banner = $this->attributes('disallowed-offer') + ['banner_type' => 'offer', 'position' => 'offer'];
        $banner['desktop_image'] = UploadedFile::fake()->image('offer.png');
        $this->postJson('/api/v1/admin/website-banners', $banner)
            ->assertUnprocessable()->assertJsonValidationErrors(['banner_type', 'position']);
    }

    public function test_scheduling_and_sorting_are_ist_based_with_homepage_compatibility_alias(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 1, 30, 0, 'Asia/Kolkata'));
        $first = WebsiteOffer::create($this->attributes('first') + ['sort_order' => 0, 'start_date' => '2026-10-03 01:30:00', 'end_date' => '2026-10-03 01:30:00']);
        $later = WebsiteOffer::create(array_merge($this->attributes('later'), ['sort_order' => 5, 'display_mode' => 'full_image']));
        $future = WebsiteOffer::create($this->attributes('future') + ['start_date' => '2026-10-03 01:31:00']);
        $expired = WebsiteOffer::create($this->attributes('expired') + ['end_date' => '2026-10-03 01:29:00']);
        $inactive = WebsiteOffer::create(array_merge($this->attributes('inactive'), ['status' => false]));
        $deleted = WebsiteOffer::create($this->attributes('deleted'));
        $deleted->delete();
        $this->assertSame([$first->id, $later->id], app(WebsiteOfferRepositoryInterface::class)->active()->modelKeys());
        $this->assertSame('scheduled', $future->publicationStatus());
        $this->assertSame('expired', $expired->publicationStatus());
        $this->assertSame('inactive', $inactive->publicationStatus());
        $banner = WebsiteBanner::create($this->attributes('banner') + ['banner_type' => 'slider', 'position' => 'home_hero']);
        $response = $this->getJson('/api/v1/website/home')->assertOk()
            ->assertJsonCount(2, 'data.offers')->assertJsonCount(1, 'data.sliders')
            ->assertJsonPath('data.offers.0.id', $first->id)->assertJsonPath('data.sliders.0.id', $banner->id);
        $this->assertSame($response->json('data.offers'), $response->json('data.offer_banners'));
        $this->getJson('/api/v1/admin/website-offers?search=later&status=1')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/website-offers?status=0')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_status_trash_restore_and_permanent_deletion_preserve_files_until_final_deletion(): void
    {
        $offer = WebsiteOffer::create($this->attributes('trash'));
        Storage::disk('public')->put($offer->desktop_image, 'image');
        $this->patchJson("/api/v1/admin/website-offers/{$offer->id}/status")->assertOk()->assertJsonPath('data.status', false);
        $this->patchJson("/api/v1/admin/website-offers/{$offer->id}/status", ['status' => false])->assertOk()->assertJsonPath('data.status', false);
        $this->patchJson("/api/v1/admin/website-offers/{$offer->id}/status", ['status' => true])->assertOk()->assertJsonPath('data.status', true);
        $this->deleteJson("/api/v1/admin/website-offers/{$offer->id}/force-delete")->assertNotFound();
        $this->deleteJson("/api/v1/admin/website-offers/{$offer->id}")->assertOk();
        $this->assertSoftDeleted('website_offers', ['id' => $offer->id]);
        Storage::disk('public')->assertExists($offer->desktop_image);
        $this->getJson('/api/v1/admin/website-offers/trash')->assertOk()->assertJsonPath('data.data.0.id', $offer->id);
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}/restore")->assertOk();
        $this->getJson("/api/v1/admin/website-offers/{$offer->id}")->assertOk();
        $this->deleteJson("/api/v1/admin/website-offers/{$offer->id}")->assertOk();
        $this->deleteJson("/api/v1/admin/website-offers/{$offer->id}/force-delete")->assertOk();
        $this->assertDatabaseMissing('website_offers', ['id' => $offer->id]);
        Storage::disk('public')->assertMissing($offer->desktop_image);
    }

    public function test_offer_permissions_are_independent_of_banner_permissions(): void
    {
        $offer = WebsiteOffer::create($this->attributes('restricted'));
        $user = $this->user();
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'website-banner.view', 'guard_name' => 'web']));
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/admin/website-offers')->assertForbidden();
        $this->getJson("/api/v1/admin/website-offers/{$offer->id}")->assertForbidden();
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", ['title' => 'No', 'slug' => 'no', 'type' => 'image'])->assertForbidden();
        $this->deleteJson("/api/v1/admin/website-offers/{$offer->id}")->assertForbidden();
        $this->patchJson("/api/v1/admin/website-offers/{$offer->id}/status")->assertForbidden();
        $user->givePermissionTo(Permission::findByName('website-offer.view', 'web'));
        $this->getJson("/api/v1/admin/website-offers/{$offer->id}")->assertOk();
    }

    public function test_partial_schedule_changes_validate_against_preserved_dates(): void
    {
        $offer = WebsiteOffer::create($this->attributes('schedule-edit') + [
            'start_date' => '2026-10-03 01:20:00', 'end_date' => '2026-10-04 01:20:00',
        ]);
        $data = ['title' => $offer->title, 'slug' => $offer->slug, 'type' => 'image'];
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['end_date' => '2026-10-02 01:20:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['start_date' => '2026-10-05 01:20:00'])
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['start_date' => null])
            ->assertOk()->assertJsonPath('data.start_date', null);
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", $data + ['end_date' => '2026-10-02 01:20:00'])
            ->assertOk()->assertJsonPath('data.end_date', '2026-10-02 01:20:00');
    }

    public function test_invalid_query_and_non_nullable_controls_return_validation_errors(): void
    {
        $this->getJson('/api/v1/admin/website-offers?per_page=0')->assertUnprocessable();
        $this->getJson('/api/v1/admin/website-offers?per_page=101')->assertUnprocessable();
        $this->getJson('/api/v1/admin/website-offers?status=other')->assertUnprocessable();
        $offer = WebsiteOffer::create($this->attributes('nullable'));
        $this->putJson("/api/v1/admin/website-offers/{$offer->id}", [
            'title' => $offer->title, 'slug' => $offer->slug, 'type' => 'image', 'status' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_replacing_images_removes_only_the_old_image_after_saving(): void
    {
        $offer = WebsiteOffer::create($this->attributes('replace'));
        Storage::disk('public')->put($offer->desktop_image, 'old');
        $response = $this->putJson("/api/v1/admin/website-offers/{$offer->id}", [
            'title' => $offer->title, 'slug' => $offer->slug, 'type' => 'image',
            'desktop_image' => UploadedFile::fake()->image('replacement.png', 900, 300),
        ])->assertOk();
        Storage::disk('public')->assertMissing($offer->desktop_image);
        Storage::disk('public')->assertExists($response->json('data.desktop_image'));
    }

    private function migration()
    {
        return require database_path('migrations/2026_10_03_000005_separate_website_offers.php');
    }

    private function attributes(string $slug): array
    {
        return [
            'title' => "Offer {$slug}", 'slug' => $slug, 'desktop_image' => "offers/{$slug}.png",
            'type' => 'image', 'status' => true, 'sort_order' => 0,
        ];
    }

    private function user(): User
    {
        $key = uniqid('offer-');
        return User::create([
            'first_name' => 'Offers', 'last_name' => 'Manager', 'username' => $key,
            'email' => "{$key}@example.com", 'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }
}
