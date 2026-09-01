<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Delivery\ConfirmCustomerDeliveryRequest;
use App\Http\Requests\Admin\Delivery\DisputeDeliveryRequest;
use App\Http\Resources\Delivery\DeliveryConfirmationResource;
use App\Services\Delivery\DeliveryConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryConfirmationController extends Controller
{
    public function __construct(private readonly DeliveryConfirmationService $service)
    {
    }

    public function confirm(ConfirmCustomerDeliveryRequest $request, string $saleNo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Delivery and payment confirmed.',
            'data' => new DeliveryConfirmationResource(
                $this->service->confirmByCustomer(
                    $request->user(),
                    $saleNo,
                    (float) $request->validated('amount_paid')
                )
            ),
        ]);
    }

    public function otp(Request $request, string $saleNo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Delivery code generated.',
            'data' => $this->service->generateOtp($request->user(), $saleNo),
        ]);
    }

    public function dispute(DisputeDeliveryRequest $request, string $saleNo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Delivery dispute submitted for review.',
            'data' => new DeliveryConfirmationResource(
                $this->service->dispute(
                    $request->user(),
                    $saleNo,
                    $request->validated('reason')
                )
            ),
        ]);
    }
}
