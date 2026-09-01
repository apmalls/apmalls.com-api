<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin\Delivery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Delivery\ResolveDeliveryConfirmationRequest;
use App\Http\Resources\Delivery\DeliveryConfirmationResource;
use App\Models\Delivery\DeliveryConfirmation;
use App\Services\Delivery\DeliveryConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryConfirmationController extends Controller
{
    public function __construct(private readonly DeliveryConfirmationService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $confirmations = DeliveryConfirmation::query()
            ->with([
                'assignment.saleOrder.customer',
                'assignment.deliveryBoy.user',
                'deliveryReportedBy',
                'customerConfirmedBy',
                'disputedBy',
                'resolvedBy',
            ])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => DeliveryConfirmationResource::collection($confirmations->items()),
            'meta' => [
                'current_page' => $confirmations->currentPage(),
                'last_page' => $confirmations->lastPage(),
                'per_page' => $confirmations->perPage(),
                'total' => $confirmations->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $confirmation = DeliveryConfirmation::query()
            ->with([
                'assignment.saleOrder.customer',
                'assignment.deliveryBoy.user',
                'deliveryReportedBy',
                'customerConfirmedBy',
                'disputedBy',
                'resolvedBy',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new DeliveryConfirmationResource($confirmation),
        ]);
    }

    public function resolve(ResolveDeliveryConfirmationRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'success' => true,
            'message' => $data['resolution'] === 'confirm'
                ? 'Delivery confirmed by manager.'
                : 'Delivery reopened for reassignment.',
            'data' => new DeliveryConfirmationResource(
                $this->service->resolve(
                    $request->user(),
                    $id,
                    $data['resolution'],
                    $data['remarks']
                )
            ),
        ]);
    }
}
