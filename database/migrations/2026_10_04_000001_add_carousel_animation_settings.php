<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->boolean('banner_autoplay_enabled')->default(false);
            $table->boolean('offer_autoplay_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['banner_autoplay_enabled', 'offer_autoplay_enabled']);
        });
    }
};
