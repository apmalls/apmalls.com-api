<?php

namespace App\Http\Controllers\Api\V1\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Delivery\AssignDeliveryBoyRequest;
use App\Http\Resources\Delivery\DeliveryAssignmentResource;
use App\Services\Contracts\DeliveryAssignmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAssignmentController extends Controller
{
    public function __construct(
        protected DeliveryAssignmentServiceInterface $deliveryAssignmentService
    ) {
    }

    /**
     * Assignment Listing
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = $this->deliveryAssignmentService->paginate(
            filters: $request->all(),
            perPage: (int) $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => DeliveryAssignmentResource::collection($assignments->items()),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    /**
     * Assign Order
     */
    public function assign(
        AssignDeliveryBoyRequest $request
    ): JsonResponse {

        $assignment = $this->deliveryAssignmentService
            ->assignOrder([...$request->validated(), 'assigned_by' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Order assigned successfully.',
            'data' => new DeliveryAssignmentResource($assignment),
        ], 201);
    }

    /**
     * Show Assignment
     */
    public function show(int $id): JsonResponse
    {
        $assignment = $this->deliveryAssignmentService
            ->findById($id);

        if (! $assignment) {
            abort(404, 'Delivery assignment not found.');
        }

        return response()->json([
            'success' => true,
            'data' => new DeliveryAssignmentResource($assignment),
        ]);
    }

    /**
     * Delete Assignment
     */
    public function destroy(int $id): JsonResponse
    {
        $this->deliveryAssignmentService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Assignment cancelled successfully.',
        ]);
    }

    /**
     * Accept Assignment
     */
    public function accept(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order accepted successfully.',
            'data' => new DeliveryAssignmentResource(
                $this->deliveryAssignmentService->accept($id)
            ),
        ]);
    }

    /**
     * Reject Assignment
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order rejected successfully.',
            'data' => new DeliveryAssignmentResource(
                $this->deliveryAssignmentService->reject(
                    $id,
                    $request->input('remarks')
                )
            ),
        ]);
    }

    /**
     * Pickup Order
     */
    public function pickup(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order picked successfully.',
            'data' => new DeliveryAssignmentResource(
                $this->deliveryAssignmentService->pickup($id)
            ),
        ]);
    }

    /**
     * Out For Delivery
     */
    public function outForDelivery(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order is out for delivery.',
            'data' => new DeliveryAssignmentResource(
                $this->deliveryAssignmentService->outForDelivery($id)
            ),
        ]);
    }

    /**
     * Delivered
     */
    public function delivered(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Order delivered successfully.',
            'data' => new DeliveryAssignmentResource(
                $this->deliveryAssignmentService->delivered($id)
            ),
        ]);
    }

    /**
     * Assignment History
     */
    public function history(int $saleOrderId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DeliveryAssignmentResource::collection(
                $this->deliveryAssignmentService->history($saleOrderId)
            ),
        ]);
    }
}
