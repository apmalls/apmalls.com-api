<?php

declare(strict_types=1);

namespace App\Repositories\Coupon;

use App\Models\Coupon\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;

class CouponRepository implements CouponRepositoryInterface
{
    /**
     * Find coupon.
     */
    public function find(
        int $id
    ): Coupon {

        return Coupon::query()->findOrFail($id);

    }

    /**
     * Find coupon by code.
     */
    public function findByCode(
        string $code
    ): ?Coupon {

        return Coupon::query()

            ->where(
                'code',
                strtoupper($code)
            )

            ->where(
                'is_active',
                true
            )

            ->first();

    }

    /**
     * Create coupon.
     */
    public function create(
        array $data
    ): Coupon {

        return Coupon::query()->create($data);

    }

    /**
     * Update coupon.
     */
    public function update(
        int $id,
        array $data
    ): Coupon {

        $coupon = $this->find($id);

        $coupon->update($data);

        return $coupon->refresh();

    }

    /**
     * Delete coupon.
     */
    public function delete(
        int $id
    ): bool {

        return $this->find($id)->delete();

    }

    /**
     * Increase usage count.
     */
    public function incrementUsage(
        int $id
    ): void {

        Coupon::query()

            ->findOrFail($id)

            ->increment('used_count');

    }

    /**
     * Decrease usage count.
     */
    public function decrementUsage(
        int $id
    ): void {

        Coupon::query()

            ->findOrFail($id)

            ->decrement('used_count');

    }
}
