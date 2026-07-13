<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Cash Register Session
            |--------------------------------------------------------------------------
            */

            $table->foreignId('cash_register_session_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Module Reference
            |--------------------------------------------------------------------------
            */

            $table->string('module');

            // Sale
            // Expense
            // Income
            // Refund
            // Opening
            // Closing

            $table->unsignedBigInteger('module_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payment Mode
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payment_mode_id')
                ->nullable()
                ->constrained('payment_modes')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            $table->enum('type', [

                'In',

                'Out',

            ]);

            $table->decimal('amount', 12, 2);

            $table->timestamp('transaction_at');

            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};
