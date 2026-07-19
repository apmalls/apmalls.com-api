<?php

namespace App\Http\Controllers\Api\V1\Admin\Permission;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Permission\PermissionService;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $permissions = $this->permissionService->index($request);

            return response()->json([
                'success' => true,
                'message' => 'Permission list fetched successfully.',
                'data' => $permissions,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Display grouped permissions.
     */
    public function grouped(): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'message' => 'Grouped permissions fetched successfully.',
                'data' => $this->permissionService->grouped(),
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Display the specified permission.
     */
    public function show(int $id): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'message' => 'Permission fetched successfully.',
                'data' => $this->permissionService->show($id),
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        }
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        try {

            $permission = $this->permissionService->store($validated);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully.',
                'data' => $permission,
            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        try {

            $permission = $this->permissionService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully.',
                'data' => $permission,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(int $id): JsonResponse
    {
        try {

            $this->permissionService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully.',
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }
}
