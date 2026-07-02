<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    /**
     * User Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query()
                ->with('roles')
                ->latest();

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%")
                        ->orWhere('username', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('mobile', 'ILIKE', "%{$search}%");
                });
            }

            // Status Filter
            if ($request->filled('status')) {
                $query->where('is_active', $request->boolean('status'));
            }

            $users = $query->paginate(
                $request->get('per_page', 10)
            );

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
            $user = User::create($data);

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
            $user = User::with('roles')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'User fetched successfully.',
                'data' => $user,
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
            $user = User::findOrFail($id);

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
            $user->update($data);

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
            $user = User::findOrFail($id);

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
            $user->delete();

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
            $user = User::findOrFail($id);

            $user->update([
                'is_active' => $request->boolean('is_active')
            ]);

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
            $users = User::onlyTrashed()
                ->with('roles')
                ->latest()
                ->paginate($request->get('per_page', 10));

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
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

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
            $user = User::withTrashed()->findOrFail($id);

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
            $user->forceDelete();

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
