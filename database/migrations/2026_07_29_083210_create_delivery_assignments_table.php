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
        Schema::create('delivery_assignments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_order_id')
                ->constrained('sale_orders')
                ->cascadeOnDelete();

            $table->foreignId('delivery_boy_id')
                ->constrained('delivery_boys')
                ->cascadeOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status', [
                'assigned',
                'accepted',
                'rejected',
                'picked',
                'out_for_delivery',
                'delivered',
                'cancelled',
            ])->default('assigned');

            $table->text('remarks')->nullable();

            $table->timestamp('assigned_at')->nullable();

            $table->timestamp('accepted_at')->nullable();

            $table->timestamp('rejected_at')->nullable();

            $table->timestamp('picked_at')->nullable();

            $table->timestamp('out_for_delivery_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['sale_order_id', 'status']);

            $table->index(['delivery_boy_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_assignments');
    }
};
