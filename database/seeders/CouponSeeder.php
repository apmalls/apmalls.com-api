<?php

namespace Database\Seeders;

use App\Models\Coupon\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::updateOrCreate(

            [
                'code' => 'WELCOME10',
            ],

            [

                'name' => 'Welcome Offer',

                'discount_type' => 'Percentage',

                'discount_value' => 10,

                'minimum_order_amount' => 500,

                'maximum_discount_amount' => 500,

                'usage_limit' => 1000,

                'used_count' => 0,

                'is_active' => true,

            ]

        );

        Coupon::updateOrCreate(

            [
                'code' => 'SAVE100',
            ],

            [

                'name' => 'Flat ₹100 Off',

                'discount_type' => 'Fixed',

                'discount_value' => 100,

                'minimum_order_amount' => 1000,

                'usage_limit' => 500,

                'used_count' => 0,

                'is_active' => true,

            ]

        );
    }
}
