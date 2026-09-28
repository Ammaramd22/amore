@extends('layouts.admin')
@section('title', 'Day End Report')
@section('page_title', 'Day End Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-calendar-check"></i> Day End</h3>
            <p class="rpt-sub">Store-wide day closes with every cashier shift listed</p>
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

    <div class="rpt-kpis is-4">
        <div class="rpt-kpi is-stone">
            <div class="rpt-kpi-value">{{ $summary['days'] }}</div>
            <div class="rpt-kpi-label">Day closes</div>
            <i class="fas fa-calendar-check rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['total_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Day-end sales</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['avg_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Avg / day</div>
            <i class="fas fa-chart-line rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi {{ $summary['difference'] >= 0 ? 'is-blue' : 'is-violet' }}">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['difference'], 2) }}</div>
            <div class="rpt-kpi-label">Cash difference</div>
            <i class="fas fa-balance-scale rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Sales by day</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="daySalesChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-receipt"></i> Orders by day</h4>
            <div class="rpt-chart-wrap"><canvas id="dayOrdersChart"></canvas></div>
        </div>
    </div>

    @forelse($dayEnds as $day)
    @php
        $date = $day->business_date?->toDateString();
        $dayShifts = $shiftsByDate->get($date, collect());
        $diff = (float) ($day->difference ?? 0);
    @endphp
    <div class="rpt-panel">
        <div class="rpt-panel-head d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="mb-0">
                {{ $day->business_date?->format('D, d M Y') ?? 'Day #'.$day->id }}
                <span class="text-muted small fw-normal ms-1">#{{ $day->id }} · closed by {{ $day->user?->name ?? '—' }}</span>
            </h4>
            <a href="{{ route('pos.register.print', $day) }}?format=html" target="_blank" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-print"></i> 80mm Day End
            </a>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Day totals</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Closing</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">Card</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Diff</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fw-semibold">
                        <td>Store day end</td>
                        <td class="text-end">{{ number_format($day->opening_balance, 2) }}</td>
                        <td class="text-end">{{ number_format($day->closing_balance, 2) }}</td>
                        <td class="text-end">{{ number_format($day->cash_sales, 2) }}</td>
                        <td class="text-end">{{ number_format($day->card_sales, 2) }}</td>
                        <td class="text-end">{{ number_format((float)$day->bank_transfer_sales + (float)$day->online_sales + (float)$day->credit_sales, 2) }}</td>
                        <td class="text-end">{{ number_format($day->total_sales, 2) }}</td>
                        <td class="text-end">{{ $day->orders_count }}</td>
                        <td class="text-end {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">{{ ($diff >= 0 ? '+' : '') . number_format($diff, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="px-3 pt-3 pb-1 fw-bold small text-uppercase text-muted">All shifts ({{ $dayShifts->count() }})</div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cashier</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Counted</th>
                        <th class="text-end">Diff</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dayShifts as $shift)
                    @php $sDiff = (float) ($shift->difference ?? 0); @endphp
                    <tr>
                        <td>{{ $shift->id }}</td>
                        <td class="fw-semibold">{{ $shift->user?->name ?? '—' }}</td>
                        <td>{{ $shift->opened_at?->format('H:i') }}</td>
                        <td>{{ $shift->closed_at?->format('H:i') }}</td>
                        <td class="text-end">{{ number_format($shift->opening_balance, 2) }}</td>
                        <td class="text-end">{{ number_format($shift->closing_balance, 2) }}</td>
                        <td class="text-end {{ $sDiff >= 0 ? 'text-success' : 'text-danger' }}">{{ ($sDiff >= 0 ? '+' : '') . number_format($sDiff, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($shift->total_sales, 2) }}</td>
                        <td class="text-end">{{ $shift->orders_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('pos.register.print', $shift) }}?format=html" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="rpt-empty">No shift rows linked to this business date.</td></tr>
                    @endforelse
                    @if($dayShifts->isNotEmpty())
                    <tr class="table-light fw-bold">
                        <td colspan="6">Shift total</td>
                        <td class="text-end">{{ number_format($dayShifts->sum(fn ($s) => (float) ($s->difference ?? 0)), 2) }}</td>
                        <td class="text-end">{{ number_format($dayShifts->sum(fn ($s) => (float) $s->total_sales), 2) }}</td>
                        <td class="text-end">{{ $dayShifts->sum('orders_count') }}</td>
                        <td></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="rpt-panel">
        <div class="rpt-empty py-5">No day-end reports in this range. Close shifts after cutoff or run Day End from POS.</div>
    </div>
    @endforelse

    @if(($orphanShifts ?? collect())->isNotEmpty())
    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Days with shifts — day end not closed yet</h4></div>
        @foreach($orphanShifts as $date => $dayShifts)
        <div class="px-3 pt-3 fw-semibold">{{ $date }} · {{ $dayShifts->count() }} shift(s) · sales {{ $cur }} {{ number_format($dayShifts->sum(fn ($s) => (float) $s->total_sales), 2) }}</div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Cashier</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayShifts as $shift)
                    <tr>
                        <td>{{ $shift->user?->name ?? '—' }}</td>
                        <td>{{ $shift->opened_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $shift->closed_at?->format('H:i') }}</td>
                        <td class="text-end">{{ number_format($shift->total_sales, 2) }}</td>
                        <td class="text-end">{{ $shift->orders_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
    @endif

    <div class="rpt-footer">QRPOS BY AVENQUE <span>|</span> 076 822 2201</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const cur = @json($cur);
    const charts = @json($charts ?? []);
    const sales = charts.sales || {labels:[], data:[]};
    const orders = charts.orders || {labels:[], data:[]};
    const sEl = document.getElementById('daySalesChart');
    if (sEl) {
        new Chart(sEl, {
            type: 'bar',
            data: { labels: sales.labels, datasets: [{ data: sales.data, backgroundColor: '#f59e0b', borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed.y||0).toLocaleString() } } }, scales: { y: { beginAtZero: true } } }
        });
    }
    const oEl = document.getElementById('dayOrdersChart');
    if (oEl) {
        new Chart(oEl, {
            type: 'line',
            data: { labels: orders.labels, datasets: [{ data: orders.data, borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.12)', fill: true, tension: .3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }
})();
</script>
@endpush
