PAYMENT FAILED - RAPOLLO

Dear {{ $user->user_name ?? $user->email }},

We're sorry to inform you that your payment could not be processed.

REASON FOR FAILURE:
{{ $failureReason }}

@if($purchase->type === 'ticket')
EVENT DETAILS:
- Event: {{ $purchase->event->title ?? 'Event' }}
- Tickets: {{ $purchase->purchaseItems->sum('quantity') }} ticket(s)
- Price per Ticket: ₱{{ number_format($purchase->event->ticket_price ?? 0, 2) }}
@else
ORDER DETAILS:
- Order Number: #{{ $purchase->id }}
- Items: {{ $purchase->purchaseItems->sum('quantity') }} item(s)
@endif
- Total Amount: ₱{{ number_format($purchase->total, 2) }}
- Payment Date: {{ now()->format('M d, Y \a\t g:i A') }}

WHAT TO DO NEXT:

@if($purchase->type === 'ticket')
1. Try purchasing your tickets again: {{ config('app.frontend_url', 'http://localhost:3000') }}/events
2. View your existing tickets: {{ config('app.frontend_url', 'http://localhost:3000') }}/my-tickets
@else
1. Try placing your order again: {{ config('app.frontend_url', 'http://localhost:3000') }}/shop
2. View your existing orders: {{ config('app.frontend_url', 'http://localhost:3000') }}/my-orders
@endif

NEED HELP?

If you continue to experience payment issues, please try the following:
- Check that your payment method has sufficient funds
- Verify your billing information is correct
- Try using a different payment method
- Contact your bank if the issue persists

For additional support, please contact us at support@rapollo.com

Thank you for choosing RAPOLLO. We appreciate your business and look forward to serving you again.

---
RAPOLLO
{{ config('app.frontend_url', 'http://localhost:3000') }}

© {{ date('Y') }} RAPOLLO. All rights reserved.
