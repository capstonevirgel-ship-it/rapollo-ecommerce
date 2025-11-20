@extends('emails.layouts.base')

@section('title', 'Event Ticket Confirmation - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; text-transform:none; letter-spacing:0;">Event Ticket Confirmation</div>
@endsection

@section('content')
    @php
        $event = $purchase->event;
        $tickets = collect($purchase->tickets ?? []);
        $formatCurrency = fn ($value) => number_format((float) $value, 2);

        $ticketTotals = $tickets->map(function ($ticket) {
            $priceCandidates = [
                $ticket->final_price ?? null,
                $ticket->price ?? null,
            ];

            $price = 0.0;
            foreach ($priceCandidates as $candidate) {
                if ($candidate !== null) {
                    $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                    if (is_finite($numeric)) {
                        $price = $numeric;
                        break;
                    }
                }
            }

            $quantity = (int) ($ticket->quantity ?? 1);
            $quantity = max($quantity, 1);

            return [
                'price' => $price,
                'quantity' => $quantity,
                'total' => $price * $quantity,
            ];
        });

        $calculatedTotal = $ticketTotals->sum('total');

        $overallTotal = $calculatedTotal > 0 ? $calculatedTotal : 0.0;

        if ($overallTotal <= 0) {
            $totalCandidateValues = [
                $purchase->total ?? null,
                $purchase->total_amount ?? null,
            ];

            foreach ($totalCandidateValues as $candidate) {
                if ($candidate !== null) {
                    $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                    if (is_finite($numeric) && $numeric > 0) {
                        $overallTotal = $numeric;
                        break;
                    }
                }
            }
        }

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
        Thank you for purchasing event tickets! Your tickets have been successfully confirmed and payment has been processed.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:24px 28px; background-color:#f9fafb;">
                <table role="presentation" width="100%" style="font-size:14px; color:#4b5563;">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.08em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Ticket Confirmation</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Order Number</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827; font-family:'Courier New', Courier, monospace;">TKT-{{ $purchase->id }}</td>
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
                                        <span style="display:inline-block; background-color:#dcfce7; color:#166534; padding:6px 16px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:0.08em;">
                                            Confirmed
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

    <div style="text-transform:uppercase; letter-spacing:0.08em; font-size:16px; font-weight:700; color:#18181b; margin-bottom:18px;">Your Tickets</div>

    @foreach($tickets as $index => $ticket)
        @php
            $line = $ticketTotals[$index] ?? ['price' => 0, 'quantity' => 1, 'total' => 0];
            $ticketNumber = $ticket->ticket_number ?? ('TKT-' . $purchase->id . '-' . ($index + 1));
        @endphp
        <table role="presentation" width="100%" style="margin-bottom:24px; border:1px solid #d1d5db; border-radius:18px; overflow:hidden;">
            <tr>
                <td style="background-color:#0f172a; padding:28px 26px;">
                    <table role="presentation" width="100%">
                        <tr>
                            <td style="color:#f8fafc; font-size:18px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;">
                                {{ $event->title ?? 'Event Ticket' }}
                            </td>
                        </tr>
                        @if($event?->location)
                            <tr>
                                <td style="padding-top:10px; color:#e2e8f0; font-size:12px; font-weight:600; letter-spacing:0.22em; text-transform:uppercase;">
                                    {{ $event->location }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="padding-top:18px;">
                                <table role="presentation" width="100%">
                                    <tr>
                                        @if($formattedEventDate)
                                            <td class="stack-column" style="padding:6px 0; color:#f8fafc; font-size:12px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;">
                                                {{ $formattedEventDate }}
                                            </td>
                                        @endif
                                        @if($formattedEventTime)
                                            <td class="stack-column" style="padding:6px 0; color:#f8fafc; font-size:12px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;">
                                                {{ $formattedEventTime }}
                                            </td>
                                        @endif
                                        <td class="stack-column" style="padding:6px 0; color:#f8fafc; font-size:12px; font-weight:700; letter-spacing:0.16em; text-transform:uppercase;" align="right">
                                            Price: ₱{{ $formatCurrency($line['price']) }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="background-color:#f8fafc; border-top:1px dashed #cbd5e1; padding:22px 26px;">
                    <table role="presentation" width="100%">
                        <tr>
                            <td style="text-transform:uppercase; letter-spacing:0.18em; font-size:11px; font-weight:600; color:#6b7280;">Ticket Number</td>
                            <td align="right" style="font-size:20px; font-weight:800; color:#0f172a; letter-spacing:0.22em;">{{ strtoupper($ticketNumber) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endforeach

    <table role="presentation" width="100%" style="margin-top:32px; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="background:linear-gradient(135deg, #18181b 0%, #111827 100%); padding:26px 28px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="font-size:13px; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; color:rgba(244,244,245,0.8);">
                            Total Paid
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:10px; font-size:30px; font-weight:800; letter-spacing:-0.01em; color:#f9fafb;">
                            ₱{{ $formatCurrency($overallTotal) }}
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
        Thank you for choosing us for your entertainment needs!
    </div>
    <div style="margin-top:18px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#4b5563;">
        Need help? Contact our events team at
        <a href="mailto:events@rapollo.com" style="color:#18181b; font-weight:700; text-decoration:none;">events@rapollo.com</a>
    </div>
@endsection