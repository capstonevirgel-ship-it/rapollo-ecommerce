@extends('emails.layouts.base')

@section('title', 'Order Cancelled - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">Order Cancelled</div>
@endsection

@section('content')
    <div style="font-size:18px; font-weight:600; color:#18181b; margin-bottom:16px;">Hello {{ $user->user_name }},</div>
    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563;">
        We're writing to inform you that your order #{{ $purchase->id }} has been cancelled.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:24px 28px; background-color:#f9fafb;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Order Information</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Order Number</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827; font-family:'Courier New', Courier, monospace;">
                                        #{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Status</td>
                                    <td align="right">
                                        <span style="display:inline-block; background-color:#fee2e2; color:#991b1b; padding:6px 16px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                            Cancelled
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563;">
        If you have any questions or concerns about this cancellation, please don't hesitate to contact our customer service team.
    </p>
@endsection

@section('footer')
    <div style="font-size:14px; font-weight:700; letter-spacing:0.12em;">Rapollo</div>
    <div style="margin-top:10px; font-size:12px; font-weight:500; letter-spacing:0.06em; text-transform:uppercase; color:#52525b;">
        Thank you for choosing us for your fashion needs!
    </div>
    <div style="margin-top:18px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#4b5563;">
        Need help? Contact our customer service team at
        <a href="mailto:support@rapollo.com" style="color:#18181b; font-weight:700; text-decoration:none;">support@rapollo.com</a>
    </div>
@endsection

