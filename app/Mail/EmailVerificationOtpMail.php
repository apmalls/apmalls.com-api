<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your AP Malls email');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.verify-email',
            with: ['user' => $this->user, 'otp' => $this->otp],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
