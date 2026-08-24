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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            $table->string('payment_no', 50)->unique();

            $table->date('payment_date');

            /*
            |--------------------------------------------------------------------------
            | Polymorphic Relation
            |--------------------------------------------------------------------------
            */

            $table->morphs('paymentable');

            /*
            |--------------------------------------------------------------------------
            | Party
            |--------------------------------------------------------------------------
            */

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Payment Mode
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payment_mode_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 15, 2);

            $table->decimal('paid_amount', 15, 2)->default(0);

            $table->decimal('refunded_amount', 15, 2)->default(0);

            $table->decimal('charges', 15, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | References
            |--------------------------------------------------------------------------
            */

            $table->string('transaction_no', 150)->nullable();

            $table->string('reference_no', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Gateway
            |--------------------------------------------------------------------------
            */

            $table->string('gateway', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [

                'pending',

                'completed',

                'failed',

                'cancelled',

                'refunded',

            ])->default('completed');

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->softDeletes();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('payment_date');

            $table->index('customer_id');

            $table->index('supplier_id');

            $table->index('payment_mode_id');

            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
