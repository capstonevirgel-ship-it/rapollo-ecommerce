@extends('emails.layouts.base')

@section('title', 'New Contact Form Submission - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">New Contact Form Submission</div>
@endsection

@section('content')
    <p style="margin:0 0 24px 0; font-size:15px; color:#4b5563;">
        You have received a new message from the contact form on your website.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:24px; border:1px solid #e5e7eb; border-radius:14px;">
        <tr>
            <td style="padding:24px 28px; background-color:#ffffff;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:14px;">
                            Contact Information
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.16em; color:#6b7280;">Name</td>
                                    <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
                                        {{ $formData['firstName'] }} {{ $formData['lastName'] }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.16em; color:#6b7280;">Email</td>
                                    <td align="right" style="font-size:15px; font-weight:600;">
                                        <a href="mailto:{{ $formData['email'] }}" style="color:#18181b; font-weight:700; text-decoration:none;">{{ $formData['email'] }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if(!empty($formData['phone']))
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.16em; color:#6b7280;">Phone</td>
                                        <td align="right" style="font-size:15px; font-weight:600;">
                                            <a href="tel:{{ $formData['phone'] }}" style="color:#18181b; font-weight:700; text-decoration:none;">{{ $formData['phone'] }}</a>
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
                                    <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.16em; color:#6b7280;">Subject</td>
                                    <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">
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
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" style="border:1px solid #e4e4e7; border-radius:14px; background-color:#f9fafb;">
        <tr>
            <td style="padding:24px 28px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Message</td>
                    </tr>
                    <tr>
                        <td style="font-size:15px; color:#1f2937; line-height:1.8; white-space:pre-wrap; font-weight:500;">
                            {{ $formData['message'] }}
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
        This is an automated notification from your website contact form.
    </div>
@endsection

