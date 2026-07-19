<?php

namespace Database\Seeders;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [

            [
                'role'       => 'Super Admin',
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'username'   => 'superadmin',
                'email'      => 'superadmin@apmalls.com',
                'mobile'     => '9999999991',
            ],

            [
                'role'       => 'Admin',
                'first_name' => 'System',
                'last_name'  => 'Admin',
                'username'   => 'admin',
                'email'      => 'admin@apmalls.com',
                'mobile'     => '9999999992',
            ],

            [
                'role'       => 'Store Manager',
                'first_name' => 'Store',
                'last_name'  => 'Manager',
                'username'   => 'manager',
                'email'      => 'manager@apmalls.com',
                'mobile'     => '9999999993',
            ],

            [
                'role'       => 'Delivery Boy',
                'first_name' => 'Delivery',
                'last_name'  => 'Boy',
                'username'   => 'deliveryboy',
                'email'      => 'delivery@apmalls.com',
                'mobile'     => '9999999994',
            ],

            [
                'role'       => 'Customer',
                'first_name' => 'Rahul',
                'last_name'  => 'Sharma',
                'username'   => 'customer',
                'email'      => 'customer@apmalls.com',
                'mobile'     => '9999999995',
            ],

        ];

        foreach ($users as $data) {

            $user = User::firstOrCreate(

                [
                    'email' => $data['email'],
                ],

                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'username'   => $data['username'],
                    'mobile'     => $data['mobile'],
                    'password'   => Hash::make('Admin@123'),
                    'is_active'  => true,
                ]

            );

            $user->syncRoles($data['role']);

            /*
            |--------------------------------------------------------------------------
            | Customer Tables
            |--------------------------------------------------------------------------
            */

            if ($data['role'] === 'Customer') {

                $customer = Customer::firstOrCreate(

                    [
                        'user_id' => $user->id,
                    ],

                    [
                        'customer_code'    => 'CUS000001',
                        'customer_type'    => 'Retail',
                        'first_name'       => $user->first_name,
                        'last_name'        => $user->last_name,
                        'mobile'           => $user->mobile,
                        'email'            => $user->email,
                        'opening_balance'  => 0,
                        'credit_limit'     => 10000,
                        'reward_points'    => 0,
                        'is_active'        => true,
                        'created_by'       => 1,
                    ]
                );

                CustomerAddress::firstOrCreate(

                    [
                        'customer_id' => $customer->id,
                        'address_type' => 'Billing',
                    ],

                    [
                        'contact_person'  => $customer->first_name . ' ' . $customer->last_name,
                        'mobile'          => $customer->mobile,
                        'email'           => $customer->email,
                        'address_line_1'  => 'Bhatta Bazar',
                        'city'            => 'Purnea',
                        'state'           => 'Bihar',
                        'country'         => 'India',
                        'postal_code'     => '854301',
                        'is_default'      => true,
                        'created_by'      => 1,
                    ]

                );
            }
        }
    }
}
