<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles')
            ->latest();

        // Search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('username', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('mobile', 'ILIKE', "%{$search}%");
            });
        }

        // Status Filter
        if (isset($filters['status'])) {
            $query->where('is_active', (bool) $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function trash(array $filters = []): LengthAwarePaginator
    {
        $query = User::onlyTrashed()
            ->with('roles')
            ->latest('deleted_at');

        // Search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('username', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('mobile', 'ILIKE', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);

        return $user;
    }

    public function delete(int $id): bool
    {
        return (bool) User::findOrFail($id)->delete();
    }

    public function restore(int $id): bool
    {
        return (bool) User::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(int $id): bool
    {
        return (bool) User::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function changeStatus(int $id, bool $isActive): User
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => $isActive]);

        return $user;
    }

    public function findByEmail(
        string $email
    ): ?User {

        return User::query()

            ->where('email', $email)

            ->first();
    }

    public function findByGoogleId(
        string $googleId
    ): ?User {

        return User::query()

            ->where('google_id', $googleId)

            ->first();
    }
}
