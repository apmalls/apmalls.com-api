<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters);
    }

    public function all(): Collection
    {
        return $this->userRepository->all();
    }

    public function find(int $id): \App\Models\User
    {
        return $this->userRepository->findOrFail($id);
    }

    public function create(array $data): \App\Models\User
    {
        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): \App\Models\User
    {
        return $this->userRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    public function changeStatus(int $id, bool $isActive): \App\Models\User
    {
        return $this->userRepository->changeStatus($id, $isActive);
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->trash($filters);
    }

    public function restore(int $id): bool
    {
        return $this->userRepository->restore($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->userRepository->forceDelete($id);
    }
}
