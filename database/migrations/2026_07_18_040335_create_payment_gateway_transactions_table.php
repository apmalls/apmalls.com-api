<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('payment_mode_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('gateway');                 // razorpay

            $table->string('gateway_order_id')->unique();

            $table->string('gateway_payment_id')->nullable();

            $table->string('gateway_signature')->nullable();

            $table->string('gateway_status')->default('created');
            // created, authorized, captured, failed, refunded

            $table->decimal('amount', 12, 2);

            $table->string('currency', 10)->default('INR');

            $table->json('request_payload')->nullable();

            $table->json('response_payload')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
