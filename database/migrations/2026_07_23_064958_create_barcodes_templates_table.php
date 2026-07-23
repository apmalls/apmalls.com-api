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
        Schema::create('barcode_templates', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->enum('paper_size', [
                '40x30',
                '50x25',
                '60x40',
                '80x50',
                '100x50'
            ]);

            $table->unsignedInteger('width');

            $table->unsignedInteger('height');

            $table->unsignedTinyInteger('font_size')->default(10);

            $table->boolean('show_name')->default(true);

            $table->boolean('show_price')->default(true);

            $table->boolean('show_barcode')->default(true);

            $table->boolean('show_sku')->default(false);

            $table->boolean('show_qr')->default(false);

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
