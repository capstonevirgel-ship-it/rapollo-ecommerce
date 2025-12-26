Order Processing - Rapollo

Hello {{ $user->user_name }},

Great news! Your order #{{ $purchase->id }} is now being processed. We're preparing your items for shipment.

Order Number: #{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}
Status: Processing

We'll notify you once your order has been shipped. You can track your order status anytime from your account.

Need help? Contact our customer service team at support@rapollo.com

Thank you for choosing Rapollo!

