<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Coupon\Coupon;

interface CouponServiceInterface
{
    /**
     * Find coupon.
     */
    public function find(
        int $id
    ): Coupon;

    /**
     * Find coupon by code.
     */
    public function findByCode(
        string $code
    ): ?Coupon;

    /**
     * Increase usage.
     */
    public function incrementUsage(
        int $id
    ): void;

    /**
     * Decrease usage.
     */
    public function decrementUsage(
        int $id
    ): void;
}
