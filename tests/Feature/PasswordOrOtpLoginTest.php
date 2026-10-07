<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Mail\StaffInvitationMail;
use App\Models\Auth\LoginChallenge;
use App\Models\OTP\OtpVerification;
use App\Models\User;
use App\Services\Auth\AccountInvitationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordOrOtpLoginTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_verified_password_login_needs_no_mail_or_challenge_and_replaces_tokens(): void
    {
        Mail::fake();
        $user = $this->user('superadmin@apmalls.com');
        $oldToken = $user->createToken('old')->plainTextToken;
        $response = $this->passwordLogin($user)->assertOk()->assertJsonStructure(['data' => ['token', 'user', 'roles', 'permissions']]);
        $this->assertNotSame($oldToken, $response->json('data.token'));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertDatabaseCount('login_challenges', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_wrong_password_inactive_and_unverified_accounts_never_receive_tokens(): void
    {
        Mail::fake();
        $user = $this->user();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        $user->update(['is_active' => false]);
        $this->passwordLogin($user)->assertForbidden();
        $user->update(['is_active' => true, 'email_verified_at' => null]);
        $this->passwordLogin($user)->assertForbidden()->assertJsonPath('code', 'email_verification_required');
        $this->assertSame(0, $user->tokens()->count());
        Mail::assertNothingOutgoing();
    }

    public function test_ineligible_email_addresses_share_one_rejection_and_create_nothing(): void
    {
        Mail::fake();
        $inactive = $this->user('inactive@example.com');
        $inactive->update(['is_active' => false]);
        $pending = $this->user('pending@example.com');
        $pending->update(['email_verified_at' => null]);
        $invited = $this->user('invited@example.com');
        $invited->update(['email_verified_at' => null]);
        app(AccountInvitationService::class)->invite($invited);
        // Even an accidentally verified invited user must finish activation first.
        $invited->update(['email_verified_at' => now()]);
        Mail::fake();
        $responses = [];
        foreach (['missing@example.com', $inactive->email, $pending->email, $invited->email] as $email) {
            $responses[] = $this->postJson('/api/v1/auth/login/send-otp', ['email' => $email])
                ->assertUnprocessable()->assertJsonPath('code', 'login_otp_unavailable')->json();
        }
        foreach ($responses as $response) {
            $this->assertSame($responses[0], $response);
        }
        $this->assertDatabaseCount('login_challenges', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_both_login_choices_work_for_every_role(): void
    {
        foreach (['Customer', 'Cashier', 'Delivery Boy', 'Store Manager', 'Admin', 'Super Admin'] as $index => $role) {
            Mail::fake();
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.($index + 1)]);
            Role::create(['name' => $role, 'guard_name' => 'web']);
            $user = $this->user("role{$index}@example.com");
            $user->assignRole($role);
            $this->passwordLogin($user)->assertOk()->assertJsonPath('data.roles.0', $role);
            $tokenId = $user->tokens()->firstOrFail()->id;
            $challenge = $this->start($user)->assertStatus(202)->assertJsonMissingPath('data.token')->assertJsonMissingPath('data.user');
            $this->assertSame($tokenId, $user->tokens()->firstOrFail()->id);
            $otp = $this->queuedOtp();
            $stored = LoginChallenge::where('challenge_id', $challenge->json('data.challenge_id'))->firstOrFail();
            $this->assertNotSame($otp, $stored->otp_hash);
            $this->assertTrue(Hash::check($otp, $stored->otp_hash));
            $this->assertStringNotContainsString($otp, $challenge->getContent());
            $this->assertStringNotContainsString($stored->otp_hash, $challenge->getContent());
            $this->verify($stored->challenge_id, $otp)->assertOk()->assertJsonPath('data.roles.0', $role);
            $this->assertSame(1, $user->tokens()->count());
            $this->assertNotSame($tokenId, $user->tokens()->firstOrFail()->id);
        }
    }

    public function test_initial_request_and_resend_share_cooldown_across_ips(): void
    {
        Mail::fake();
        $user = $this->user();
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);
        $this->start($user)->assertStatus(429)->assertJsonPath('code', 'login_otp_resend_cooldown');
        $this->postJson('/api/v1/auth/login/resend-otp', ['challenge_id' => $id])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/login/resend-otp', ['challenge_id' => $id])->assertOk();
        $this->start($user)->assertStatus(429);
        Mail::assertQueued(LoginOtpMail::class, 2);
    }

    public function test_account_hourly_limit_cannot_be_bypassed_by_changing_ip_or_using_resend(): void
    {
        Mail::fake();
        $user = $this->user();
        for ($send = 1; $send <= 5; $send++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$send}"]);
            $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
            $this->travel(61)->seconds();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.6']);
        $this->start($user)->assertStatus(429)->assertJsonPath('code', 'login_otp_rate_limited');
        $this->postJson('/api/v1/auth/login/resend-otp', ['challenge_id' => $id])
            ->assertStatus(429)->assertJsonPath('code', 'login_otp_rate_limited');
        Mail::assertQueued(LoginOtpMail::class, 5);
    }

    public function test_ip_hourly_limit_applies_across_accounts(): void
    {
        Mail::fake();
        for ($send = 1; $send <= 5; $send++) {
            $this->start($this->user("ip{$send}@example.com"))->assertStatus(202);
        }
        $this->start($this->user('ip6@example.com'))->assertStatus(429)->assertJsonPath('code', 'login_otp_rate_limited');
        Mail::assertQueued(LoginOtpMail::class, 5);
    }

    public function test_password_login_invalidates_outstanding_codes_without_sending_mail(): void
    {
        Mail::fake();
        $user = $this->user();
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $otp = $this->queuedOtp();
        Mail::fake();
        $this->passwordLogin($user)->assertOk();
        $tokenId = $user->tokens()->firstOrFail()->id;
        $this->verify($id, $otp)->assertUnprocessable()->assertJsonPath('code', 'login_challenge_invalid');
        $this->assertSame($tokenId, $user->tokens()->firstOrFail()->id);
        $this->postJson('/api/v1/auth/login/resend-otp', ['challenge_id' => $id])->assertUnprocessable();
        Mail::assertNothingOutgoing();
    }

    public function test_new_requests_replace_challenges_and_verification_rechecks_eligibility(): void
    {
        Mail::fake();
        $user = $this->user();
        $first = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $oldOtp = $this->queuedOtp();
        $this->travel(61)->seconds();
        Mail::fake();
        $second = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $otp = $this->queuedOtp();
        $this->verify($first, $oldOtp)->assertUnprocessable()->assertJsonPath('code', 'login_challenge_invalid');
        $user->update(['email_verified_at' => null]);
        $this->verify($second, $otp)->assertForbidden();
        $this->postJson('/api/v1/auth/login/resend-otp', ['challenge_id' => $second])->assertForbidden();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_malformed_unknown_and_exact_expiry_codes_are_rejected(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/auth/login/send-otp', ['email' => 'not-email'])->assertUnprocessable();
        $this->verify('bad-id', '123456')->assertUnprocessable();
        $this->verify((string) Str::uuid(), '123456')->assertUnprocessable();
        $user = $this->user();
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $otp = $this->queuedOtp();
        LoginChallenge::where('challenge_id', $id)->update(['expires_at' => now()]);
        $this->verify($id, $otp)->assertUnprocessable()->assertJsonPath('code', 'login_otp_expired');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_enqueue_failure_is_safe_and_password_login_still_works(): void
    {
        $user = $this->user();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('queue')->once()->andThrow(new RuntimeException('Private transport details'));
        $response = $this->start($user)->assertStatus(503)->assertJsonPath('code', 'login_otp_delivery_unavailable');
        $this->assertStringNotContainsString('Private transport details', $response->getContent());
        $this->assertNotNull(LoginChallenge::firstOrFail()->invalidated_at);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->passwordLogin($user)->assertOk();
    }

    public function test_database_queue_enqueues_mail_without_delivering_or_authenticating(): void
    {
        config(['queue.default' => 'database', 'mail.default' => 'array', 'queue.connections.database.connection' => 'sqlite']);
        $this->start($this->user())->assertStatus(202);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $payload = json_decode(DB::table('jobs')->value('payload'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(LoginOtpMail::class, $payload['displayName']);
    }

    public function test_email_reverification_invalidates_old_login_challenges(): void
    {
        Mail::fake();
        $user = $this->user();
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $oldOtp = $this->queuedOtp();
        $user->update(['email' => 'changed@example.com', 'email_verified_at' => null]);
        OtpVerification::create(['type' => 'email_verification', 'channel' => 'email', 'recipient' => $user->email,
            'otp' => Hash::make('123456'), 'expires_at' => now()->addMinutes(5)]);
        $this->postJson('/api/v1/auth/email-verification/verify', ['email' => $user->email, 'otp' => '123456'])->assertOk();
        $this->verify($id, $oldOtp)->assertUnprocessable()->assertJsonPath('code', 'login_challenge_invalid');
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_password_requests_are_throttled_per_visitor_ip(): void
    {
        Mail::fake();
        $user = $this->user();
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->passwordLogin($user)->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);
        $this->passwordLogin($user)->assertOk();
        Mail::assertNothingOutgoing();
    }

    public function test_only_configured_frontend_proxies_can_forward_visitor_ips(): void
    {
        Mail::fake();
        $user = $this->user();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->withHeader('X-Forwarded-For', '203.0.113.10');
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $this->assertSame('203.0.113.10', LoginChallenge::where('challenge_id', $id)->value('ip_address'));
        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10']);
        $id = $this->start($user)->assertStatus(202)->json('data.challenge_id');
        $this->assertSame('198.51.100.10', LoginChallenge::where('challenge_id', $id)->value('ip_address'));
    }

    public function test_signup_verification_cannot_bypass_staff_password_activation(): void
    {
        Mail::fake();
        $user = $this->user('staff-activation@example.com');
        $user->update(['email_verified_at' => null]);
        app(AccountInvitationService::class)->invite($user);
        $token = '';
        Mail::assertQueued(StaffInvitationMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });
        OtpVerification::create(['type' => 'email_verification', 'channel' => 'email', 'recipient' => $user->email,
            'otp' => Hash::make('123456'), 'expires_at' => now()->addMinutes(5)]);
        $this->postJson('/api/v1/auth/email-verification/verify', ['email' => $user->email, 'otp' => '123456'])
            ->assertForbidden()->assertJsonPath('code', 'account_activation_required');
        $this->postJson('/api/v1/auth/verify-otp', ['recipient' => $user->email, 'channel' => 'email',
            'type' => 'email_verification', 'otp' => '123456'])->assertForbidden();
        $this->assertNull($user->refresh()->email_verified_at);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/auth/activate-account', ['email' => $user->email, 'token' => $token,
            'password' => 'EmployeePassword@123', 'password_confirmation' => 'EmployeePassword@123'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'EmployeePassword@123'])->assertOk();
    }

    private function user(string $email = 'login@example.com'): User
    {
        return User::create(['first_name' => 'Test', 'last_name' => 'Login', 'username' => (string) Str::uuid(),
            'email' => $email, 'mobile' => '9'.random_int(100000000, 999999999), 'password' => 'Password@123',
            'is_active' => true, 'email_verified_at' => now()]);
    }

    private function passwordLogin(User $user)
    {
        return $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password@123']);
    }

    private function start(User $user)
    {
        return $this->postJson('/api/v1/auth/login/send-otp', ['email' => strtoupper($user->email)]);
    }

    private function verify(string $id, string $otp)
    {
        return $this->postJson('/api/v1/auth/login/verify-otp', ['challenge_id' => $id, 'otp' => $otp]);
    }

    private function queuedOtp(): string
    {
        $otp = '';
        Mail::assertQueued(LoginOtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        return $otp;
    }
}
