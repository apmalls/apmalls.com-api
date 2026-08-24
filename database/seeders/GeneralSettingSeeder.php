<?php

namespace Database\Seeders;

use App\Models\Setting\GeneralSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GeneralSetting::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'AP Malls',
                'currency_name' => 'Indian Rupee',
                'currency_code' => 'INR',
                'currency_symbol' => '₹',
                'barcode_type' => 'CODE128',
                'barcode_prefix' => 'PRD',
                'barcode_start_number' => 100000,
                'thermal_paper_size' => '80mm',
                'auto_print_invoice' => false,
                'timezone' => 'Asia/Kolkata',
                'date_format' => 'd-m-Y',
                'time_format' => 'H:i',
                'status' => true,
            ]
        );
    }
}
