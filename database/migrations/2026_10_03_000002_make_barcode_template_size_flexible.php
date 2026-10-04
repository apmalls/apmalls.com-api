<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ORIGINAL_SIZES = [
        '40x30',
        '50x25',
        '60x40',
        '80x50',
        '100x50',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE barcode_templates '
                .'DROP CONSTRAINT IF EXISTS barcode_templates_paper_size_check'
            );

            return;
        }

        Schema::table('barcode_templates', function (Blueprint $table) {
            $table->string('paper_size', 30)->change();
        });
    }

    public function down(): void
    {
        $hasCustomSizes = DB::table('barcode_templates')
            ->whereNotIn('paper_size', self::ORIGINAL_SIZES)
            ->exists();

        if ($hasCustomSizes) {
            throw new \RuntimeException(
                'Cannot restore the original barcode size constraint while custom templates exist.'
            );
        }

        if (DB::getDriverName() === 'pgsql') {
            $allowed = collect(self::ORIGINAL_SIZES)
                ->map(fn (string $size) => DB::getPdo()->quote($size))
                ->implode(', ');

            DB::statement(
                'ALTER TABLE barcode_templates '
                .'ADD CONSTRAINT barcode_templates_paper_size_check '
                .'CHECK (paper_size IN ('.$allowed.'))'
            );

            return;
        }

        Schema::table('barcode_templates', function (Blueprint $table) {
            $table->enum('paper_size', self::ORIGINAL_SIZES)->change();
        });
    }
};
