<?php

namespace App\Repositories\Role;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $query = Role::query()
            ->withCount('permissions')
            ->selectSub($this->assignedUserCountQuery(), 'users_count');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate(
            $request->get('per_page', 15)
        );
    }

    /**
     * Store a newly created role.
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Display the specified role.
     */
    public function show(int $id)
    {
        return Role::with('permissions')
            ->selectSub($this->assignedUserCountQuery(), 'users_count')
            ->findOrFail($id);
    }

    /**
     * Update the specified role.
     */
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $role = Role::findOrFail($id);

            if ($role->name === 'Super Admin') {
                throw new \Exception('Super Admin role cannot be modified.', 403);
            }

            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions(
                $data['permissions'] ?? []
            );

            return $role->load('permissions');
        });
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id)
    {
        return DB::transaction(function () use ($id) {

            $role = Role::findOrFail($id);

            // Prevent deleting Super Admin
            if ($role->name === 'Super Admin') {
                throw new \Exception('Super Admin role cannot be deleted.', 403);
            }

            if ($this->assignedUserCount($role->id) > 0) {
                throw new \Exception('Roles assigned to users cannot be deleted.', 409);
            }

            $role->delete();

            return true;
        });
    }

    private function assignedUserCountQuery()
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelType = (new User())->getMorphClass();

        return DB::table($table)
            ->selectRaw('COUNT(*)')
            ->whereColumn("{$table}.{$roleKey}", 'roles.id')
            ->where("{$table}.model_type", $modelType);
    }

    private function assignedUserCount(int $roleId): int
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';

        return DB::table($table)
            ->where($roleKey, $roleId)
            ->where('model_type', (new User())->getMorphClass())
            ->count();
    }
}
