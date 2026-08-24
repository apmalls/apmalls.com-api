<?php

namespace App\Http\Resources\Website;

use App\Http\Resources\Banner\WebsiteBannerResource;
use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\POS\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'banners' => WebsiteBannerResource::collection($this['banners']),

            'featured_categories' => CategoryResource::collection($this['featured_categories']),

            'featured_products' => ProductResource::collection($this['featured_products']),

            'new_arrivals' => ProductResource::collection($this['new_arrivals']),

            'best_sellers' => ProductResource::collection($this['best_sellers']),

            'brands' => BrandResource::collection($this['brands']),
        ];
    }
}
