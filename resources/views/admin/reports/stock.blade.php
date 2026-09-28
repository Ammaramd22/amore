@extends('layouts.admin')
@section('title', 'Stock Report')
@section('page_title', 'Stock Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-warehouse"></i> Stock</h3>
            <p class="rpt-sub">Ingredient levels and inventory value</p>
        </div>
        <form method="GET" class="rpt-filters">
            @include('partials.branch-filter', ['branchFilter' => $branchFilter ?? ['enabled'=>false], 'branchFilterAutoSubmit' => true])
        </form>
    </div>

    <div class="rpt-kpis">
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['value'] ?? 0, 2) }}</div>
            <div class="rpt-kpi-label">Stock Value</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['items'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Ingredients</div>
            <i class="fas fa-flask rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($summary['low'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Low Stock</div>
            <i class="fas fa-exclamation-triangle rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Highest stock value</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="stockValueChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-pie"></i> Stock health</h4>
            <div class="rpt-chart-wrap"><canvas id="stockHealthChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Stock valuation</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Code</th>
                        <th class="text-end">Stock</th>
                        <th>Unit</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Value ({{ $cur }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ingredients as $ing)
                    <tr class="{{ method_exists($ing, 'isLowStock') && $ing->isLowStock() ? 'table-warning' : '' }}">
                        <td class="fw-semibold">
                            {{ $ing->name }}
                            @if(method_exists($ing, 'isLowStock') && $ing->isLowStock())
                                <span class="badge-soft danger ms-1">Low</span>
                            @endif
                        </td>
                        <td>{{ $ing->code ?: '—' }}</td>
                        <td class="text-end">{{ number_format($ing->stock_quantity, 3) }}</td>
                        <td>{{ $ing->unit }}</td>
                        <td class="text-end">{{ number_format($ing->cost_per_unit, 4) }}</td>
                        <td class="text-end fw-bold">{{ number_format($ing->stock_quantity * $ing->cost_per_unit, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="rpt-empty">No ingredients yet</td></tr>
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
    const value = charts.value || {labels:[], data:[]};
    const status = charts.status || {labels:[], data:[]};
    const vEl = document.getElementById('stockValueChart');
    if (vEl) {
        new Chart(vEl, {
            type: 'bar',
            data: { labels: value.labels, datasets: [{ data: value.data, backgroundColor: '#8b5cf6', borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed.x||0).toLocaleString() } } }, scales: { x: { beginAtZero: true } } }
        });
    }
    const hEl = document.getElementById('stockHealthChart');
    if (hEl) {
        new Chart(hEl, {
            type: 'doughnut',
            data: { labels: status.labels, datasets: [{ data: status.data, backgroundColor: ['#10b981','#f59e0b'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
@endpush
