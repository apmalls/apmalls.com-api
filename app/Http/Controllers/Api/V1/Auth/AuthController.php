<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Repositories\Contracts\CustomerAddressRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Http\Request;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\DB;

use App\Mail\PasswordResetSuccessMail;


use Illuminate\Support\Facades\Mail;


class AuthController extends Controller
{

    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected CustomerAddressRepositoryInterface $customerAddressRepository
    ) {
    }



    /**
     * Register New User
     */
    // public function register(RegisterRequest $request): JsonResponse
    // {
    //     $this->beginTransaction();

    //     try {

    //         $user = User::create([

    //             'first_name' => $request->first_name,

    //             'last_name' => $request->last_name,

    //             'username' => $request->username,

    //             'email' => $request->email,

    //             'mobile' => $request->mobile,

    //             'password' => Hash::make($request->password),

    //         ]);

    //         /**
    //          * Default Role
    //          */
    //         $user->assignRole(config('roles.default'));

    //         /**
    //          * Sanctum Token
    //          */
    //         $token = $user->createToken('auth_token')->plainTextToken;

    //         $this->commit();

    //         return response()->json([

    //             'success' => true,

    //             'message' => 'Registration successful.',

    //             'data' => [

    //                 'user' => $user,

    //                 'token' => $token,

    //             ]

    //         ], 201);

    //     } catch (\Exception $e) {

    //         $this->rollback();

    //         return $this->handleException($e);

    //     }
    // }

    /**
     * Register New User
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $this->beginTransaction();

        try {

            $user = User::create([

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'username' => $request->username,

                'email' => $request->email,

                'mobile' => $request->mobile,

                'password' => Hash::make($request->password),

                'terms_accepted' => $request->boolean('terms_accepted'),

                'terms_accepted_at' => now(),

                'terms_version' => $request->terms_version,

            ]);

            /**
             * Default Role
             */
            $role = config('roles.default');

            $user->assignRole($role);

            /**
             * Create Customer Profile
             */
            if ($role === 'Customer') {

                Customer::create([

                    'user_id' => $user->id,

                    'customer_code' => 'CUS-' . str_pad(
                        (string) (Customer::max('id') + 1),
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),

                    'customer_type' => 'Retail',

                    'first_name' => $user->first_name,

                    'last_name' => $user->last_name,

                    'mobile' => $user->mobile,

                    'email' => $user->email,

                    'is_active' => true,

                ]);

            }

            /**
             * Sanctum Token
             */
            $token = $user->createToken('auth_token')->plainTextToken;

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Registration successful.',

                'data' => [

                    'user' => $user->load('roles'),

                    'token' => $token,

                ]

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

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
                'token' => $token,
                'user' => [

                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile_photo' => $user->profile_photo_url,
                    'is_active' => $user->is_active,
                    'terms_accepted' => $user->terms_accepted,
                    'terms_accepted_at' => optional($user->terms_accepted_at)->toISOString(),
                    'terms_version' => $user->terms_version,

                ],

                'roles' => $user->getRoleNames()->values(),

                'permissions' => $user->getAllPermissions()
                    ->pluck('name')
                    ->values(),



            ]

        ]);
    }

    /**
     * Logout User
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
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
        /** @var User $user */
        $user = Auth::user()->load('roles');

        /**
         * Customer Details
         */
        if ($user->hasRole('Customer')) {

            $user->load([
                'customer.addresses',
            ]);

        }

        return response()->json([

            'success' => true,

            'data' => [
                'user' => $user,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()
                    ->pluck('name')
                    ->values(),
            ],

        ]);
    }


    /**
     * Update Profile
     */
    // public function updateProfile(UpdateProfileRequest $request): JsonResponse
    // {
    //     /** @var User $user */
    //     $user = Auth::user();

    //     $this->beginTransaction();

    //     try {

    //         $data = $request->validated();

    //         /**
    //          * Upload Profile Image
    //          */
    //         if ($request->hasFile('profile_photo')) {

    //             $data['profile_photo'] = $this->replaceFile(
    //                 $request->file('profile_photo'),
    //                 $user->profile_photo,
    //                 'profile'
    //             );
    //         }

    //         $user->update($data);

    //         $this->commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Profile updated successfully.',
    //             'data' => $user->fresh(),
    //         ]);

    //     } catch (\Exception $e) {

    //         $this->rollback();

    //         return $this->handleException($e);
    //     }
    // }


    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->beginTransaction();

        try {

            $data = $request->validated();

            /**
             * Upload Profile Photo
             */
            if ($request->hasFile('profile_photo')) {

                $data['profile_photo'] = $this->replaceFile(
                    $request->file('profile_photo'),
                    $user->profile_photo,
                    'profile'
                );
            }

            /**
             * User Update
             */
            $user->update([

                'first_name' => $request->first_name,

                'last_name' => $request->last_name,

                'username' => $request->username,

                'email' => $request->email,

                'mobile' => $request->mobile,

                'profile_photo' => $data['profile_photo'] ?? $user->profile_photo,

            ]);

            /**
             * Customer Update/Create
             */
            if ($user->hasRole('Customer')) {

                $customer = $this->customerRepository->findByUser(
                    $user->id
                );

                $customerData = [

                    'user_id' => $user->id,

                    'first_name' => $request->first_name,

                    'last_name' => $request->last_name,

                    'mobile' => $request->mobile,

                    'alternate_mobile' => $request->alternate_mobile,

                    'email' => $request->email,

                    'customer_type' => $request->customer_type,

                    'company_name' => $request->company_name,

                    'gst_number' => $request->gst_number,

                    'date_of_birth' => $request->date_of_birth,

                    'anniversary_date' => $request->anniversary_date,

                    'notes' => $request->notes,

                    'updated_by' => $user->id,

                ];

                if ($customer) {

                    $customer = $this->customerRepository->update(
                        $customer->id,
                        $customerData
                    );

                } else {

                    $customerData['customer_code'] = 'CUS-' . str_pad(
                        (string) ($this->customerRepository->count() + 1),
                        6,
                        '0',
                        STR_PAD_LEFT
                    );

                    $customerData['created_by'] = $user->id;

                    $customer = $this->customerRepository->create(
                        $customerData
                    );
                }

                /**
                 * Customer Address
                 */
                if ($request->filled('address_line_1')) {

                    $addressData = [

                        'customer_id' => $customer->id,

                        'address_type' => $request->address_type,

                        'contact_person' => $request->contact_person,

                        'mobile' => $request->address_mobile,

                        'alternate_mobile' => $request->address_alternate_mobile,

                        'email' => $request->address_email,

                        'address_line_1' => $request->address_line_1,

                        'address_line_2' => $request->address_line_2,

                        'landmark' => $request->landmark,

                        'city' => $request->city,

                        'state' => $request->state,

                        'country' => $request->country,

                        'postal_code' => $request->postal_code,

                        'is_default' => $request->boolean('is_default'),

                        'updated_by' => $user->id,

                    ];

                    if ($request->boolean('is_default')) {

                        $this->customerAddressRepository
                            ->clearDefault($customer->id);
                    }

                    if ($request->filled('address_id')) {

                        $address = $this->customerAddressRepository
                            ->findByCustomer(
                                $customer->id,
                                $request->address_id
                            );

                        $this->customerAddressRepository->update(
                            $address->id,
                            $addressData
                        );

                    } else {

                        $addressData['created_by'] = $user->id;

                        $this->customerAddressRepository->create(
                            $addressData
                        );
                    }
                }
            }

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Profile updated successfully.',

                'data' => $user->load([
                    'roles',
                    'customer.addresses'
                ]),

            ]);

        } catch (\Throwable $exception) {

            $this->rollback();

            return $this->handleException($exception);
        }
    }

    /**
     * Change Password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
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
    // public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    // {
    //     $status = Password::sendResetLink([
    //         'email' => $request->email,
    //     ]);

    //     if ($status !== Password::RESET_LINK_SENT) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => __($status),
    //         ], 422);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Password reset link sent successfully.',
    //     ]);
    // }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $user->email,
            ],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)
            ->send(new ForgotPasswordMail($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link has been sent to your email address.',
        ]);
    }

    // public function forgotPassword(
    //     ForgotPasswordRequest $request
    // ): JsonResponse {

    //     $status = Password::sendResetLink([
    //         'email' => $request->validated('email'),
    //     ]);

    //     return match ($status) {

    //         Password::RESET_LINK_SENT => response()->json([

    //             'success' => true,

    //             'message' => 'Password reset link has been sent to your email address.',

    //         ], 200),

    //         Password::INVALID_USER => response()->json([

    //             'success' => false,

    //             'message' => 'No account found with this email address.',

    //         ], 404),

    //         Password::RESET_THROTTLED => response()->json([

    //             'success' => false,

    //             'message' => 'Please wait before requesting another password reset link.',

    //         ], 429),

    //         default => response()->json([

    //             'success' => false,

    //             'message' => 'Unable to send password reset link. Please try again later.',

    //         ], 500),
    //     };
    // }

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

                Mail::to($user->email)
                    ->send(new PasswordResetSuccessMail($user));

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
