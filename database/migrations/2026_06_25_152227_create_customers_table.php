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
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Future Customer Login
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Customer Details
            |--------------------------------------------------------------------------
            */

            $table->string('customer_code')->unique();

            $table->enum('customer_type', [
                'Retail',
                'Wholesale',
                'Walk-in'
            ])->default('Retail');

            $table->string('first_name');

            $table->string('last_name')->nullable();

            $table->string('mobile', 20)->unique();

            $table->string('alternate_mobile', 20)->nullable();

            $table->string('email')->nullable()->unique();

            /*
            |--------------------------------------------------------------------------
            | Business Details
            |--------------------------------------------------------------------------
            */

            $table->string('company_name')->nullable();

            $table->string('gst_number', 20)->nullable()->unique();

            /*
            |--------------------------------------------------------------------------
            | Personal Details
            |--------------------------------------------------------------------------
            */

            $table->date('date_of_birth')->nullable();

            $table->date('anniversary_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->decimal('opening_balance', 12, 2)
                ->default(0);

            $table->decimal('credit_limit', 12, 2)
                ->default(0);

            $table->integer('reward_points')
                ->default(0);

            $table->text('notes')->nullable();

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

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
        Schema::dropIfExists('customers');
    }
};
