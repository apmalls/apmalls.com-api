<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->timestamp('otp_issued_at')->nullable();
            $table->timestamp('otp_send_window_at')->nullable();
            $table->unsignedTinyInteger('otp_send_count')->default(0);
            $table->uuid('otp_version')->nullable();
            $table->string('otp_email_status', 16)->nullable();
            $table->timestamp('otp_email_sent_at')->nullable();
            $table->foreignId('otp_recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('otp_recipient_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_confirmations', function (Blueprint $table) {
            $table->dropForeign(['otp_recipient_user_id']);
            $table->dropColumn(['otp_issued_at', 'otp_send_window_at', 'otp_send_count', 'otp_version',
                'otp_email_status', 'otp_email_sent_at', 'otp_recipient_user_id', 'otp_recipient_email']);
        });
    }
};
