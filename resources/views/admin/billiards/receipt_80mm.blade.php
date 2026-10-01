<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billiards {{ $booking->booking_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            width: 72mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 3mm 2.5mm;
            color: #000;
            background: #fff;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .dline { border-top: 2px solid #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; white-space: nowrap; }
        .muted { font-size: 10px; }
        .title { font-size: 15px; letter-spacing: 1px; }
        .badge {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 2px 8px;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: .5px;
            margin: 2px 0 4px;
        }
        .ball {
            display: inline-block;
            width: 14px; height: 14px;
            border: 1.5px solid #000;
            border-radius: 50%;
            line-height: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            margin: 0 2px;
        }
        img.logo { max-width: 42mm; max-height: 18mm; width: auto; height: auto; }
        .row-lbl { width: 38%; }
        .big-total { font-size: 14px; }
        @media print {
            html, body { width: 80mm; margin: 0; padding: 2mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    $cur = $settings['currency'] ?? 'LKR';
    $logo = $settings['invoice_logo_src'] ?? null;
    $type = $booking->table?->typeLabel() ?? 'Table';
    $hoursLabel = rtrim(rtrim(number_format((float) $booking->hours, 2), '0'), '.');
    $footer = $settings['receipt_footer']
        ?? 'Thanks for playing! See you at the tables.';
    $status = strtoupper((string) ($booking->payment_status ?? 'unpaid'));
@endphp

@if($logo)
<div class="center" style="margin-bottom:4px">
    <img class="logo" src="{{ $logo }}" alt="">
</div>
@endif

<div class="center bold title">{{ $settings['company_name'] ?? 'Billiards Club' }}</div>
@if(!empty($settings['company_address']))
<div class="center muted">{{ $settings['company_address'] }}</div>
@endif
@if(!empty($settings['company_phone']))
<div class="center muted">{{ $settings['company_phone'] }}</div>
@endif

<div class="line"></div>

<div class="center">
    <span class="ball">8</span>
    <span class="bold" style="font-size:13px;letter-spacing:1px;"> BILLIARDS SESSION </span>
    <span class="ball">8</span>
</div>
<div class="center"><span class="badge">{{ strtoupper($type) }}</span></div>

<div class="dline"></div>

<table>
    <tr>
        <td class="row-lbl">Ticket</td>
        <td class="right bold">{{ $booking->booking_number }}</td>
    </tr>
    <tr>
        <td>Table</td>
        <td class="right bold">{{ $booking->table?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td>Player</td>
        <td class="right">{{ $booking->displayName() }}</td>
    </tr>
    @if($booking->displayPhone())
    <tr>
        <td>Phone</td>
        <td class="right">{{ $booking->displayPhone() }}</td>
    </tr>
    @endif
    <tr>
        <td>Source</td>
        <td class="right">{{ ucfirst(str_replace('_', ' ', (string) $booking->source)) }}</td>
    </tr>
</table>

<div class="line"></div>

<div class="center bold muted">PLAY TIME</div>
<table>
    <tr>
        <td class="row-lbl">Start</td>
        <td class="right">{{ $booking->scheduled_start?->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td>End</td>
        <td class="right">{{ $booking->scheduled_end?->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td>Duration</td>
        <td class="right bold">{{ $hoursLabel }} hr</td>
    </tr>
</table>

<div class="line"></div>

<div class="center bold muted">CHARGES</div>
<table>
    <tr>
        <td>{{ $type }} table · {{ $hoursLabel }} hr</td>
        <td class="right"></td>
    </tr>
    <tr>
        <td class="muted">@ {{ $cur }} {{ number_format((float) $booking->hourly_rate, 2) }}/hr</td>
        <td class="right">{{ number_format((float) $booking->amount, 2) }}</td>
    </tr>
</table>

<div class="dline"></div>

<table>
    <tr class="big-total bold">
        <td>TOTAL</td>
        <td class="right">{{ $cur }} {{ number_format((float) $booking->amount, 2) }}</td>
    </tr>
    <tr>
        <td>Paid</td>
        <td class="right">{{ $cur }} {{ number_format((float) $booking->paid_amount, 2) }}</td>
    </tr>
    <tr class="bold">
        <td>Balance</td>
        <td class="right">{{ $cur }} {{ number_format($booking->balanceDue(), 2) }}</td>
    </tr>
    <tr>
        <td>Status</td>
        <td class="right bold">{{ $status }}</td>
    </tr>
</table>

@if($booking->payments->isNotEmpty())
<div class="line"></div>
<div class="center bold muted">PAYMENTS</div>
<table>
@foreach($booking->payments as $p)
    <tr>
        <td>{{ $p->methodLabel() }}@if($p->reference) · {{ $p->reference }}@endif</td>
        <td class="right">{{ number_format((float) $p->amount, 2) }}</td>
    </tr>
@endforeach
</table>
@endif

<div class="dline"></div>
<div class="center bold">{{ $footer }}</div>
<div class="center muted">Printed {{ \App\Models\Setting::formatDateTime(now(), 'd/m/Y H:i') }}</div>
@include('partials.print-footer-80mm')

@if(request()->boolean('autoprint'))
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.focus(); window.print(); }, 250);
});
</script>
@endif
</body>
</html>
