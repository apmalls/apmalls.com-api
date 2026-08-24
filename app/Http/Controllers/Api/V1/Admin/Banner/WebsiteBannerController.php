<?php

namespace App\Http\Controllers\Api\V1\Admin\Banner;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Banner\WebsiteBannerResource;
use App\Http\Requests\Banner\StoreWebsiteBannerRequest;
use App\Http\Requests\Banner\UpdateWebsiteBannerRequest;
use App\Services\Contracts\WebsiteBannerServiceInterface;
use Illuminate\Http\Request;

class WebsiteBannerController extends Controller
{
    public function __construct(
        protected WebsiteBannerServiceInterface $service
    ) {
    }

    /**
     * Banner Listing
     */
    public function index(Request $request): JsonResponse
    {
        $banners = $this->service->paginate([
            'search' => $request->search,
            'status' => $request->status,
            'per_page' => $request->per_page ?? 15,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Website banner list fetched successfully.',
            'data' => WebsiteBannerResource::collection($banners),
            'meta' => [
                'current_page' => $banners->currentPage(),
                'last_page' => $banners->lastPage(),
                'per_page' => $banners->perPage(),
                'total' => $banners->total(),
            ]
        ]);
    }

    /**
     * Active Banner
     */
    public function active(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WebsiteBannerResource::collection(
                $this->service->active()
            )
        ]);
    }

    /**
     * Banner Details
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new WebsiteBannerResource(
                $this->service->findById($id)
            )
        ]);
    }

    /**
     * Create Banner
     */
    public function store(StoreWebsiteBannerRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('desktop_image')) {
            $data['desktop_image'] = $this->uploadFile(
                $request->file('desktop_image'),
                'website/banners/desktop'
            );
        }

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $this->uploadFile(
                $request->file('mobile_image'),
                'website/banners/mobile'
            );
        }

        $banner = $this->service->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Website banner created successfully.',
            'data' => new WebsiteBannerResource($banner)
        ], 201);
    }

    /**
     * Update Banner
     */
    public function update(
        UpdateWebsiteBannerRequest $request,
        int $id
    ): JsonResponse {

        $banner = $this->service->findById($id);

        $data = $request->validated();

        if ($request->hasFile('desktop_image')) {

            if (!empty($banner->desktop_image)) {
                $this->deleteFile($banner->desktop_image);
            }

            $data['desktop_image'] = $this->uploadFile(
                $request->file('desktop_image'),
                'website/banners/desktop'
            );
        }

        if ($request->hasFile('mobile_image')) {

            if (!empty($banner->mobile_image)) {
                $this->deleteFile($banner->mobile_image);
            }

            $data['mobile_image'] = $this->uploadFile(
                $request->file('mobile_image'),
                'website/banners/mobile'
            );
        }
        $banner = $this->service->update($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Website banner updated successfully.',
            'data' => new WebsiteBannerResource($banner)
        ]);
    }

    /**
     * Soft Delete
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Website banner deleted successfully.'
        ]);
    }

    /**
     * Trash List
     */
    public function trash(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->trashed()
        ]);
    }

    /**
     * Restore Banner
     */
    public function restore(int $id): JsonResponse
    {
        $this->service->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Website banner restored successfully.'
        ]);
    }

    /**
     * Force Delete
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->beginTransaction();
        $banner = $this->service->findById($id);

        if (!empty($banner->desktop_image)) {
            $this->deleteFile($banner->desktop_image);
        }

        if (!empty($banner->mobile_image)) {
            $this->deleteFile($banner->mobile_image);
        }

        $this->service->forceDelete($id);

        $this->commit();
        return response()->json([
            'success' => true,
            'message' => 'Website banner permanently deleted.'
        ]);
    }

    /**
     * Change Status
     */
    public function changeStatus(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => new WebsiteBannerResource(
                $this->service->changeStatus($id)
            )
        ]);
    }

    /**
     * Bulk Delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:website_banners,id'],
        ]);

        $this->beginTransaction();

        try {

            foreach ($request->ids as $id) {

                $banner = $this->service->findById($id);

                if (!empty($banner->desktop_image)) {
                    $this->deleteFile($banner->desktop_image);
                }

                if (!empty($banner->mobile_image)) {
                    $this->deleteFile($banner->mobile_image);
                }
            }

            $this->service->bulkDelete($request->ids);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'Selected website banners deleted successfully.',
            ]);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);
        }
    }
}
