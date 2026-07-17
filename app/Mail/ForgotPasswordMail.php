<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    /**
     * Mail Subject
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password'
        );
    }

    /**
     * Mail Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.forgot-password',
            with: [
                'user' => $this->user,
                'resetUrl' => config('app.frontend_url')
                    . '/reset-password?token='
                    . $this->token
                    . '&email='
                    . urlencode($this->user->email),
            ]
        );
    }

    /**
     * Attachments
     */
    public function attachments(): array
    {
        return [];
    }
}
