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
        Schema::create('sale_order_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->integer('quantity');

            $table->integer('returned_quantity')
                ->default(0);

            $table->decimal('purchase_price', 12, 2);

            $table->decimal('selling_price', 12, 2);

            $table->decimal('tax_percent', 5, 2)
                ->default(0);

            $table->decimal('tax_amount', 12, 2)
                ->default(0);

            $table->decimal('discount_percent', 5, 2)
                ->default(0);

            $table->decimal('discount_amount', 12, 2)
                ->default(0);

            $table->decimal('line_total', 12, 2);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_order_items');
    }
};
