<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Rapollo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #18181b;
            background-color: #f4f4f5;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .header {
            background: linear-gradient(135deg, #18181b 0%, #27272a 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .header-subtitle {
            font-size: 16px;
            color: #a1a1aa;
            font-weight: 400;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 16px;
        }
        
        .message {
            font-size: 16px;
            color: #52525b;
            margin-bottom: 32px;
            line-height: 1.7;
        }
        
        .order-summary {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .order-number {
            font-size: 20px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 8px;
        }
        
        .order-reference {
            font-size: 14px;
            color: #71717a;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background-color: #f1f5f9;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 16px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-badge::before {
            content: "✓";
            margin-right: 6px;
            font-weight: bold;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-details {
            flex: 1;
            margin-right: 20px;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #18181b;
            margin: 0;
            flex: 1;
        }
        
        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #18181b;
            white-space: nowrap;
            margin-left: 20px;
        }
        
        .product-variant {
            font-size: 14px;
            color: #71717a;
            margin-bottom: 4px;
        }
        
        .product-quantity {
            font-size: 14px;
            color: #71717a;
        }
        
        .total-section {
            background-color: #18181b;
            color: white;
            padding: 24px;
            border-radius: 8px;
            margin: 24px 0;
        }
        
        .total-label {
            font-size: 16px;
            color: #a1a1aa;
            margin-bottom: 8px;
        }
        
        .total-amount {
            font-size: 28px;
            font-weight: 700;
            color: white;
        }
        
        
        .footer {
            background-color: #f8fafc;
            padding: 32px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            color: #71717a;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .footer strong {
            color: #18181b;
            font-weight: 600;
        }
        
        .contact-info {
            color: #71717a;
            font-size: 14px;
            margin-top: 16px;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .header, .content, .footer {
                padding: 24px 20px;
            }
            
            .product-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .product-price {
                margin-left: 0;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Rapollo</div>
            <div class="header-subtitle">Order Confirmation</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hello {{ $user->user_name }},</div>
            
            <div class="message">
                Thank you for your purchase! We're excited to confirm that your order has been successfully placed and payment has been processed.
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="order-number">Order Summary</div>
                <div class="order-reference">{{ $purchase->reference_number }}</div>
                <div>
                    <span class="status-badge">Payment Confirmed</span>
                </div>
            </div>

            <!-- Order Details -->
            <div class="section-title">Order Details</div>
            @foreach($purchase->items as $item)
                <div class="product-item">
                    <div class="product-details">
                        <div class="product-header">
                            <div class="product-name">{{ $item->variant->product->name ?? 'Product' }}</div>
                            <div class="product-price">₱{{ number_format($item->price * $item->quantity, 2) }}</div>
                        </div>
                        @if($item->variant)
                            <div class="product-variant">
                                @if($item->variant->color)
                                    Color: {{ $item->variant->color->name }}
                                @endif
                                @if($item->variant->size)
                                    @if($item->variant->color) - @endif
                                    Size: {{ $item->variant->size->name }}
                                @endif
                            </div>
                        @endif
                        <div class="product-quantity">Quantity: {{ $item->quantity }}</div>
                    </div>
                </div>
            @endforeach

            <!-- Total -->
            <div class="total-section">
                <div class="total-label">Total Amount</div>
                <div class="total-amount">₱{{ number_format($purchase->total_amount, 2) }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Rapollo E-commerce</strong></p>
            <p>Thank you for choosing us for your fashion needs!</p>
            <div class="contact-info">
                <p>Need help? Contact our customer service team at support@rapollo.com</p>
            </div>
        </div>
    </div>
</body>
</html>