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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            $table->string('payment_no')->unique();

            $table->date('payment_date');

            /*
            |--------------------------------------------------------------------------
            | Party
            |--------------------------------------------------------------------------
            */

            $table->enum('party_type', [

                'Supplier',

                'Customer',

            ]);

            $table->unsignedBigInteger('party_id');

            /*
            |--------------------------------------------------------------------------
            | Reference
            |--------------------------------------------------------------------------
            */

            $table->enum('reference_type', [

                'Purchase',

                'Sale',

                'Purchase Return',

                'Sale Return',

            ]);

            $table->unsignedBigInteger('reference_id');

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payment_mode_id')
                ->constrained('payment_modes')
                ->restrictOnDelete();

            $table->decimal('amount', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            $table->string('transaction_no')->nullable();

            $table->string('reference_no')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'Pending',

                'Completed',

                'Failed',

                'Cancelled',

            ])->default('Completed');

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

            $table->softDeletes();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
