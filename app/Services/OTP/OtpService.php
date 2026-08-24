<?php

declare(strict_types=1);

namespace App\Services\OTP;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\OtpVerificationRepositoryInterface;
use App\Services\Contracts\OtpServiceInterface;

class OtpService implements OtpServiceInterface
{
    public function __construct(

        protected OtpVerificationRepositoryInterface $otpRepository

    ) {}

    public function send(
        string $recipient,
        string $channel,
        string $type
    ): array {

        $otp = (string) random_int(100000, 999999);

        $this->otpRepository->deleteOld(
            $recipient,
            $channel,
            $type
        );

        $this->otpRepository->create([

            'recipient' => $recipient,

            'channel' => $channel,

            'type' => $type,

            'otp' => Hash::make($otp),

            'expires_at' => Carbon::now()->addMinutes(5),

            'ip_address' => request()->ip(),

            'user_agent' => request()->userAgent(),

        ]);

        return [

            'success' => true,

            'message' => 'OTP sent successfully.',

            /**
             * Development Only
             */
            'otp' => $otp,

        ];
    }

    public function verify(
        string $recipient,
        string $channel,
        string $type,
        string $otp
    ): bool {

        $verification = $this->otpRepository->findActive(
            $recipient,
            $channel,
            $type
        );

        if (!$verification) {
            return false;
        }

        if ($verification->expires_at->isPast()) {
            return false;
        }

        if (!Hash::check($otp, $verification->otp)) {

            $this->otpRepository->update(
                $verification->id,
                [
                    'attempts' => $verification->attempts + 1,
                ]
            );

            return false;
        }

        $this->otpRepository->update(
            $verification->id,
            [
                'is_verified' => true,
                'verified_at' => now(),
            ]
        );

        return true;
    }
}
