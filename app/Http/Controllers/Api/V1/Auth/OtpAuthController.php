<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtpAuthController extends Controller
{
     public function __construct(
        protected OtpServiceInterface $otpService
    ) {
    }

     public function sendOtp(
        SendOtpRequest $request
    ): JsonResponse {

        $response = $this->otpService->send(

            recipient: $request->recipient,

            channel: $request->channel,

            type: $request->type

        );

        return response()->json($response);

    }


    public function verifyOtp(
        VerifyOtpRequest $request
    ): JsonResponse {

        DB::beginTransaction();

        try {

            $verified = $this->otpService->verify(

                recipient: $request->recipient,

                channel: $request->channel,

                type: $request->type,

                otp: $request->otp

            );

            if (!$verified) {

                return response()->json([

                    'success' => false,

                    'message' => 'Invalid or expired OTP.'

                ], 422);

            }

            /**
             * Login by Mobile
             */
            if ($request->channel === 'mobile') {

                $user = User::where(

                    'mobile',

                    $request->recipient

                )->first();

            } else {

                /**
                 * Login by Email
                 */
                $user = User::where(

                    'email',

                    $request->recipient

                )->first();

            }

            /**
             * Auto Register (Optional)
             */
            if (!$user) {

                return response()->json([

                    'success' => false,

                    'message' => 'User not found.'

                ], 404);

            }

            /**
             * Active Check
             */
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
             * New Token
             */
            $token = $user
                ->createToken('auth_token')
                ->plainTextToken;

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Login successful.',

                'data' => [

                    'token' => $token,

                    'user' => $user->load('roles'),

                    'roles' => $user->getRoleNames()->values(),

                    'permissions' => $user->getAllPermissions()
                        ->pluck('name')
                        ->values(),

                ]

            ]);

        } catch (\Throwable $exception) {

            DB::rollBack();

            report($exception);

            return response()->json([

                'success' => false,

                'message' => 'Something went wrong.'

            ], 500);

        }
    }
}
