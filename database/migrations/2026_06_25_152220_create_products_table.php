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
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();

            $table->string('name');

            $table->string('slug')->unique();

            $table->string('sku')->unique();

            $table->string('barcode')->nullable()->unique();

            $table->string('hsn_code')->nullable();

            $table->string('thumbnail')->nullable();

            $table->text('short_description')->nullable();

            $table->longText('description')->nullable();

            $table->decimal('purchase_price', 12, 2)->default(0);

            $table->decimal('selling_price', 12, 2);

            $table->decimal('mrp', 12, 2)->default(0);

            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->decimal('discount_percent', 5, 2)->default(0);

            $table->integer('stock')->default(0);

            $table->integer('minimum_stock')->default(0);

            $table->boolean('featured')->default(false);

            $table->boolean('new_arrival')->default(false);

            $table->boolean('best_seller')->default(false);

            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('sale_count')->default(0);

            $table->decimal('rating', 3, 2)->default(0);

            $table->unsignedInteger('review_count')->default(0);

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
        Schema::dropIfExists('products');
    }
};
