Rapollo Events - Event Ticket Confirmation

Hello {{ $user->user_name }},

Thank you for purchasing event tickets! Your tickets have been successfully confirmed and payment has been processed.

Purchase #{{ $purchase->reference_number }}
Status: Tickets Confirmed

@if($purchase->event)
Event Information:
- Event: {{ $purchase->event->title }}
- Date: {{ \Carbon\Carbon::parse($purchase->event->date)->format('F j, Y \a\t g:i A') }}
- Location: {{ $purchase->event->location }}
@if($purchase->event->description)
- Description: {{ $purchase->event->description }}
@endif
@endif

Your Tickets:
@foreach($purchase->tickets as $ticket)
- Ticket #{{ $ticket->ticket_number }}
  Type: General Admission
  Valid for: {{ $purchase->event->title ?? 'Event' }}
  Price: ₱{{ number_format($ticket->price, 2) }}
@endforeach

Total Paid: ₱{{ number_format($purchase->total_amount, 2) }}

Important Information:
- Please arrive 30 minutes before the event starts
- Bring a valid ID for ticket verification
- Tickets are non-refundable but transferable
- Keep this confirmation email for your records

If you have any questions about your tickets, please contact our events team.

Rapollo Events
Events Support: events@rapollo.com
