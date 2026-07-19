<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment\PaymentMode;
use Illuminate\Database\Eloquent\Collection;

interface PaymentModeRepositoryInterface
{
    /**
     * Active Payment Modes
     */
    public function active(): Collection;

    /**
     * Find Payment Mode
     */
    public function find(
        int $id
    ): PaymentMode;

    public function findByCode(string $code): ?PaymentMode;
}
