<?php

namespace Database\Seeders;

use App\Models\Barcode\BarcodeTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarcodeTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [

            [
                'name' => '40 x 30 Label',
                'paper_size' => '40x30',
                'width' => 40,
                'height' => 30,
            ],

            [
                'name' => '50 x 25 Label',
                'paper_size' => '50x25',
                'width' => 50,
                'height' => 25,
            ],

            [
                'name' => '60 x 40 Label',
                'paper_size' => '60x40',
                'width' => 60,
                'height' => 40,
            ],

            [
                'name' => '80 x 50 Label',
                'paper_size' => '80x50',
                'width' => 80,
                'height' => 50,
            ],

            [
                'name' => '100 x 50 Label',
                'paper_size' => '100x50',
                'width' => 100,
                'height' => 50,
            ],

        ];

        foreach ($templates as $template) {
            BarcodeTemplate::firstOrCreate(
                ['paper_size' => $template['paper_size']],
                array_merge($template, [
                    'font_size' => 10,
                    'show_name' => true,
                    'show_price' => true,
                    'show_sku' => false,
                    'show_barcode' => true,
                    'show_qr' => false,
                    'status' => true,
                ])
            );
        }
    }
}
