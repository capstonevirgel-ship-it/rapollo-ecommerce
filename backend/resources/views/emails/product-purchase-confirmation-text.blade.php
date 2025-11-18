@php
    $items = collect($purchase->items ?? $purchase->purchaseItems ?? []);
    $lineTotals = $items->map(function ($item) {
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
        $quantity = max($quantity, 0);

        $totalCandidates = [
            $item->final_total_price ?? null,
            $item->finalTotalPrice ?? null,
            $item->total_price ?? null,
            $item->totalPrice ?? null,
        ];

        $lineTotal = null;
        foreach ($totalCandidates as $candidate) {
            if ($candidate !== null) {
                $numeric = is_string($candidate) ? (float) preg_replace('/[^\d.\-]/', '', $candidate) : (float) $candidate;
                if (is_finite($numeric)) {
                    $lineTotal = $numeric;
                    break;
                }
            }
        }

        if ($lineTotal === null) {
            $lineTotal = $unit * $quantity;
        }

        return [
            'unit' => $unit,
            'quantity' => $quantity,
            'total' => $lineTotal,
        ];
    });

    $calculatedTotal = $lineTotals->sum('total');

    $overallTotal = $calculatedTotal > 0 ? $calculatedTotal : null;

    if ($overallTotal === null) {
        foreach ([$purchase->total ?? null, $purchase->total_amount ?? null] as $candidate) {
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

RAPOLLO - Order Confirmation

Hello {{ $user->user_name }},

Thank you for your purchase! We're excited to confirm that your order has been successfully placed and payment has been processed.

Order #{{ $purchase->reference_number }}
Status: Payment Confirmed

Order Details:
@foreach($items as $index => $item)
@php $line = $lineTotals[$index] ?? ['unit' => 0, 'quantity' => 0, 'total' => 0]; @endphp
- {{ $item->variant->product->name ?? 'Product' }}
  @php
      $variantParts = [];
      if ($item->variant?->color?->name) {
          $variantParts[] = 'Color: ' . $item->variant->color->name;
      }
      if ($item->variant?->size?->name) {
          $variantParts[] = 'Size: ' . $item->variant->size->name;
      }
  @endphp
  @if(!empty($variantParts))
  Variant: {{ implode(' | ', $variantParts) }}
  @endif
  Quantity: {{ $line['quantity'] }}
  Price (each): ₱{{ $formatCurrency($line['unit']) }}
  Line Total: ₱{{ $formatCurrency($line['total']) }}
@endforeach

Total: ₱{{ $formatCurrency($overallTotal) }}

Next Steps:
- Your order is being prepared for shipment
- You will receive a tracking number once your order ships
- Expected delivery time: 3-5 business days

If you have any questions about your order, please contact our customer service team.

RAPOLLO
Customer Service: support@rapollo.com
