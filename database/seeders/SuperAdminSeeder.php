<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(

            [
                'email' => 'admin@apmalls.com'
            ],

            [
                'first_name' => 'Super',

                'last_name' => 'Admin',

                'username' => 'superadmin',

                'mobile' => '9999999999',

                'password' => Hash::make('Admin@123'),

                'is_active' => true,
            ]
        );

        $user->assignRole('Super Admin');
    }
}
