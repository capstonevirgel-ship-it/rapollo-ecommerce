Ticket Cancelled - Rapollo

Hello {{ $user->user_name }},

We're writing to inform you that your ticket {{ $ticket->ticket_number }} has been cancelled.

Ticket Number: {{ $ticket->ticket_number }}
Status: Cancelled

@if($ticket->event)
Event Information:
- Event: {{ $ticket->event->title }}
- Date: {{ $ticket->event->date ? \Carbon\Carbon::parse($ticket->event->date)->format('F j, Y \a\t g:i A') : 'To be announced' }}
- Location: {{ $ticket->event->location ?? 'To be announced' }}
@endif

If you have any questions or concerns about this cancellation, please don't hesitate to contact our events team.

Need help? Contact our events team at events@rapollo.com

Thank you for choosing Rapollo!

