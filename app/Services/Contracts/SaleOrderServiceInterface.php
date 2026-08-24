<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

interface SaleOrderServiceInterface
{
    public function index(int $customerId): Collection;

    public function show(int $customerId, int $orderId);

    public function placeOrder(int $customerId, array $data);

    public function cancel(int $customerId, int $orderId);

    public function downloadInvoice(int $customerId, int $orderId): Response;
}
