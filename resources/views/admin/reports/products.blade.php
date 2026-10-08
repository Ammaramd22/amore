@extends('layouts.admin')
@section('title', 'Product Report')
@section('page_title', 'Product Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-hamburger"></i> Products</h3>
            <p class="rpt-sub">Best sellers by quantity and revenue</p>
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
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['revenue'] ?? 0, 2) }}</div>
            <div class="rpt-kpi-label">Item Revenue</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['items_sold'] ?? 0, 0) }}</div>
            <div class="rpt-kpi-label">Qty Sold</div>
            <i class="fas fa-boxes rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($summary['skus'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Products Sold</div>
            <i class="fas fa-list rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Top by quantity</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="productQtyChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-pie"></i> Top by revenue</h4>
            <div class="rpt-chart-wrap"><canvas id="productRevChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Best selling products</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Code</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue ({{ $cur }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $i => $p)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $p->name }}</td>
                        <td>{{ $p->code ?: '—' }}</td>
                        <td class="text-end">{{ number_format($p->qty, 0) }}</td>
                        <td class="text-end fw-bold" style="color:#059669;">{{ number_format($p->revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="rpt-empty">No product sales in this period</td></tr>
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
    const qty = charts.qty || {labels:[], data:[]};
    const rev = charts.revenue || {labels:[], data:[]};
    const palette = ['#f59e0b','#ea580c','#10b981','#0ea5e9','#8b5cf6','#ef4444','#64748b','#d97706'];

    const qEl = document.getElementById('productQtyChart');
    if (qEl) {
        new Chart(qEl, {
            type: 'bar',
            data: { labels: qty.labels, datasets: [{ label: 'Qty', data: qty.data, backgroundColor: palette, borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
        });
    }
    const rEl = document.getElementById('productRevChart');
    if (rEl) {
        new Chart(rEl, {
            type: 'doughnut',
            data: { labels: rev.labels, datasets: [{ data: rev.data, backgroundColor: palette, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed||0).toLocaleString() } } } }
        });
    }
})();
</script>
@endpush
