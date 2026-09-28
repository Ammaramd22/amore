<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KOT {{ $kot->kot_number }}</title>
    <style>
        /* Kitchen KOT only — 80mm thermal (aligned with Print Bridge ESC/POS) */
        @page {
            size: 80mm auto;
            margin: 0;
        }
        html, body {
            width: 80mm;
            max-width: 80mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        body {
            font-family: monospace;
            font-size: 16px;
            padding: 3mm;
            box-sizing: border-box;
        }
        .kot-print {
            width: 74mm;
            max-width: 74mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            min-height: 0;
            height: auto;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .kot-title {
            font-size: 26px;
            font-weight: 800;
            line-height: 1.15;
        }
        .kot-reorder {
            font-size: 22px;
            font-weight: 800;
            margin-top: 4px;
        }
        .kot-meta {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.35;
        }
        .kot-meta .kot-number {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 2px;
        }
        .kot-takeaway-flag {
            font-size: 24px;
            font-weight: 900;
            text-align: center;
            margin: 8px 0 2px;
            letter-spacing: 0.5px;
            border: 2px solid #000;
            padding: 6px 4px;
        }
        .kot-item {
            font-size: 26px;
            font-weight: 800;
            margin: 4px 0;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }
        .kot-note {
            font-size: 22px;
            font-weight: 800;
            padding-left: 8px;
            margin: 2px 0 6px;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .kot-order-notes {
            font-size: 22px;
            font-weight: 800;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .receipt-software-footer { font-size: 13px !important; font-weight: 700 !important; }
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                min-height: 0 !important;
                height: auto !important;
                background: #fff !important;
            }
            .kot-print {
                width: 74mm !important;
                max-width: 74mm !important;
                margin: 0 !important;
                padding: 3mm !important;
                min-height: 0 !important;
                height: auto !important;
                box-sizing: border-box;
            }
        }
    </style>
</head>
@php
    $orderTypeLabel = \App\Support\KotPrint::orderTypeLabel($order->order_type ?? null);
    $hasTakeAway = \App\Support\KotPrint::hasTakeAwayNote($order, $kot);
    $tableLabel = \App\Support\KotPrint::tableLabel($order);
    $waiterLabel = \App\Support\KotPrint::waiterLabel($order);
@endphp
<body>
    <div class="kot-print">
        <div class="center kot-title">{{ $kot->type === 'bar' ? 'BOT' : 'KOT' }} ({{ $orderTypeLabel }})</div>
        @if($kot->is_reorder)
        <div class="center kot-reorder">** REORDER **</div>
        @endif
        <div class="line"></div>
        <div class="kot-meta">
            <div class="kot-number">{{ $kot->kot_number }}</div>
            <div>Invoice: {{ $order->order_number }}</div>
            <div>Table: {{ $tableLabel }}</div>
            <div>Waiter: {{ $waiterLabel }}</div>
            <div>Time: {{ \App\Models\Setting::formatDateTime($kot->created_at, 'H:i') }}</div>
        </div>
        @if($hasTakeAway)
        <div class="kot-takeaway-flag">*** TAKE AWAY ***</div>
        @endif
        <div class="line"></div>
        @foreach($kot->items as $item)
        <div class="kot-item">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}x {{ $item->product_name }}</div>
        @php
            $kotAddons = $item->orderItem?->addons ?? collect();
        @endphp
        @foreach($kotAddons as $addon)
        <div class="kot-note">+ {{ $addon->addon_name }}@if((float) $addon->price > 0) ({{ number_format((float) $addon->price, 2) }})@endif</div>
        @endforeach
        @if($item->special_instructions)
        <div class="kot-note">NOTE: {{ $item->special_instructions }}</div>
        @endif
        @endforeach
        <div class="line"></div>
        @if(!empty($order->order_notes))
        <div class="center kot-order-notes">NOTES: {{ $order->order_notes }}</div>
        <div class="line"></div>
        @endif
        @include('partials.print-footer-80mm')
    </div>
    <script>
        (function () {
            function applyThermalPageSize() {
                var el = document.querySelector('.kot-print');
                if (!el) return;
                var px = Math.max(el.scrollHeight, el.offsetHeight, 1);
                var mm = Math.max(50, Math.ceil(px * 25.4 / 96) + 8);
                var style = document.getElementById('kot-thermal-page');
                if (!style) {
                    style = document.createElement('style');
                    style.id = 'kot-thermal-page';
                    document.head.appendChild(style);
                }
                style.textContent = '@page { size: 80mm ' + mm + 'mm; margin: 0; }';
            }
            window.addEventListener('load', applyThermalPageSize);
            window.addEventListener('beforeprint', applyThermalPageSize);
            if (document.readyState !== 'loading') applyThermalPageSize();
        })();
    </script>
</body>
</html>
