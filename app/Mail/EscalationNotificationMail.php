<?php

namespace App\Mail;

use App\Models\EscalationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EscalationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EscalationNotification $notification,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket Escalated: {$this->notification->ticket->subject}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.escalation_notification',
        );
    }
}
