<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\ChangeUserStatusRequest;
use App\Traits\FileUploadTrait;

class UserController extends Controller
{
    use FileUploadTrait;
    /**
     * User Listing
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with('roles')
            ->latest();

        /**
         * Search
         */
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

        /**
         * Status Filter
         */
        if ($request->filled('status')) {

            $query->where('is_active', $request->status);

        }

        $users = $query->paginate(
            $request->get('per_page', 10)
        );

        return response()->json([

            'success' => true,

            'message' => 'Users fetched successfully.',

            'data' => $users

        ]);
    }

    /**
     * Create User
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        DB::beginTransaction();

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

            /**
             * Upload Profile Photo
             */
            if ($request->hasFile('profile_photo')) {

                $data['profile_photo'] = $this->uploadFile(
                    $request->file('profile_photo'),
                    'users/profile'
                );
            }

            /**
             * Create User
             */
            $user = User::create($data);

            /**
             * Assign Role
             */
            $user->assignRole($request->role);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'User created successfully.',

                'data' => $user->load('roles')

            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            /**
             * Delete Uploaded Image if Transaction Failed
             */
            if (
                isset($data['profile_photo']) &&
                !empty($data['profile_photo'])
            ) {
                $this->deleteFile($data['profile_photo']);
            }

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }


    /**
     * Display User Details
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User fetched successfully.',
            'data' => $user,
        ]);
    }


    /**
     * Update User
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();

        try {

            $data = [

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'username' => $request->username,

                'email' => $request->email,

                'mobile' => $request->mobile,

                'is_active' => $request->boolean('is_active'),

            ];

            /**
             * Upload Profile Photo
             */
            if ($request->hasFile('profile_photo')) {

                $data['profile_photo'] = $this->replaceFile(
                    $request->file('profile_photo'),
                    $user->profile_photo,
                    'users/profile'
                );
            }

            /**
             * Update Password
             */
            if ($request->filled('password')) {

                $data['password'] = Hash::make($request->password);

            }

            /**
             * Update User
             */
            $user->update($data);

            /**
             * Update Role
             */
            $user->syncRoles([$request->role]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'User updated successfully.',

                'data' => $user->load('roles')

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }

    /**
     * Delete User
     */
    public function destroy(User $user): JsonResponse
    {
        DB::beginTransaction();

        try {

            if ($user->hasRole('Super Admin')) {

                return response()->json([

                    'success' => false,

                    'message' => 'Super Admin cannot be deleted.'

                ], 403);
            }

            /**
             * Delete Profile Photo
             */
            if (!empty($user->profile_photo)) {

                $this->deleteFile($user->profile_photo);

            }

            /**
             * Delete User
             */
            $user->delete();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'User deleted successfully.'

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()

            ], 500);

        }
    }

    /**
     * Change User Status
     */
    public function changeStatus(
        ChangeUserStatusRequest $request,
        User $user
    ): JsonResponse {

        $user->update([

            'is_active' => $request->boolean('is_active')

        ]);

        return response()->json([

            'success' => true,

            'message' => 'User status updated successfully.',

            'data' => $user

        ]);
    }



}
