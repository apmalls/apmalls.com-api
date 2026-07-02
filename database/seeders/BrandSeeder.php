<?php

namespace Database\Seeders;

use App\Models\Product\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $brands = [
            'Aashirvaad',
            'Fortune',
            'Tata',
            'Amul',
            'Parle',
            'Britannia',
            'Nestle',
            'Maggi',
            'Surf Excel',
            'Wheel',
            'Lux',
            'Lifebuoy',
            'Patanjali',
            'Dabur',
            'Havells',
            'Asian Paints',
            'Berger',
            'Nerolac',
            'Pidilite',
            'Fevicol',
            'JK Cement',
            'UltraTech',
            'Ambuja',
            'Pidilite',
            'Anchor',
            'Crompton',
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand)],
                [
                    'name'        => $brand,
                    'description' => $brand . ' Brand',
                    'is_active'   => true,
                ]
            );
        }
    }
}
