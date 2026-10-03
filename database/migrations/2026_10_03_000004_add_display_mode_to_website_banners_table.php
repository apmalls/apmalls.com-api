<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_banners', function (Blueprint $table) {
            $table->string('display_mode', 32)->default('image_with_text');
        });
    }

    public function down(): void
    {
        Schema::table('website_banners', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
