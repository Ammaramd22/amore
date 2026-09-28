@extends('layouts.admin')
@section('title', 'Payment Methods Report')
@section('page_title', 'Payment Methods Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-money-check-alt"></i> Payments</h3>
            <p class="rpt-sub">Method totals and multi-pay bills</p>
        </div>
        <form method="GET" class="rpt-filters">
            @include('partials.branch-filter', ['branchFilter' => $branchFilter ?? ['enabled'=>false], 'branchFilterAutoSubmit' => true])

            <div>
                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $from }}" onchange="this.form.submit()">
            </div>
            <div>
                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ $to }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="rpt-kpis">
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($totalCollected, 2) }}</div>
            <div class="rpt-kpi-label">Total Collected</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($txnCount) }}</div>
            <div class="rpt-kpi-label">Payment Lines</div>
            <i class="fas fa-list rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($splitOrders->count()) }}</div>
            <div class="rpt-kpi-label">Split / Multi-pay</div>
            <i class="fas fa-random rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-pie"></i> By method</h4>
            <div class="rpt-chart-wrap"><canvas id="payMethodChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-area"></i> Daily collections</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="payDailyChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>By payment method</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="text-end">Transactions</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byMethod as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['label'] }}</td>
                        <td class="text-end">{{ $row['txn_count'] }}</td>
                        <td class="text-end fw-bold" style="color:#059669;">{{ $cur }} {{ number_format($row['total_amount'], 2) }}</td>
                        <td class="text-end">
                            {{ $totalCollected > 0 ? number_format(($row['total_amount'] / $totalCollected) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="rpt-empty">No payments in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Multi-pay bills (2+ methods)</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Table</th>
                        <th>Waiter</th>
                        <th>Payments</th>
                        <th class="text-end">Bill total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($splitOrders as $order)
                    <tr>
                        <td class="fw-semibold">
                            <a href="{{ route('orders.show', $order['id']) }}">{{ $order['order_number'] }}</a>
                        </td>
                        <td>{{ $order['table'] ?? '—' }}</td>
                        <td>{{ $order['waiter'] ?? '—' }}</td>
                        <td>
                            @foreach($order['lines'] as $line)
                                <span class="badge-soft me-1 mb-1">
                                    {{ ucfirst(str_replace('_', ' ', $line['method'])) }}
                                    {{ $cur }} {{ number_format($line['amount'], 2) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="text-end fw-bold">{{ $cur }} {{ number_format($order['total'], 2) }}</td>
                        <td class="text-muted">{{ $order['created_at'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="rpt-empty">No multi-pay bills in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-footer">QRPOS BY AVENQUE <span>|</span> 076 822 2201</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const cur = @json($cur);
    const charts = @json($charts ?? []);
    const methods = charts.methods || {labels:[], data:[], colors:[]};
    const daily = charts.daily || {labels:[], data:[]};
    const mEl = document.getElementById('payMethodChart');
    if (mEl) {
        new Chart(mEl, {
            type: 'doughnut',
            data: { labels: methods.labels, datasets: [{ data: methods.data, backgroundColor: methods.colors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed||0).toLocaleString() } } } }
        });
    }
    const dEl = document.getElementById('payDailyChart');
    if (dEl) {
        const ctx = dEl.getContext('2d');
        const g = ctx.createLinearGradient(0,0,0,240);
        g.addColorStop(0,'rgba(14,165,233,.3)'); g.addColorStop(1,'rgba(14,165,233,.02)');
        new Chart(ctx, {
            type: 'line',
            data: { labels: daily.labels, datasets: [{ data: daily.data, borderColor: '#0ea5e9', backgroundColor: g, fill: true, tension: .35 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
})();
</script>
@endpush
