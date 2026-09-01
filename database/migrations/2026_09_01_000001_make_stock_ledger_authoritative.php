<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->integer('legacy_product_stock')->nullable()->after('maximum_stock');
            $table->boolean('reconciliation_required')->default(false)->after('legacy_product_stock');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_required');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('idempotency_key', 190)->nullable()->after('reference_id');
            $table->unique('idempotency_key', 'stock_movements_idempotency_unique');
            $table->index(['product_id', 'created_at']);
        });

        DB::table('products')
            ->select(['id', 'stock', 'minimum_stock'])
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    $stock = DB::table('stocks')->where('product_id', $product->id)->first();

                    if (! $stock) {
                        DB::table('stocks')->insert([
                            'product_id' => $product->id,
                            'current_stock' => $product->stock,
                            'reserved_stock' => 0,
                            'available_stock' => $product->stock,
                            'minimum_stock' => $product->minimum_stock,
                            'maximum_stock' => 0,
                            'legacy_product_stock' => $product->stock,
                            'reconciliation_required' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }

                    DB::table('stocks')->where('id', $stock->id)->update([
                        'legacy_product_stock' => $product->stock,
                        'reconciliation_required' => (int) $stock->current_stock !== (int) $product->stock,
                        'minimum_stock' => $product->minimum_stock,
                        'updated_at' => now(),
                    ]);

                    // Preserve the old product value on stocks, then make the
                    // compatibility column mirror the authoritative ledger.
                    DB::table('products')->where('id', $product->id)->update([
                        'stock' => $stock->current_stock,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropUnique('stock_movements_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['legacy_product_stock', 'reconciliation_required', 'reconciled_at']);
        });
    }
};
