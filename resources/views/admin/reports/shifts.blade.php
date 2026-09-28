@extends('layouts.admin')
@section('title', 'Shift Report')
@section('page_title', 'Shift Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-clock"></i> Shifts</h3>
            <p class="rpt-sub">Register opens, closes, and cash difference</p>
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
            <div class="rpt-kpi-value">{{ $summary['shifts'] }}</div>
            <div class="rpt-kpi-label">Shifts</div>
            <i class="fas fa-clock rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['total_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Total Sales (all shifts)</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['cash_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Cash Sales</div>
            <i class="fas fa-money-bill rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi {{ $summary['difference'] >= 0 ? 'is-blue' : 'is-violet' }}">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['difference'], 2) }}</div>
            <div class="rpt-kpi-label">Cash Difference</div>
            <i class="fas fa-balance-scale rpt-kpi-icon"></i>
        </div>
    </div>

    @if(!empty($summary['day_ends']))
    <p class="text-muted small mb-3">Day-end closes in range: <strong>{{ $summary['day_ends'] }}</strong> (shown below; sales KPIs count shifts only so totals are not doubled).</p>
    @endif

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Sales per shift</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="shiftSalesChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-line"></i> Cash difference</h4>
            <div class="rpt-chart-wrap"><canvas id="shiftDiffChart"></canvas></div>
        </div>
    </div>

    @if($openRegisters->isNotEmpty())
    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Open shifts now</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cashier</th>
                        <th>Opened</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openRegisters as $reg)
                    <tr>
                        <td>{{ $reg->id }}</td>
                        <td class="fw-semibold">{{ $reg->user?->name ?? '—' }}</td>
                        <td>{{ $reg->opened_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end">{{ $cur }} {{ number_format($reg->opening_balance, 2) }}</td>
                        <td class="text-end">{{ $cur }} {{ number_format($reg->total_sales, 2) }}</td>
                        <td class="text-end">{{ $reg->orders_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>All shifts (by day)</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Day</th>
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
                    @forelse(($byDate ?? collect()) as $date => $dayShifts)
                        @foreach($dayShifts->sortBy('opened_at') as $reg)
                        @php $diff = (float) ($reg->difference ?? 0); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $date }}</td>
                            <td>{{ $reg->id }}</td>
                            <td>{{ $reg->user?->name ?? '—' }}</td>
                            <td>{{ $reg->opened_at?->format('H:i') }}</td>
                            <td>{{ $reg->closed_at?->format('H:i') }}</td>
                            <td class="text-end">{{ number_format($reg->opening_balance, 2) }}</td>
                            <td class="text-end">{{ number_format($reg->closing_balance, 2) }}</td>
                            <td class="text-end fw-bold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ ($diff >= 0 ? '+' : '') . number_format($diff, 2) }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($reg->total_sales, 2) }}</td>
                            <td class="text-end">{{ $reg->orders_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('pos.register.print', $reg) }}?format=html" target="_blank" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="table-light">
                            <td colspan="8" class="fw-bold">Day total {{ $date }} ({{ $dayShifts->count() }} shifts)</td>
                            <td class="text-end fw-bold">{{ number_format($dayShifts->sum(fn ($s) => (float) $s->total_sales), 2) }}</td>
                            <td class="text-end fw-bold">{{ $dayShifts->sum('orders_count') }}</td>
                            <td></td>
                        </tr>
                    @empty
                    <tr><td colspan="11" class="rpt-empty">No closed shifts in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(($dayEnds ?? collect())->isNotEmpty())
    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Day-end closes</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Business day</th>
                        <th>Closed by</th>
                        <th>Ended</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Diff</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayEnds as $day)
                    @php $diff = (float) ($day->difference ?? 0); @endphp
                    <tr>
                        <td>{{ $day->id }}</td>
                        <td class="fw-semibold">{{ $day->business_date?->format('Y-m-d') }}</td>
                        <td>{{ $day->user?->name ?? '—' }}</td>
                        <td>{{ $day->closed_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end">{{ number_format($day->total_sales, 2) }}</td>
                        <td class="text-end">{{ $day->orders_count }}</td>
                        <td class="text-end {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">{{ ($diff >= 0 ? '+' : '') . number_format($diff, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('pos.register.print', $day) }}?format=html" target="_blank" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-print"></i> Day 80mm
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Shift history</h4></div>
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
                        <th class="text-end">Expected</th>
                        <th class="text-end">Diff</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Orders</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registers as $reg)
                    @php $diff = (float) ($reg->difference ?? 0); @endphp
                    <tr>
                        <td>{{ $reg->id }}</td>
                        <td class="fw-semibold">{{ $reg->user?->name ?? '—' }}</td>
                        <td>{{ $reg->opened_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $reg->closed_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end">{{ number_format($reg->opening_balance, 2) }}</td>
                        <td class="text-end">{{ number_format($reg->closing_balance, 2) }}</td>
                        <td class="text-end">{{ number_format($reg->expected_cash, 2) }}</td>
                        <td class="text-end fw-bold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ ($diff >= 0 ? '+' : '') . number_format($diff, 2) }}
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($reg->total_sales, 2) }}</td>
                        <td class="text-end">{{ $reg->orders_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('pos.register.print', $reg) }}?format=html" target="_blank" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-print"></i> 80mm
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="rpt-empty">No closed shifts in this range</td></tr>
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
    const sales = charts.sales || {labels:[], data:[]};
    const diff = charts.diff || {labels:[], data:[]};
    const sEl = document.getElementById('shiftSalesChart');
    if (sEl) {
        new Chart(sEl, {
            type: 'bar',
            data: { labels: sales.labels, datasets: [{ data: sales.data, backgroundColor: '#f59e0b', borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed.y||0).toLocaleString() } } }, scales: { x: { ticks: { maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: 8 } }, y: { beginAtZero: true } } }
        });
    }
    const dEl = document.getElementById('shiftDiffChart');
    if (dEl) {
        new Chart(dEl, {
            type: 'line',
            data: { labels: diff.labels, datasets: [{ data: diff.data, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.12)', fill: true, tension: .3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }
})();
</script>
@endpush
