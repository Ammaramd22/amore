<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        /* Customer receipt only (this document). Arial Bold; sizes/layout unchanged. */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-weight: 700;
            font-size: 13px;
            width: 80mm;
            margin: 0 auto;
            padding: 5px;
            box-sizing: border-box;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        .receipt-address { font-size: 15px; font-weight: 700; }
        .receipt-phone { font-size: 14px; font-weight: 700; }
        .receipt-order-type { font-size: 15px; font-weight: 700; }
        .receipt-meta { font-size: 13px; font-weight: 700; }
        .receipt-items { font-size: 13px; font-weight: 700; }
        .receipt-items .receipt-items-header { font-size: 13px; font-weight: 700; }
        .receipt-items .receipt-item-row { font-size: 13px; font-weight: 700; }
        .receipt-items .receipt-item-disc { font-size: 11px; font-weight: 700; }
        .receipt-totals { font-size: 13px; font-weight: 700; }
        .receipt-totals .receipt-total-row { font-size: 15px; font-weight: 700; }
        .receipt-thanks { font-size: 13px; font-weight: 700; }
        .receipt-software-footer {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 12px !important;
            font-weight: 700 !important;
        }
        @media print {
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-weight: 700;
            }
        }
    </style>
</head>
<body>
@php
    $payments = $order->payments
        ? $order->payments->where('status', 'completed')->values()
        : collect();
    $cashierName = $payments->sortByDesc('id')->first()?->creator?->name
        ?? $order->cashier?->name
        ?? 'N/A';
    $methodLabel = function (?string $method): string {
        return match ($method) {
            'cash' => 'Cash',
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online',
            'credit' => 'Credit',
            'split' => 'Split',
            default => $method ? ucfirst(str_replace('_', ' ', $method)) : 'Paid',
        };
    };
    $byMethod = $payments->groupBy('method')->map(fn ($rows) => (float) $rows->sum('amount'));
    $orderTypeLabel = match ($order->order_type) {
        'dine_in' => 'Dine In',
        'takeaway' => 'Take Away',
        'delivery' => 'Delivery',
        'express' => 'Express',
        default => ucfirst(str_replace('_', ' ', (string) $order->order_type)),
    };
    $customer = $order->customer;
    $invoiceLogoSrc = $settings['invoice_logo_src'] ?? \App\Models\Setting::invoiceLogoDataUri();
