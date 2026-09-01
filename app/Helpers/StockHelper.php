<?php

namespace App\Helpers;

use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class StockHelper
{
    /**
     * Increase Stock
     */
    public static function increase(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Stock {
        return self::change($productId, $quantity, $referenceType, $referenceId, 'IN', $remarks, $idempotencyKey);
    }

    /**
     * Decrease Stock
     */
    public static function decrease(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Stock {
        return self::change($productId, $quantity, $referenceType, $referenceId, 'OUT', $remarks, $idempotencyKey);
    }

    /**
     * Manual Stock Adjustment
     */
    public static function adjust(
        int $productId,
        int $physicalStock,
        string $referenceType = 'Adjustment',
        int $referenceId = 0,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Stock {
        if ($physicalStock < 0) {
            throw new Exception('Physical stock cannot be negative.');
        }

        return DB::transaction(function () use ($productId, $physicalStock, $referenceType, $referenceId, $remarks, $idempotencyKey) {
            $stock = self::lockedStock($productId);
            if ($idempotencyKey && StockMovement::where('idempotency_key', $idempotencyKey)->exists()) {
                return $stock;
            }
            if ($physicalStock < $stock->reserved_stock) {
                throw new Exception('Physical stock cannot be lower than reserved stock.');
            }
            $before = $stock->current_stock;
            $difference = $physicalStock - $before;

            self::persistBalance($stock, $physicalStock);
            $stock->update([
                'reconciliation_required' => false,
                'reconciled_at' => now(),
            ]);
            self::createMovement($productId, $referenceType, $referenceId, 'ADJUSTMENT', abs($difference), $before, $physicalStock, $remarks, $idempotencyKey);

            return $stock->refresh();
        });
    }

    /**
     * Current Stock
     */
    public static function currentStock(int $productId): int
    {
        return self::stockRecord($productId)->current_stock;
    }

    /**
     * Available Stock
     */
    public static function availableStock(int $productId): int
    {
        return self::stockRecord($productId)->available_stock;
    }

    public static function lockForUpdate(int $productId): Stock
    {
        return self::lockedStock($productId);
    }

    /**
     * Create Stock Movement
     */
    private static function change(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        string $movementType,
        ?string $remarks,
        ?string $idempotencyKey
    ): Stock {
        if ($quantity <= 0) {
            throw new Exception('Stock quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($productId, $quantity, $referenceType, $referenceId, $movementType, $remarks, $idempotencyKey) {
            $stock = self::lockedStock($productId);
            if ($idempotencyKey && StockMovement::where('idempotency_key', $idempotencyKey)->exists()) {
                return $stock;
            }
            if ($movementType === 'OUT' && $stock->available_stock < $quantity) {
                throw new Exception('Insufficient stock.');
            }

            $before = $stock->current_stock;
            $after = $movementType === 'IN' ? $before + $quantity : $before - $quantity;
            self::persistBalance($stock, $after);
            self::createMovement($productId, $referenceType, $referenceId, $movementType, $quantity, $before, $after, $remarks, $idempotencyKey);

            return $stock->refresh();
        });
    }

    private static function lockedStock(int $productId): Stock
    {
        self::stockRecord($productId);

        return Stock::where('product_id', $productId)->lockForUpdate()->firstOrFail();
    }

    private static function stockRecord(int $productId): Stock
    {
        $product = Product::query()->findOrFail($productId);
        return Stock::firstOrCreate(
            ['product_id' => $productId],
            [
                'current_stock' => (int) $product->stock,
                'reserved_stock' => 0,
                'available_stock' => (int) $product->stock,
                'minimum_stock' => (int) $product->minimum_stock,
                'maximum_stock' => 0,
            ]
        );
    }

    private static function persistBalance(Stock $stock, int $currentStock): void
    {
        $stock->update([
            'current_stock' => $currentStock,
            'available_stock' => $currentStock - $stock->reserved_stock,
        ]);

        Product::whereKey($stock->product_id)->update(['stock' => $currentStock]);
    }

    private static function createMovement(
        int $productId,
        string $referenceType,
        int $referenceId,
        string $movementType,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): void {

        StockMovement::create([

            'product_id'     => $productId,

            'reference_type' => $referenceType,

            'reference_id'   => $referenceId,

            'idempotency_key' => $idempotencyKey,

            'movement_type'  => $movementType,

            'quantity'       => $quantity,

            'stock_before'   => $stockBefore,

            'stock_after'    => $stockAfter,

            'remarks'        => $remarks,

            'created_by'     => Auth::id(),

        ]);
    }
}
