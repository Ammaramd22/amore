<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bar Order Ticket</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5px; box-sizing: border-box; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
    </style>
</head>
@php
    $orderTypeLabel = match ($order->order_type) {
        'dine_in' => 'Dine In',
        'takeaway' => 'Take Away',
        'delivery' => 'Delivery',
        'express' => 'Express',
        default => ucfirst(str_replace('_', ' ', (string) $order->order_type)),
    };
@endphp
<body>
    <div class="center bold" style="font-size: 14px;">BOT ({{ $orderTypeLabel }})</div>
    @if($bot->is_reorder)
    <div class="center bold" style="font-size: 14px;">** REORDER **</div>
    @endif
    <div class="line"></div>
    <div class="bold">{{ $bot->kot_number }}</div>
    <div>{{ $bot->is_reorder ? 'Original Order' : 'Order' }}: {{ $order->order_number }}</div>
    <div>Table: {{ \App\Support\KotPrint::tableLabel($order) }}</div>
    <div>Waiter: {{ $order->waiter?->name ?? '—' }}</div>
    <div>Time: {{ \App\Models\Setting::formatDateTime($bot->created_at, 'H:i') }}</div>
    <div class="line"></div>
    @foreach($bot->items as $item)
    <div class="bold">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}x {{ $item->product_name }}</div>
    @php
        $botAddons = $item->orderItem?->addons ?? collect();
    @endphp
    @foreach($botAddons as $addon)
    <div style="padding-left:10px;">+ {{ $addon->addon_name }}</div>
    @endforeach
    @if($item->special_instructions)
    <div style="padding-left:10px;">Note: {{ $item->special_instructions }}</div>
    @endif
    @endforeach
    <div class="line"></div>
    <div class="center">{{ $order->order_notes ?? '' }}</div>
    @include('partials.print-footer-80mm')
</body>
</html>
