<?php

namespace App\Http\Controllers\Api\V1\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\GeneralSettingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarouselAnimationController extends Controller
{
    public function __construct(private GeneralSettingRepositoryInterface $settings) {}

    public function showBanners(): JsonResponse
    {
        return $this->response('banner_autoplay_enabled');
    }

    public function showOffers(): JsonResponse
    {
        return $this->response('offer_autoplay_enabled');
    }

    public function updateBanners(Request $request): JsonResponse
    {
        return $this->save($request, 'banner_autoplay_enabled');
    }

    public function updateOffers(Request $request): JsonResponse
    {
        return $this->save($request, 'offer_autoplay_enabled');
    }

    private function save(Request $request, string $column): JsonResponse
    {
        $request->validate(['enabled' => ['required', 'boolean:strict']]);
        // Update only this section, never a stale snapshot of all settings.
        $this->settings->update([$column => $request->boolean('enabled')]);

        return $this->response($column);
    }

    private function response(string $column): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Carousel setting saved or retrieved successfully.',
            'data' => ['enabled' => (bool) $this->settings->get()->{$column}],
        ]);
    }
}
