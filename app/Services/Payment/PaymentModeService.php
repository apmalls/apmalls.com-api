<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment\PaymentMode;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use App\Services\Contracts\PaymentModeServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentModeService implements PaymentModeServiceInterface
{
    public function __construct(
        protected PaymentModeRepositoryInterface $paymentModeRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->paymentModeRepository->paginate($filters);
    }

    public function trashedPaginate(array $filters = []): LengthAwarePaginator
    {
        return $this->paymentModeRepository->trashedPaginate($filters);
    }

    public function all(): Collection
    {
        return $this->paymentModeRepository->all();
    }

    public function find(int $id): PaymentMode
    {
        return $this->paymentModeRepository->findOrFail($id);
    }

    public function create(array $data): PaymentMode
    {
        return $this->paymentModeRepository->create($data);
    }

    public function update(int $id, array $data): PaymentMode
    {
        return $this->paymentModeRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->paymentModeRepository->delete($id);
    }

    public function restore(int $id): bool
    {
        return $this->paymentModeRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->paymentModeRepository->forceDelete($id);
    }

    public function active(): Collection
    {
        return $this->paymentModeRepository->active();
    }
}
