@extends('layouts.admin')
@section('title', 'Waiter Report')
@section('page_title', 'Waiter Sales Report')
@section('content')
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-user-tie"></i> Waiters</h3>
            <p class="rpt-sub">Service sales, rankings and ratings</p>
        </div>
        <form method="GET" class="rpt-filters">
            @include('partials.branch-filter', ['branchFilter' => $branchFilter ?? ['enabled'=>false], 'branchFilterAutoSubmit' => true])

            <div>
                <label>Range</label>
                <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $range === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="week" {{ $range === 'week' ? 'selected' : '' }}>This week</option>
                    <option value="month" {{ $range === 'month' ? 'selected' : '' }}>This month</option>
                    <option value="year" {{ $range === 'year' ? 'selected' : '' }}>This year</option>
                    <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>
            <div>
                <label>From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" onchange="this.form.range.value='custom'; this.form.submit()">
            </div>
            <div>
                <label>To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" onchange="this.form.range.value='custom'; this.form.submit()">
            </div>
        </form>
    </div>

    <div class="rpt-kpis" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $currency }} {{ number_format($summary['total_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Total Sales</div>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ $summary['total_orders'] }}</div>
            <div class="rpt-kpi-label">Orders</div>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value" style="font-size:1.25rem;">{{ $topWaiter['waiter_name'] ?? '—' }}</div>
            <div class="rpt-kpi-label">Top Waiter</div>
        </div>
        <div class="rpt-kpi is-stone">
            <div class="rpt-kpi-value">{{ $summary['without_waiter'] }}</div>
            <div class="rpt-kpi-label">No Waiter</div>
        </div>
        <div class="rpt-kpi is-violet">
            <div class="rpt-kpi-value">{{ $summary['avg_rating'] !== null ? $summary['avg_rating'].' ★' : '—' }}</div>
            <div class="rpt-kpi-label">{{ $summary['ratings_count'] }} Ratings</div>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-bar"></i> Waiter sales</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="waiterSalesChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-pie"></i> Orders by waiter</h4>
            <div class="rpt-chart-wrap"><canvas id="waiterOrdersChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Who did good sales</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Waiter</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Sales</th>
                        <th class="text-end">Avg bill</th>
                        <th class="text-end">Rating</th>
                        <th class="text-end">Share</th>
                        <th class="text-end">Unpaid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $row)
                    <tr>
                        <td>
                            @if($row['rank'] === 1)
                                <span class="badge bg-warning text-dark">1</span>
                            @elseif($row['rank'] === 2)
                                <span class="badge bg-secondary">2</span>
                            @elseif($row['rank'] === 3)
                                <span class="badge bg-info">3</span>
                            @else
                                {{ $row['rank'] }}
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $row['waiter_name'] }}</td>
                        <td class="text-end">{{ $row['orders_count'] }}</td>
                        <td class="text-end fw-bold text-success">{{ $currency }} {{ number_format($row['total_sales'], 2) }}</td>
                        <td class="text-end">{{ $currency }} {{ number_format($row['avg_order'], 2) }}</td>
                        <td class="text-end">
                            @if($row['avg_rating'] !== null)
                                {{ $row['rating_emoji'] ?? '' }} {{ $row['avg_rating'] }}
                                <small class="text-muted">({{ $row['ratings_count'] }})</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $row['share_pct'] }}%</td>
                        <td class="text-end">{{ $row['unpaid_orders'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="rpt-empty">No waiter sales in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Recent guest ratings</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Invoice</th>
                        <th>Table</th>
                        <th>Waiter</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRatings ?? [] as $r)
                    <tr>
                        <td>{{ $r['rated_at'] }}</td>
                        <td><span class="badge bg-dark">{{ $r['order_number'] }}</span></td>
                        <td>{{ $r['table'] ?? '—' }}</td>
                        <td>{{ $r['waiter_name'] }}</td>
                        <td style="font-size:1.25rem;">{{ $r['emoji'] }} <small class="text-muted">{{ $r['rating'] }}/5</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="rpt-empty">No ratings in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Invoices &amp; KOT waiter</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Time</th>
                        <th>Table</th>
                        <th>Waiter</th>
                        <th>Rating</th>
                        <th>Cashier</th>
                        <th>KOT / BOT</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td><span class="badge bg-dark">{{ $inv['order_number'] }}</span></td>
                        <td>{{ $inv['created_at'] }}</td>
                        <td>{{ $inv['table'] ?? '—' }}</td>
                        <td class="fw-semibold">{{ $inv['waiter_name'] }}</td>
                        <td>
                            @if(!empty($inv['rating']))
                                <span style="font-size:1.1rem;">{{ $inv['rating_emoji'] }}</span>
                                <small class="text-muted">{{ $inv['rating'] }}/5</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $inv['cashier_name'] }}</td>
                        <td>
                            @forelse($inv['kots'] as $kot)
                                <span class="badge {{ $kot['type'] === 'bar' ? 'bg-info' : 'bg-warning text-dark' }} mb-1">
                                    {{ $kot['number'] }} · {{ $kot['waiter_name'] }}
                                </span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-end">{{ $currency }} {{ number_format($inv['total'], 2) }}</td>
                        <td>
                            <span class="badge {{ $inv['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $inv['payment_status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="rpt-empty">No invoices in this range</td></tr>
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
    const cur = @json($currency);
    const charts = @json($charts ?? []);
    const sales = charts.sales || {labels:[], data:[]};
    const orders = charts.orders || {labels:[], data:[]};
    const palette = ['#f59e0b','#10b981','#0ea5e9','#8b5cf6','#ef4444','#ea580c','#64748b','#14b8a6'];
    const sEl = document.getElementById('waiterSalesChart');
    if (sEl) {
        new Chart(sEl, {
            type: 'bar',
            data: { labels: sales.labels, datasets: [{ data: sales.data, backgroundColor: palette, borderRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed.y||0).toLocaleString() } } }, scales: { y: { beginAtZero: true } } }
        });
    }
    const oEl = document.getElementById('waiterOrdersChart');
    if (oEl) {
        new Chart(oEl, {
            type: 'doughnut',
            data: { labels: orders.labels, datasets: [{ data: orders.data, backgroundColor: palette, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
@endpush
