<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductPurchase;
use App\Models\User;

class OrderDelivered extends Mailable
{
    use Queueable, SerializesModels;

    public ProductPurchase $purchase;
    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(ProductPurchase $purchase, User $user)
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
            subject: 'Order Delivered - Order #' . $this->purchase->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load necessary relationships for the email template
        $this->purchase->load([
            'items.variant.product',
            'items.variant.color',
            'items.variant.size'
        ]);

        return new Content(
            html: 'emails.order-delivered',
            text: 'emails.order-delivered-text',
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

