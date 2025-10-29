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
        
        .ticket-summary {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
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
        
        .event-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
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
        
        /* Ticket Design */
        .ticket-container {
            margin-bottom: 32px;
        }
        
        .ticket {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 0;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .ticket::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #18181b 0%, #27272a 50%, #18181b 100%);
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #18181b 0%, #27272a 100%);
            color: white;
            padding: 20px 24px;
            text-align: center;
            position: relative;
        }
        
        .ticket-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .ticket-subtitle {
            font-size: 14px;
            color: #a1a1aa;
            font-weight: 400;
        }
        
        .ticket-body {
            padding: 24px;
        }
        
        .ticket-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .ticket-info-item {
            text-align: center;
            padding: 12px;
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .ticket-info-label {
            font-size: 12px;
            color: #71717a;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .ticket-info-value {
            font-size: 16px;
            font-weight: 700;
            color: #18181b;
        }
        
        .ticket-number-display {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .ticket-number-label {
            font-size: 12px;
            color: #71717a;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .ticket-number-value {
            font-size: 24px;
            font-weight: 700;
            color: #18181b;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background-color: #f1f5f9;
            padding: 12px 20px;
            border-radius: 8px;
            display: inline-block;
            border: 2px dashed #cbd5e1;
        }
        
        .qr-section {
            text-align: center;
            margin-top: 20px;
        }
        
        .qr-placeholder {
            width: 120px;
            height: 120px;
            background-color: #f1f5f9;
            border: 3px dashed #cbd5e1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
        }
        
        .qr-label {
            font-size: 12px;
            color: #71717a;
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
            
            .ticket-info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .ticket-number-value {
                font-size: 20px;
                padding: 10px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Rapollo</div>
            <div class="header-subtitle">Event Ticket Confirmation</div>
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
            <div class="ticket-container">
                @foreach($purchase->tickets as $ticket)
                    <div class="ticket">
                        <div class="ticket-header">
                            <div class="ticket-title">EVENT TICKET</div>
                            <div class="ticket-subtitle">{{ $purchase->event->title ?? 'Event' }}</div>
                        </div>
                        
                        <div class="ticket-body">
                            <div class="ticket-info-grid">
                                <div class="ticket-info-item">
                                    <div class="ticket-info-label">Date</div>
                                    <div class="ticket-info-value">{{ \Carbon\Carbon::parse($purchase->event->date)->format('M j, Y') }}</div>
                                </div>
                                <div class="ticket-info-item">
                                    <div class="ticket-info-label">Time</div>
                                    <div class="ticket-info-value">{{ \Carbon\Carbon::parse($purchase->event->date)->format('g:i A') }}</div>
                                </div>
                                <div class="ticket-info-item">
                                    <div class="ticket-info-label">Location</div>
                                    <div class="ticket-info-value">{{ $purchase->event->location ?? 'TBA' }}</div>
                                </div>
                                <div class="ticket-info-item">
                                    <div class="ticket-info-label">Price</div>
                                    <div class="ticket-info-value">₱{{ number_format($ticket->price, 2) }}</div>
                                </div>
                            </div>
                            
                            <div class="ticket-number-display">
                                <div class="ticket-number-label">Ticket Number</div>
                                <div class="ticket-number-value">#{{ $ticket->ticket_number }}</div>
                            </div>
                            
                            <div class="qr-section">
                                <div class="qr-placeholder">
                                    QR Code
                                </div>
                                <div class="qr-label">Present this QR code at the entrance</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

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
            <p><strong>Rapollo</strong></p>
            <p>Thank you for choosing us for your entertainment needs!</p>
            <div class="contact-info">
                <p>Need help? Contact our events team at events@rapollo.com</p>
            </div>
        </div>
    </div>
</body>
</html>