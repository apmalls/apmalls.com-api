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
        Schema::create('payment_modes', function (Blueprint $table) {

            $table->id();

            $table->string('name', 100);

            $table->string('code', 50)->unique();

            $table->text('description')->nullable();

            $table->string('icon')->nullable();

            $table->boolean('is_online')->default(false);

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->softDeletes();

            $table->index('is_active');

            $table->index('sort_order');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_modes');
    }
};
