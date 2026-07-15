<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\User;
use App\Models\Customer\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\CustomerRepositoryInterface;

class AuthService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
    ) {
    }

    /**
     * Customer Registration
     */
    public function register(
        array $data
    ): array {

        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Create User
            |--------------------------------------------------------------------------
            */

            $user = User::create([

                'first_name' => $data['first_name'],

                'last_name' => $data['last_name'] ?? null,

                'email' => $data['email'],

                'mobile' => $data['mobile'],

                'password' => Hash::make(
                    $data['password']
                ),

            ]);

            /*
            |--------------------------------------------------------------------------
            | Assign Customer Role
            |--------------------------------------------------------------------------
            */

            $user->assignRole(
                'Customer'
            );

            /*
            |--------------------------------------------------------------------------
            | Create Customer
            |--------------------------------------------------------------------------
            */

            $customer = $this->customerRepository
                ->create([

                    'user_id' => $user->id,

                    'customer_code' => $this->generateCustomerCode(),

                    'customer_type' => 'Retail',

                    'first_name' => $user->first_name,

                    'last_name' => $user->last_name,

                    'mobile' => $user->mobile,

                    'email' => $user->email,

                    'is_active' => true,

                ]);

            /*
            |--------------------------------------------------------------------------
            | Sanctum Token
            |--------------------------------------------------------------------------
            */

            $token = $user

                ->createToken(
                    'customer-token'
                )

                ->plainTextToken;

            return [

                'user' => $user,

                'customer' => $customer,

                'token' => $token,

            ];

        });

    }

    /**
     * Generate Customer Code
     */
    private function generateCustomerCode(): string
    {
        return 'CUS-' . str_pad(

            (string) (
                Customer::max('id') + 1
            ),

            6,

            '0',

            STR_PAD_LEFT

        );
    }
}
