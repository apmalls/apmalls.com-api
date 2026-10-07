<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Repositories\Contracts\CustomerAddressRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Http\Request;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendLoginOtpRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ActivateAccountRequest;
use App\Http\Requests\Auth\ResendEmailVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\Auth\AccountInvitationService;
use App\Services\Auth\LoginChallengeService;
use App\Http\Requests\Auth\SendLoginOtpRequest;
use App\Exceptions\LoginChallengeException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use App\Mail\ForgotPasswordMail;
use Illuminate\Support\Facades\DB;

use App\Mail\PasswordResetSuccessMail;


use Illuminate\Support\Facades\Mail;


class AuthController extends Controller
{

    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected CustomerAddressRepositoryInterface $customerAddressRepository,
        protected OtpServiceInterface $otpService,
        protected AccountInvitationService $accountInvitationService,
        protected LoginChallengeService $loginChallengeService,
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

            $this->commit();

            try {
                $verification = $this->otpService->send(
                    recipient: $user->email,
                    channel: 'email',
                    type: 'email_verification',
                );
            } catch (\Throwable $mailException) {
                report($mailException);
                $verification = [
                    'masked_recipient' => $user->email,
                    'resend_after' => 0,
                ];
            }

            return response()->json([

                'success' => true,

                'message' => 'Registration successful. Verify your email to continue.',

                'data' => [

                    'user' => $user->load('roles'),

                    'verification' => $verification,

                ]

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($request->email)])
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        if ($user->latestInvitation()->whereNull('accepted_at')->exists()) {
            return response()->json([
                'success' => false,
                'code' => 'account_activation_required',
                'message' => 'Activate this account using the link in your invitation email.',
            ], 403);
        }

        if (! $this->otpService->verify(
            recipient: Str::lower($request->email),
            channel: 'email',
            type: 'email_verification',
            otp: $request->otp,
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return $this->authenticatedResponse($user, 'Email verified successfully.');
    }

    public function resendEmailVerification(ResendEmailVerificationRequest $request): JsonResponse
    {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($request->email)])
            ->first();

        $verification = $user && $user->email_verified_at === null
            ? $this->otpService->send($user->email, 'email', 'email_verification')
            : [
                'success' => true,
                'message' => 'If verification is required, a code has been requested. Check your email shortly.',
                'resend_after' => 60,
            ];

        return response()->json($verification);
    }

    public function activateAccount(ActivateAccountRequest $request): JsonResponse
    {
        $this->accountInvitationService->accept(
            email: $request->email,
            token: $request->token,
            password: $request->password,
        );

        return response()->json([
            'success' => true,
            'message' => 'Account activated successfully. You can now log in.',
        ]);
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

            Auth::logout();

            return response()->json([

                'success' => false,

                'message' => 'Your account has been deactivated.'

            ], 403);

        }

        if ($user->latestInvitation()->whereNull('accepted_at')->exists()) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'code' => 'account_activation_required',
                'message' => 'Activate this account using the link in your invitation email.',
            ], 403);
        }

        if ($user->email_verified_at === null) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'code' => 'email_verification_required',
                'message' => 'Verify your email address before logging in.',
                'data' => ['email' => $user->email, 'resend_after' => 0],
            ], 403);
        }

        Auth::logout();

        try {
            $result = $this->loginChallengeService->authenticatePassword($user, $credentials['password']);
        } catch (LoginChallengeException $exception) {
            return $this->loginChallengeError($exception);
        }

        return $this->loginResponse($result);
    }

    public function sendLoginOtp(SendLoginOtpRequest $request): JsonResponse
    {
        $user = User::query()->whereRaw('LOWER(email) = ?', [$request->validated('email')])->first();

        if (! $user || ! $this->loginChallengeService->canLogin($user)) {
            return response()->json([
                'success' => false,
                'code' => 'login_otp_unavailable',
                'message' => 'Unable to request a login code. Check your email address and complete account verification or activation.',
            ], 422);
        }

        try {
            $challenge = $this->loginChallengeService->create(
                $user,
                $request->ip(),
                $request->userAgent(),
            );
        } catch (LoginChallengeException $exception) {
            return $this->loginChallengeError($exception);
        }

        return response()->json([
            'success' => true,
            'code' => 'login_otp_required',
            'message' => 'A login code has been requested. Check your email shortly.',
            'data' => $challenge,
        ], 202);
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->loginChallengeService->verify(
                $request->validated('challenge_id'),
                $request->validated('otp'),
            );
        } catch (LoginChallengeException $exception) {
            return $this->loginChallengeError($exception);
        }

        return $this->loginResponse($result);
    }

    private function loginResponse(array $result): JsonResponse
    {
        /** @var User $user */
        $user = $result['user'];

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $result['token'],
                'user' => $user->load('roles'),
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function resendLoginOtp(ResendLoginOtpRequest $request): JsonResponse
    {
        try {
            $challenge = $this->loginChallengeService->resend(
                $request->validated('challenge_id'),
                $request->ip(),
            );
        } catch (LoginChallengeException $exception) {
            return $this->loginChallengeError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new login code has been requested. Check your email shortly.',
            'data' => $challenge,
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

        $emailChanged = Str::lower((string) $user->email) !== Str::lower((string) $request->email);

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

                'email_verified_at' => $emailChanged ? null : $user->email_verified_at,

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

            if ($emailChanged) {
                $user->tokens()->delete();
                $this->loginChallengeService->invalidateOutstanding($user);

                try {
                    $this->otpService->send($user->email, 'email', 'email_verification');
                } catch (\Throwable $mailException) {
                    report($mailException);
                }
            }

            return response()->json([

                'success' => true,

                'message' => $emailChanged
                    ? 'Profile updated. Verify your new email address to continue.'
                    : 'Profile updated successfully.',

                'requires_email_verification' => $emailChanged,

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

    private function authenticatedResponse(User $user, string $message): JsonResponse
    {
        $this->loginChallengeService->invalidateOutstanding($user);
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'token' => $token,
                'user' => $user->load('roles'),
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    private function loginChallengeError(LoginChallengeException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $exception->errorCode,
            'message' => $exception->getMessage(),
            'data' => $exception->data,
        ], $exception->status);
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
                if (Hash::check($password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => ['Your new password must be different from your current password.'],
                    ]);
                }

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
