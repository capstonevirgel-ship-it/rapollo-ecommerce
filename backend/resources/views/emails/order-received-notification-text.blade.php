Order Received by Customer - Rapollo

Hello Admin,

The customer has confirmed receipt of their order. Order #{{ $purchase->id }} has been marked as completed.

Order Number: #{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}
Customer: {{ $customer->user_name }} ({{ $customer->email }})
Status: Completed

