<?php

namespace Database\Seeders;

use App\Models\Category\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [

            'Grocery' => [
                'Rice',
                'Flour',
                'Oil & Ghee',
                'Pulses',
                'Sugar',
                'Salt',
                'Tea & Coffee',
                'Biscuits',
                'Snacks',
                'Beverages',
                'Dairy Products',
                'Frozen Foods',
            ],

            'Hardware' => [
                'Tools',
                'Fasteners',
                'Nails',
                'Screws',
                'Locks',
                'Door Fittings',
            ],

            'Paint' => [
                'Interior Paint',
                'Exterior Paint',
                'Primer',
                'Putty',
                'Wood Polish',
            ],

            'Electrical' => [
                'Switches',
                'Wires & Cables',
                'LED Lights',
                'Fans',
                'MCB',
            ],

            'Plumbing' => [
                'Pipes',
                'Taps',
                'Bathroom Accessories',
                'Water Tanks',
            ],

            'Personal Care' => [
                'Soap',
                'Shampoo',
                'Toothpaste',
                'Hair Oil',
            ],

            'Home Care' => [
                'Floor Cleaner',
                'Detergent',
                'Dishwash',
                'Air Freshener',
            ],

            'Stationery' => [
                'Notebook',
                'Pen',
                'Pencil',
            ],

            'Baby Care' => [
                'Baby Food',
                'Baby Soap',
                'Diapers',
            ],

            'Pet Care' => [
                'Pet Food',
                'Pet Shampoo',
            ],
        ];

        $sortOrder = 1;

        foreach ($categories as $parent => $children) {

            $parentCategory = Category::updateOrCreate(
                ['slug' => Str::slug($parent)],
                [
                    'name' => $parent,
                    'parent_id' => null,
                    'description' => $parent . ' Category',
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]
            );

            foreach ($children as $child) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($child)],
                    [
                        'name' => $child,
                        'parent_id' => $parentCategory->id,
                        'description' => $child . ' Category',
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
