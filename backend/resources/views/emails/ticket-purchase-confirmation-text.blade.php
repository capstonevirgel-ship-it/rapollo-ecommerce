@php
    $event = $purchase->event;
    $tickets = collect($purchase->tickets ?? []);

    $ticketTotals = $tickets->map(function ($ticket) {
        $priceCandidates = [
            $ticket->final_price ?? null,
            $ticket->price ?? null,
        ];

        $price = 0.0;
        foreach ($priceCandidates as $candidate) {
            if ($candidate !== null) {
                $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                if (is_finite($numeric)) {
                    $price = $numeric;
                    break;
                }
            }
        }

        $quantity = (int) ($ticket->quantity ?? 1);
        $quantity = max($quantity, 1);

        return [
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
        ];
    });

    $calculatedTotal = $ticketTotals->sum('total');

    $overallTotal = $calculatedTotal > 0 ? $calculatedTotal : null;

    if ($overallTotal === null) {
        foreach ([$purchase->total ?? null, $purchase->total_amount ?? null] as $candidate) {
            if ($candidate !== null) {
                $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                if (is_finite($numeric) && $numeric > 0) {
                    $overallTotal = $numeric;
                    break;
                }
            }
        }
    }

    $formatCurrency = fn ($value) => number_format((float) $value, 2);
@endphp

RAPOLLO - Event Ticket Confirmation

Hello {{ $user->user_name }},

Thank you for purchasing event tickets! Your tickets have been successfully confirmed and payment has been processed.

Purchase #{{ $purchase->reference_number }}
Status: Tickets Confirmed

@if($event)
Event Information:
- Event: {{ $event->title }}
- Date: {{ $event->date ? \Carbon\Carbon::parse($event->date)->format('F j, Y \a\t g:i A') : 'To be announced' }}
- Location: {{ $event->location ?? 'To be announced' }}
@if($event->description)
- Description: {{ $event->description }}
@endif
@endif

Your Tickets:
@foreach($tickets as $index => $ticket)
@php $line = $ticketTotals[$index] ?? ['price' => 0, 'quantity' => 1, 'total' => 0]; @endphp
- Ticket #{{ $ticket->ticket_number ?? ($purchase->reference_number . '-' . ($loop->iteration)) }}
  Valid for: {{ $event->title ?? 'Event' }}
  Quantity: {{ $line['quantity'] }}
  Price (each): ₱{{ $formatCurrency($line['price']) }}
  Line Total: ₱{{ $formatCurrency($line['total']) }}
@endforeach

Total Paid: ₱{{ $formatCurrency($overallTotal) }}

Important Information:
- Please arrive 30 minutes before the event starts
- Bring a valid ID for ticket verification
- Tickets are non-refundable but transferable
- Keep this confirmation email for your records

If you have any questions about your tickets, please contact our events team.

RAPOLLO
Events Support: events@rapollo.com
