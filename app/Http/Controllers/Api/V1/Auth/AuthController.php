<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{


    /**
     * Register New User
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            $user = User::create([

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'username' => $request->username,

                'email' => $request->email,

                'mobile' => $request->mobile,

                'password' => Hash::make($request->password),

            ]);

            /**
             * Default Role
             */
            $user->assignRole(config('roles.default'));

            /**
             * Sanctum Token
             */
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Registration successful.',

                'data' => [

                    'user' => $user,

                    'token' => $token,

                ]

            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);

        }
    }


    /**
     * User Login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {

            return response()->json([

                'success' => false,

                'message' => 'Invalid email or password.'

            ], 401);

        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->is_active) {

            return response()->json([

                'success' => false,

                'message' => 'Your account has been deactivated.'

            ], 403);

        }

        /**
         * Remove Old Tokens
         */
        $user->tokens()->delete();

        /**
         * Create New Token
         */
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([

            'success' => true,

            'message' => 'Login successful.',

            'data' => [

                'user' => $user,

                'token' => $token,

            ]

        ]);
    }

    /**
     * Logout User
     */
    public function logout(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * Authenticated User Profile
     */
    public function profile(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }


    /**
     * Update Profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        DB::beginTransaction();

        try {

            $data = $request->validated();

            /**
             * Upload Profile Image
             */
            if ($request->hasFile('profile_photo')) {

                if (
                    $user->profile_photo &&
                    Storage::disk('public')->exists($user->profile_photo)
                ) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $data['profile_photo'] = $request
                    ->file('profile_photo')
                    ->store('profile', 'public');
            }

            $user->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => $user->fresh(),
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change Password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {

            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        /**
         * Logout from all devices
         */
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }



    /**
     * Send Reset Password Link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink([
            'email' => $request->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {

            return response()->json([
                'success' => false,
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent successfully.',
        ]);
    }

    /**
     * Reset Password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function (User $user, string $password) {

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                /**
                 * Logout All Devices
                 */
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {

            return response()->json([
                'success' => false,
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }




}
