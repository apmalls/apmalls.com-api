<?php

namespace Tests\Feature;

use App\Helpers\StockHelper;
use App\Jobs\SendDeliveryOtp;
use App\Jobs\SendOrderNotification;
use App\Mail\DeliveryOtpMail;
use App\Mail\OrderNotificationMail;
use App\Models\Category\Category;
use App\Models\Customer\Customer;
use App\Models\Delivery\DeliveryAssignment;
use App\Models\Delivery\DeliveryBoy;
use App\Models\Delivery\DeliveryConfirmation;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentMode;
use App\Models\POS\CashRegister;
use App\Models\Product\Product;
use App\Models\Product\Unit;
use App\Models\Sale\OrderEmailDelivery;
use App\Models\Sale\SaleOrder;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Delivery\DeliveryConfirmationService;
use App\Services\POS\POSService;
use App\Services\Sale\OrderNotificationService;
use App\Services\Sale\SaleService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.order_notification_email' => 'operations@example.com']);
        Queue::fake();
        Mail::fake();
    }

    public function test_verification_ignores_developer_config_cache_and_uses_only_in_memory_sqlite(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertNotSame(base_path('bootstrap/cache/config.php'), app()->getCachedConfigPath());
        $this->assertFalse(app()->configurationIsCached());
        $this->assertSame('array', config('mail.default'));
    }

    public function test_pos_email_renders_itemized_details_and_cancellation_restore_do_not_add_events(): void
    {
        [$service, $product, $cash] = $this->pos();
        $result = $service->checkout(['items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_mode_id' => $cash->id, 'paid_amount' => 80]);
        $this->job()->handle(app(OrderNotificationService::class));
        $mail = Mail::sent(OrderNotificationMail::class)->first();
        $html = $mail->render();
        $this->assertStringContainsString('Test product', $html);
        $this->assertStringContainsString('Unit price', $html);
        $this->assertStringContainsString('Discount:', $html);
        $this->assertStringContainsString('Tax:', $html);
        $this->assertStringContainsString('80.00', $html);
        $this->assertSame(['Cash'], $mail->details['payment_methods']);
        $this->assertSame('completed', $result['sale']->status);
        $sale = app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        app(SaleService::class)->changeStatus($sale->id, 'cancelled');
        app(SaleService::class)->delete($sale->id);
        app(SaleService::class)->restore($sale->id);
        $this->assertDatabaseCount('order_email_deliveries', 2);
        Queue::assertPushed(SendOrderNotification::class, 2);
    }

    public function test_manual_sales_notify_only_when_confirmed_and_keep_original_snapshot(): void
    {
        $sale = app(SaleService::class)->create($this->saleData());
        $this->assertDatabaseCount('order_email_deliveries', 0);
        app(SaleService::class)->update($sale->id, ['remarks' => 'Draft edit', 'items' => []]);
        app(SaleService::class)->changeStatus($sale->id, 'confirmed');
        Queue::assertPushed(SendOrderNotification::class, 1);
        $notification = OrderEmailDelivery::firstOrFail();
        $this->assertSame('Manual', $notification->snapshot['source']);
        $this->assertEquals(125.0, $notification->snapshot['total']);
        app(SaleService::class)->changeStatus($sale->id, 'confirmed');
        app(SaleService::class)->update($sale->id, ['grand_total' => 999, 'items' => []]);
        app(SaleService::class)->changeStatus($sale->id, 'completed');
        $this->assertDatabaseCount('order_email_deliveries', 1);
        $this->job()->handle(app(OrderNotificationService::class));
        $this->job()->handle(app(OrderNotificationService::class));
        Mail::assertSentCount(1);
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('operations@example.com')
            && ! $mail->cc && ! $mail->bcc && (float) $mail->details['total'] === 125.0);
        $this->assertSame('sent', $notification->fresh()->status);
    }

    public function test_initial_confirmed_or_completed_manual_sales_notify_but_pos_inner_create_does_not(): void
    {
        app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        app(SaleService::class)->create($this->saleData(['status' => 'completed']));
        app(SaleService::class)->create($this->saleData(['status' => 'completed', 'order_source' => 'pos']));
        $this->assertDatabaseCount('order_email_deliveries', 2);
        Queue::assertPushed(SendOrderNotification::class, 2);
    }

    public function test_website_cod_and_paid_confirmation_notify_once_not_draft_or_failed_payment(): void
    {
        $service = app(CheckoutService::class);
        $cod = SaleOrder::create($this->saleData(['order_source' => 'online']));
        $service->paymentFailed($cod->id);
        $this->assertDatabaseCount('order_email_deliveries', 0);
        $service->confirmCashOnDelivery($cod->id);
        $service->confirmCashOnDelivery($cod->id);
        $paid = SaleOrder::create($this->saleData(['order_source' => 'online', 'paid_amount' => 125, 'due_amount' => 0, 'payment_status' => 'completed']));
        $service->paymentSuccess($paid->id);
        $service->paymentSuccess($paid->id);
        Queue::assertPushed(SendOrderNotification::class, 2);
        $this->assertSame('Online', OrderEmailDelivery::where('sale_order_id', $cod->id)->first()->snapshot['source']);
        $this->assertEquals(125.0, OrderEmailDelivery::where('sale_order_id', $paid->id)->first()->snapshot['paid']);
    }

    public function test_nested_pos_checkout_records_final_payment_and_queues_only_after_commit(): void
    {
        [$service, $product, $cash] = $this->pos();
        DB::beginTransaction();
        $result = $service->checkout(['items' => [['product_id' => $product->id, 'quantity' => 2]], 'payment_mode_id' => $cash->id, 'paid_amount' => 80]);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('order_email_deliveries', 1);
        $snapshot = OrderEmailDelivery::first()->snapshot;
        $this->assertSame('POS', $snapshot['source']);
        $this->assertSame('Walk-in Customer', $snapshot['customer_name']);
        $this->assertSame(['Cash'], $snapshot['payment_methods']);
        $this->assertEquals(80.0, $snapshot['paid']);
        $this->assertSame('completed', $snapshot['payment_status']);
        $this->assertSame(2, $snapshot['items'][0]['quantity']);
        DB::commit();
        Queue::assertPushed(SendOrderNotification::class, 1);
        $this->assertSame('completed', $result['sale']->status);
    }

    public function test_rollback_and_invalid_pos_purchase_do_not_queue_or_leave_notification_records(): void
    {
        DB::beginTransaction();
        app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        DB::rollBack();
        $this->assertDatabaseCount('order_email_deliveries', 0);
        Queue::assertNothingPushed();
        [$service, $product, $cash] = $this->pos();
        try {
            $service->checkout(['items' => [['product_id' => $product->id, 'quantity' => 2]], 'payment_mode_id' => $cash->id, 'paid_amount' => 999]);
            $this->fail('Expected invalid payment');
        } catch (ValidationException) {}
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('order_email_deliveries', 0);
    }

    public function test_queue_dispatch_failure_preserves_sale_and_can_be_retried_by_command(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $sale = app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        $notification = OrderEmailDelivery::firstOrFail();
        $this->assertSame('confirmed', $sale->status);
        $this->assertSame('failed', $notification->status);
        $this->assertSame('queue_dispatch_failed', $notification->error_code);
        Bus::clearResolvedInstances();
        Bus::swap(app(\Illuminate\Bus\Dispatcher::class));
        $this->artisan('orders:retry-notifications', ['--id' => $notification->id])->assertSuccessful();
        Queue::assertPushed(SendOrderNotification::class, 1);
        $this->assertSame('queued', $notification->fresh()->status);
    }

    public function test_missing_inbox_is_blocked_and_recovery_never_changes_snapshot_or_retries_sent_records(): void
    {
        config(['mail.order_notification_email' => 'not-an-email']);
        $sale = app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        $notification = OrderEmailDelivery::firstOrFail();
        $this->assertSame('blocked', $notification->status);
        Queue::assertNothingPushed();
        $sale->update(['grand_total' => 999]);
        config(['mail.order_notification_email' => 'real-inbox@example.com']);
        $this->artisan('orders:retry-notifications')->assertSuccessful();
        $this->job()->handle(app(OrderNotificationService::class));
        $this->artisan('orders:retry-notifications', ['--include-queued' => true])->assertSuccessful();
        Queue::assertPushed(SendOrderNotification::class, 1);
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('real-inbox@example.com') && (float) $mail->details['total'] === 125.0);
        $this->artisan('orders:retry-notifications', ['--id' => 'invalid'])->assertFailed();
    }

    public function test_failed_transport_attempts_persist_and_stale_jobs_cannot_replace_sent_status(): void
    {
        app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        $job = $this->job();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Private message content'));
        try {
            $job->handle(app(OrderNotificationService::class));
            $this->fail('Expected sanitized failure');
        } catch (\RuntimeException $error) {
            $this->assertSame('Order notification could not be sent to the mail server.', $error->getMessage());
            $this->assertNull($error->getPrevious());
        }
        $notification = OrderEmailDelivery::first();
        $this->assertSame(1, $notification->attempts);
        $this->assertNotNull($notification->last_attempt_at);
        $job->failed(null);
        $this->assertSame('failed', $notification->fresh()->status);
        Mail::swap(new \Illuminate\Mail\MailManager(app()));
        Mail::fake();
        app(OrderNotificationService::class)->retry($notification->id);
        $job->handle(app(OrderNotificationService::class));
        Mail::assertNothingSent();
        $this->job()->handle(app(OrderNotificationService::class));
        $job->failed(null);
        $this->assertSame('sent', $notification->fresh()->status);
        $this->assertSame(2, $notification->fresh()->attempts);
    }

    public function test_duplicate_requests_and_recovery_invalidate_queued_jobs(): void
    {
        $sale = app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        app(OrderNotificationService::class)->orderPlaced($sale);
        Queue::assertPushed(SendOrderNotification::class, 1);
        $old = $this->job();
        $this->artisan('orders:retry-notifications', ['--include-queued' => true])->assertSuccessful();
        $old->handle(app(OrderNotificationService::class));
        Mail::assertNothingSent();
        $this->job()->handle(app(OrderNotificationService::class));
        $this->job()->handle(app(OrderNotificationService::class));
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('order_email_deliveries', 1);
    }

    public function test_otp_completion_notifies_admin_and_customer_without_otp_or_internal_remarks(): void
    {
        [$driver, $assignment, $confirmation] = $this->delivery();
        app(DeliveryConfirmationService::class)->confirmByOtp($driver, $assignment->id, '123456', true, 'Private courier note');
        $this->assertDatabaseCount('order_email_deliveries', 2);
        foreach (Queue::pushed(SendOrderNotification::class) as $job) {
            $job->handle(app(OrderNotificationService::class));
            $job->handle(app(OrderNotificationService::class));
        }
        Mail::assertSentCount(2);
        $customer = $assignment->saleOrder->customer->user;
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo($customer->email)
            && $mail->details['confirmation_method'] === 'Delivery OTP' && (float) $mail->details['cod_collected'] === 125.0);
        foreach (Mail::sent(OrderNotificationMail::class) as $mail) {
            $html = $mail->render();
            $this->assertStringNotContainsString('123456', $html);
            $this->assertStringNotContainsString('Private courier note', $html);
            $this->assertStringContainsString('Courier Tester', $html);
            $this->assertStringContainsString('9876543210', $html);
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('completed', $assignment->saleOrder->fresh()->status);
        try { app(DeliveryConfirmationService::class)->confirmByOtp($driver, $assignment->id, '123456', true); }
        catch (ValidationException) {}
        $this->assertDatabaseCount('order_email_deliveries', 2);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_assistance_and_reopen_do_not_notify_but_audited_manager_completion_does(): void
    {
        [$driver, $assignment] = $this->delivery();
        $service = app(DeliveryConfirmationService::class);
        $confirmation = $service->reportHandover($driver, $assignment->id, true, 'Customer email inaccessible');
        $this->assertDatabaseCount('order_email_deliveries', 0);
        $service->resolve($driver, $confirmation->id, 'confirm', 'Private manager audit note');
        $this->assertDatabaseCount('order_email_deliveries', 2);
        $this->assertSame('Audited manager confirmation', OrderEmailDelivery::first()->snapshot['confirmation_method']);
        [$otherDriver, $otherAssignment] = $this->delivery('9876543211');
        $other = $service->reportHandover($otherDriver, $otherAssignment->id, true, 'Delivery must be rescheduled');
        $service->resolve($otherDriver, $other->id, 'reopen', 'Reassign the delivery');
        $this->assertDatabaseCount('order_email_deliveries', 2);
    }

    public function test_customer_recipient_never_falls_back_to_contact_email_and_admin_is_independent(): void
    {
        [$driver, $assignment] = $this->delivery();
        $user = $assignment->saleOrder->customer->user;
        $user->update(['email_verified_at' => null]);
        $service = app(DeliveryConfirmationService::class);
        $confirmation = $service->reportHandover($driver, $assignment->id, true, 'Manager exception with no mailbox');
        $service->resolve($driver, $confirmation->id, 'confirm', 'Verified handover manually');
        $blocked = OrderEmailDelivery::where('audience', 'customer')->first();
        $this->assertSame('blocked', $blocked->status);
        $this->assertNull($blocked->recipient);
        Queue::assertPushed(SendOrderNotification::class, 1);
        $this->job()->handle(app(OrderNotificationService::class));
        Mail::assertSentCount(1);
        $user->update(['email_verified_at' => now()]);
        app(OrderNotificationService::class)->retry($blocked->id);
        $this->job()->handle(app(OrderNotificationService::class));
        Mail::assertSentCount(2);
        Mail::assertNotSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo($assignment->saleOrder->customer->email));
    }

    public function test_recipient_changes_block_old_customer_job_while_admin_still_sends(): void
    {
        [$driver, $assignment] = $this->delivery();
        app(DeliveryConfirmationService::class)->confirmByOtp($driver, $assignment->id, '123456', true);
        $assignment->saleOrder->customer->user->update(['email' => 'updated@example.com', 'email_verified_at' => null]);
        foreach (Queue::pushed(SendOrderNotification::class) as $job) {
            $job->handle(app(OrderNotificationService::class));
        }
        Mail::assertSentCount(1);
        $this->assertSame('blocked', OrderEmailDelivery::where('audience', 'customer')->first()->status);
    }

    public function test_delivery_code_emails_include_current_assigned_courier_and_omit_missing_contact(): void
    {
        [, $assignment] = $this->delivery();
        $assignment->confirmation->update(['otp_version' => 'version-one', 'otp_recipient_user_id' => $assignment->saleOrder->customer->user_id,
            'otp_recipient_email' => $assignment->saleOrder->customer->user->email, 'otp_email_status' => 'queued']);
        (new SendDeliveryOtp($assignment->id, 'version-one', '123456'))->handle();
        Mail::assertSent(DeliveryOtpMail::class, fn ($mail) => $mail->courierName === 'Courier Tester' && $mail->courierPhone === '9876543210');
        $assignment->deliveryBoy->update(['phone' => '']);
        $assignment->deliveryBoy->user->update(['first_name' => 'Updated Courier']);
        $assignment->confirmation->fresh()->update(['otp_version' => 'version-two', 'otp_email_status' => 'queued']);
        (new SendDeliveryOtp($assignment->id, 'version-two', '654321'))->handle();
        Mail::assertSent(DeliveryOtpMail::class, fn ($mail) => $mail->courierName === 'Updated Courier Tester' && $mail->courierPhone === '');
        $html = (new DeliveryOtpMail('SO-TEST', '123456', now()->addDay()->toIso8601String(), 0))->render();
        $this->assertStringNotContainsString('Delivery person:', $html);
        $this->assertStringNotContainsString('Mobile:', $html);
    }

    public function test_job_and_ledger_payloads_are_encrypted_and_migration_does_not_backfill_history(): void
    {
        $sale = SaleOrder::create($this->saleData(['status' => 'completed']));
        $this->assertDatabaseCount('order_email_deliveries', 0);
        app(SaleService::class)->create($this->saleData(['status' => 'confirmed']));
        $raw = DB::table('order_email_deliveries')->first();
        $this->assertStringNotContainsString('operations@example.com', $raw->recipient);
        $this->assertStringNotContainsString('customer_name', $raw->snapshot);
        $queue = new DatabaseQueue(DB::connection(), 'jobs');
        $queue->setContainer(app());
        $id = $queue->push($this->job());
        $command = json_decode(DB::table('jobs')->where('id', $id)->value('payload'), true)['data']['command'];
        $this->assertStringNotContainsString('SendOrderNotification', $command);
        $this->assertStringContainsString('SendOrderNotification', app('encrypter')->decrypt($command));
        $migration = require database_path('migrations/2026_10_07_130000_create_order_email_deliveries.php');
        $migration->down();
        $migration->up();
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertDatabaseCount('order_email_deliveries', 0);
    }

    private function job(): SendOrderNotification
    {
        return Queue::pushed(SendOrderNotification::class)->last();
    }

    private function saleData(array $overrides = []): array
    {
        $user = $this->user();
        $customer = Customer::create(['user_id' => $user->id, 'customer_code' => 'CUS-'.uniqid(), 'customer_type' => 'Retail',
            'first_name' => 'Buyer', 'email' => 'untrusted-contact-'.uniqid().'@example.com', 'is_active' => true]);
        return array_replace(['customer_id' => $customer->id, 'sale_no' => 'SO-'.uniqid(), 'sale_date' => today(),
            'grand_total' => 125, 'sub_total' => 125, 'due_amount' => 125, 'status' => 'draft', 'order_source' => 'manual'], $overrides);
    }

    private function delivery(string $phone = '9876543210'): array
    {
        $driver = $this->user(['first_name' => 'Courier', 'last_name' => 'Tester']);
        $profile = DeliveryBoy::create(['user_id' => $driver->id, 'employee_code' => 'EMP-'.uniqid(), 'phone' => $phone, 'is_active' => true]);
        $order = SaleOrder::create($this->saleData(['status' => 'confirmed', 'delivery_status' => 'out_for_delivery']));
        $assignment = DeliveryAssignment::create(['sale_order_id' => $order->id, 'delivery_boy_id' => $profile->id,
            'status' => 'out_for_delivery', 'assigned_at' => now()]);
        $confirmation = DeliveryConfirmation::create(['delivery_assignment_id' => $assignment->id, 'customer_id' => $order->customer_id,
            'status' => 'pending_handover', 'otp_hash' => Hash::make('123456'), 'otp_expires_at' => now()->addDay(),
            'otp_attempts' => 0, 'otp_max_attempts' => 5]);
        PaymentMode::firstOrCreate(['code' => 'CASH'], ['name' => 'Cash', 'is_active' => true, 'is_online' => false]);
        return [$driver, $assignment, $confirmation];
    }

    private function pos(): array
    {
        $cashier = $this->user();
        $this->actingAs($cashier);
        CashRegister::create(['register_no' => 'REG-'.uniqid(), 'name' => 'Till', 'user_id' => $cashier->id,
            'opening_balance' => 0, 'opened_at' => now(), 'status' => 'Open']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pc', 'is_active' => true]);
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $product = Product::create(['name' => 'Test product', 'slug' => 'test-product', 'sku' => 'TEST', 'category_id' => $category->id,
            'unit_id' => $unit->id, 'selling_price' => 40, 'purchase_price' => 20, 'is_active' => true]);
        StockHelper::increase($product->id, 20, Product::class, $product->id, 'Opening', 'notification-stock');
        $cash = PaymentMode::create(['name' => 'Cash', 'code' => 'CASH', 'is_active' => true, 'is_online' => false]);
        $service = app(POSService::class);
        $service->openSession([]);
        return [$service, $product, $cash];
    }

    private function user(array $overrides = []): User
    {
        return User::create(array_replace(['first_name' => 'Test', 'last_name' => 'Buyer',
            'email' => uniqid().'@example.com', 'password' => Hash::make('password'),
            'is_active' => true, 'email_verified_at' => now()], $overrides));
    }
}
