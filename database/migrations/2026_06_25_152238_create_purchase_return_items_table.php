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
        Schema::create('purchase_return_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('purchase_return_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('purchase_order_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('unit_cost', 15, 2)->default(0);

            $table->decimal('quantity', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('purchase_return_id');

            $table->index('purchase_order_item_id');

            $table->index('product_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
