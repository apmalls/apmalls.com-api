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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Reference
            |--------------------------------------------------------------------------
            */

            $table->string('reference_type', 50);
            // Purchase, Sale, Purchase Return, Sale Return, Adjustment

            $table->unsignedBigInteger('reference_id');

            /*
            |--------------------------------------------------------------------------
            | Movement
            |--------------------------------------------------------------------------
            */

            $table->enum('movement_type', [
                'IN',
                'OUT',
                'ADJUSTMENT',
            ]);

            $table->integer('quantity');

            /*
            |--------------------------------------------------------------------------
            | Stock History
            |--------------------------------------------------------------------------
            */

            $table->integer('stock_before');

            $table->integer('stock_after');

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
