<?php

namespace App\Repositories\Dashboard;


use App\Models\Category\Category;
use App\Models\Customer\Customer;
use App\Models\Delivery\DeliveryAssignment;
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

            return $this->deliveryBoyDashboard($user);

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

    private function deliveryBoyDashboard(User $user): array
    {
        $profile = $user->deliveryBoy;

        if (! $profile) {
            return [
                'role' => 'Delivery Boy',
                'profile_setup_required' => true,
                'cards' => [],
                'recent_assignments' => [],
            ];
        }

        $assignments = DeliveryAssignment::query()
            ->where('delivery_boy_id', $profile->id);
        $activeStatuses = [
            DeliveryAssignment::STATUS_ASSIGNED,
            DeliveryAssignment::STATUS_ACCEPTED,
            DeliveryAssignment::STATUS_PICKED,
            DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
        ];

        return [

            'role' => 'Delivery Boy',

            'profile_setup_required' => false,

            'delivery_profile' => [
                'id' => $profile->id,
                'employee_code' => $profile->employee_code,
                'phone' => $profile->phone,
                'vehicle_type' => $profile->vehicle_type,
                'vehicle_number' => $profile->vehicle_number,
                'is_available' => $profile->is_available,
                'is_active' => $profile->is_active,
            ],

            'cards' => [

                'assigned_orders' => (clone $assignments)
                    ->where('status', DeliveryAssignment::STATUS_ASSIGNED)->count(),

                'active_orders' => (clone $assignments)
                    ->whereIn('status', $activeStatuses)->count(),

                'out_for_delivery' => (clone $assignments)
                    ->where('status', DeliveryAssignment::STATUS_OUT_FOR_DELIVERY)->count(),

                'delivered_today' => (clone $assignments)
                    ->where('status', DeliveryAssignment::STATUS_DELIVERED)
                    ->whereDate('delivered_at', today())->count(),

                'delivered_orders' => (clone $assignments)
                    ->where('status', DeliveryAssignment::STATUS_DELIVERED)->count(),

            ],

            'recent_assignments' => (clone $assignments)
                ->with('saleOrder:id,sale_no,invoice_no,delivery_status,due_amount,shipping_address_id')
                ->whereIn('status', $activeStatuses)
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (DeliveryAssignment $assignment) => [
                    'id' => $assignment->id,
                    'status' => $assignment->status,
                    'assigned_at' => $assignment->assigned_at,
                    'order_no' => $assignment->saleOrder?->sale_no
                        ?? $assignment->saleOrder?->invoice_no,
                    'due_amount' => $assignment->saleOrder?->due_amount,
                ]),

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
