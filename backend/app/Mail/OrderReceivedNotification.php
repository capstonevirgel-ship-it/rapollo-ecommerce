<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductPurchase;
use App\Models\User;

class OrderReceivedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public ProductPurchase $purchase;
    public User $customer;

    /**
     * Create a new message instance.
     */
    public function __construct(ProductPurchase $purchase, User $customer)
    {
        $this->purchase = $purchase;
        $this->customer = $customer;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Received by Customer - Order #' . $this->purchase->id,
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
            'items.variant.size',
            'user'
        ]);

        return new Content(
            html: 'emails.order-received-notification',
            text: 'emails.order-received-notification-text',
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

