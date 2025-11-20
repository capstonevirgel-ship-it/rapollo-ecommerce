@extends('emails.layouts.base')

@section('title', 'Reset Your Password - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">Password Reset Request</div>
@endsection

@section('content')
    <div style="font-size:18px; font-weight:600; color:#18181b; margin-bottom:16px;">Hello {{ $user->user_name ?? 'there' }},</div>
    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563; line-height:1.6;">
        We received a request to reset your password for your Rapollo account. If you didn't make this request, you can safely ignore this email.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden; background-color:#f9fafb;">
        <tr>
            <td style="padding:24px 28px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Reset Instructions</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Email Address</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827;">
                                        {{ $user->email }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Link Expires</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827;">
                                        In 60 minutes
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
        <a href="{{ $url }}" style="display:inline-block; background-color:#18181b; color:#ffffff; text-decoration:none; padding:14px 32px; border-radius:10px; font-weight:700; font-size:15px; letter-spacing:0.08em; text-transform:uppercase; margin:6px;">
            Reset Password
        </a>
    </div>

    <table role="presentation" width="100%" style="border:1px solid #fef3c7; border-radius:14px; background-color:#fffbeb;">
        <tr>
            <td style="padding:20px 24px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="padding-bottom:12px;">
                            <div style="font-size:13px; font-weight:700; color:#92400e; letter-spacing:0.06em; text-transform:uppercase;">
                                Security Notice
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#78350f; line-height:1.6;">
                            <p style="margin:0 0 8px 0;">
                                For your security, this password reset link will expire in 60 minutes. If you didn't request a password reset, please ignore this email or contact our support team if you have concerns.
                            </p>
                            <p style="margin:0;">
                                If the button above doesn't work, you can copy and paste this link into your browser:
                            </p>
                            <p style="margin:8px 0 0 0; word-break:break-all;">
                                <a href="{{ $url }}" style="color:#18181b; font-weight:600; text-decoration:underline;">{{ $url }}</a>
                            </p>
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
        Need help? Contact our support team
    </div>
    <div style="margin-top:18px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#4b5563;">
        Email us at
        <a href="mailto:support@rapollo.com" style="color:#18181b; font-weight:700; text-decoration:none;">support@rapollo.com</a>
    </div>
@endsection

