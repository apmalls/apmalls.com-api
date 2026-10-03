<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Website;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Category\CategoryResource;
use App\Services\Contracts\Website\HomeServiceInterface;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected HomeServiceInterface $homeService,
    ) {}

    /**
     * Website Home Page.
     */
    public function index(): JsonResponse
    {
        try {

            $data = $this->homeService->index();

            return response()->json([

                'success' => true,

                'message' => 'Home data fetched successfully.',

                'data' => [
                    'carousel_settings' => $data['carousel_settings'],

                    /*
                    |--------------------------------------------------------------------------
                    | Sliders
                    |--------------------------------------------------------------------------
                    */

                    'sliders' => $data['sliders'],

                    /*
                    |--------------------------------------------------------------------------
                    | Offer Banners
                    |--------------------------------------------------------------------------
                    */

                    'offers' => \App\Http\Resources\Offer\WebsiteOfferResource::collection($data['offers']),

                    'offer_banners' => \App\Http\Resources\Offer\WebsiteOfferResource::collection($data['offers']),

                    /*
                    |--------------------------------------------------------------------------
                    | Categories
                    |--------------------------------------------------------------------------
                    */

                    'featured_categories' => CategoryResource::collection(
                        $data['featured_categories']
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Brands
                    |--------------------------------------------------------------------------
                    */

                    'featured_brands' => BrandResource::collection(
                        $data['featured_brands']
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Products
                    |--------------------------------------------------------------------------
                    */

                    'featured_products' => ProductResource::collection(
                        $data['featured_products']
                    ),

                    'new_arrivals' => ProductResource::collection(
                        $data['new_arrivals']
                    ),

                    'best_sellers' => ProductResource::collection(
                        $data['best_sellers']
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | Future Sections
                    |--------------------------------------------------------------------------
                    */

                    'trending_products' => ProductResource::collection(
                        collect($data['trending_products'])
                    ),

                    'recommended_products' => ProductResource::collection(
                        collect($data['recommended_products'])
                    ),

                ],

            ]);

        } catch (Exception $exception) {

            return response()->json([

                'success' => false,

                'message' => $exception->getMessage(),

            ], 500);

        }
    }
}
