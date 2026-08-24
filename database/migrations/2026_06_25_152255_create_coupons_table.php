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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            /*
            |--------------------------------------------------------------------------
            | Coupon Information
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('code')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            $table->enum('discount_type', [

                'Fixed',

                'Percentage',

            ]);

            $table->decimal('discount_value', 12, 2);

            /*
            |--------------------------------------------------------------------------
            | Conditions
            |--------------------------------------------------------------------------
            */

            $table->decimal('minimum_order_amount', 12, 2)
                ->default(0);

            $table->decimal('maximum_discount_amount', 12, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Usage
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('usage_limit')
                ->nullable();

            $table->unsignedInteger('used_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Validity
            |--------------------------------------------------------------------------
            */

            $table->timestamp('start_at')
                ->nullable();

            $table->timestamp('end_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('code');

            $table->index('is_active');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
