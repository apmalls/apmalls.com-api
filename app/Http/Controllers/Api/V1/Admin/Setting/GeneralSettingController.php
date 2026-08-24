<?php

namespace App\Http\Controllers\API\V1\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Setting\UpdateGeneralSettingRequest;
use App\Services\Contracts\GeneralSettingServiceInterface;
use Illuminate\Http\Request;
use App\Http\Resources\GeneralSettingResource;

use Illuminate\Http\JsonResponse;
use Throwable;

class GeneralSettingController extends Controller
{
    public function __construct(
        protected GeneralSettingServiceInterface $generalSettingService
    ) {
    }

    /**
     * Display General Settings.
     */
    public function show(): JsonResponse
    {
        try {

            $setting = $this->generalSettingService->get();

            return response()->json([
                'success' => true,
                'message' => 'General settings fetched successfully.',
                'data' => new GeneralSettingResource($setting),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch general settings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update General Settings.
     */
    public function update(UpdateGeneralSettingRequest $request): JsonResponse
    {
        try {

            $setting = $this->generalSettingService->update(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully.',
                'data' => new GeneralSettingResource($setting),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update general settings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
