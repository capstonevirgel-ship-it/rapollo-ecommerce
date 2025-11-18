@extends('emails.layouts.base')

@section('title', 'Payment Failed - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">Payment Failed Notification</div>
@endsection

@section('content')
    <div style="text-align:center; margin-bottom:20px;">
        <div style="font-size:20px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:#b91c1c;">Payment Failed</div>
        <div style="margin-top:8px; font-size:15px; color:#4b5563;">We're sorry, but your payment could not be processed.</div>
    </div>

    <table role="presentation" width="100%" style="margin-bottom:24px; border:1px solid #fee2e2; border-radius:14px; background-color:#fef2f2;">
        <tr>
            <td style="padding:22px 26px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#b45309;">Reason for Failure</td>
                    </tr>
                    <tr>
                        <td style="padding-top:10px; font-size:15px; color:#7f1d1d; font-weight:600; line-height:1.7;">
                            {{ $failureReason }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e5e7eb; border-radius:14px;">
        <tr>
            <td style="padding:24px 28px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:14px;">Payment Information</td>
                    </tr>
                    @if($purchase->type === 'ticket')
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Event</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                            {{ $purchase->event->title ?? 'Event' }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Tickets</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                            {{ $purchase->purchaseItems->sum('quantity') }} ticket(s)
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Price per Ticket</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                            ₱{{ number_format($purchase->event->ticket_price ?? 0, 2) }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Order Number</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                            #{{ $purchase->id }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Items</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                            {{ $purchase->purchaseItems->sum('quantity') }} item(s)
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Total Amount</td>
                                    <td align="right" style="font-size:15px; font-weight:700; color:#18181b;">
                                        ₱{{ number_format($purchase->total, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; font-weight:600; letter-spacing:0.16em; text-transform:uppercase; color:#6b7280;">Payment Date</td>
                                    <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                        {{ now()->format('M d, Y \a\t g:i A') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-bottom:30px;">
        @if($purchase->type === 'ticket')
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/events" style="display:inline-block; background-color:#18181b; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:10px; font-weight:700; font-size:14px; letter-spacing:0.08em; text-transform:uppercase; margin:6px;">Try Again</a>
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/my-tickets" style="display:inline-block; background-color:#f4f4f5; color:#18181b; text-decoration:none; padding:12px 24px; border-radius:10px; font-weight:700; font-size:14px; letter-spacing:0.08em; text-transform:uppercase; margin:6px; border:1px solid #d4d4d8;">View My Tickets</a>
        @else
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/shop" style="display:inline-block; background-color:#18181b; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:10px; font-weight:700; font-size:14px; letter-spacing:0.08em; text-transform:uppercase; margin:6px;">Try Again</a>
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/my-orders" style="display:inline-block; background-color:#f4f4f5; color:#18181b; text-decoration:none; padding:12px 24px; border-radius:10px; font-weight:700; font-size:14px; letter-spacing:0.08em; text-transform:uppercase; margin:6px; border:1px solid #d4d4d8;">View My Orders</a>
        @endif
    </div>

    <table role="presentation" width="100%" style="border:1px solid #f59e0b; border-radius:14px; background-color:#fef3c7;">
        <tr>
            <td style="padding:22px 26px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="font-size:15px; font-weight:700; color:#92400e; margin:0; padding-bottom:10px;">
                            Need Help?
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#92400e; line-height:1.7;">
                            If you continue to experience payment issues, please try the following:
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:14px;">
                            <table role="presentation" width="100%" style="font-size:14px; color:#92400e;">
                                <tr>
                                    <td style="padding:4px 0;">• Check that your payment method has sufficient funds</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;">• Verify your billing information is correct</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;">• Try using a different payment method</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;">• Contact your bank if the issue persists</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:16px; font-size:14px; color:#7c2d12;">
                            For additional support, please contact us at
                            <a href="mailto:support@rapollo.com" style="color:#7c2d12; font-weight:700; text-decoration:none;">support@rapollo.com</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection

@section('footer')
    <div style="font-size:14px; font-weight:700; letter-spacing:0.12em;">Rapollo</div>
    <div style="margin-top:10px; font-size:12px; font-weight:500; letter-spacing:0.06em; text-transform:uppercase; color:#52525b;">
        Thank you for choosing RAPOLLO. We appreciate your business and look forward to serving you again.
    </div>
    <div style="margin-top:16px; font-size:12px; letter-spacing:0.08em;">
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" style="color:#18181b; text-decoration:none; font-weight:700; margin:0 8px;">Home</a>
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/shop" style="color:#18181b; text-decoration:none; font-weight:700; margin:0 8px;">Shop</a>
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/events" style="color:#18181b; text-decoration:none; font-weight:700; margin:0 8px;">Events</a>
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/contact" style="color:#18181b; text-decoration:none; font-weight:700; margin:0 8px;">Contact</a>
    </div>
    <div style="margin-top:14px; font-size:12px; font-weight:500; letter-spacing:0.08em; text-transform:uppercase; color:#52525b;">
        © {{ date('Y') }} Rapollo. All rights reserved.
    </div>
@endsection
