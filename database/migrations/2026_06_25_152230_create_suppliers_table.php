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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | User (Future Supplier Login)
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Supplier Details
            |--------------------------------------------------------------------------
            */

            $table->string('supplier_code')->unique();

            $table->string('company_name');

            $table->string('contact_person');

            $table->string('mobile', 20)->unique();

            $table->string('alternate_mobile', 20)->nullable();

            $table->string('email')->nullable()->unique();

            /*
            |--------------------------------------------------------------------------
            | Tax Details
            |--------------------------------------------------------------------------
            */

            $table->string('gst_number', 20)->nullable()->unique();

            $table->string('pan_number', 20)->nullable()->unique();


            /*
            |--------------------------------------------------------------------------
            | Bank Details
            |--------------------------------------------------------------------------
            */

            $table->string('bank_name')->nullable();

            $table->string('account_holder_name')->nullable();

            $table->string('account_number')->nullable();

            $table->string('ifsc_code')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->decimal('opening_balance', 12, 2)
                ->default(0);

            $table->decimal('credit_limit', 12, 2)
                ->default(0);

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('suppliers');
    }
};
