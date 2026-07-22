<?php

namespace App\Services\POS;

use App\Models\POS\CashRegisterSession;
use App\Models\POS\CashRegisterTransaction;
use App\Repositories\Contracts\CashRegisterSessionRepositoryInterface;
use App\Repositories\Contracts\CashRegisterTransactionRepositoryInterface;
use App\Services\Contracts\CashRegisterTransactionServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterTransactionService implements CashRegisterTransactionServiceInterface
{
    public function __construct(
        protected CashRegisterTransactionRepositoryInterface $transactionRepository,
        protected CashRegisterSessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function create(array $data): CashRegisterTransaction
    {
        return DB::transaction(function () use ($data) {

            $session = $this->sessionRepository->findOrFail(
                $data['cash_register_session_id']
            );

            if ($session->status !== CashRegisterSession::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'session' => 'Cash register session is closed.',
                ]);
            }

            $data['transaction_at'] ??= now();

            return $this->transactionRepository->create($data);
        });
    }

    public function cashIn(array $data): CashRegisterTransaction
    {
        $data['type'] = CashRegisterTransaction::TYPE_CASH_IN;

        return $this->create($data);
    }

    public function cashOut(array $data): CashRegisterTransaction
    {
        $data['type'] = CashRegisterTransaction::TYPE_CASH_OUT;

        return $this->create($data);
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->transactionRepository->delete($id);

        });
    }
}
