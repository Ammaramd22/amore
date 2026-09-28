<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register Report</title>
</head>
<body style="font-family: Arial, sans-serif; color:#1c1917; line-height:1.45;">
@php
    $currency = $settings['currency_symbol'] ?? 'LKR';
    $fmt = fn ($n) => number_format((float) $n, 2);
    $isDay = $register->mode === 'day_end';
@endphp
<h2 style="margin:0 0 8px;">{{ $isDay ? 'Day End Report' : 'Shift Report' }} #{{ $register->id }}</h2>
<p style="margin:0 0 16px; color:#57534e;">
    {{ $settings['company_name'] ?? 'QRPOS' }} ·
    {{ $isDay ? 'Business day' : 'Cashier' }}:
    <strong>{{ $isDay ? ($register->business_date?->format('Y-m-d') ?? '') : ($register->user?->name ?? '') }}</strong>
</p>

<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:520px;">
    <tr><td>Opened</td><td align="right">{{ $register->opened_at?->format('Y-m-d H:i') }}</td></tr>
    <tr><td>Closed</td><td align="right">{{ $register->closed_at?->format('Y-m-d H:i') }}</td></tr>
    <tr><td>Orders</td><td align="right">{{ $register->orders_count }}</td></tr>
    <tr><td>Total sales</td><td align="right"><strong>{{ $currency }} {{ $fmt($register->total_sales) }}</strong></td></tr>
    <tr><td>Cash / Card / Bank / Online / Credit</td><td align="right">
        {{ $fmt($register->cash_sales) }} /
        {{ $fmt($register->card_sales) }} /
        {{ $fmt($register->bank_transfer_sales) }} /
        {{ $fmt($register->online_sales) }} /
        {{ $fmt($register->credit_sales) }}
    </td></tr>
    <tr><td>Opening cash</td><td align="right">{{ $currency }} {{ $fmt($register->opening_balance) }}</td></tr>
    <tr><td>Expected cash</td><td align="right">{{ $currency }} {{ $fmt($register->expected_cash) }}</td></tr>
    <tr><td>Closing cash</td><td align="right">{{ $currency }} {{ $fmt($register->closing_balance) }}</td></tr>
    <tr><td>Difference</td><td align="right"><strong>{{ $currency }} {{ $fmt($register->difference) }}</strong></td></tr>
</table>

@if(($cashierBreakdown ?? collect())->isNotEmpty())
<h3 style="margin:20px 0 8px;">By cashier</h3>
<ul>
@foreach($cashierBreakdown as $row)
    <li>{{ $row->cashier_name ?: 'N/A' }} — {{ $row->orders_count }} orders — {{ $currency }} {{ $fmt($row->total_sales) }}</li>
@endforeach
</ul>
@endif

<p style="margin-top:24px; color:#78716c; font-size:12px;">PDF slip attached. QRPOS By Avenque</p>
</body>
</html>
