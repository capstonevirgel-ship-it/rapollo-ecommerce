<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Ticket Confirmation - Rapollo</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
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
            color: #fecaca;
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
        
        .ticket-summary {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .ticket-number {
            font-size: 20px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 8px;
        }
        
        .ticket-reference {
            font-size: 14px;
            color: #71717a;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background-color: #f8fafc;
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
        
        .event-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .event-info {
            display: grid;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
            min-width: 80px;
            margin-right: 12px;
        }
        
        .info-value {
            color: #52525b;
            flex: 1;
        }
        
        .ticket-item {
            background-color: #ffffff;
            border: 2px solid #dc2626;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .ticket-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .ticket-number-large {
            font-size: 18px;
            font-weight: 700;
            color: #18181b;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        
        .ticket-price {
            font-size: 20px;
            font-weight: 700;
            color: #dc2626;
        }
        
        .ticket-details {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .ticket-detail {
            font-size: 14px;
            color: #71717a;
        }
        
        .qr-placeholder {
            width: 80px;
            height: 80px;
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 16px auto 0;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
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
        
        .important-info {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 24px;
            margin: 32px 0;
        }
        
        .important-info h3 {
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .important-info ul {
            list-style: none;
            padding: 0;
        }
        
        .important-info li {
            color: #92400e;
            font-size: 14px;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .important-info li::before {
            content: "⚠";
            position: absolute;
            left: 0;
            color: #f59e0b;
            font-weight: bold;
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
            
            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .ticket-price {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Rapollo Events</div>
            <div class="header-subtitle">Ticket Confirmation</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hello {{ $user->user_name }},</div>
            
            <div class="message">
                Thank you for purchasing event tickets! Your tickets have been successfully confirmed and payment has been processed.
            </div>

            <!-- Ticket Summary -->
            <div class="ticket-summary">
                <div class="ticket-number">Ticket Confirmation</div>
                <div class="ticket-reference">{{ $purchase->reference_number }}</div>
                <div>
                    <span class="status-badge">Tickets Confirmed</span>
                </div>
            </div>

            @if($purchase->event)
            <!-- Event Information -->
            <div class="event-details">
                <div class="section-title">Event Information</div>
                <div class="event-info">
                    <div class="info-item">
                        <div class="info-label">Event:</div>
                        <div class="info-value">{{ $purchase->event->title }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date:</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($purchase->event->date)->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Location:</div>
                        <div class="info-value">{{ $purchase->event->location }}</div>
                    </div>
                    @if($purchase->event->description)
                    <div class="info-item">
                        <div class="info-label">About:</div>
                        <div class="info-value">{{ $purchase->event->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Tickets -->
            <div class="section-title">Your Tickets</div>
            @foreach($purchase->tickets as $ticket)
                <div class="ticket-item">
                    <div class="ticket-header">
                        <div class="ticket-number-large">#{{ $ticket->ticket_number }}</div>
                        <div class="ticket-price">₱{{ number_format($ticket->price, 2) }}</div>
                    </div>
                    <div class="ticket-details">
                        <div class="ticket-detail">Type: General Admission</div>
                        <div class="ticket-detail">Valid for: {{ $purchase->event->title ?? 'Event' }}</div>
                        <div class="ticket-detail">Status: Confirmed</div>
                    </div>
                    <div class="qr-placeholder">
                        QR Code
                    </div>
                </div>
            @endforeach

            <!-- Total -->
            <div class="total-section">
                <div class="total-label">Total Paid</div>
                <div class="total-amount">₱{{ number_format($purchase->total_amount, 2) }}</div>
            </div>

            <!-- Important Information -->
            <div class="important-info">
                <h3>Important Information</h3>
                <ul>
                    <li>Please arrive 30 minutes before the event starts</li>
                    <li>Bring a valid ID for ticket verification</li>
                    <li>Tickets are non-refundable but transferable</li>
                    <li>Keep this confirmation email for your records</li>
                    <li>Show this email or the QR code at the entrance</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Rapollo Events</strong></p>
            <p>Thank you for choosing us for your entertainment needs!</p>
            <div class="contact-info">
                <p>Need help? Contact our events team at events@rapollo.com</p>
            </div>
        </div>
    </div>
</body>
</html>