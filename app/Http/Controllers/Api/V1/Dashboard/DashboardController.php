<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {

            $data = $this->dashboardService
                ->getDashboard(auth()->user());

            return response()->json([

                'success' => true,

                'message' => 'Dashboard loaded successfully.',

                'data' => $data,

            ]);

        } catch (Throwable $exception) {

            return response()->json([

                'success' => false,

                'message' => $exception->getMessage(),

            ], 500);

        }
    }
}
