<?php

declare(strict_types=1);

use App\Models\Delivery\DeliveryAssignment;
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
        Schema::table('sale_orders', function (Blueprint $table) {

            $table->enum('delivery_status', [
                DeliveryAssignment::STATUS_ASSIGNED,
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_REJECTED,
                DeliveryAssignment::STATUS_PICKED,
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
                DeliveryAssignment::STATUS_DELIVERED,
                DeliveryAssignment::STATUS_CANCELLED,
            ])
            ->nullable()
            ->after('status');

            $table->timestamp('expected_delivery_at')
                ->nullable()
                ->after('delivery_status');

            $table->timestamp('delivered_at')
                ->nullable()
                ->after('expected_delivery_at');

            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {

            $table->dropIndex(['delivery_status']);

            $table->dropColumn([
                'delivery_status',
                'expected_delivery_at',
                'delivered_at',
            ]);
        });
    }
};
