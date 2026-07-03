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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();

            // Document numbers
            $table->string('purchase_no', 50);
            $table->string('invoice_no', 50)->nullable();

            // Dates
            $table->date('purchase_date');
            $table->date('invoice_date')->nullable();

            // Financial fields
            $table->decimal('sub_total', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_charge', 15, 2)->default(0.00);
            $table->decimal('other_charge', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('due_amount', 15, 2)->default(0.00);

            // Status and remarks
            $table->enum('status', ['draft', 'ordered', 'received', 'partial', 'cancelled'])->default('draft');
            $table->text('remarks')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['supplier_id']);
            $table->index(['purchase_no']);
            $table->index(['invoice_no']);
            $table->index(['purchase_date']);
            $table->index(['invoice_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
