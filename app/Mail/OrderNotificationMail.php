<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderNotificationMail extends Mailable
{
    public function __construct(public string $event, public string $audience, public array $details) {}

    public function envelope(): Envelope
    {
        $label = $this->event === 'order_placed' ? 'New order' : 'Delivery completed';
        return new Envelope(subject: "AP Malls {$label}: {$this->details['order_number']}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.order-notification');
    }
}
