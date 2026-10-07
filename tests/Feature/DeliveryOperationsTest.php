<?php

namespace Tests\Feature;

use App\Jobs\SendDeliveryOtp;
use App\Mail\DeliveryOtpMail;
use App\Services\Delivery\DeliveryAssignmentService;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\DatabaseQueue;
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
        Mail::fake();
        Queue::fake([SendDeliveryOtp::class]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::create(['name' => 'Delivery Boy', 'guard_name' => 'web']);
        foreach (['dashboard.view', 'delivery-assignment.list', 'delivery-assignment.view', 'delivery-assignment.update'] as $name) {
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

        $otp = $this->requestCode($assignment);
        $endpoint = "/api/v1/delivery/assignments/{$assignment->id}/confirm-otp";
        $this->postJson($endpoint, ['otp' => $otp, 'cash_collected' => true])->assertOk();
        $this->postJson($endpoint, ['otp' => $otp, 'cash_collected' => true])->assertUnprocessable();

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
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true, 'remarks' => 'Unable to obtain delivery code.'])->assertOk();

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
            ->assertForbidden();
    }

    public function test_customer_otp_confirms_delivery_and_cannot_be_reused(): void
    {
        [$driver, $profile] = $this->deliveryPerson('otp-driver');
        $assignment = $this->assignment($profile, DeliveryAssignment::STATUS_OUT_FOR_DELIVERY, 0);
        Sanctum::actingAs($driver);
        $otp = $this->requestCode($assignment);

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
        $this->requestCode($assignment);

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
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true, 'remarks' => 'Customer cannot access email.'])->assertOk();

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
            'email_verified_at' => now(),
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
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Customer');

        return $user;
    }

    private function requestCode(DeliveryAssignment $assignment): string
    {
        Sanctum::actingAs($assignment->deliveryBoy->user);
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertOk()
            ->assertJsonMissingPath('data.otp')->assertJsonMissingPath('data.delivery_confirmation.otp_hash');
        $job = Queue::pushed(SendDeliveryOtp::class)->last();
        $job->handle();
        $otp = '';
        Mail::assertSent(DeliveryOtpMail::class, function ($mail) use (&$otp, $assignment) {
            if ($mail->orderNumber !== $assignment->saleOrder->sale_no) return false;
            $otp = $mail->otp;
            return true;
        });
        return $otp;
    }

    public function test_dispatch_queues_code_but_acceptance_does_not(): void
    {
        [$driver, $profile] = $this->deliveryPerson('dispatch');
        $assignment = $this->assignment($profile);
        Sanctum::actingAs($driver);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/accept")->assertOk();
        Queue::assertNothingPushed();
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/pickup")->assertOk();
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/out-for-delivery")->assertOk()
            ->assertJsonPath('data.delivery_confirmation.status', 'pending_handover');
        Queue::assertPushed(SendDeliveryOtp::class, 1);
        $confirmation = $assignment->fresh()->confirmation;
        $this->assertSame(86400, (int) $confirmation->otp_issued_at->diffInSeconds($confirmation->otp_expires_at));
        Queue::pushed(SendDeliveryOtp::class)->last()->handle();
        Mail::assertSent(DeliveryOtpMail::class, fn ($mail) => $mail->hasTo($assignment->saleOrder->customer->user->email)
            && Hash::check($mail->otp, $confirmation->otp_hash));
        $this->assertSame('sent', $confirmation->fresh()->otp_email_status);
    }

    public function test_admin_dispatch_uses_identical_code_service(): void
    {
        [, $profile] = $this->deliveryPerson('admin-dispatch');
        $assignment = $this->assignment($profile, 'picked');
        app(DeliveryAssignmentService::class)->outForDelivery($assignment->id);
        Queue::assertPushed(SendDeliveryOtp::class, 1);
        $this->assertSame('pending_handover', $assignment->fresh()->confirmation->status);
    }

    public function test_resend_cooldown_limits_and_old_job_invalidation_are_shared(): void
    {
        [$driver, $profile] = $this->deliveryPerson('resends');
        $assignment = $this->assignment($profile, 'out_for_delivery');
        $this->requestCode($assignment);
        $firstJob = Queue::pushed(SendDeliveryOtp::class)->last();
        $customerRoute = "/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/otp";
        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->postJson($customerRoute)->assertStatus(429);
        $this->travel(60)->seconds();
        $this->postJson($customerRoute)->assertOk()->assertJsonMissingPath('data.otp');
        $firstJob->handle();
        Mail::assertSentCount(1);
        for ($i = 0; $i < 3; $i++) {
            $this->travel(60)->seconds();
            Sanctum::actingAs($driver);
            $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertOk();
        }
        $this->travel(60)->seconds();
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertStatus(429);
        $this->travel(1)->hours();
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertOk();
    }

    public function test_expired_code_and_missing_cash_do_not_complete_delivery(): void
    {
        [, $profile] = $this->deliveryPerson('expiry');
        $assignment = $this->assignment($profile, 'out_for_delivery', 200);
        $otp = $this->requestCode($assignment);
        $route = "/api/v1/delivery/assignments/{$assignment->id}/confirm-otp";
        $this->postJson($route, ['otp' => $otp])->assertUnprocessable()->assertJsonValidationErrors('cash_collected');
        $this->travel(24)->hours();
        $this->postJson($route, ['otp' => $otp, 'cash_collected' => true])->assertUnprocessable();
        $this->assertSame('confirmed', $assignment->saleOrder->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unverified_customer_and_cancelled_assignment_never_send_mail(): void
    {
        [, $profile] = $this->deliveryPerson('no-email');
        $assignment = $this->assignment($profile, 'picked');
        $assignment->saleOrder->customer->user->update(['email_verified_at' => null]);
        app(DeliveryAssignmentService::class)->outForDelivery($assignment->id);
        Queue::assertNothingPushed();
        $this->assertSame('failed', $assignment->fresh()->confirmation->otp_email_status);
        $this->assertNull($assignment->fresh()->confirmation->otp_hash);
        $assignment->saleOrder->customer->user->update(['email_verified_at' => now()]);
        $this->travel(60)->seconds();
        $this->requestCode($assignment);
        $job = Queue::pushed(SendDeliveryOtp::class)->last();
        app(DeliveryAssignmentService::class)->delete($assignment->id);
        $job->handle();
        $this->assertNull($assignment->fresh()->confirmation->otp_hash);
        Mail::assertSentCount(1);
    }

    public function test_direct_customer_confirmation_is_blocked_and_codes_are_order_bound(): void
    {
        [$driver, $profile] = $this->deliveryPerson('bound');
        $assignment = $this->assignment($profile, 'out_for_delivery');
        $otp = $this->requestCode($assignment);
        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/confirm", ['amount_paid' => 0])->assertForbidden();
        [, $otherProfile] = $this->deliveryPerson('bound-other');
        $other = $this->assignment($otherProfile, 'out_for_delivery');
        Sanctum::actingAs($driver);
        $this->postJson("/api/v1/delivery/assignments/{$other->id}/confirm-otp", ['otp' => $otp])->assertForbidden();
        $this->postJson("/api/v1/delivery/assignments/{$other->id}/resend-otp")->assertForbidden();
    }

    public function test_replacement_codes_email_changes_and_disputes_invalidate_previous_codes(): void
    {
        [$driver, $profile] = $this->deliveryPerson('invalidated');
        $assignment = $this->assignment($profile, 'out_for_delivery');
        $old = $this->requestCode($assignment);
        $this->travel(60)->seconds();
        $this->requestCode($assignment);
        $route = "/api/v1/delivery/assignments/{$assignment->id}/confirm-otp";
        $this->postJson($route, ['otp' => $old])->assertUnprocessable();
        $assignment->saleOrder->customer->user->update(['email' => 'changed-delivery@example.com']);
        $this->postJson($route, ['otp' => $old])->assertUnprocessable();
        Sanctum::actingAs($assignment->saleOrder->customer->user);
        $this->postJson("/api/v1/website/checkout/orders/{$assignment->saleOrder->sale_no}/delivery/dispute", ['reason' => 'Incorrect items in order.'])->assertOk();
        $this->assertNull($assignment->fresh()->confirmation->otp_hash);
        Sanctum::actingAs($driver);
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertUnprocessable();
    }

    public function test_queue_failure_is_visible_and_does_not_undo_dispatch_or_complete_order(): void
    {
        [, $profile] = $this->deliveryPerson('queue-fail');
        $assignment = $this->assignment($profile, 'picked');
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        app(DeliveryAssignmentService::class)->outForDelivery($assignment->id);
        $this->assertSame('out_for_delivery', $assignment->fresh()->status);
        $this->assertSame('failed', $assignment->fresh()->confirmation->otp_email_status);
        $this->assertSame('confirmed', $assignment->saleOrder->fresh()->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_mail_failure_is_sanitized_and_stale_failed_jobs_cannot_change_replacements(): void
    {
        [, $profile] = $this->deliveryPerson('mail-fail');
        $assignment = $this->assignment($profile, 'out_for_delivery');
        $this->requestCode($assignment);
        $this->travel(60)->seconds();
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertOk();
        $job = Queue::pushed(SendDeliveryOtp::class)->last();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Sensitive transport content'));
        try { $job->handle(); $this->fail('Expected sanitized mail failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('Delivery email could not be sent to the mail server.', $exception->getMessage()); }
        $job->failed(null);
        $this->assertSame('failed', $assignment->fresh()->confirmation->otp_email_status);
        $this->travel(60)->seconds();
        $this->postJson("/api/v1/delivery/assignments/{$assignment->id}/resend-otp")->assertOk();
        $job->failed(null);
        $this->assertSame('queued', $assignment->fresh()->confirmation->otp_email_status);
    }

    public function test_database_queue_encrypts_code_and_email_contains_safety_instructions(): void
    {
        [, $profile] = $this->deliveryPerson('encrypted');
        $assignment = $this->assignment($profile, 'out_for_delivery', 200);
        $otp = $this->requestCode($assignment);
        $queue = new DatabaseQueue(DB::connection(), 'jobs');
        $queue->setContainer(app());
        $id = $queue->push(Queue::pushed(SendDeliveryOtp::class)->last());
        $payload = DB::table('jobs')->where('id', $id)->value('payload');
        $this->assertStringNotContainsString($otp, $payload);
        $command = json_decode($payload, true)['data']['command'];
        $this->assertStringContainsString($otp, app('encrypter')->decrypt($command));
        $html = (new DeliveryOtpMail($assignment->saleOrder->sale_no, $otp, now()->addDay()->toIso8601String(), 200))->render();
        $this->assertStringContainsString('physically handing over', $html);
        $this->assertStringContainsString('INR 200.00', $html);
    }

    public function test_manager_completion_requires_permission_and_records_audit_without_duplicate_payment(): void
    {
        [$driver, $profile] = $this->deliveryPerson('manager-exception');
        $assignment = $this->assignment($profile, 'out_for_delivery', 200);
        $this->requestCode($assignment);
        PaymentMode::create(['name' => 'Cash', 'code' => 'CASH', 'is_online' => false, 'is_active' => true]);
        $this->patchJson("/api/v1/delivery/assignments/{$assignment->id}/delivered", ['cash_collected' => true, 'remarks' => 'Customer cannot access mailbox.'])->assertOk();
        $confirmation = $assignment->fresh()->confirmation;
        $route = "/api/v1/admin/delivery-confirmations/{$confirmation->id}/resolve";
        $this->patchJson($route, ['resolution' => 'confirm', 'remarks' => 'Receipt verified by phone.'])->assertForbidden();
        $driver->syncRoles(['Store Manager']);
        Sanctum::actingAs($driver);
        $this->patchJson($route, ['resolution' => 'confirm'])->assertUnprocessable();
        $this->patchJson($route, ['resolution' => 'confirm', 'remarks' => 'Receipt verified by manager.'])->assertOk();
        $this->patchJson($route, ['resolution' => 'confirm', 'remarks' => 'Receipt verified by manager.'])->assertUnprocessable();
        $this->assertSame('manager', $confirmation->fresh()->confirmation_method);
        $this->assertSame($driver->id, $confirmation->fresh()->resolved_by);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_rolled_back_dispatch_does_not_queue_email(): void
    {
        [, $profile] = $this->deliveryPerson('rollback-dispatch');
        $assignment = $this->assignment($profile, 'picked');
        DB::beginTransaction();
        app(DeliveryAssignmentService::class)->outForDelivery($assignment->id);
        DB::rollBack();
        Queue::assertNothingPushed();
        $this->assertSame('picked', $assignment->fresh()->status);
        $this->assertNull($assignment->fresh()->confirmation);
    }

    public function test_mail_metadata_migration_round_trip_preserves_historical_confirmation(): void
    {
        [, $profile] = $this->deliveryPerson('migration-history');
        $assignment = $this->assignment($profile, 'delivered');
        $confirmation = DeliveryConfirmation::create(['delivery_assignment_id' => $assignment->id,
            'customer_id' => $assignment->saleOrder->customer_id, 'status' => 'legacy_completed',
            'confirmation_method' => 'legacy', 'delivery_reported_at' => now()]);
        $migration = require database_path('migrations/2026_10_07_120000_add_delivery_otp_mail_metadata.php');
        $migration->down();
        $this->assertSame('legacy_completed', $confirmation->fresh()->status);
        $migration->up();
        $this->assertSame('legacy', $confirmation->fresh()->confirmation_method);
        $this->assertNull($confirmation->fresh()->otp_email_status);
        $this->assertSame(0, $confirmation->fresh()->otp_send_count);
    }
}
