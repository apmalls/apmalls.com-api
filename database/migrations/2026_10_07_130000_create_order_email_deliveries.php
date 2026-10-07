<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained('sale_orders')->cascadeOnDelete();
            $table->string('event', 32);
            $table->string('audience', 16);
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('recipient')->nullable();
            $table->text('snapshot');
            $table->string('status', 16)->index();
            $table->uuid('version');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('error_code', 48)->nullable();
            $table->timestamps();
            $table->unique(['sale_order_id', 'event', 'audience'], 'order_email_event_audience_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_email_deliveries');
    }
};
