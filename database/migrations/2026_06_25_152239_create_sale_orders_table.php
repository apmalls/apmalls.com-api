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
        Schema::create('sale_orders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->string('sale_no')->unique();

            $table->string('invoice_no')
                ->nullable()
                ->unique();

            $table->date('sale_date');

            $table->date('invoice_date')
                ->nullable();

            $table->decimal('sub_total', 12, 2)->default(0);

            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->decimal('shipping_amount', 12, 2)->default(0);

            $table->decimal('other_amount', 12, 2)->default(0);

            $table->decimal('round_off', 12, 2)->default(0);

            $table->decimal('grand_total', 12, 2)->default(0);

            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->decimal('due_amount', 12, 2)->default(0);

            $table->decimal('refund_amount', 12, 2)->default(0);

            $table->enum('payment_status', [
                'pending',
                'partial',
                'completed',
                'refunded'
            ])->default('pending');

            $table->enum('status', [
                'draft',
                'confirmed',
                'completed',
                'cancelled'
            ])->default('draft');

            $table->text('remarks')->nullable();

            $table->foreignId('billing_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();

            $table->foreignId('shipping_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();

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

            $table->index('customer_id');
            $table->index('sale_no');
            $table->index('invoice_no');
            $table->index('sale_date');
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
        Schema::dropIfExists('sale_orders');
    }
};
