<?php

declare(strict_types=1);

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
        Schema::create('delivery_boys', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('employee_code', 50)->unique();

            $table->string('phone', 20)->unique();

            $table->string('alternate_phone', 20)->nullable();

            $table->string('vehicle_type', 50)->nullable();

            $table->string('vehicle_number', 50)->nullable();

            $table->string('license_number', 100)->nullable();

            $table->string('aadhaar_no', 20)->nullable();

            $table->string('pan_no', 20)->nullable();

            $table->string('photo')->nullable();

            $table->text('address')->nullable();

            $table->decimal('current_latitude', 10, 7)->nullable();

            $table->decimal('current_longitude', 10, 7)->nullable();

            $table->boolean('is_available')->default(true);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_boys');
    }
};
