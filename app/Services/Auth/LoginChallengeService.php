<?php

namespace App\Services\Auth;

use App\Exceptions\LoginChallengeException;
use App\Mail\LoginOtpMail;
use App\Models\Auth\LoginChallenge;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class LoginChallengeService
{
    private const OTP_LIFETIME_SECONDS = 300;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    private const MAX_SENDS_PER_HOUR = 5;

    public function canLogin(User $user): bool
    {
        return $user->is_active && $user->email_verified_at !== null
            && ! $user->latestInvitation()->whereNull('accepted_at')->exists();
    }

    public function authenticatePassword(User $user, string $password): array
    {
        return DB::transaction(function () use ($user, $password) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $this->canLogin($user) || ! Hash::check($password, $user->password)) {
                throw $this->exception('login_unavailable', 'This account cannot complete login.', [], 403);
            }

            $this->invalidateOutstanding($user);

            return $this->issueToken($user);
        });
    }

    public function create(User $user, ?string $ipAddress, ?string $userAgent): array
    {
        return $this->withSendLocks($user, $ipAddress, function () use ($user, $ipAddress, $userAgent) {
            return DB::transaction(function () use ($user, $ipAddress, $userAgent) {
                $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $this->ensureEligible($user);
                $this->ensureSendAllowed($user, $ipAddress);
                $this->ensureCooldown($user);
                $otp = $this->generateOtp();
                $this->invalidateOutstanding($user);

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

                $this->recordSend($user, $ipAddress);
                $this->queueMail($challenge, $user, $otp);

                return $this->challengePayload($challenge, $user);
            });
        });
    }

    public function resend(string $challengeId, ?string $ipAddress): array
    {
        $existing = LoginChallenge::query()->with('user')->where('challenge_id', $challengeId)->first();
        if (! $existing || ! $existing->user) {
            throw $this->invalidChallenge();
        }

        return $this->withSendLocks($existing->user, $ipAddress, function () use ($existing, $challengeId, $ipAddress) {
            return DB::transaction(function () use ($existing, $challengeId, $ipAddress) {
                $user = User::query()->whereKey($existing->user_id)->lockForUpdate()->first();
                $this->ensureEligible($user);
                $challenge = LoginChallenge::query()->where('challenge_id', $challengeId)->lockForUpdate()->first();
                if (! $challenge || $challenge->consumed_at || $challenge->invalidated_at) {
                    throw $this->invalidChallenge();
                }

                $this->ensureSendAllowed($user, $ipAddress);
                $this->ensureCooldown($user);
                $otp = $this->generateOtp();
                $challenge->update([
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addSeconds(self::OTP_LIFETIME_SECONDS),
                    'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
                    'attempts' => 0,
                    'send_count' => $challenge->send_count + 1,
                    'ip_address' => $ipAddress,
                ]);
                $this->recordSend($user, $ipAddress);
                $this->queueMail($challenge, $user, $otp);

                return $this->challengePayload($challenge, $user);
            });
        });
    }

    public function verify(string $challengeId, string $otp): array
    {
        $userId = LoginChallenge::query()->where('challenge_id', $challengeId)->value('user_id');
        if (! $userId) {
            throw $this->invalidChallenge();
        }

        // All login paths lock the user before the challenge to serialize session replacement.
        $result = DB::transaction(function () use ($userId, $challengeId, $otp) {
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();
            $challenge = LoginChallenge::query()->where('challenge_id', $challengeId)->lockForUpdate()->first();
            if (! $challenge || $challenge->invalidated_at) {
                return ['error' => $this->invalidChallenge()];
            }
            if ($challenge->consumed_at) {
                return ['error' => $this->exception('login_challenge_consumed', 'This login code has already been used.')];
            }
            if (! $challenge->expires_at->isFuture()) {
                return ['error' => $this->exception('login_otp_expired', 'This login code has expired. Request a new code.')];
            }
            if ($challenge->attempts >= $challenge->max_attempts) {
                return ['error' => $this->exception('login_otp_locked', 'Too many incorrect attempts. Request a new code.')];
            }
            $this->ensureEligible($user);
            if (! Hash::check($otp, $challenge->otp_hash)) {
                $challenge->increment('attempts');
                $remaining = max(0, $challenge->max_attempts - $challenge->attempts);

                return ['error' => $this->exception(
                    $remaining === 0 ? 'login_otp_locked' : 'login_otp_invalid',
                    $remaining === 0 ? 'Too many incorrect attempts. Request a new code.'
                        : "The login code is incorrect. {$remaining} attempts remaining.",
                    ['attempts_remaining' => $remaining],
                )];
            }

            $challenge->update(['consumed_at' => now()]);
            $this->invalidateOutstanding($user);

            return $this->issueToken($user);
        });

        if (isset($result['error'])) {
            throw $result['error'];
        }

        return $result;
    }

    private function withSendLocks(User $user, ?string $ipAddress, callable $callback): array
    {
        try {
            return Cache::lock('login-otp-ip-lock:'.hash('sha256', $ipAddress ?? 'unknown'), 15)
                ->block(5, fn () => Cache::lock('login-otp-user-lock:'.$user->id, 15)->block(5, $callback));
        } catch (LockTimeoutException) {
            throw $this->exception('login_otp_rate_limited', 'Please try again shortly.', ['resend_after' => 5], 429);
        }
    }

    private function ensureEligible(?User $user): void
    {
        if (! $user || ! $this->canLogin($user)) {
            throw $this->exception('login_challenge_invalid', 'This account cannot complete login.', [], 403);
        }
    }

    private function ensureCooldown(User $user): void
    {
        $availableAt = LoginChallenge::query()->where('user_id', $user->id)->max('resend_available_at');
        $retryAfter = $availableAt ? max(0, (int) ceil(now()->diffInSeconds($availableAt, false))) : 0;
        if ($retryAfter > 0) {
            throw $this->exception('login_otp_resend_cooldown',
                "Please wait {$retryAfter} seconds before requesting another code.", ['resend_after' => $retryAfter], 429);
        }
    }

    private function rateKeys(User $user, ?string $ipAddress): array
    {
        return ['login-otp-user:'.$user->id, 'login-otp-ip:'.hash('sha256', $ipAddress ?? 'unknown')];
    }

    private function ensureSendAllowed(User $user, ?string $ipAddress): void
    {
        foreach ($this->rateKeys($user, $ipAddress) as $key) {
            if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS_PER_HOUR)) {
                throw $this->exception('login_otp_rate_limited', 'Too many login codes requested. Try again later.',
                    ['resend_after' => max(1, RateLimiter::availableIn($key))], 429);
            }
        }
    }

    private function recordSend(User $user, ?string $ipAddress): void
    {
        foreach ($this->rateKeys($user, $ipAddress) as $key) {
            RateLimiter::hit($key, 3600);
        }
    }

    private function queueMail(LoginChallenge $challenge, User $user, string $otp): void
    {
        DB::afterCommit(function () use ($challenge, $user, $otp) {
            try {
                Mail::to($user->email)->queue(new LoginOtpMail($user, $otp));
            } catch (Throwable) {
                $challenge->update(['invalidated_at' => now()]);
                throw $this->exception('login_otp_delivery_unavailable',
                    'Unable to queue a login code. Please try again later or use password login.', [], 503);
            }
        });
    }

    public function invalidateOutstanding(User $user): void
    {
        LoginChallenge::query()->where('user_id', $user->id)->whereNull('consumed_at')->whereNull('invalidated_at')
            ->update(['invalidated_at' => now()]);
    }

    private function issueToken(User $user): array
    {
        $user->tokens()->delete();

        return ['user' => $user, 'token' => $user->createToken('auth_token')->plainTextToken];
    }

    private function challengePayload(LoginChallenge $challenge, User $user): array
    {
        [$local, $domain] = explode('@', $user->email, 2);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return [
            'challenge_id' => $challenge->challenge_id,
            'masked_recipient' => $visible.str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain,
            'expires_in' => self::OTP_LIFETIME_SECONDS,
            'expires_at' => $challenge->expires_at->toISOString(),
            'resend_after' => self::RESEND_COOLDOWN_SECONDS,
            'resend_available_at' => $challenge->resend_available_at->toISOString(),
        ];
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function invalidChallenge(): LoginChallengeException
    {
        return $this->exception('login_challenge_invalid', 'This login challenge is no longer valid.');
    }

    private function exception(string $code, string $message, array $data = [], int $status = 422): LoginChallengeException
    {
        return new LoginChallengeException($code, $message, $data, $status);
    }
}
