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
            'dashboard.statistics',

            'category.view',
            'category.create',
            'category.update',
            'category.delete',
            'category.change-status',

            'brand.view',
            'brand.create',
            'brand.update',
            'brand.delete',
            'brand.change-status',

            'unit.list',
            'unit.view',
            'unit.create',
            'unit.update',
            'unit.delete',
            'unit.change-status',

            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'product.change-status',
            'product-image.list',
            'product-image.view',
            'product-image.create',
            'product-image.update',
            'product-image.delete',

            'barcode-template.list',
            'barcode-template.view',
            'barcode-template.create',
            'barcode-template.update',
            'barcode-template.delete',
            'barcode-print.view',
            'barcode-print.create',

            'customer.view',

            'sale-order.view',
            'sale-order.invoice',
            'sale-order.change-status',

            'cash-register.list',
            'cash-register.view',
            'cash-register.create',
            'cash-register.update',
            'cash-register.delete',

            'website-banner.list',
            'website-banner.view',
            'website-banner.create',
            'website-banner.update',
            'website-banner.delete',
            'website-banner.change-status',

            'setting.manage',

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
            'delivery-confirmation.list',
            'delivery-confirmation.view',
            'delivery-confirmation.resolve',
        ]);

        /**
         * Cashier
         */
        $cashier = Role::where('name', 'Cashier')->firstOrFail();

        $cashier->syncPermissions([
            'dashboard.view',
            'product.list',
            'product.view',
            'sale-order.list',
            'sale-order.view',
            'sale-order.create',
            'sale-order.update',
            'cash-register.view',
            'cash-register-session.view',
            'cash-register-session.create',
            'cash-register-session.update',
            'cash-register-transaction.list',
            'cash-register-transaction.view',
            'cash-register-transaction.create',
            'cash-hold.list',
            'cash-hold.view',
            'cash-hold.create',
            'cash-hold.update',
            'cash-hold.delete',
            'payment-mode.list',
            'payment-mode.view',
        ]);

        /**
         * Store Manager
         */
        $storeManager = Role::where('name', 'Store Manager')->firstOrFail();

        $storeManager->syncPermissions([
            'dashboard.view',
            'dashboard.statistics',

            'category.list',
            'category.view',
            'brand.list',
            'brand.view',
            'unit.list',
            'unit.view',

            'product.list',
            'product.view',
            'product.update',
            'product.change-status',

            'stock.list',
            'stock.view',
            'stock-movement.list',
            'stock-movement.view',
            'stock-adjustment.list',
            'stock-adjustment.view',
            'stock-adjustment.create',

            'customer.list',
            'customer.view',

            'sale-order.list',
            'sale-order.view',
            'sale-order.change-status',
            'sale-order.invoice',
            'sale-order.dispatch',
            'sale-order.complete',
            'sale-order.cancel',

            'delivery-boy.list',
            'delivery-boy.view',
            'delivery-assignment.list',
            'delivery-assignment.view',
            'delivery-assignment.create',
            'delivery-assignment.delete',
            'delivery-confirmation.list',
            'delivery-confirmation.view',
            'delivery-confirmation.resolve',
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