@endphp
    @if($invoiceLogoSrc)
    <div class="center" style="margin-bottom: 6px;">
        <img src="{{ $invoiceLogoSrc }}" alt="Logo" style="max-width: 48mm; max-height: 22mm; width: auto; height: auto;">
    </div>
    @endif
    <div class="center bold" style="font-family: Arial, sans-serif; font-size: 11px;">{{ $settings['company_name'] ?? 'Restaurant' }}</div>
    @if(!empty($settings['branch_name']) && \App\Services\BranchService::enabled())
    <div class="center bold" style="font-size: 11px;">{{ $settings['branch_name'] }}@if(!empty($settings['branch_code'])) ({{ $settings['branch_code'] }})@endif</div>
    @endif
    <div class="center receipt-address">{{ $settings['company_address'] ?? '' }}</div>
    <div class="center receipt-phone">{{ $settings['company_phone'] ?? '' }}</div>
    <div class="line"></div>
    <div class="center receipt-order-type">{{ $orderTypeLabel }}</div>
    <div class="line"></div>
    <div class="receipt-meta">
        <div>Order: <span class="bold">{{ $order->order_number }}</span></div>
        @if($customer)
        <div>Customer: {{ $customer->name }}</div>
        @if($customer->phone)
        <div>Phone: {{ $customer->phone }}</div>
        @endif
        @endif
        @if($order->order_type === 'delivery')
        @if($order->delivery_address)
        <div>Address: {{ $order->delivery_address }}</div>
        @endif
        @if($order->deliveryPartner)
        <div>Partner: {{ $order->deliveryPartner->name }}</div>
        @endif
        @if($order->payment_on_delivery)
        <div>Payment: Cash on Delivery</div>
        @endif
        @endif
        @if($order->order_type === 'dine_in')
        <div>Table: {{ $order->table?->name ?? 'N/A' }}</div>
        @endif
        <div>Waiter: {{ $order->waiter?->name ?? '—' }}</div>
        <div>Cashier: {{ $cashierName }}</div>
        <div>Date: {{ \App\Models\Setting::formatDateTime($order->created_at, 'Y-m-d H:i') }}</div>
    </div>
    <div class="line"></div>
    @php
        $liveItems = $order->items->where('is_void', false);
        $itemDiscountTotal = (float) $liveItems->sum(fn ($i) => (float) ($i->discount_amount ?? 0));
        $hasItemDiscount = $itemDiscountTotal > 0.009;
        $grossSubtotal = (float) $order->subtotal + $itemDiscountTotal;
    @endphp
    <table class="receipt-items">
        @if($hasItemDiscount)
        <tr class="receipt-items-header">
            <td>Item</td>
            <td class="right" style="width:18%;">Disc.</td>
            <td class="right" style="width:22%;">Amount</td>
        </tr>
        @foreach($liveItems as $item)
        @php
            $isFree = (float) $item->total_price <= 0 || str_contains(strtoupper((string) $item->special_instructions), 'LOYALTY FREE');
            $isComp = (bool) ($item->is_comp ?? false) || str_contains(strtoupper((string) $item->product_name), 'COMP');
            $itemDisc = (float) ($item->discount_amount ?? 0);
            $lineNet = (float) $item->total_price;
            $lineGross = $lineNet + $itemDisc;
            $qty = (float) $item->quantity;
            $unitDisplay = $qty > 0 ? ($lineGross / $qty) : (float) $item->unit_price;
            $qtyLabel = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') ?: '0';
        @endphp
        <tr class="receipt-item-row">
            <td colspan="3">{{ $item->product_name }}{{ $isComp ? ' [COMP]' : ($isFree ? ' [FREE]' : '') }}</td>
        </tr>
        <tr class="receipt-item-disc">
            <td>{{ ($isFree || $isComp) ? ($isComp ? 'COMP' : 'FREE') : (number_format($unitDisplay, 2).' × '.$qtyLabel) }}</td>
            <td class="right">{{ ($itemDisc > 0 && ! $isFree && ! $isComp) ? number_format($itemDisc, 2) : '—' }}</td>
            <td class="right">{{ ($isFree || $isComp) ? ($isComp ? 'COMP' : 'FREE') : number_format($lineNet, 2) }}</td>
        </tr>
        @endforeach
        @else
        <tr class="receipt-items-header">
            <td>Item</td>
            <td class="right" style="width:14%;">Qty</td>
            <td class="right" style="width:22%;">Amount</td>
        </tr>
        @foreach($liveItems as $item)
        @php
            $isFree = (float) $item->total_price <= 0 || str_contains(strtoupper((string) $item->special_instructions), 'LOYALTY FREE');
            $isComp = (bool) ($item->is_comp ?? false) || str_contains(strtoupper((string) $item->product_name), 'COMP');
            $lineNet = (float) $item->total_price;
            $qty = (float) $item->quantity;
            $qtyLabel = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') ?: '0';
        @endphp
        <tr class="receipt-item-row">
            <td>{{ $item->product_name }}{{ $isComp ? ' [COMP]' : ($isFree ? ' [FREE]' : '') }}</td>
            <td class="right">{{ $qtyLabel }}</td>
            <td class="right">{{ ($isFree || $isComp) ? ($isComp ? 'COMP' : 'FREE') : number_format($lineNet, 2) }}</td>
        </tr>
        @endforeach
        @endif
    </table>
    <div class="line"></div>
    <table class="receipt-totals">
        <tr><td>Subtotal</td><td class="right">{{ number_format($hasItemDiscount ? $grossSubtotal : (float) $order->subtotal, 2) }}</td></tr>
        @if($hasItemDiscount)
        <tr><td>Item Discount</td><td class="right">-{{ number_format($itemDiscountTotal, 2) }}</td></tr>
        @endif
        @if($order->discount_amount > 0)
        <tr><td>{{ str_contains(strtolower((string) $order->order_notes), 'loyalty') ? 'Free drink' : 'Bill Discount' }}</td><td class="right">-{{ number_format($order->discount_amount, 2) }}</td></tr>
        @endif
        @if(!empty($settings['tax_enabled']))
        <tr><td>{{ $settings['tax_name'] ?? 'Tax' }}</td><td class="right">{{ number_format($order->tax_amount, 2) }}</td></tr>
        @endif
        @if($order->service_charge > 0)
        <tr><td>Service Charge</td><td class="right">{{ number_format($order->service_charge, 2) }}</td></tr>
        @endif
        @if($order->delivery_charge > 0)
        <tr><td>Delivery</td><td class="right">{{ number_format($order->delivery_charge, 2) }}</td></tr>
        @endif
        @if((float) ($order->rounding_amount ?? 0) != 0)
        <tr><td>Rounding</td><td class="right">{{ number_format($order->rounding_amount, 2) }}</td></tr>
        @endif
        @if((float) ($order->card_surcharge_amount ?? 0) > 0)
        <tr><td>Card surcharge</td><td class="right">{{ number_format($order->card_surcharge_amount, 2) }}</td></tr>
        @endif
        <tr class="receipt-total-row"><td>TOTAL</td><td class="right">{{ number_format((float) $order->total_amount + (float) ($order->card_surcharge_amount ?? 0), 2) }}</td></tr>
        @if($byMethod->isNotEmpty())
            @foreach($byMethod as $method => $amount)
            <tr><td>{{ $methodLabel($method) }}</td><td class="right">{{ number_format($amount, 2) }}</td></tr>
            @endforeach
            <tr class="bold"><td>Paid Total</td><td class="right">{{ number_format((float) $order->paid_amount, 2) }}</td></tr>
        @else
            <tr><td>Paid</td><td class="right">{{ number_format($order->paid_amount, 2) }}</td></tr>
        @endif
        @if($order->change_amount > 0)
        <tr><td>Change</td><td class="right">{{ number_format($order->change_amount, 2) }}</td></tr>
        @endif
    </table>
    <div class="line"></div>
    <div class="center receipt-thanks">{{ $settings['receipt_footer'] ?? 'Thank you!' }}</div>
    @include('partials.print-footer-80mm')
</body>
</html>
