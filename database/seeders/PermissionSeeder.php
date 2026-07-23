<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [

            'dashboard',

            'user',
            'role',
            'permission',

            'category',
            'brand',
            'unit',

            'product',
            'product-image',

            'stock-movement',
            'stock-adjustment',

            'customer',
            'customer-address',

            'supplier',
            'supplier-address',

            'purchase-order',
            'purchase-order-item',

            'purchase-return',
            'purchase-return-item',

            'sale-order',
            'sale-order-item',

            'sale-return',
            'sale-return-item',

            'payment-mode',
            'payment',

            'delivery',

            'company-setting',
            'invoice-setting',
            'tax-setting',
            'mail-setting',
            'general-setting',

            'coupon',

            'cart',
            'cart-item',

            'wishlist',

            'cash-register',
            'cash-register-session',
            'cash-register-transaction',
            'cash-hold',

            'payment-gateway-transaction',

            'report',

        ];

        $actions = [
            'list',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force-delete',
            'change-status'

        ];

        foreach ($modules as $module) {

            foreach ($actions as $action) {

                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);

            }

        }

        $extraPermissions = [

            // Dashboard
            'dashboard.statistics',

            // Orders
            'sale-order.approve',
            'sale-order.cancel',
            'sale-order.invoice',
            'sale-order.dispatch',
            'sale-order.complete',

            // Purchase
            'purchase-order.approve',

            // Inventory
            'stock-adjustment.approve',

            // Delivery
            'delivery.assign',
            'delivery.complete',

            // Payment
            'payment.refund',

            // Reports
            'report.export',

            // Settings
            'setting.manage',

        ];

        foreach ($extraPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

        }

    }
}
