<?php

declare(strict_types=1);

namespace App\Services\OTP;

use App\Mail\EmailVerificationOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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

        if ($channel !== 'email') {
            throw ValidationException::withMessages([
                'channel' => ['Email is the only available OTP delivery channel.'],
            ]);
        }

        $recipient = mb_strtolower(trim($recipient));
        $rateKey = 'otp-send:' . hash('sha256', $recipient . '|' . $type);

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages([
                'recipient' => ['Too many verification codes requested. Try again later.'],
            ]);
        }

        if (! Cache::add($rateKey . ':cooldown', true, 60)) {
            throw ValidationException::withMessages([
                'recipient' => ['Please wait 60 seconds before requesting another code.'],
            ]);
        }

        RateLimiter::hit($rateKey, 3600);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$recipient])->first();

        $verificationOnly = in_array($type, ['register', 'email_verification'], true);

        if (! $user || ($verificationOnly && $user->email_verified_at !== null)) {
            return [
                'success' => true,
                'message' => 'If verification is required, a code has been sent.',
                'masked_recipient' => $this->maskEmail($recipient),
                'resend_after' => 60,
            ];
        }

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

        DB::afterCommit(function () use ($user, $otp): void {
            Mail::to($user->email)->queue(new EmailVerificationOtpMail($user, $otp));
        });

        return [

            'success' => true,

            'message' => 'Verification code sent successfully.',

            'masked_recipient' => $this->maskEmail($recipient),

            'resend_after' => 60,

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

        if ($verification->attempts >= $verification->max_attempts) {
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

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }
}
