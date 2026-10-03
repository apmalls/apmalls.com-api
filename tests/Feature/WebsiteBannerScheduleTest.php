<?php

namespace Tests\Feature;

use App\Http\Resources\Banner\WebsiteBannerResource;
use App\Models\Banner\WebsiteBanner;
use App\Models\Offer\WebsiteOffer;
use App\Repositories\Contracts\WebsiteOfferRepositoryInterface;
use App\Repositories\Contracts\WebsiteBannerRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebsiteBannerScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_homepage_banner_queries_use_the_business_timezone(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 30, 1, 30, 0, 'Asia/Kolkata'));

        $published = $this->banner('published', 'slider', true, '2026-09-30 01:20:00', '2026-09-30 02:00:00');
        $immediate = $this->banner('immediate', 'slider', true);
        $scheduled = $this->banner('scheduled', 'slider', true, '2026-09-30 01:31:00');
        $expired = $this->banner('expired', 'slider', true, null, '2026-09-30 01:29:00');
        $inactive = $this->banner('inactive', 'slider', false);
        $offer = WebsiteOffer::create([
            'title' => 'Offer', 'slug' => 'offer', 'status' => true,
            'start_date' => '2026-09-30 01:20:00', 'end_date' => '2026-09-30 02:00:00',
        ]);

        $repository = app(WebsiteBannerRepositoryInterface::class);

        $this->assertEqualsCanonicalizing(
            [$published->id, $immediate->id],
            $repository->sliders()->modelKeys()
        );
        $this->assertSame([$offer->id], app(WebsiteOfferRepositoryInterface::class)->active()->modelKeys());
        $this->assertSame('published', $published->publicationStatus());
        $this->assertSame('scheduled', $scheduled->publicationStatus());
        $this->assertSame('expired', $expired->publicationStatus());
        $this->assertSame('inactive', $inactive->publicationStatus());
    }

    public function test_schedule_boundaries_and_resource_metadata_are_consistent(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 30, 1, 30, 0, 'Asia/Kolkata'));

        $banner = $this->banner(
            'boundary',
            'slider',
            true,
            '2026-09-30 01:30:00',
            '2026-09-30 01:30:00'
        );

        $this->assertTrue(WebsiteBanner::published()->whereKey($banner->getKey())->exists());

        $resource = (new WebsiteBannerResource($banner->fresh()))->resolve();

        $this->assertSame('published', $resource['publication_status']);
        $this->assertSame('Asia/Kolkata', $resource['schedule_timezone']);
        $this->assertSame('2026-09-30 01:30:00', $resource['start_date']);
        $this->assertSame('2026-09-30 01:30:00', $resource['end_date']);
    }

    private function banner(
        string $slug,
        string $bannerType,
        bool $status,
        ?string $startDate = null,
        ?string $endDate = null
    ): WebsiteBanner {
        return WebsiteBanner::create([
            'title' => ucfirst($slug),
            'slug' => $slug,
            'desktop_image' => "website/banners/{$slug}.webp",
            'type' => 'image',
            'banner_type' => $bannerType,
            'position' => $bannerType === 'offer' ? 'offer' : 'home_hero',
            'sort_order' => 0,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
