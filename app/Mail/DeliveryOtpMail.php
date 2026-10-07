<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DeliveryOtpMail extends Mailable
{
    public function __construct(
        public string $orderNumber,
        public string $otp,
        public string $expiresAt,
        public float $amountDue,
        public ?string $courierName = null,
        public ?string $courierPhone = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "AP Malls delivery code for {$this->orderNumber}");
    }

    public function content(): Content { return new Content(markdown: 'emails.delivery.otp'); }
}
