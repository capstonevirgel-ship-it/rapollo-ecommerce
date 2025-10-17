Rapollo E-commerce - Order Confirmation

Hello {{ $user->user_name }},

Thank you for your purchase! We're excited to confirm that your order has been successfully placed and payment has been processed.

Order #{{ $purchase->reference_number }}
Status: Payment Confirmed

Order Details:
@foreach($purchase->items as $item)
- {{ $item->product->name ?? 'Product' }}
  @if($item->variant)
    Variant: {{ $item->variant->color->name ?? 'N/A' }}
    @if($item->variant->size)
    - Size: {{ $item->variant->size->name }}
    @endif
  @endif
  Quantity: {{ $item->quantity }}
  Price: ₱{{ number_format($item->price * $item->quantity, 2) }}
@endforeach

Total: ₱{{ number_format($purchase->total_amount, 2) }}

Next Steps:
- Your order is being prepared for shipment
- You will receive a tracking number once your order ships
- Expected delivery time: 3-5 business days

If you have any questions about your order, please contact our customer service team.

Rapollo E-commerce
Customer Service: support@rapollo.com
