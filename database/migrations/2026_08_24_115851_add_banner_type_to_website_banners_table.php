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
        Schema::table('website_banners', function (Blueprint $table) {
            $table->enum('banner_type', ['slider', 'offer'])
                ->default('slider')
                ->after('type');

            $table->index(['status', 'banner_type', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_banners', function (Blueprint $table) {
            $table->dropIndex(['status', 'banner_type', 'sort_order']);
            $table->dropColumn('banner_type');
        });
    }
};
