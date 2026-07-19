<?php

namespace App\Http\Controllers\Api\V1\Role;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Role\RoleService;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;

class RoleController extends Controller
{
    /**
     * Role Service Instance
     */
    protected RoleService $roleService;

    /**
     * Constructor
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;

        $this->middleware('permission:role.view')->only([
            'index',
            'show',
        ]);

        $this->middleware('permission:role.create')->only([
            'store',
        ]);

        $this->middleware('permission:role.update')->only([
            'update',
        ]);

        $this->middleware('permission:role.delete')->only([
            'destroy',
        ]);
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $roles = $this->roleService->index($request);

            return response()->json([
                'success' => true,
                'message' => 'Roles fetched successfully.',
                'data' => $roles,
            ]);

        } catch (Exception $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);

        }
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {

            $role = $this->roleService->store(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => $role,
            ], 201);

        } catch (Exception $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);

        }
    }

    /**
     * Display the specified role.
     */
    public function show(int $id): JsonResponse
    {
        try {

            $role = $this->roleService->show($id);

            return response()->json([
                'success' => true,
                'message' => 'Role details fetched successfully.',
                'data' => $role,
            ]);

        } catch (Exception $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);

        }
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {

            $role = $this->roleService->update(
                $id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role,
            ]);

        } catch (Exception $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);

        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id): JsonResponse
    {
        try {

            $this->roleService->destroy($id);

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);

        } catch (Exception $exception) {

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);

        }
    }
}
