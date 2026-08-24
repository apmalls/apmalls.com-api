<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    /**
     * Display a listing of users.
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    /**
     * Get all users.
     */
    public function all(): Collection;

    /**
     * Find user by id.
     */
    public function find(int $id): User;

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
     * Change user status.
     */
    public function changeStatus(int $id, bool $isActive): User;

    /**
     * Get trashed users.
     */
    public function trash(array $filters = []): LengthAwarePaginator;

    /**
     * Restore a soft deleted user.
     */
    public function restore(int $id): bool;

    /**
     * Force delete a user.
     */
    public function forceDelete(int $id): bool;
}
