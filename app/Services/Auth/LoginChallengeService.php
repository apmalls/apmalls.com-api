<?php

namespace App\Services\Auth;

use App\Exceptions\LoginChallengeException;
use App\Mail\LoginOtpMail;
use App\Models\Auth\LoginChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginChallengeService
{
    private const OTP_LIFETIME_SECONDS = 300;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    private const MAX_SENDS_PER_HOUR = 5;

    public function create(User $user, ?string $ipAddress, ?string $userAgent): array
    {
        $rateKey = $this->rateKey($user, $ipAddress);
        $this->ensureSendAllowed($rateKey);
        $otp = $this->generateOtp();

        $challenge = DB::transaction(function () use ($user, $ipAddress, $userAgent, $otp) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            LoginChallenge::query()
                ->where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            $challenge = LoginChallenge::create([
                'challenge_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addSeconds(self::OTP_LIFETIME_SECONDS),
                'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
                'max_attempts' => self::MAX_ATTEMPTS,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            DB::afterCommit(fn () => Mail::to($user->email)->queue(new LoginOtpMail($user, $otp)));

            return $challenge;
        });

        RateLimiter::hit($rateKey, 3600);

        return $this->challengePayload($challenge);
    }

    public function resend(string $challengeId, ?string $ipAddress): array
    {
        $otp = $this->generateOtp();

        $result = DB::transaction(function () use ($challengeId, $ipAddress, $otp) {
            $challenge = LoginChallenge::query()
                ->with('user')
                ->where('challenge_id', $challengeId)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->consumed_at || $challenge->invalidated_at) {
                return ['error' => $this->exception('login_challenge_invalid', 'This login challenge is no longer valid.')];
            }

            if ($challenge->resend_available_at->isFuture()) {
                $retryAfter = max(1, (int) ceil(now()->diffInSeconds($challenge->resend_available_at)));

                return ['error' => $this->exception(
                    'login_otp_resend_cooldown',
                    "Please wait {$retryAfter} seconds before requesting another code.",
                    ['resend_after' => $retryAfter],
                    429,
                )];
            }

            $rateKey = $this->rateKey($challenge->user, $ipAddress);
            try {
                $this->ensureSendAllowed($rateKey);
            } catch (LoginChallengeException $exception) {
                return ['error' => $exception];
            }

            $challenge->update([
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addSeconds(self::OTP_LIFETIME_SECONDS),
                'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
                'attempts' => 0,
                'send_count' => $challenge->send_count + 1,
                'ip_address' => $ipAddress,
            ]);

            DB::afterCommit(fn () => Mail::to($challenge->user->email)->queue(
                new LoginOtpMail($challenge->user, $otp)
            ));

            return ['challenge' => $challenge->refresh(), 'rate_key' => $rateKey];
        });

        if (isset($result['error'])) {
            throw $result['error'];
        }

        RateLimiter::hit($result['rate_key'], 3600);

        return $this->challengePayload($result['challenge']);
    }

    public function verify(string $challengeId, string $otp): array
    {
        $result = DB::transaction(function () use ($challengeId, $otp) {
            $challenge = LoginChallenge::query()
                ->with('user.roles')
                ->where('challenge_id', $challengeId)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->invalidated_at) {
                return ['error' => $this->exception('login_challenge_invalid', 'This login challenge is no longer valid.')];
            }

            if ($challenge->consumed_at) {
                return ['error' => $this->exception('login_challenge_consumed', 'This login code has already been used.')];
            }

            if ($challenge->expires_at->isPast()) {
                return ['error' => $this->exception('login_otp_expired', 'This login code has expired. Request a new code.')];
            }

            if ($challenge->attempts >= $challenge->max_attempts) {
                return ['error' => $this->exception('login_otp_locked', 'Too many incorrect attempts. Request a new code.')];
            }

            $user = $challenge->user;
            if (! $user || ! $user->is_active || $user->email_verified_at === null) {
                return ['error' => $this->exception('login_challenge_invalid', 'This account cannot complete login.', [], 403)];
            }

            if (! Hash::check($otp, $challenge->otp_hash)) {
                $challenge->increment('attempts');
                $remaining = max(0, $challenge->max_attempts - $challenge->attempts);

                return ['error' => $this->exception(
                    $remaining === 0 ? 'login_otp_locked' : 'login_otp_invalid',
                    $remaining === 0
                        ? 'Too many incorrect attempts. Request a new code.'
                        : "The login code is incorrect. {$remaining} attempts remaining.",
                    ['attempts_remaining' => $remaining],
                )];
            }

            $challenge->update(['consumed_at' => now()]);
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });

        if (isset($result['error'])) {
            throw $result['error'];
        }

        return $result;
    }

    private function ensureSendAllowed(string $rateKey): void
    {
        if (! RateLimiter::tooManyAttempts($rateKey, self::MAX_SENDS_PER_HOUR)) {
            return;
        }

        $retryAfter = max(1, RateLimiter::availableIn($rateKey));

        throw $this->exception(
            'login_otp_rate_limited',
            'Too many login codes requested. Try again later.',
            ['resend_after' => $retryAfter],
            429,
        );
    }

    private function challengePayload(LoginChallenge $challenge): array
    {
        return [
            'challenge_id' => $challenge->challenge_id,
            'masked_recipient' => $this->maskEmail($challenge->user->email),
            'expires_in' => self::OTP_LIFETIME_SECONDS,
            'expires_at' => $challenge->expires_at->toISOString(),
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
            'resend_available_at' => $challenge->resend_available_at->toISOString(),
        ];
    }

    private function rateKey(User $user, ?string $ipAddress): string
    {
        return 'login-otp-send:' . hash('sha256', $user->id . '|' . ($ipAddress ?? 'unknown'));
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible . str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
    }

    private function exception(
        string $code,
        string $message,
        array $data = [],
        int $status = 422,
    ): LoginChallengeException {
        return new LoginChallengeException($code, $message, $data, $status);
    }
}
