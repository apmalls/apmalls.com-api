<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Activate your AP Malls account');
    }

    public function content(): Content
    {
        $activationUrl = rtrim((string) config('app.frontend_url'), '/')
            . '/activate-account?token=' . urlencode($this->token)
            . '&email=' . urlencode($this->user->email);

        return new Content(
            markdown: 'emails.auth.staff-invitation',
            with: ['user' => $this->user, 'activationUrl' => $activationUrl],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
