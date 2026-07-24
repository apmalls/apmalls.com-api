<?php

namespace App\Http\Controllers\Api\V1\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {
    }

    /**
     * User Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'status' => $request->filled('status') ? $request->boolean('status') : null,
                'per_page' => $request->get('per_page', 10),
            ];

            $users = $this->userService->paginate($filters);

            return response()->json([
                'success' => true,
                'message' => 'Users fetched successfully.',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create User
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->beginTransaction();

        try {
            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
                'is_active' => $request->boolean('is_active'),
            ];

            // Upload Profile Photo
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $this->uploadFile(
                    $request->file('profile_photo'),
                    'users/profile'
                );
            }

            // Create User
            $user = $this->userService->create($data);

            // Assign Role
            if ($request->filled('role')) {
                $user->assignRole($request->role);
            }

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $user->load('roles')
            ], 201);

        } catch (ValidationException $e) {
            $this->rollback();
            $this->cleanupUploadedFile($data['profile_photo'] ?? null);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
            $this->cleanupUploadedFile($data['profile_photo'] ?? null);

            return $this->handleException($e);
        }
    }

    /**
     * Display User Details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = $this->userService->find($id);

            return response()->json([
                'success' => true,
                'message' => 'User fetched successfully.',
                'data' => $user->load('roles'),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update User
     */
    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $user = $this->userService->find($id);

            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'is_active' => $request->boolean('is_active'),
            ];

            // Upload Profile Photo
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $this->replaceFile(
                    $request->file('profile_photo'),
                    $user->profile_photo,
                    'users/profile'
                );
            }

            // Update Password
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // Update User
            $user = $this->userService->update($id, $data);

            // Update Role
            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => $user->load('roles')
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (ValidationException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Delete User (Soft Delete)
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $user = $this->userService->find($id);

            // Prevent deletion of Super Admin
            if ($user->hasRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super Admin cannot be deleted.'
                ], 403);
            }

            // Delete Profile Photo
            if (!empty($user->profile_photo)) {
                $this->cleanupUploadedFile($user->profile_photo);
            }

            // Soft Delete User
            $this->userService->delete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Change User Status
     */
    public function changeStatus(ChangeUserStatusRequest $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->changeStatus($id, $request->boolean('is_active'));

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully.',
                'data' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get Trashed Users
     */
    public function trash(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'per_page' => $request->get('per_page', 10),
            ];

            $users = $this->userService->trash($filters);

            return response()->json([
                'success' => true,
                'message' => 'Trashed users fetched successfully.',
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore Trashed User
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $this->userService->restore($id);
            $user = $this->userService->find($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully.',
                'data' => $user->load('roles')
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'User not found in trash.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

    /**
     * Force Delete User (Permanent)
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $user = $this->userService->find($id);

            // Prevent force deletion of Super Admin
            if ($user->hasRole('Super Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Super Admin cannot be permanently deleted.'
                ], 403);
            }

            // Delete Profile Photo
            if (!empty($user->profile_photo)) {
                $this->cleanupUploadedFile($user->profile_photo);
            }

            // Force Delete User
            $this->userService->forceDelete($id);

            $this->commit();

            return response()->json([
                'success' => true,
                'message' => 'User permanently deleted successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            $this->rollback();
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);

        } catch (\Exception $e) {
            $this->rollback();
            return $this->handleException($e);
        }
    }

}
