<?php

namespace Database\Seeders;

use App\Models\Payment\PaymentMode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $paymentModes = [

            [
                'name' => 'Cash',
                'code' => 'CASH',
                'is_active' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'UPI',
                'code' => 'UPI',
                'is_active' => true,
                'sort_order' => 2,
            ],

            [
                'name' => 'Bank Transfer',
                'code' => 'BANK_TRANSFER',
                'is_active' => true,
                'sort_order' => 3,
            ],

            [
                'name' => 'Cheque',
                'code' => 'CHEQUE',
                'is_active' => true,
                'sort_order' => 4,
            ],

            [
                'name' => 'Credit Card',
                'code' => 'CREDIT_CARD',
                'is_active' => true,
                'sort_order' => 5,
            ],

            [
                'name' => 'Debit Card',
                'code' => 'DEBIT_CARD',
                'is_active' => true,
                'sort_order' => 6,
            ],

            [
                'name' => 'Wallet',
                'code' => 'WALLET',
                'is_active' => true,
                'sort_order' => 7,
            ],

        ];

        foreach ($paymentModes as $paymentMode) {

            PaymentMode::updateOrCreate(

                [
                    'code' => $paymentMode['code'],
                ],

                $paymentMode

            );
        }
    }
}
