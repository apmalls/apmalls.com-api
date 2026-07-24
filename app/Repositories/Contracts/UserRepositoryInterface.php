<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users.
     */
    public function all(): Collection;

    /**
     * Paginate users with filters.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get trashed users with filters.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Find user by id.
     */
    public function find(int $id): ?User;

    /**
     * Find user by id or fail.
     */
    public function findOrFail(int $id): User;

    /**
     * Create a user.
     */
    public function create(array $data): User;

    /**
     * Update a user.
     */
    public function update(int $id, array $data): User;

    /**
     * Delete a user.
     */
    public function delete(int $id): bool;

    /**
     * Restore a soft deleted user.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a user.
     */
    public function forceDelete(int $id): bool;

    /**
     * Change user status.
     */
    public function changeStatus(int $id, bool $isActive): User;
}
