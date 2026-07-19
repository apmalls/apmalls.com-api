<?php

namespace App\Repositories\Dashboard;


use App\Models\Category\Category;
use App\Models\Customer\Customer;
use App\Models\Product\Brand;
use App\Models\Product\Product;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getDashboard(User $user): array
    {
        if ($user->hasRole('Super Admin')) {

            return $this->superAdminDashboard();

        }

        if ($user->hasRole('Admin')) {

            return $this->adminDashboard();

        }

        if ($user->hasRole('Store Manager')) {

            return $this->storeManagerDashboard();

        }

        if ($user->hasRole('Delivery Boy')) {

            return $this->deliveryBoyDashboard();

        }

        return $this->customerDashboard($user);
    }

    private function superAdminDashboard(): array
    {
        return [

            'role' => 'Super Admin',

            'cards' => [

                'customers' => Customer::count(),

                'categories' => Category::count(),

                'brands' => Brand::count(),

                'products' => Product::count(),

                'orders' => SaleOrder::count(),

                'pending_orders' => SaleOrder::where('status', 'Pending')->count(),

                'completed_orders' => SaleOrder::where('status', 'Completed')->count(),

                'cancelled_orders' => SaleOrder::where('status', 'Cancelled')->count(),

                'revenue' => SaleOrder::sum('grand_total'),

            ],

            'recent_orders' => SaleOrder::latest()
                ->take(10)
                ->get(),

        ];
    }

    private function adminDashboard(): array
    {
        return $this->superAdminDashboard();
    }

    private function storeManagerDashboard(): array
    {
        return [

            'role' => 'Store Manager',

            'cards' => [

                'products' => Product::count(),

                'orders' => SaleOrder::count(),

                'pending_orders' => SaleOrder::where('status', 'Pending')->count(),

            ],

        ];
    }

    private function deliveryBoyDashboard(): array
    {
        return [

            'role' => 'Delivery Boy',

            'cards' => [

                'assigned_orders' => 0,

                'delivered_orders' => 0,

                'pending_delivery' => 0,

            ],

        ];
    }

    private function customerDashboard(User $user): array
    {
        $customer = $user->customer;

        return [

            'role' => 'Customer',

            'cards' => [

                'orders' => SaleOrder::where('customer_id', $customer->id)->count(),

                'pending_orders' => SaleOrder::where('customer_id', $customer->id)
                    ->where('status', 'Pending')
                    ->count(),

                'completed_orders' => SaleOrder::where('customer_id', $customer->id)
                    ->where('status', 'Completed')
                    ->count(),

            ],

        ];
    }
}
