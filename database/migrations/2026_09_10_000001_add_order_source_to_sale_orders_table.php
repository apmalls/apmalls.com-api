<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('order_source', 20)
                ->default('manual')
                ->index();
        });

        DB::table('sale_orders')
            ->where('sale_no', 'like', 'SAL-%')
            ->update(['order_source' => 'online']);

        DB::table('sale_orders')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('payments')
                    ->join('cash_register_transactions', function ($join) {
                        $join->on('cash_register_transactions.reference_id', '=', 'payments.id')
                            ->where('cash_register_transactions.reference_type', '=', 'App\\Models\\Payment\\Payment');
                    })
                    ->whereColumn('payments.paymentable_id', 'sale_orders.id')
                    ->where('payments.paymentable_type', 'App\\Models\\Sale\\SaleOrder');
            })
            ->update(['order_source' => 'pos']);
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropIndex(['order_source']);
            $table->dropColumn('order_source');
        });
    }
};
