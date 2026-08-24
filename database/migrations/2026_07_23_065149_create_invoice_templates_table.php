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
        Schema::create('invoice_templates', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->enum('type', [

                'a4',
                'thermal_58',
                'thermal_80'

            ]);

            $table->string('blade_file');

            $table->boolean('is_default')->default(false);

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
        Schema::dropIfExists('invoice_templates');
    }
};
