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

            $table->string('invoice_no')->nullable()->unique();
            $table->timestamp('invoice_date')
                ->nullable();

            $table->date('sale_date');

            $table->decimal('sub_total', 12, 2)->default(0);

            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->decimal('shipping_charge', 12, 2)->default(0);

            $table->decimal('other_charge', 12, 2)->default(0);

            $table->decimal('grand_total', 12, 2);

            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->decimal('due_amount', 12, 2)->default(0);

            $table->enum('status', [
                'Draft',
                'Confirmed',
                'Completed',
                'Cancelled'
            ])->default('Draft');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('billing_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();

            $table->foreignId('shipping_address_id')
                ->nullable()
                ->constrained('customer_addresses')
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
        Schema::dropIfExists('sale_orders');
    }
};
