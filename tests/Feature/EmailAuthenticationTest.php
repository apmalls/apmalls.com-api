<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationOtpMail;
use App\Mail\LoginOtpMail;
use App\Mail\StaffInvitationMail;
use App\Models\Auth\LoginChallenge;
use App\Models\OTP\OtpVerification;
use App\Models\User;
use App\Services\Auth\AccountInvitationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailAuthenticationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_customer_registration_requires_email_verification(): void
    {
        Mail::fake();
        Role::create(['name' => 'Customer', 'guard_name' => 'web']);

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'New',
            'last_name' => 'Customer',
            'username' => 'new-customer',
            'email' => 'new@example.com',
            'mobile' => '9876543210',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'terms_accepted' => true,
            'terms_version' => '2026-08-30',
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.verification.otp');

        $this->assertNull(User::where('email', 'new@example.com')->firstOrFail()->email_verified_at);
        Mail::assertQueued(EmailVerificationOtpMail::class);
        $this->postJson('/api/v1/auth/login', ['email' => 'new@example.com', 'password' => 'Password@123'])
            ->assertForbidden()->assertJsonPath('code', 'email_verification_required');
        $this->postJson('/api/v1/auth/login/send-otp', ['email' => 'new@example.com'])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unverified_user_cannot_log_in(): void
    {
        $user = $this->user('pending@example.com');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ])->assertForbidden()
            ->assertJsonPath('code', 'email_verification_required')
            ->assertJsonMissingPath('data.token');
    }

    public function test_email_otp_login_requires_a_valid_one_time_code_before_issuing_a_token(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('login-otp@example.com');
        $otp = null;

        $login = $this->postJson('/api/v1/auth/login/send-otp', [
            'email' => $user->email,
        ])->assertStatus(202)
            ->assertJsonPath('code', 'login_otp_required')
            ->assertJsonMissingPath('data.token');

        Mail::assertQueued(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$otp) {
            $otp = $mail->otp;
            return true;
        });

        $challengeId = $login->json('data.challenge_id');
        $this->assertNotNull($challengeId);
        $this->assertNotNull($otp);

        $this->postJson('/api/v1/auth/login/verify-otp', [
            'challenge_id' => $challengeId,
            'otp' => $otp,
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->assertNotNull(LoginChallenge::where('challenge_id', $challengeId)->firstOrFail()->consumed_at);

        $this->postJson('/api/v1/auth/login/verify-otp', [
            'challenge_id' => $challengeId,
            'otp' => $otp,
        ])->assertUnprocessable()->assertJsonPath('code', 'login_challenge_consumed');
    }

    public function test_login_code_expires_and_locks_after_five_incorrect_attempts(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('login-limit@example.com');

        $challengeId = $this->postJson('/api/v1/auth/login/send-otp', [
            'email' => $user->email,
        ])->assertStatus(202)->json('data.challenge_id');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/v1/auth/login/verify-otp', [
                'challenge_id' => $challengeId,
                'otp' => '000000',
            ])->assertUnprocessable();

            $response->assertJsonPath('code', $attempt === 5 ? 'login_otp_locked' : 'login_otp_invalid');
        }

        $this->assertSame(5, LoginChallenge::where('challenge_id', $challengeId)->firstOrFail()->attempts);
        $this->assertCount(0, $user->tokens);

        $this->travel(61)->seconds();
        $expired = $this->postJson('/api/v1/auth/login/send-otp', [
            'email' => $user->email,
        ])->assertStatus(202)->json('data.challenge_id');
        LoginChallenge::where('challenge_id', $expired)->update(['expires_at' => now()->subSecond()]);

        $this->postJson('/api/v1/auth/login/verify-otp', [
            'challenge_id' => $expired,
            'otp' => '123456',
        ])->assertUnprocessable()->assertJsonPath('code', 'login_otp_expired');
    }

    public function test_resending_login_code_invalidates_the_previous_code(): void
    {
        Mail::fake();
        $user = $this->verifiedUser('login-resend@example.com');
        $firstOtp = null;

        $challengeId = $this->postJson('/api/v1/auth/login/send-otp', [
            'email' => $user->email,
        ])->assertStatus(202)->json('data.challenge_id');

        Mail::assertQueued(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$firstOtp) {
            $firstOtp = $mail->otp;
            return true;
        });

        LoginChallenge::where('challenge_id', $challengeId)->update([
            'resend_available_at' => now()->subSecond(),
        ]);

        Mail::fake();
        $secondOtp = null;
        $this->postJson('/api/v1/auth/login/resend-otp', [
            'challenge_id' => $challengeId,
        ])->assertOk();
        Mail::assertQueued(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$secondOtp) {
            $secondOtp = $mail->otp;
            return true;
        });

        $this->assertNotSame($firstOtp, $secondOtp);
        $this->postJson('/api/v1/auth/login/verify-otp', [
            'challenge_id' => $challengeId,
            'otp' => $firstOtp,
        ])->assertUnprocessable()->assertJsonPath('code', 'login_otp_invalid');

        $this->postJson('/api/v1/auth/login/verify-otp', [
            'challenge_id' => $challengeId,
            'otp' => $secondOtp,
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_generic_otp_endpoints_cannot_be_used_for_login(): void
    {
        $this->postJson('/api/v1/auth/send-otp', [
            'recipient' => 'generic-login@example.com',
            'channel' => 'email',
            'type' => 'login',
        ])->assertUnprocessable()->assertJsonValidationErrors('type');

        $this->postJson('/api/v1/auth/verify-otp', [
            'recipient' => 'generic-login@example.com',
            'channel' => 'email',
            'type' => 'login',
            'otp' => '123456',
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_valid_email_otp_verifies_and_authenticates_user(): void
    {
        $user = $this->user('verify@example.com');
        OtpVerification::create([
            'type' => 'email_verification',
            'channel' => 'email',
            'recipient' => $user->email,
            'otp' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/email-verification/verify', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_staff_invitation_is_single_use_and_sets_password(): void
    {
        Mail::fake();
        $user = $this->user('staff@example.com');
        $token = null;

        app(AccountInvitationService::class)->invite($user);
        Mail::assertQueued(StaffInvitationMail::class, function (StaffInvitationMail $mail) use (&$token) {
            $token = $mail->token;
            return true;
        });

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ];

        $this->postJson('/api/v1/auth/activate-account', $payload)->assertOk();
        $this->assertTrue(Hash::check('NewPassword@123', $user->refresh()->password));
        $this->assertNotNull($user->email_verified_at);
        $this->postJson('/api/v1/auth/activate-account', $payload)->assertUnprocessable();
    }

    public function test_password_reset_rejects_the_current_password(): void
    {
        Mail::fake();
        $user = $this->user('reset@example.com');
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('Password@123', $user->refresh()->password));

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'DifferentPassword@123',
            'password_confirmation' => 'DifferentPassword@123',
        ])->assertOk();

        $this->assertTrue(Hash::check('DifferentPassword@123', $user->refresh()->password));
    }

    private function user(string $email): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => uniqid('user-', true),
            'email' => $email,
            'mobile' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => 'Password@123',
            'is_active' => true,
            'email_verified_at' => null,
        ]);
    }

    private function verifiedUser(string $email): User
    {
        $user = $this->user($email);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
