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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            /*
     |--------------------------------------------------------------------------
     | Relations
     |--------------------------------------------------------------------------
     */

            $table->foreignId('supplier_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // $table->foreignId('warehouse_id')
            //     ->nullable()
            //     ->constrained()
            //     ->nullOnDelete()
            //     ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Document Details
            |--------------------------------------------------------------------------
            */

            $table->string('purchase_no', 50)->unique();

            $table->string('invoice_no', 50)->nullable();

            $table->date('purchase_date');

            $table->date('invoice_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            $table->decimal('sub_total', 15, 2)->default(0);

            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('shipping_amount', 15, 2)->default(0);

            $table->decimal('other_amount', 15, 2)->default(0);

            $table->decimal('round_off', 15, 2)->default(0);

            $table->decimal('grand_total', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Payment Summary
            |--------------------------------------------------------------------------
            */

            $table->decimal('paid_amount', 15, 2)->default(0);

            $table->decimal('due_amount', 15, 2)->default(0);

            $table->decimal('refund_amount', 15, 2)->default(0);

            $table->string('payment_status', 30)
                ->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->string('status', 30)
                ->default('draft');

            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->softDeletes();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('supplier_id');

            // $table->index('warehouse_id');

            $table->index('purchase_no');

            $table->index('invoice_no');

            $table->index('purchase_date');

            $table->index('invoice_date');

            $table->index('status');

            $table->index('payment_status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
