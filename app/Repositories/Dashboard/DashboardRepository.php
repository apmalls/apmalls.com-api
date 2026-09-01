<?php

namespace App\Repositories\Dashboard;


use App\Models\Category\Category;
use App\Models\Customer\Customer;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Product\Brand;
use App\Models\Product\Product;
use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

                'pending_orders' => SaleOrder::where('status', SaleOrder::STATUS_DRAFT)->count(),

                'completed_orders' => SaleOrder::where('status', SaleOrder::STATUS_COMPLETED)->count(),

                'cancelled_orders' => SaleOrder::where('status', SaleOrder::STATUS_CANCELLED)->count(),

                'pending_delivery_confirmations' => DeliveryConfirmation::query()
                    ->where('status', DeliveryConfirmation::STATUS_AWAITING_CUSTOMER)
                    ->count(),

                'delivery_disputes' => DeliveryConfirmation::query()
                    ->where('status', DeliveryConfirmation::STATUS_DISPUTED)
                    ->count(),

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
        $today = Carbon::today(config('app.business_timezone'));
        $saleStatuses = [SaleOrder::STATUS_CONFIRMED, SaleOrder::STATUS_COMPLETED];
        $todaySales = SaleOrder::query()
            ->whereDate('sale_date', $today)
            ->whereIn('status', $saleStatuses);

        $todayRevenue = (float) (clone $todaySales)->sum('grand_total');
        $todayCost = (float) DB::table('sale_order_items')
            ->join('sale_orders', 'sale_orders.id', '=', 'sale_order_items.sale_order_id')
            ->whereNull('sale_orders.deleted_at')
            ->whereDate('sale_orders.sale_date', $today)
            ->whereIn('sale_orders.status', $saleStatuses)
            ->sum(DB::raw('sale_order_items.purchase_price * sale_order_items.quantity'));

        return [
            'role' => 'Store Manager',
            'cards' => [
                'products' => Product::count(),
                'low_stock' => Stock::query()
                    ->where('available_stock', '>', 0)
                    ->whereColumn('available_stock', '<=', 'minimum_stock')
                    ->count(),
                'out_of_stock' => Stock::where('available_stock', '<=', 0)->count(),
                'today_orders' => SaleOrder::whereDate('sale_date', $today)->count(),
                'pending_fulfillment' => SaleOrder::where('status', SaleOrder::STATUS_CONFIRMED)->count(),
                'unassigned_deliveries' => SaleOrder::query()
                    ->where('status', SaleOrder::STATUS_CONFIRMED)
                    ->whereDoesntHave('deliveryAssignment', fn ($query) => $query->where('status', '!=', 'cancelled'))
                    ->count(),
                'pending_delivery_confirmations' => DeliveryConfirmation::query()
                    ->where('status', DeliveryConfirmation::STATUS_AWAITING_CUSTOMER)
                    ->count(),
                'delivery_disputes' => DeliveryConfirmation::query()
                    ->where('status', DeliveryConfirmation::STATUS_DISPUTED)
                    ->count(),
                'today_revenue' => $todayRevenue,
                'cost_of_goods' => $todayCost,
                'gross_margin' => $todayRevenue - $todayCost,
            ],
            'recent_orders' => SaleOrder::query()
                ->with([
                    'customer.user',
                    'deliveryAssignment.deliveryBoy.user',
                    'deliveryAssignment.confirmation',
                ])
                ->latest('id')
                ->limit(8)
                ->get(),
            'low_stock_products' => Stock::query()
                ->with('product:id,name,sku,thumbnail,minimum_stock')
                ->whereColumn('available_stock', '<=', 'minimum_stock')
                ->orderBy('available_stock')
                ->limit(8)
                ->get(),
            'recent_stock_movements' => StockMovement::query()
                ->with(['product:id,name,sku', 'creator:id,first_name,last_name'])
                ->latest('id')
                ->limit(8)
                ->get(),
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
                    ->where('status', SaleOrder::STATUS_DRAFT)
                    ->count(),

                'completed_orders' => SaleOrder::where('customer_id', $customer->id)
                    ->where('status', SaleOrder::STATUS_COMPLETED)
                    ->count(),

            ],

        ];
    }
}
