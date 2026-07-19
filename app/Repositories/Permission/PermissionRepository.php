<?php

namespace App\Repositories\Permission;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $query = Permission::query();

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate(
            $request->get('per_page', 15)
        );
    }

    /**
     * Group permissions by module.
     */
    public function grouped()
    {
        $permissions = Permission::orderBy('name')->get();

        return $permissions
            ->groupBy(function ($permission) {

                return ucfirst(
                    explode('.', $permission->name)[0]
                );
            })
            ->map(function ($items, $module) {

                return [
                    'module' => $module,

                    'permissions' => $items->map(function ($permission) {

                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ];

                    })->values(),
                ];

            })
            ->values();
    }

    /**
     * Display the specified permission.
     */
    public function show(int $id)
    {
        return Permission::findOrFail($id);
    }

    /**
     * Store permission.
     */
    public function store(array $data)
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
    }

    /**
     * Update permission.
     */
    public function update(int $id, array $data)
    {
        $permission = Permission::findOrFail($id);

        $permission->update([
            'name' => $data['name'],
        ]);

        return $permission->fresh();
    }

    /**
     * Delete permission.
     */
    public function destroy(int $id)
    {
        $permission = Permission::findOrFail($id);

        $permission->delete();

        return true;
    }
}
