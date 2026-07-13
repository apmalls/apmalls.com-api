<?php

declare(strict_types=1);

namespace App\Services\POS;

use App\Helpers\NumberHelper;
use App\Models\POS\CashRegister;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashRegisterService
{
    public function __construct(
        protected CashRegisterRepositoryInterface $cashRegisterRepository,
    ) {
    }

    /**
     * Register Listing
     */
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->cashRegisterRepository
            ->paginate($filters);

    }

    /**
     * Trash Listing
     */
    public function trash(
        array $filters = []
    ): LengthAwarePaginator {

        return $this->cashRegisterRepository
            ->trash($filters);

    }

    /**
     * Find Register
     */
    public function find(
        int $id
    ): CashRegister {

        return $this->cashRegisterRepository
            ->find($id);

    }

    /**
     * Open Cash Register
     */
    public function open(
        array $data
    ): CashRegister {

        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Validate Existing Open Register
            |--------------------------------------------------------------------------
            */

            $this->validateOpenRegister();

            /*
            |--------------------------------------------------------------------------
            | Create Register
            |--------------------------------------------------------------------------
            */

            return $this->cashRegisterRepository
                ->create([

                    'register_no' => $this->generateRegisterNumber(),

                    'name' => $data['name'],

                    'user_id' => auth()->id(),

                    'opening_balance' => $data['opening_balance'],

                    'opened_at' => now(),

                    'status' => 'Open',

                    'remarks' => $data['remarks'] ?? null,

                    'created_by' => auth()->id(),

                ]);

        });

    }

    /**
     * Close Cash Register
     */
    public function close(
        int $id,
        array $data
    ): CashRegister {

        return DB::transaction(function () use ($id, $data) {

            $register = $this->cashRegisterRepository
                ->find($id);

            if ($register->status === 'Closed') {

                throw new InvalidArgumentException(
                    'Cash register is already closed.'
                );

            }

            return $this->cashRegisterRepository
                ->update($register, [

                    'closing_balance' => $data['closing_balance'],

                    'closed_at' => now(),

                    'status' => 'Closed',

                    'remarks' => $data['remarks'] ?? null,

                    'updated_by' => auth()->id(),

                ]);

        });

    }

    /**
     * Delete Register
     */
    public function delete(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            $register = $this->cashRegisterRepository
                ->find($id);

            if ($register->status === 'Open') {

                throw new InvalidArgumentException(
                    'Open register cannot be deleted.'
                );

            }

            return $this->cashRegisterRepository
                ->delete($register);

        });

    }

    /**
     * Restore Register
     */
    public function restore(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            return $this->cashRegisterRepository
                ->restore($id);

        });

    }

    /**
     * Force Delete Register
     */
    public function forceDelete(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            return $this->cashRegisterRepository
                ->forceDelete($id);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | Private Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Validate Existing Open Register
     */
    private function validateOpenRegister(): void
    {
        $register = $this->cashRegisterRepository
            ->findOpenRegisterByUser(auth()->id());

        if ($register !== null) {

            throw new InvalidArgumentException(
                'You already have an open cash register.'
            );

        }
    }

    /**
     * Generate Register Number
     */
    private function generateRegisterNumber(): string
    {
        return NumberHelper::generate(

            CashRegister::class,

            'register_no',

            'CR'

        );
    }
}
