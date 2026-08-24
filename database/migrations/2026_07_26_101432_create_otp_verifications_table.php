<?php

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
        Schema::create('otp_verifications', function (Blueprint $table) {

            $table->id();

            /**
             * OTP Purpose
             */
            $table->enum('type', [

                'login',

                'register',

                'forgot_password',

                'email_verification',

                'mobile_verification',

            ])->index();

            /**
             * Delivery Channel
             */
            $table->enum('channel', [

                'email',

                'mobile',

                'whatsapp',

            ])->index();

            /**
             * Email OR Mobile Number
             */
            $table->string('recipient', 255)->index();

            /**
             * OTP
             */
            $table->string('otp');

            /**
             * Expiry
             */
            $table->timestamp('expires_at');

            /**
             * Verification
             */
            $table->boolean('is_verified')
                ->default(false);

            $table->timestamp('verified_at')
                ->nullable();

            /**
             * Security
             */
            $table->unsignedTinyInteger('attempts')
                ->default(0);

            $table->unsignedTinyInteger('max_attempts')
                ->default(5);

            $table->ipAddress('ip_address')
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->timestamps();

            /**
             * Performance Index
             */
            $table->index([

                'recipient',

                'channel',

                'type',

            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
