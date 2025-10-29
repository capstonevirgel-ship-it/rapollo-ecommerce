<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - Rapollo</title>
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
        
        .message {
            font-size: 16px;
            color: #52525b;
            margin-bottom: 32px;
            line-height: 1.7;
        }
        
        .info-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .info-row {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #71717a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 16px;
            color: #18181b;
            font-weight: 500;
        }
        
        .info-value a {
            color: #18181b;
            text-decoration: none;
        }
        
        .info-value a:hover {
            text-decoration: underline;
        }
        
        .message-section {
            background-color: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .message-label {
            font-size: 14px;
            font-weight: 600;
            color: #71717a;
            margin-bottom: 12px;
        }
        
        .message-content {
            font-size: 16px;
            color: #18181b;
            line-height: 1.8;
            white-space: pre-wrap;
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
        
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .header, .content, .footer {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Rapollo</div>
            <div class="header-subtitle">New Contact Form Submission</div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="message">
                You have received a new message from the contact form on your website.
            </div>

            <!-- Contact Information -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Name</div>
                    <div class="info-value">{{ $formData['firstName'] }} {{ $formData['lastName'] }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value">
                        <a href="mailto:{{ $formData['email'] }}">{{ $formData['email'] }}</a>
                    </div>
                </div>
                
                @if(!empty($formData['phone']))
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value">
                        <a href="tel:{{ $formData['phone'] }}">{{ $formData['phone'] }}</a>
                    </div>
                </div>
                @endif
                
                <div class="info-row">
                    <div class="info-label">Subject</div>
                    <div class="info-value">
                        @php
                            $subjectLabels = [
                                'general' => 'General Inquiry',
                                'order' => 'Order Support',
                                'return' => 'Returns & Exchanges',
                                'technical' => 'Technical Support',
                                'partnership' => 'Partnership',
                                'other' => 'Other'
                            ];
                            echo $subjectLabels[$formData['subject']] ?? 'General Inquiry';
                        @endphp
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div class="message-section">
                <div class="message-label">Message:</div>
                <div class="message-content">{{ $formData['message'] }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Rapollo E-commerce</strong></p>
            <p>This is an automated notification from your website contact form.</p>
        </div>
    </div>
</body>
</html>

