<?php

namespace Tests\Feature;

use App\Http\Resources\Sale\SaleResource;
use App\Models\Customer\Customer;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Services\Contracts\SaleServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_source_is_the_default_for_service_created_sales(): void
    {
        $sale = app(SaleServiceInterface::class)->create([
            'customer_id' => $this->customer()->id,
            'sale_date' => now()->toDateString(),
            'items' => [],
        ]);

        $this->assertSame(SaleOrder::SOURCE_MANUAL, $sale->order_source);
    }

    public function test_order_source_is_serialized_and_filterable(): void
    {
        $customer = $this->customer();
        $online = $this->sale($customer, 'SAL-ONLINE', SaleOrder::SOURCE_ONLINE);
        $this->sale($customer, 'SO-POS', SaleOrder::SOURCE_POS);

        $orders = app(SaleRepositoryInterface::class)->paginate(15, [
            'order_source' => SaleOrder::SOURCE_ONLINE,
        ]);

        $this->assertCount(1, $orders->items());
        $this->assertSame($online->id, $orders->items()[0]->id);
        $this->assertSame(
            SaleOrder::SOURCE_ONLINE,
            (new SaleResource($online))->resolve()['order_source']
        );
    }

    private function customer(): Customer
    {
        return Customer::create([
            'customer_code' => 'CUS-'.fake()->unique()->numerify('#####'),
            'customer_type' => 'Retail',
            'first_name' => 'Order Source Customer',
            'is_active' => true,
        ]);
    }

    private function sale(Customer $customer, string $saleNo, string $source): SaleOrder
    {
        return SaleOrder::create([
            'customer_id' => $customer->id,
            'sale_no' => $saleNo,
            'order_source' => $source,
            'sale_date' => now()->toDateString(),
        ]);
    }
}
