<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Purchase;
use App\Models\User;

class PaymentFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Purchase $purchase;
    public User $user;
    public string $failureReason;
    public string $failureCode;

    /**
     * Create a new message instance.
     */
    public function __construct(Purchase $purchase, User $user, string $failureReason = 'Payment checkout has expired', string $failureCode = 'CLOSED')
    {
        $this->purchase = $purchase;
        $this->user = $user;
        $this->failureReason = $failureReason;
        $this->failureCode = $failureCode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->purchase->type === 'ticket' 
            ? 'Payment Failed - Event Tickets' 
            : 'Payment Failed - Order #' . $this->purchase->id;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load necessary relationships for the email template
        if ($this->purchase->type === 'ticket') {
            $this->purchase->load(['event']);
        } else {
            $this->purchase->load([
                'items.variant.product.images',
                'items.variant.color',
                'items.variant.size'
            ]);
        }

        return new Content(
            html: 'emails.payment-failure-notification',
            text: 'emails.payment-failure-notification-text',
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
