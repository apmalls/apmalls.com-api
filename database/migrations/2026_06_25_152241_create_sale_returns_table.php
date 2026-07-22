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
        Schema::create('sale_returns', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sale_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->string('return_no')
                ->unique();

            $table->date('return_date');

            $table->decimal('total_amount', 12, 2);

            $table->text('remarks')
                ->nullable();

            $table->enum('status', [
                'draft',
                'approved',
                'completed',
                'cancelled'
            ])->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('return_no');
            $table->index('return_date');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
