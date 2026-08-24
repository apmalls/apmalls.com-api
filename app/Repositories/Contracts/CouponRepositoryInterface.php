<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Coupon\Coupon;

interface CouponRepositoryInterface
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
     * Create coupon.
     */
    public function create(
        array $data
    ): Coupon;

    /**
     * Update coupon.
     */
    public function update(
        int $id,
        array $data
    ): Coupon;

    /**
     * Delete coupon.
     */
    public function delete(
        int $id
    ): bool;

    /**
     * Increase usage count.
     */
    public function incrementUsage(
        int $id
    ): void;

    /**
     * Decrease usage count.
     */
    public function decrementUsage(
        int $id
    ): void;
}
