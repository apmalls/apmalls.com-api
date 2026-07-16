<?php

declare(strict_types=1);

namespace App\Repositories\Payment;

use App\Models\Payment\PaymentMode;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;

class PaymentModeRepository implements PaymentModeRepositoryInterface
{
    /**
     * Active Payment Modes
     */
    public function active(): Collection
    {
        return PaymentMode::query()

            ->where(
                'is_active',
                true
            )

            ->orderBy(
                'sort_order'
            )

            ->get();
    }

    /**
     * Find Payment Mode
     */
    public function find(
        int $id
    ): PaymentMode {

        return PaymentMode::query()

            ->findOrFail($id);
    }
}
