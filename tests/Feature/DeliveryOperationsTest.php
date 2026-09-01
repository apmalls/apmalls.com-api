<?php

namespace Tests\Feature;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryBoy;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMode;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DeliveryOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::create(['name' => 'Delivery Boy', 'guard_name' => 'web']);
        foreach (['delivery-assignment.list', 'delivery-assignment.view', 'delivery-assignment.update'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
        $role->syncPermissions(Permission::all());
        Role::create(['name' => 'Customer', 'guard_name' => 'web']);
        $manager = Role::create(['name' => 'Store Manager', 'guard_name' => 'web']);
        $resolve = Permission::create(['name' => 'delivery-confirmation.resolve', 'guard_name' => 'web']);
        $manager->givePermissionTo($resolve);
    }

    public function test_delivery_person_cannot_view_another_persons_assignment(): void
    {
        [$owner, $ownerProfile] = $this->deliveryPerson('owner');
        [$other] = $this->deliveryPerson('other');
        $assignment = $this->assignment($ownerProfile);
        Sanctum::actingAs($other);

        $this->getJson("/api/v1/delivery/assignments/{$assignment->id}")
            ->assertForbidden();
    }

    public function test_delivery_list_contains_only_the_logged_in_persons_assignments(): void
    {
        [$user, $profile] = $this->deliveryPerson('listed');
        [, $otherProfile] = $this->deliveryPerson('hidden');
        $ownAssignment = $this->assignment($profile);
        $this->assignment($otherProfile);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/delivery/assignments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownAssignment->id);
    }

    public function test_delivery_dashboard_reports_an_incomplete_profile(): void
    {
        $user = User::create([
            'first_name' => 'Unlinked',
            'last_name' => 'Driver',
            'email' => 'unlinked@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole('Delivery Boy');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile_setup_required', true)
            ->assertJsonCount(0, 'data.cards');
    }

    public function test_delivery_status_transitions_are_ordered(): void
    {
        [$user, $profile] = $this->deliveryPerson('driver');
        $assignment = $this->assignment($profile);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/pickup")
            ->assertUnprocessable();
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/accept")->assertOk();
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/pickup")->assertOk();
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/out-for-delivery")->assertOk();
    }

    public function test_rejection_requires_remarks_and_closes_the_assignment(): void
    {
        [$user, $profile] = $this->deliveryPerson('rejector');
        $assignment = $this->assignment($profile);
        Sanctum::actingAs($user);

        $endpoint = "/api/v1/delivery/assignments/{$assignment->id}/reject";
        $this->patchJson($endpoint)->assertUnprocessable();
        $this->patchJson($endpoint, ['remarks' => 'Customer requested another day'])
            ->assertOk()
            ->assertJsonPath('data.status', DeliveryAssignment::STATUS_REJECTED);

        $this->assertDatabaseHas('delivery_assignments', [
            'id' => $assignment->id,
            'status' => DeliveryAssignment::STATUS_REJECTED,
        ]);
        $this->assertDatabaseHas('sale_orders', [
            'id' => $assignment->sale_order_id,
            'delivery_status' => null,
        ]);
    }

    public function test_handover_report_does_not_complete_order_or_create_payment(): void
    {
        [$user, $profile] = $this->deliveryPerson('confirmer');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 450);
        PaymentMode::create(['name' => 'Cash', 'code' => 'CASH', 'is_online' => false, 'is_active' => true]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", [
            'cash_collected' => true,
            'remarks' => 'Handed to customer.',
        ])->assertOk()->assertJsonPath('data.delivery_confirmation.status', 'awaiting_customer');

        $this->assertDatabaseMissing('payments', [
            'reference_no' => "DELIVERY-{$assignment->id}",
        ]);
        $this->assertDatabaseHas('sale_orders', [
            'id' => $assignment->sale_order_id,
            'status' => SaleOrder::STATUS_CONFIRMED,
            'delivery_status' => DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
        ]);
        $this->assertNull($assignment->saleOrder->fresh()->delivered_at);

        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->getJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}")
            ->assertOk()
            ->assertJsonPath(
                'data.delivery_assignment.delivery_confirmation.status',
                DeliveryConfirmation::STATUS_AWAITING_CUSTOMER
            );
    }

    public function test_cod_completion_is_idempotent(): void
    {
        [$user, $profile] = $this->deliveryPerson('collector');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 450);
        PaymentMode::create(['name' => 'Cash', 'code' => 'CASH', 'is_online' => false, 'is_active' => true]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true])->assertOk();
        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $endpoint = "/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/confirm";
        $this->postJson($endpoint, ['amount_paid' => 450])->assertOk();
        $this->postJson($endpoint, ['amount_paid' => 450])->assertOk();

        $this->assertSame(1, Payment::where('reference_no', "DELIVERY-{$assignment->id}")->count());
        $this->assertDatabaseHas('sale_orders', [
            'id' => $assignment->sale_order_id,
            'status' => SaleOrder::STATUS_COMPLETED,
            'delivery_status' => DeliveryAssignment::STATUS_DELIVERED,
            'due_amount' => 0,
        ]);
    }

    public function test_customer_cannot_confirm_another_customers_delivery(): void
    {
        [$driver, $profile] = $this->deliveryPerson('owner-check');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 200);
        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true])->assertOk();

        $other = $this->customerUser('other-customer');
        Customer::create([
            'user_id' => $other->id,
            'customer_code' => 'CUS-' . uniqid(),
            'customer_type' => 'Retail',
            'first_name' => 'Other',
            'mobile' => $other->mobile,
            'is_active' => true,
        ]);
        Sanctum::actingAs($other);
        $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/confirm", ['amount_paid' => 200])
            ->assertNotFound();
    }

    public function test_customer_otp_confirms_delivery_and_cannot_be_reused(): void
    {
        [$driver, $profile] = $this->deliveryPerson('otp-driver');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 0);
        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered")->assertOk();

        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $otp = $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/otp")
            ->assertOk()
            ->json('data.otp');

        Sanctum::actingAs($driver);
        $endpoint = "/api/v1/delivery/assignments/{$assignment->id}/confirm-otp";
        $this->postJson($endpoint, ['otp' => $otp])->assertOk();
        $this->postJson($endpoint, ['otp' => $otp])->assertUnprocessable();
        $this->assertDatabaseHas('delivery_confirmations', [
            'delivery_assignment_id' => $assignment->id,
            'status' => DeliveryConfirmation::STATUS_CONFIRMED,
            'confirmation_method' => DeliveryConfirmation::METHOD_OTP,
        ]);
    }

    public function test_invalid_otp_attempts_are_persisted_and_limited(): void
    {
        [$driver, $profile] = $this->deliveryPerson('otp-limit');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 0);
        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered")->assertOk();

        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/otp")->assertOk();

        Sanctum::actingAs($driver);
        $endpoint = "/api/v1/delivery/assignments/{$assignment->id}/confirm-otp";
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson($endpoint, ['otp' => '000000'])->assertUnprocessable();
        }
        $this->assertDatabaseHas('delivery_confirmations', [
            'delivery_assignment_id' => $assignment->id,
            'otp_attempts' => 5,
        ]);
        $this->postJson($endpoint, ['otp' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('otp');
    }

    public function test_dispute_can_only_be_reopened_through_manager_resolution(): void
    {
        [$driver, $profile] = $this->deliveryPerson('dispute-driver');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 200);
        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true])->assertOk();

        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/dispute", [
            'reason' => 'The order was not handed to me.',
        ])->assertOk();

        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true])
            ->assertUnprocessable();

        $manager = User::create([
            'first_name' => 'Store', 'last_name' => 'Manager', 'email' => 'manager-delivery@example.com',
            'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $manager->assignRole('Store Manager');
        Sanctum::actingAs($manager);
        $confirmation = DeliveryConfirmation::where('delivery_assignment_id', $assignment->id)->firstOrFail();
        $this->patchJson("/api/v1/admin/delivery-confirmations/{$confirmation->id}/resolve", [
            'resolution' => 'reopen',
            'remarks' => 'Customer report accepted after review.',
        ])->assertOk();

        $this->assertDatabaseHas('delivery_assignments', ['id' => $assignment->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('sale_orders', ['id' => $assignment->sale_order_id, 'status' => 'confirmed', 'delivery_status' => null]);
        $this->assertDatabaseMissing('payments', ['reference_no' => "DELIVERY-{$assignment->id}"]);
    }

    private function deliveryPerson(string $key): array
    {
        $user = User::create([
            'first_name' => ucfirst($key),
            'last_name' => 'Driver',
            'email' => "{$key}@example.com",
            'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole('Delivery Boy');
        $profile = DeliveryBoy::create([
            'user_id' => $user->id,
            'employee_code' => strtoupper($key),
            'phone' => $user->mobile,
            'is_available' => true,
            'is_active' => true,
        ]);

        return [$user, $profile];
    }

    private function assignment(DeliveryBoy $profile, string $status = DeliveryAssignment::STATUS_ASSIGNED, float $due = 0): DeliveryAssignment
    {
        $customerUser = $this->customerUser('buyer-' . uniqid());
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUS-' . uniqid(),
            'customer_type' => 'Retail',
            'first_name' => 'Test',
            'mobile' => '8' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'is_active' => true,
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_type' => 'Shipping',
            'address_line_1' => 'Test Road',
            'city' => 'Purnea',
            'state' => 'Bihar',
            'country' => 'India',
            'postal_code' => '854301',
        ]);
        $order = SaleOrder::create([
            'customer_id' => $customer->id,
            'sale_no' => 'SO-' . uniqid(),
            'sale_date' => today(),
            'grand_total' => $due,
            'paid_amount' => 0,
            'due_amount' => $due,
            'payment_status' => $due > 0 ? SaleOrder::PAYMENT_PENDING : SaleOrder::PAYMENT_COMPLETED,
            'status' => SaleOrder::STATUS_CONFIRMED,
            'delivery_status' => $status,
            'shipping_address_id' => $address->id,
        ]);

        return DeliveryAssignment::create([
            'sale_order_id' => $order->id,
            'delivery_boy_id' => $profile->id,
            'status' => $status,
            'assigned_at' => now(),
        ]);
    }

    private function customerUser(string $key): User
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => "{$key}@example.com",
            'mobile' => '7' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->assignRole('Customer');

        return $user;
    }
}
