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
        Schema::create('supplier_addresses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Address Type
            |--------------------------------------------------------------------------
            */

            $table->enum('address_type', [

                'Office',

                'Billing',

                'Warehouse',

                'Other',

            ])->default('Office');

            /*
            |--------------------------------------------------------------------------
            | Contact Details
            |--------------------------------------------------------------------------
            */

            $table->string('contact_person')->nullable();

            $table->string('mobile', 20)->nullable();

            $table->string('alternate_mobile', 20)->nullable();

            $table->string('email')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            $table->string('address_line_1');

            $table->string('address_line_2')->nullable();

            $table->string('landmark')->nullable();

            $table->string('city');

            $table->string('state');

            $table->string('country')->default('India');

            $table->string('postal_code', 10);

            /*
            |--------------------------------------------------------------------------
            | Default Address
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_default')
                ->default(false);
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
        Schema::dropIfExists('supplier_addresses');
    }
};
