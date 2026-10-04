<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\StaffInvitationMail;
use App\Models\Auth\UserInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountInvitationService
{
    public function invite(User $user, ?User $inviter = null): UserInvitation
    {
        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => ['This account is already activated.'],
            ]);
        }

        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($user, $inviter, $token) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            UserInvitation::query()
                ->where('user_id', $user->id)
                ->whereNull('accepted_at')
                ->delete();

            return UserInvitation::create([
                'user_id' => $user->id,
                'invited_by' => $inviter?->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDay(),
                'sent_at' => now(),
            ]);
        });

        DB::afterCommit(function () use ($user, $token): void {
            Mail::to($user->email)->queue(new StaffInvitationMail($user, $token));
        });

        return $invitation;
    }

    public function accept(string $email, string $token, string $password): User
    {
        return DB::transaction(function () use ($email, $token, $password) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw $this->invalidInvitation();
            }

            if ($user->email_verified_at !== null) {
                throw $this->invalidInvitation();
            }

            $invitation = UserInvitation::query()
                ->where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $token))
                ->whereNull('accepted_at')
                ->lockForUpdate()
                ->first();

            if (! $invitation || $invitation->expires_at->isPast()) {
                throw $this->invalidInvitation();
            }

            $user->forceFill([
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ])->save();
            $user->tokens()->delete();

            $invitation->update(['accepted_at' => now()]);

            UserInvitation::query()
                ->where('user_id', $user->id)
                ->whereKeyNot($invitation->id)
                ->whereNull('accepted_at')
                ->delete();

            return $user->refresh();
        });
    }

    private function invalidInvitation(): ValidationException
    {
        return ValidationException::withMessages([
            'token' => ['This activation link is invalid or has expired.'],
        ]);
    }
}
