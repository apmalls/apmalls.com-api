<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table) {
            $table->boolean('show_manufacture_date')
                ->default(false)
                ->after('show_sku');
            $table->boolean('show_expiry_date')
                ->default(false)
                ->after('show_manufacture_date');
        });
    }

    public function down(): void
    {
        Schema::table('barcode_templates', function (Blueprint $table) {
            $table->dropColumn([
                'show_manufacture_date',
                'show_expiry_date',
            ]);
        });
    }
};
