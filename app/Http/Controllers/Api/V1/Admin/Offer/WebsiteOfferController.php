<?php

namespace App\Http\Controllers\Api\V1\Admin\Offer;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Offer\WebsiteOfferResource;
use App\Http\Requests\Offer\StoreWebsiteOfferRequest;
use App\Http\Requests\Offer\UpdateWebsiteOfferRequest;
use App\Services\Contracts\WebsiteOfferServiceInterface;
use Illuminate\Http\Request;

class WebsiteOfferController extends Controller
{
    public function __construct(
        protected WebsiteOfferServiceInterface $service
    ) {
    }

    /**
     * Offer Listing
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $offers = $this->service->paginate([
            'search' => $request->search,
            'status' => $request->status,
            'per_page' => $request->per_page ?? 15,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Website offer list fetched successfully.',
            'data' => WebsiteOfferResource::collection($offers),
            'meta' => [
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ]
        ]);
    }

    /**
     * Active Offer
     */
    public function active(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WebsiteOfferResource::collection(
                $this->service->active()
            )
        ]);
    }

    /**
     * Offer Details
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new WebsiteOfferResource(
                $this->service->findById($id)
            )
        ]);
    }

    /**
     * Create Offer
     */
    public function store(StoreWebsiteOfferRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('desktop_image')) {
            $data['desktop_image'] = $this->uploadFile(
                $request->file('desktop_image'),
                'website/offers/desktop'
            );
        }

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $this->uploadFile(
                $request->file('mobile_image'),
                'website/offers/mobile'
            );
        }

        $offer = $this->service->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Website offer created successfully.',
            'data' => new WebsiteOfferResource($offer)
        ], 201);
    }

    /**
     * Update Offer
     */
    public function update(
        UpdateWebsiteOfferRequest $request,
        int $id
    ): JsonResponse {

        $offer = $this->service->findById($id);

        $data = $request->validated();

        if ($request->hasFile('desktop_image')) {

            $data['desktop_image'] = $this->uploadFile(
                $request->file('desktop_image'),
                'website/offers/desktop'
            );
        }

        if ($request->hasFile('mobile_image')) {

            $data['mobile_image'] = $this->uploadFile(
                $request->file('mobile_image'),
                'website/offers/mobile'
            );
        }
        $previous = $offer;
        $offer = $this->service->update($id, $data);
        foreach (['desktop_image', 'mobile_image'] as $field) {
            if ($request->hasFile($field) && $previous->{$field}) {
                $this->deleteFile($previous->{$field});
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Website offer updated successfully.',
            'data' => new WebsiteOfferResource($offer)
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
            'message' => 'Website offer deleted successfully.'
        ]);
    }

    /**
     * Trash List
     */
    public function trash(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => WebsiteOfferResource::collection($this->service->trashed())->response()->getData(true)
        ]);
    }

    /**
     * Restore Offer
     */
    public function restore(int $id): JsonResponse
    {
        $this->service->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Website offer restored successfully.'
        ]);
    }

    /**
     * Force Delete
     */
    public function forceDelete(int $id): JsonResponse
    {
        $offer = $this->service->findTrashedById($id);
        $this->service->forceDelete($id);

        if (!empty($offer->desktop_image)) {
            $this->deleteFile($offer->desktop_image);
        }

        if (!empty($offer->mobile_image)) {
            $this->deleteFile($offer->mobile_image);
        }

        return response()->json([
            'success' => true,
            'message' => 'Website offer permanently deleted.'
        ]);
    }

    /**
     * Change Status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => ['sometimes', 'required', 'boolean']]);
        $offer = $request->exists('status')
            ? $this->service->update($id, ['status' => $request->boolean('status')])
            : $this->service->changeStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => new WebsiteOfferResource(
                $offer
            )
        ]);
    }

}
