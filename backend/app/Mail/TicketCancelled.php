<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Ticket;
use App\Models\User;

class TicketCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;
    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $user)
    {
        $this->ticket = $ticket;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Cancelled - ' . $this->ticket->ticket_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load necessary relationships for the email template
        $this->ticket->load([
            'event',
            'user'
        ]);

        return new Content(
            html: 'emails.ticket-cancelled',
            text: 'emails.ticket-cancelled-text',
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

