<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\TicketPurchase;
use App\Models\User;

class TicketPurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public TicketPurchase $purchase;
    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(TicketPurchase $purchase, User $user)
    {
        $this->purchase = $purchase;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Ticket Confirmation - Order #' . $this->purchase->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load necessary relationships for the email template
        $this->purchase->load([
            'event',
            'tickets'
        ]);

        return new Content(
            html: 'emails.ticket-purchase-confirmation',
            text: 'emails.ticket-purchase-confirmation-text',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
