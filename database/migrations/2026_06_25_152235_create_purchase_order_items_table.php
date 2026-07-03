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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();

            // Pricing fields
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->decimal('selling_price', 15, 2)->default(0.00);

            // Quantity fields
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->decimal('received_quantity', 15, 2)->default(0.00);

            // Tax fields
            $table->decimal('tax_percent', 8, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);

            // Discount fields
            $table->decimal('discount_percent', 8, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);

            // Total
            $table->decimal('line_total', 15, 2)->default(0.00);

            $table->timestamps();

            // Indexes
            $table->index(['purchase_order_id']);
            $table->index(['product_id']);
            $table->index(['purchase_price']);
            $table->index(['selling_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
