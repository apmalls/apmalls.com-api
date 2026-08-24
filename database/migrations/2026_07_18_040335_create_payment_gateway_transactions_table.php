<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('payment_mode_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('gateway');

            $table->string('gateway_order_id')
                ->unique();

            $table->string('gateway_payment_id')
                ->nullable();

            $table->string('gateway_signature')
                ->nullable();

            $table->string('gateway_status')
                ->default('created');

            $table->decimal('amount',15,2);

            $table->string('currency',10)
                ->default('INR');

            $table->json('request_payload')
                ->nullable();

            $table->json('response_payload')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->softDeletes();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
