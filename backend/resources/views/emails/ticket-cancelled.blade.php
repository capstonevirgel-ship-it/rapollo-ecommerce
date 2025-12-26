@extends('emails.layouts.base')

@section('title', 'Ticket Cancelled - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">Ticket Cancelled</div>
@endsection

@section('content')
    @php
        $event = $ticket->event;
        $formatCurrency = fn ($value) => number_format((float) $value, 2);

        $formattedEventDate = null;
        $formattedEventTime = null;
        if ($event?->date) {
            $eventDate = \Carbon\Carbon::parse($event->date);
            $formattedEventDate = $eventDate->format('F j, Y');
            $formattedEventTime = $eventDate->format('g:i A');
        }
    @endphp

    <div style="font-size:18px; font-weight:600; color:#18181b; margin-bottom:16px;">Hello {{ $user->user_name }},</div>
    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563;">
        We're writing to inform you that your ticket {{ $ticket->ticket_number }} has been cancelled.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:24px 28px; background-color:#f9fafb;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Ticket Information</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Ticket Number</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827; font-family:'Courier New', Courier, monospace;">
                                        {{ $ticket->ticket_number }}
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

    @if($event)
        <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e5e7eb; border-radius:14px;">
            <tr>
                <td style="padding:22px 26px; background-color:#ffffff;">
                    <table role="presentation" width="100%" style="font-size:14px; color:#4b5563;">
                        <tr>
                            <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:14px;">Event Information</td>
                        </tr>
                        <tr>
                            <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#6b7280;">Event</td>
                                        <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">{{ $event->title }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        @if($formattedEventDate)
                            <tr>
                                <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                    <table role="presentation" width="100%">
                                        <tr>
                                            <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#6b7280;">Date</td>
                                            <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">{{ $formattedEventDate }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif
                        @if($formattedEventTime)
                            <tr>
                                <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                    <table role="presentation" width="100%">
                                        <tr>
                                            <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#6b7280;">Time</td>
                                            <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">{{ $formattedEventTime }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif
                        @if($event->location)
                            <tr>
                                <td style="padding:14px 0; border-top:1px solid #e5e7eb;">
                                    <table role="presentation" width="100%">
                                        <tr>
                                            <td style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.18em; color:#6b7280;">Location</td>
                                            <td align="right" style="font-size:15px; font-weight:600; color:#18181b;">{{ $event->location }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563;">
        If you have any questions or concerns about this cancellation, please don't hesitate to contact our events team.
    </p>
@endsection

@section('footer')
    <div style="font-size:14px; font-weight:700; letter-spacing:0.12em;">Rapollo</div>
    <div style="margin-top:10px; font-size:12px; font-weight:500; letter-spacing:0.06em; text-transform:uppercase; color:#52525b;">
        Thank you for choosing us for your entertainment needs!
    </div>
    <div style="margin-top:18px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#4b5563;">
        Need help? Contact our events team at
        <a href="mailto:events@rapollo.com" style="color:#18181b; font-weight:700; text-decoration:none;">events@rapollo.com</a>
    </div>
@endsection

