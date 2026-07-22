<?php

namespace App\Services\Contracts;

use App\Models\POS\CashRegister;
use App\Models\POS\CashRegisterSession;
use App\Models\POS\PosHold;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface POSServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | Cash Register
    |--------------------------------------------------------------------------
    */

    public function registers(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function register(
        int $id
    ): CashRegister;

    public function createRegister(
        array $data
    ): CashRegister;

    public function updateRegister(
        int $id,
        array $data
    ): CashRegister;

    public function deleteRegister(
        int $id
    ): bool;

    /*
    |--------------------------------------------------------------------------
    | Register Session
    |--------------------------------------------------------------------------
    */

    public function sessions(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function session(
        int $id
    ): CashRegisterSession;

    public function openSession(
        array $data
    ): CashRegisterSession;

    public function closeSession(
        int $id,
        array $data
    ): CashRegisterSession;

    /*
    |--------------------------------------------------------------------------
    | Hold
    |--------------------------------------------------------------------------
    */

    public function holds(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function hold(
        int $id
    ): PosHold;

    public function createHold(
        array $data
    ): PosHold;

    public function updateHold(
        int $id,
        array $data
    ): PosHold;

    public function deleteHold(
        int $id
    ): bool;

    /*
    |--------------------------------------------------------------------------
    | POS
    |--------------------------------------------------------------------------
    */

    public function checkout(
        array $data
    );

    public function barcode(
        string $barcode
    );

    public function searchProducts(
        array $filters = []
    ): Collection;

    public function cashIn(
        array $data
    );

    public function cashOut(
        array $data
    );
}
