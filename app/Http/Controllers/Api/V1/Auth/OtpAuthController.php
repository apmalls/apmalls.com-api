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

                DB::rollBack();

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

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' => 'User not found.'

                ], 404);

            }

            /**
             * Active Check
             */
            if (!$user->is_active) {

                DB::rollBack();

                return response()->json([

                    'success' => false,

                    'message' => 'Your account has been deactivated.'

                ], 403);

            }

            if ($user->latestInvitation()->whereNull('accepted_at')->exists()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'code' => 'account_activation_required',
                    'message' => 'Activate this account using the link in your invitation email.',
                ], 403);
            }

            if ($request->type === 'email_verification' && $request->channel === 'email') {
                $user->forceFill(['email_verified_at' => now()])->save();
            } elseif ($user->email_verified_at === null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'code' => 'email_verification_required',
                    'message' => 'Verify your email address before logging in.',
                ], 403);
            }

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'OTP verified successfully.',

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
