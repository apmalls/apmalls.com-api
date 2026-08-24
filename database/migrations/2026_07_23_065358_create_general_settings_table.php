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
        Schema::create('general_settings', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company Information
            |--------------------------------------------------------------------------
            */

            $table->string('company_name');
            $table->string('company_email')->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_logo')->nullable();

            $table->text('company_address')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Currency
            |--------------------------------------------------------------------------
            */

            $table->string('currency_name')->default('Indian Rupee');
            $table->string('currency_code', 10)->default('INR');
            $table->string('currency_symbol', 10)->default('₹');

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            $table->decimal('default_tax', 8, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Barcode
            |--------------------------------------------------------------------------
            */

            $table->string('barcode_type')->default('CODE128');
            $table->string('barcode_prefix')->default('PRD');
            $table->unsignedBigInteger('barcode_start_number')->default(100000);

            /*
            |--------------------------------------------------------------------------
            | Printing
            |--------------------------------------------------------------------------
            */

            $table->foreignId('default_printer_id')
                ->nullable()
                ->constrained('printers')
                ->nullOnDelete();

            $table->foreignId('default_barcode_template_id')
                ->nullable()
                ->constrained('barcode_templates')
                ->nullOnDelete();

            $table->foreignId('default_invoice_template_id')
                ->nullable()
                ->constrained('invoice_templates')
                ->nullOnDelete();

            $table->enum('thermal_paper_size', [
                '58mm',
                '80mm'
            ])->default('80mm');

            $table->boolean('auto_print_invoice')->default(false);

            /*
            |--------------------------------------------------------------------------
            | System
            |--------------------------------------------------------------------------
            */

            $table->string('timezone')->default('Asia/Kolkata');
            $table->string('date_format')->default('d-m-Y');
            $table->string('time_format')->default('H:i');

            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
