<?php

declare(strict_types=1);

namespace App\Services\Coupon;

use App\Models\Coupon\Coupon;
use App\Services\Contracts\CouponServiceInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;

class CouponService implements CouponServiceInterface
{
    public function __construct(
        protected CouponRepositoryInterface $couponRepository,
    ) {}

    /**
     * Find coupon.
     */
    public function find(
        int $id
    ): Coupon {

        return $this->couponRepository->find($id);

    }

    /**
     * Find coupon by code.
     */
    public function findByCode(
        string $code
    ): ?Coupon {

        return $this->couponRepository->findByCode($code);

    }

    /**
     * Increase usage.
     */
    public function incrementUsage(
        int $id
    ): void {

        $this->couponRepository->incrementUsage($id);

    }

    /**
     * Decrease usage.
     */
    public function decrementUsage(
        int $id
    ): void {

        $this->couponRepository->decrementUsage($id);

    }
}
