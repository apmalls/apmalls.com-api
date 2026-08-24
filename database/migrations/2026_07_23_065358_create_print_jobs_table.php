<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('printer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->morphs('reference');

            $table->enum('job_type', [
                'barcode',
                'invoice',
                'receipt'
            ]);

            $table->enum('status', [
                'pending',
                'printing',
                'completed',
                'failed'
            ])->default('pending');

            $table->text('error_message')
                ->nullable();

            $table->timestamp('printed_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
