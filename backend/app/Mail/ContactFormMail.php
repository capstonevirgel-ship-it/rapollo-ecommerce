<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $formData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $formData)
    {
        $this->formData = $formData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->formData['subject'] ?? 'General Inquiry';
        $subjectLabels = [
            'general' => 'General Inquiry',
            'order' => 'Order Support',
            'return' => 'Returns & Exchanges',
            'technical' => 'Technical Support',
            'partnership' => 'Partnership',
            'other' => 'Other'
        ];
        
        $subjectText = $subjectLabels[$subject] ?? 'General Inquiry';
        
        return new Envelope(
            subject: 'New Contact Form Submission: ' . $subjectText,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.contact-form',
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

