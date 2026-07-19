<?php

namespace App\Repositories\Role;



use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $query = Role::query()
            ->withCount('permissions');

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
            ->findOrFail($id);
    }

    /**
     * Update the specified role.
     */
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $role = Role::findOrFail($id);

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
                throw new \Exception('Super Admin role cannot be deleted.');
            }

            $role->delete();

            return true;
        });
    }
}
