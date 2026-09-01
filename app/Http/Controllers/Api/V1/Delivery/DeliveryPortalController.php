<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Delivery\DeliveryActionRequest;
use App\Http\Requests\Admin\Delivery\ConfirmDeliveryOtpRequest;
use App\Http\Requests\Admin\Delivery\UpdateDeliveryAvailabilityRequest;
use App\Http\Resources\Delivery\DeliveryAssignmentResource;
use App\Http\Resources\Delivery\DeliveryBoyResource;
use App\Services\Delivery\DeliveryPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryPortalController extends Controller
{
    public function __construct(private readonly DeliveryPortalService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $assignments = $this->service->assignments($request->user(), $request->all());

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

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new DeliveryAssignmentResource($this->service->assignment($request->user(), $id)),
        ]);
    }

    public function availability(UpdateDeliveryAvailabilityRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'success' => true,
            'message' => 'Availability updated.',
            'data' => new DeliveryBoyResource(
                $this->service->updateAvailability($request->user(), $data['is_available'])
            ),
        ]);
    }

    public function action(DeliveryActionRequest $request, int $id, string $action): JsonResponse
    {
        $action = str_replace('-', '_', $action);
        $data = $request->validated();

        $assignment = $action === 'delivered'
            ? $this->service->delivered(
                $request->user(),
                $id,
                (bool) ($data['cash_collected'] ?? false),
                $data['remarks'] ?? null
            )
            : $this->service->transition(
                $request->user(),
                $id,
                $action,
                $data['remarks'] ?? null
            );

        return response()->json([
            'success' => true,
            'message' => $action === 'delivered'
                ? 'Handover reported. Awaiting customer confirmation.'
                : 'Delivery assignment updated.',
            'data' => new DeliveryAssignmentResource($assignment),
        ]);
    }

    public function confirmOtp(ConfirmDeliveryOtpRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Delivery confirmed with customer code.',
            'data' => new DeliveryAssignmentResource(
                $this->service->confirmOtp($request->user(), $id, $request->validated('otp'))
            ),
        ]);
    }
}
