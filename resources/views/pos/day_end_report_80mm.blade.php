<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Day End Report #{{ $register->id }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5px; box-sizing: border-box; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .title { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>
@php
    $currency = $settings['currency_symbol'] ?? 'LKR';
    $fmt = fn ($n) => number_format((float) $n, 2);
    $diff = $register->difference;
    $shiftDetails = $shiftDetails ?? collect();
    $cashierBreakdown = $cashierBreakdown ?? collect();
@endphp

    <div class="center title">{{ $settings['company_name'] ?? 'QRPOS' }}</div>
    <div class="center">{{ $settings['company_address'] ?? '' }}</div>
    <div class="center">{{ $settings['company_phone'] ?? '' }}</div>
    <div class="line"></div>
    <div class="center bold">DAY END REPORT</div>
    <div class="center">{{ $register->business_date?->format('Y-m-d') ?? $register->opened_at?->format('Y-m-d') }}</div>
    <div class="center">#{{ $register->id }}</div>
    <div class="line"></div>

    <table>
        <tr><td>Closed by</td><td class="right bold">{{ $register->user?->name ?? 'N/A' }}</td></tr>
        <tr><td>Started</td><td class="right">{{ \App\Models\Setting::formatDateTime($register->opened_at, 'Y-m-d H:i') }}</td></tr>
        <tr><td>Ended</td><td class="right">{{ $register->closed_at ? \App\Models\Setting::formatDateTime($register->closed_at, 'Y-m-d H:i') : 'OPEN' }}</td></tr>
        <tr><td>Orders</td><td class="right">{{ $register->orders_count }}</td></tr>
    </table>

    <div class="line"></div>
    <div class="bold">SALES BY METHOD</div>
    <table>
        <tr><td>Cash</td><td class="right">{{ $currency }} {{ $fmt($register->cash_sales) }}</td></tr>
        <tr><td>Card</td><td class="right">{{ $currency }} {{ $fmt($register->card_sales) }}</td></tr>
        <tr><td>Bank Transfer</td><td class="right">{{ $currency }} {{ $fmt($register->bank_transfer_sales) }}</td></tr>
        <tr><td>Online</td><td class="right">{{ $currency }} {{ $fmt($register->online_sales) }}</td></tr>
        <tr><td>Credit</td><td class="right">{{ $currency }} {{ $fmt($register->credit_sales) }}</td></tr>
        <tr class="bold"><td>TOTAL SALES</td><td class="right">{{ $currency }} {{ $fmt($register->total_sales) }}</td></tr>
    </table>

    <div class="line"></div>
    <div class="bold">CASH DRAWER</div>
    <table>
        <tr><td>Opening cash</td><td class="right">{{ $currency }} {{ $fmt($register->opening_balance) }}</td></tr>
        <tr><td>Cash Sales</td><td class="right">{{ $currency }} {{ $fmt($register->cash_sales) }}</td></tr>
        <tr><td>Cash In</td><td class="right">{{ $currency }} {{ $fmt($register->cash_in) }}</td></tr>
        <tr><td>Cash Out</td><td class="right">-{{ $currency }} {{ $fmt($register->cash_out) }}</td></tr>
        <tr class="bold"><td>Expected</td><td class="right">{{ $currency }} {{ $fmt($register->expected_cash) }}</td></tr>
        @if($register->closing_balance !== null)
        <tr><td>Closing cash</td><td class="right">{{ $currency }} {{ $fmt($register->closing_balance) }}</td></tr>
        <tr class="bold">
            <td>Difference</td>
            <td class="right">{{ ($diff >= 0 ? '+' : '-') . $currency . ' ' . $fmt(abs($diff)) }}</td>
        </tr>
        @endif
    </table>

    @if($cashierBreakdown->isNotEmpty())
    <div class="line"></div>
    <div class="bold">BY CASHIER</div>
    <table>
        <tr class="bold"><td>Cashier</td><td class="right">Ord</td><td class="right">Sales</td></tr>
        @foreach($cashierBreakdown as $row)
        <tr>
            <td>{{ \Illuminate\Support\Str::limit($row->cashier_name ?: 'N/A', 14) }}</td>
            <td class="right">{{ $row->orders_count }}</td>
            <td class="right">{{ $fmt($row->total_sales) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if($shiftDetails->isNotEmpty())
    <div class="line"></div>
    <div class="bold">ALL SHIFTS ({{ $shiftDetails->count() }})</div>
    <table>
        <tr class="bold"><td>Cashier</td><td class="right">Time</td><td class="right">Ord</td><td class="right">Sales</td></tr>
        @foreach($shiftDetails as $shift)
        <tr>
            <td>{{ \Illuminate\Support\Str::limit($shift->user?->name ?? '#'.$shift->id, 10) }}</td>
            <td class="right">{{ \App\Models\Setting::formatDateTime($shift->opened_at, 'H:i') }}-{{ $shift->closed_at ? \App\Models\Setting::formatDateTime($shift->closed_at, 'H:i') : '…' }}</td>
            <td class="right">{{ $shift->orders_count }}</td>
            <td class="right">{{ $fmt($shift->total_sales) }}</td>
        </tr>
        <tr>
            <td colspan="3">Open {{ $fmt($shift->opening_balance) }} / Close {{ $fmt($shift->closing_balance) }}</td>
            <td class="right">{{ $fmt($shift->difference ?? 0) }}</td>
        </tr>
        @endforeach
        <tr class="bold">
            <td colspan="2">TOTAL SHIFTS</td>
            <td class="right">{{ $shiftDetails->sum('orders_count') }}</td>
            <td class="right">{{ $fmt($shiftDetails->sum(fn ($s) => (float) $s->total_sales)) }}</td>
        </tr>
    </table>
    @endif

    <div class="line"></div>
    <div class="bold">TOP CATEGORIES</div>
    @if(($topCategories ?? collect())->isEmpty())
    <div>No category sales</div>
    @else
    <table>
        <tr class="bold"><td>Category</td><td class="right">Qty</td><td class="right">Amt</td></tr>
        @foreach($topCategories as $cat)
        <tr>
            <td>{{ \Illuminate\Support\Str::limit($cat['name'], 16) }}</td>
            <td class="right">{{ rtrim(rtrim(number_format($cat['qty'], 2), '0'), '.') }}</td>
            <td class="right">{{ $fmt($cat['revenue']) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if(!empty($register->notes))
    <div class="line"></div>
    <div class="bold">NOTES</div>
    <div style="white-space: pre-wrap;">{{ trim($register->notes) }}</div>
    @endif

    <div class="line"></div>
    <div class="center">Printed {{ \App\Models\Setting::formatDateTime(now(), 'Y-m-d H:i') }}</div>
    @include('partials.print-footer-80mm')
</body>
</html>
