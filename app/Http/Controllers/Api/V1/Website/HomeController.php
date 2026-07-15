<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Website\HomeService;

class HomeController extends Controller
{
    public function __construct(
        protected HomeService $homeService,
    ) {
    }

    /**
     * Home Page
     */
    public function index(): JsonResponse
    {
        try {

            return response()->json([

                'success' => true,

                'message' => 'Home page fetched successfully.',

                'data' => $this->homeService->index(),

            ], 200);

        } catch (Throwable $exception) {

            return $this->handleException($exception);

        }
    }
}
