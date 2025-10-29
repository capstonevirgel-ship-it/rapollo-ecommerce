<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Rapollo</title>
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
        
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .subtitle {
            font-size: 16px;
            color: #71717a;
            margin-bottom: 32px;
            text-align: center;
        }
        
        .info-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .info-value {
            color: #18181b;
            font-size: 14px;
            text-align: right;
        }
        
        .failure-reason {
            background-color: #f4f4f5;
            border: 1px solid #d4d4d8;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 32px;
        }
        
        .failure-reason-title {
            font-weight: 600;
            color: #18181b;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .failure-reason-text {
            color: #71717a;
            font-size: 14px;
        }
        
        .action-buttons {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin: 0 8px 8px 0;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: #18181b;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #27272a;
        }
        
        .btn-secondary {
            background-color: #f4f4f5;
            color: #18181b;
            border: 1px solid #d4d4d8;
        }
        
        .btn-secondary:hover {
            background-color: #e4e4e7;
        }
        
        .help-section {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .help-title {
            font-weight: 600;
            color: #18181b;
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .help-text {
            color: #374151;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .help-list {
            color: #374151;
            font-size: 14px;
            padding-left: 20px;
        }
        
        .help-list li {
            margin-bottom: 8px;
        }
        
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .footer-links {
            margin-bottom: 16px;
        }
        
        .footer-links a {
            color: #18181b;
            text-decoration: none;
            margin: 0 12px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            margin-top: 16px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #6b7280;
            text-decoration: none;
        }
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .footer {
                padding: 20px;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 8px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">RAPOLLO</div>
            <div class="header-subtitle">Payment Failed Notification</div>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Title -->
            <h1 class="title">Payment Failed</h1>
            <p class="subtitle">We're sorry, but your payment could not be processed.</p>

            <!-- Failure Reason -->
            <div class="failure-reason">
                <div class="failure-reason-title">Reason for Failure:</div>
                <div class="failure-reason-text">{{ $failureReason }}</div>
            </div>

            <!-- Purchase Information -->
            <div class="info-card">
                @if($purchase->type === 'ticket')
                    <div class="info-row">
                        <span class="info-label">Event:</span>
                        <span class="info-value">{{ $purchase->event->title ?? 'Event' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tickets:</span>
                        <span class="info-value">{{ $purchase->purchaseItems->sum('quantity') }} ticket(s)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Price per Ticket:</span>
                        <span class="info-value">₱{{ number_format($purchase->event->ticket_price ?? 0, 2) }}</span>
                    </div>
                @else
                    <div class="info-row">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value">#{{ $purchase->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Items:</span>
                        <span class="info-value">{{ $purchase->purchaseItems->sum('quantity') }} item(s)</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">₱{{ number_format($purchase->total, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">{{ now()->format('M d, Y \a\t g:i A') }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                @if($purchase->type === 'ticket')
                    <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/events" class="btn btn-primary">Try Again</a>
                    <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/my-tickets" class="btn btn-secondary">View My Tickets</a>
                @else
                    <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/shop" class="btn btn-primary">Try Again</a>
                    <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/my-orders" class="btn btn-secondary">View My Orders</a>
                @endif
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <div class="help-title">Need Help?</div>
                <div class="help-text">If you continue to experience payment issues, please try the following:</div>
                <ul class="help-list">
                    <li>Check that your payment method has sufficient funds</li>
                    <li>Verify your billing information is correct</li>
                    <li>Try using a different payment method</li>
                    <li>Contact your bank if the issue persists</li>
                </ul>
                <div class="help-text">
                    For additional support, please contact us at 
                    <a href="mailto:support@rapollo.com" style="color: #18181b;">support@rapollo.com</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                Thank you for choosing RAPOLLO. We appreciate your business and look forward to serving you again.
            </div>
            <div class="footer-links">
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}">Home</a>
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/shop">Shop</a>
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/events">Events</a>
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/contact">Contact</a>
            </div>
            <div class="footer-text">
                © {{ date('Y') }} RAPOLLO. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
