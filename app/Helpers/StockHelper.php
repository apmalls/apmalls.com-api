<?php

namespace App\Helpers;

use App\Models\Inventory\Stock;
use App\Models\Inventory\StockMovement;
use Illuminate\Support\Facades\Auth;
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
        ?string $remarks = null
    ): Stock {

        $stock = Stock::firstOrCreate(
            ['product_id' => $productId],
            [
                'current_stock'   => 0,
                'reserved_stock'  => 0,
                'available_stock' => 0,
                'minimum_stock'   => 0,
                'maximum_stock'   => 0,
            ]
        );

        $before = $stock->current_stock;

        $after = $before + $quantity;

        $stock->update([
            'current_stock'   => $after,
            'available_stock' => $after - $stock->reserved_stock,
        ]);

        self::createMovement(
            productId: $productId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            movementType: 'IN',
            quantity: $quantity,
            stockBefore: $before,
            stockAfter: $after,
            remarks: $remarks
        );

        return $stock;
    }

    /**
     * Decrease Stock
     */
    public static function decrease(
        int $productId,
        int $quantity,
        string $referenceType,
        int $referenceId,
        ?string $remarks = null
    ): Stock {

        $stock = Stock::where('product_id', $productId)->firstOrFail();

        if ($stock->available_stock < $quantity) {
            throw new Exception('Insufficient stock.');
        }

        $before = $stock->current_stock;

        $after = $before - $quantity;

        $stock->update([
            'current_stock'   => $after,
            'available_stock' => $after - $stock->reserved_stock,
        ]);

        self::createMovement(
            productId: $productId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            movementType: 'OUT',
            quantity: $quantity,
            stockBefore: $before,
            stockAfter: $after,
            remarks: $remarks
        );

        return $stock;
    }

    /**
     * Manual Stock Adjustment
     */
    public static function adjust(
        int $productId,
        int $physicalStock,
        string $referenceType = 'Adjustment',
        int $referenceId = 0,
        ?string $remarks = null
    ): Stock {

        $stock = Stock::firstOrCreate(
            ['product_id' => $productId]
        );

        $before = $stock->current_stock;

        $difference = $physicalStock - $before;

        $stock->update([
            'current_stock'   => $physicalStock,
            'available_stock' => $physicalStock - $stock->reserved_stock,
        ]);

        self::createMovement(
            productId: $productId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            movementType: 'ADJUSTMENT',
            quantity: abs($difference),
            stockBefore: $before,
            stockAfter: $physicalStock,
            remarks: $remarks
        );

        return $stock;
    }

    /**
     * Current Stock
     */
    public static function currentStock(int $productId): int
    {
        return Stock::where('product_id', $productId)
            ->value('current_stock') ?? 0;
    }

    /**
     * Available Stock
     */
    public static function availableStock(int $productId): int
    {
        return Stock::where('product_id', $productId)
            ->value('available_stock') ?? 0;
    }

    /**
     * Create Stock Movement
     */
    private static function createMovement(
        int $productId,
        string $referenceType,
        int $referenceId,
        string $movementType,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        ?string $remarks = null
    ): void {

        StockMovement::create([

            'product_id'     => $productId,

            'reference_type' => $referenceType,

            'reference_id'   => $referenceId,

            'movement_type'  => $movementType,

            'quantity'       => $quantity,

            'stock_before'   => $stockBefore,

            'stock_after'    => $stockAfter,

            'remarks'        => $remarks,

            'created_by'     => Auth::id(),

        ]);
    }
}
