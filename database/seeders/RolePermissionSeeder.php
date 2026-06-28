<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Role::firstOrCreate([
            'name' => 'Super Admin'
        ]);

        Role::firstOrCreate([
            'name' => 'Admin'
        ]);

        Role::firstOrCreate([
            'name' => 'Store Manager'
        ]);

        Role::firstOrCreate([
            'name' => 'Delivery Boy'
        ]);

        Role::firstOrCreate([
            'name' => 'Customer'
        ]);
    }
}
