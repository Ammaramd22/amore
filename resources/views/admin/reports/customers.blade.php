@extends('layouts.admin')
@section('title', 'Customer Report')
@section('page_title', 'Customer Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-users"></i> Customers</h3>
            <p class="rpt-sub">Top spenders in the selected period</p>
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
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['spent'] ?? 0, 2) }}</div>
            <div class="rpt-kpi-label">Total Spent</div>
            <i class="fas fa-wallet rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['customers'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Active Customers</div>
            <i class="fas fa-user-friends rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($summary['orders'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Orders</div>
            <i class="fas fa-receipt rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Top spenders</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="custSpendChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-line"></i> Orders per guest</h4>
            <div class="rpt-chart-wrap"><canvas id="custOrdersChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Top customers</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Total Spent ({{ $cur }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $i => $c)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $c->name }}</td>
                        <td>{{ $c->phone ?: '—' }}</td>
                        <td class="text-end">{{ $c->orders_count }}</td>
                        <td class="text-end fw-bold" style="color:#059669;">{{ number_format($c->orders_sum_total_amount ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="rpt-empty">No customer orders in this period</td></tr>
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
    const spend = charts.spend || {labels:[], data:[]};
    const orders = charts.orders || {labels:[], data:[]};
    const sEl = document.getElementById('custSpendChart');
    if (sEl) {
        new Chart(sEl, {
            type: 'bar',
            data: { labels: spend.labels, datasets: [{ data: spend.data, backgroundColor: '#0ea5e9', borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed.y||0).toLocaleString() } } }, scales: { y: { beginAtZero: true } } }
        });
    }
    const oEl = document.getElementById('custOrdersChart');
    if (oEl) {
        new Chart(oEl, {
            type: 'line',
            data: { labels: orders.labels, datasets: [{ data: orders.data, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.15)', fill: true, tension: .35 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }
})();
</script>
@endpush
