<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /**
         * Super Admin
         */
        $superAdmin = Role::where('name', 'Super Admin')->firstOrFail();

        $superAdmin->syncPermissions(
            Permission::all()
        );

        /**
         * Admin
         */
        $admin = Role::where('name', 'Admin')->firstOrFail();

        $admin->syncPermissions([
            'dashboard.view',

            'category.view',
            'category.create',
            'category.update',
            'category.delete',

            'brand.view',
            'brand.create',
            'brand.update',
            'brand.delete',

            'product.view',
            'product.create',
            'product.update',
            'product.delete',

            'customer.view',

            'sale-order.view',
            'sale-order.invoice',

            'delivery-boy.list',
            'delivery-boy.view',
            'delivery-boy.create',
            'delivery-boy.update',
            'delivery-boy.change-status',

            'delivery-assignment.list',
            'delivery-assignment.view',
            'delivery-assignment.create',
            'delivery-assignment.update',
            'delivery-assignment.delete',
        ]);

        /**
         * Store Manager
         */
        $storeManager = Role::where('name', 'Store Manager')->firstOrFail();

        $storeManager->syncPermissions([
            'dashboard.view',

            'product.view',
            'product.update',

            'stock-movement.view',

            'sale-order.view',
        ]);

        /**
         * Delivery Boy
         */
        $deliveryBoy = Role::where('name', 'Delivery Boy')->firstOrFail();

        $deliveryBoy->syncPermissions([
            'dashboard.view',

            'delivery-assignment.list',
            'delivery-assignment.view',
            'delivery-assignment.update',
        ]);

        /**
         * Customer
         */
        $customer = Role::where('name', 'Customer')->firstOrFail();

        $customer->syncPermissions([
            'dashboard.view',

            'wishlist.view',
            'wishlist.create',
            'wishlist.delete',

            'cart.view',
            'cart.create',
            'cart.update',
            'cart.delete',

            'sale-order.view',
        ]);
    }
}
