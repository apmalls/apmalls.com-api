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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            /*
     |--------------------------------------------------------------------------
     | Relations
     |--------------------------------------------------------------------------
     */

            $table->foreignId('purchase_order_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            $table->decimal('quantity', 15, 2)->default(0);

            $table->decimal('received_quantity', 15, 2)->default(0);

            $table->decimal('free_quantity', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            $table->decimal('unit_cost', 15, 2)->default(0);

            $table->decimal('tax_percent', 8, 2)->default(0);

            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('discount_percent', 8, 2)->default(0);

            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('purchase_order_id');

            $table->index('product_id');

            $table->index('unit_id');

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
