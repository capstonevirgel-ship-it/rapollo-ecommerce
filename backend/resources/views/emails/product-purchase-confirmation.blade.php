@extends('emails.layouts.base')

@section('title', 'Order Confirmation - Rapollo')

@section('header')
    <div style="font-size:30px; font-weight:800; text-transform:uppercase; letter-spacing:0.18em; color:#f4f4f5;">RAPOLLO</div>
    <div style="margin-top:12px; font-size:15px; font-weight:500; color:#d4d4d8; letter-spacing:0.04em;">Order Confirmation</div>
@endsection

@section('content')
    @php
        $items = collect($purchase->items ?? $purchase->purchaseItems ?? []);
        $computedLineTotals = $items->map(function ($item) {
            $unitCandidates = [
                $item->final_unit_price ?? null,
                $item->finalUnitPrice ?? null,
                $item->unit_price ?? null,
                $item->unitPrice ?? null,
                $item->price ?? null,
            ];

            $unit = 0.0;
            foreach ($unitCandidates as $candidate) {
                if ($candidate !== null) {
                    $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                    if (is_finite($numeric)) {
                        $unit = $numeric;
                        break;
                    }
                }
            }

            $quantity = (int) ($item->quantity ?? 0);

            $totalCandidates = [
                $item->final_total_price ?? null,
                $item->finalTotalPrice ?? null,
                $item->total_price ?? null,
                $item->totalPrice ?? null,
            ];

            $computedTotal = null;
            foreach ($totalCandidates as $candidate) {
                if ($candidate !== null) {
                    $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                    if (is_finite($numeric)) {
                        $computedTotal = $numeric;
                        break;
                    }
                }
            }

            if ($computedTotal === null) {
                $computedTotal = $unit * max($quantity, 0);
            }

            return [
                'unit' => $unit,
                'total' => $computedTotal,
                'quantity' => max($quantity, 0),
            ];
        });

        $calculatedSum = $computedLineTotals->sum('total');

        $overallTotal = $calculatedSum > 0 ? $calculatedSum : 0.0;

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

        $formatCurrency = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div style="font-size:18px; font-weight:600; color:#18181b; margin-bottom:16px;">Hello {{ $user->user_name }},</div>
    <p style="margin:0 0 28px 0; font-size:15px; color:#4b5563;">
        Thank you for your purchase! We're excited to confirm that your order has been successfully placed and payment has been processed.
    </p>

    <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:24px 28px; background-color:#f9fafb;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:12px;">Order Summary</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0; border-top:1px solid #e4e4e7;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="font-size:12px; text-transform:uppercase; letter-spacing:0.2em; color:#6b7280; font-weight:600;">Reference</td>
                                    <td align="right" style="font-size:14px; font-weight:600; color:#111827; font-family:'Courier New', Courier, monospace;">
                                        {{ $purchase->reference_number }}
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
                                        <span style="display:inline-block; background-color:#dcfce7; color:#166534; padding:6px 16px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">
                                            Payment Confirmed
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

    @if($items->isNotEmpty())
        <table role="presentation" width="100%" style="margin-bottom:28px; border:1px solid #e5e7eb; border-radius:14px;">
            <tr>
                <td style="padding:24px 28px;">
                    <table role="presentation" width="100%">
                        <tr>
                            <td style="text-transform:uppercase; letter-spacing:0.1em; font-weight:700; color:#18181b; font-size:16px; padding-bottom:16px;">Order Details</td>
                        </tr>
                        <tr>
                            <td>
                                <table role="presentation" width="100%" style="border-collapse:separate; border-spacing:0;">
                                    <tr style="border-bottom:1px solid #e5e7eb;">
                                        <td style="padding:12px 0; font-size:12px; text-transform:uppercase; letter-spacing:0.12em; color:#6b7280; font-weight:700;">Item</td>
                                        <td style="padding:12px 0; font-size:12px; text-transform:uppercase; letter-spacing:0.12em; color:#6b7280; font-weight:700;" align="center">Qty</td>
                                        <td style="padding:12px 0; font-size:12px; text-transform:uppercase; letter-spacing:0.12em; color:#6b7280; font-weight:700;" align="right">Line Total</td>
                                    </tr>
                                    @foreach($items as $index => $item)
                                        @php
                                            $line = $computedLineTotals[$index] ?? ['unit' => 0, 'total' => 0, 'quantity' => 0];
                                            $unitFormatted = $formatCurrency($line['unit']);
                                            $totalFormatted = $formatCurrency($line['total']);
                                            $variantParts = [];
                                            if ($item->variant?->color?->name) {
                                                $variantParts[] = 'Color: ' . $item->variant->color->name;
                                            }
                                            if ($item->variant?->size?->name) {
                                                $variantParts[] = 'Size: ' . $item->variant->size->name;
                                            }
                                            $variantText = implode(' · ', $variantParts);
                                        @endphp
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:16px 0;">
                                                <div style="font-size:15px; font-weight:600; color:#18181b;">
                                                    {{ $item->variant->product->name ?? 'Product' }}
                                                </div>
                                                <div style="margin-top:6px; font-size:13px; color:#6b7280;">
                                                    ₱{{ $unitFormatted }} each
                                                    @if($variantText)
                                                        <span style="margin-left:8px; color:#9ca3af;">| {{ $variantText }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td align="center" style="padding:16px 0; font-size:14px; font-weight:700; color:#18181b;">
                                                {{ $line['quantity'] }}
                                            </td>
                                            <td align="right" style="padding:16px 0; font-size:15px; font-weight:700; color:#18181b;">
                                                ₱{{ $totalFormatted }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" style="margin-top:32px; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="background:linear-gradient(135deg, #18181b 0%, #111827 100%); padding:26px 28px;">
                <table role="presentation" width="100%">
                    <tr>
                        <td style="font-size:13px; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; color:rgba(244,244,245,0.8);">
                            Total Amount
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
        Thank you for choosing us for your fashion needs!
    </div>
    <div style="margin-top:18px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#4b5563;">
        Need help? Contact our customer service team at
        <a href="mailto:support@rapollo.com" style="color:#18181b; font-weight:700; text-decoration:none;">support@rapollo.com</a>
    </div>
@endsection