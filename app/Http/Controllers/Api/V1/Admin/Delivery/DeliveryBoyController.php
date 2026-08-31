<?php

namespace App\Http\Controllers\Api\V1\Admin\Delivery;


use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Delivery\CreateDeliveryBoyRequest;
use App\Http\Requests\Admin\Delivery\UpdateDeliveryBoyRequest;
use App\Http\Resources\Delivery\DeliveryBoyResource;
use App\Services\Contracts\DeliveryBoyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class DeliveryBoyController extends Controller
{
    public function __construct(
        protected DeliveryBoyServiceInterface $deliveryBoyService
    ) {
    }

    /**
     * Delivery Boy role users without an operational profile.
     */
    public function unlinkedUsers(): JsonResponse
    {
        $users = User::role('Delivery Boy')
            ->whereDoesntHave('deliveryBoy', fn ($query) => $query->withTrashed())
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'mobile']);

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Delivery Boy Listing
     */
    public function index(Request $request): JsonResponse
    {
        $deliveryBoys = $this->deliveryBoyService->paginate(
            filters: $request->all(),
            perPage: (int) $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery boys fetched successfully.',
            'data' => DeliveryBoyResource::collection($deliveryBoys),
            'meta' => [
                'current_page' => $deliveryBoys->currentPage(),
                'last_page' => $deliveryBoys->lastPage(),
                'per_page' => $deliveryBoys->perPage(),
                'total' => $deliveryBoys->total(),
            ],
        ]);
    }

    /**
     * Store Delivery Boy
     */
    public function store(
        CreateDeliveryBoyRequest $request
    ): JsonResponse {

        $deliveryBoy = $this->deliveryBoyService
            ->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Delivery boy created successfully.',
            'data' => new DeliveryBoyResource($deliveryBoy),
        ], 201);
    }

    /**
     * Show Delivery Boy
     */
    public function show(
        int $id
    ): JsonResponse {

        $deliveryBoy = $this->deliveryBoyService
            ->findById($id);

        if (! $deliveryBoy) {
            abort(404, 'Delivery profile not found.');
        }

        return response()->json([
            'success' => true,
            'data' => new DeliveryBoyResource($deliveryBoy),
        ]);
    }

    /**
     * Update Delivery Boy
     */
    public function update(
        UpdateDeliveryBoyRequest $request,
        int $id
    ): JsonResponse {

        $deliveryBoy = $this->deliveryBoyService
            ->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Delivery boy updated successfully.',
            'data' => new DeliveryBoyResource($deliveryBoy),
        ]);
    }

    /**
     * Delete Delivery Boy
     */
    public function destroy(
        int $id
    ): JsonResponse {

        $this->deliveryBoyService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Delivery boy deleted successfully.',
        ]);
    }
}
