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
                'description' => 'Cash Payment',
                'icon' => 'payment-modes/cash.png',
                'is_online' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'UPI',
                'code' => 'UPI',
                'description' => 'UPI Payment',
                'icon' => 'payment-modes/upi.png',
                'is_online' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],

            [
                'name' => 'Credit/Debit Card',
                'code' => 'CARD',
                'description' => 'Card Payment',
                'icon' => 'payment-modes/card.png',
                'is_online' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],

            [
                'name' => 'Bank Transfer',
                'code' => 'BANK',
                'description' => 'Bank Transfer',
                'icon' => 'payment-modes/bank.png',
                'is_online' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],

            [
                'name' => 'Razorpay',
                'code' => 'RAZORPAY',
                'description' => 'Razorpay Gateway',
                'icon' => 'payment-modes/razorpay.png',
                'is_online' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],

        ];

        foreach ($paymentModes as $paymentMode) {

            PaymentMode::updateOrCreate(

                ['code' => $paymentMode['code']],

                $paymentMode

            );

        }
    }
}
