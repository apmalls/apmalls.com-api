<?php

namespace Database\Seeders;

use App\Models\Product\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [

            ['name' => 'Piece', 'short_name' => 'Pc'],
            ['name' => 'Kilogram', 'short_name' => 'Kg'],
            ['name' => 'Gram', 'short_name' => 'Gm'],
            ['name' => 'Liter', 'short_name' => 'L'],
            ['name' => 'Milliliter', 'short_name' => 'Ml'],
            ['name' => 'Meter', 'short_name' => 'M'],
            ['name' => 'Centimeter', 'short_name' => 'Cm'],
            ['name' => 'Foot', 'short_name' => 'Ft'],
            ['name' => 'Inch', 'short_name' => 'In'],
            ['name' => 'Pack', 'short_name' => 'Pack'],
            ['name' => 'Box', 'short_name' => 'Box'],
            ['name' => 'Bottle', 'short_name' => 'Bottle'],
            ['name' => 'Can', 'short_name' => 'Can'],
            ['name' => 'Bag', 'short_name' => 'Bag'],
            ['name' => 'Dozen', 'short_name' => 'Doz'],
            ['name' => 'Carton', 'short_name' => 'Carton'],
            ['name' => 'Roll', 'short_name' => 'Roll'],
            ['name' => 'Sheet', 'short_name' => 'Sheet'],
            ['name' => 'Pair', 'short_name' => 'Pair'],
            ['name' => 'Set', 'short_name' => 'Set'],

        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['short_name' => $unit['short_name']],
                [
                    'name'        => $unit['name'],
                    'description' => $unit['name'] . ' Unit',
                    'is_active'   => true,
                ]
            );
        }
    }
}
