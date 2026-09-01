<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->index();

            $table->foreignId('delivery_reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivery_reported_at')->nullable();
            $table->text('courier_remarks')->nullable();
            $table->boolean('cash_collected_reported')->default(false);
            $table->decimal('cash_amount_reported', 15, 2)->default(0);

            $table->foreignId('customer_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->decimal('customer_confirmed_amount', 15, 2)->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->string('confirmation_method', 24)->nullable();

            $table->string('otp_hash')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('otp_max_attempts')->default(5);

            $table->foreignId('disputed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disputed_at')->nullable();
            $table->text('dispute_reason')->nullable();

            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_remarks')->nullable();
            $table->timestamps();
        });

        DB::table('delivery_assignments')
            ->join('sale_orders', 'sale_orders.id', '=', 'delivery_assignments.sale_order_id')
            ->where('delivery_assignments.status', 'delivered')
            ->select([
                'delivery_assignments.id as assignment_id',
                'delivery_assignments.delivered_at',
                'sale_orders.customer_id',
            ])
            ->orderBy('delivery_assignments.id')
            ->chunk(200, function ($assignments): void {
                foreach ($assignments as $assignment) {
                    DB::table('delivery_confirmations')->insertOrIgnore([
                        'delivery_assignment_id' => $assignment->assignment_id,
                        'customer_id' => $assignment->customer_id,
                        'status' => 'legacy_completed',
                        'delivery_reported_at' => $assignment->delivered_at,
                        'cash_collected_reported' => false,
                        'cash_amount_reported' => 0,
                        'confirmation_method' => 'legacy',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_confirmations');
    }
};
