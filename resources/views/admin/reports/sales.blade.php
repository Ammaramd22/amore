@extends('layouts.admin')
@section('title', 'Sales Report')
@section('page_title', 'Sales Report')
@section('content')
@php
    $cur = $currency ?? 'LKR';
    $types = collect($byType ?? []);
    $dineIn = $types->firstWhere('type', 'dine_in') ?? ['label'=>'Dine In','icon'=>'fa-utensils','color'=>'green','orders'=>0,'total'=>0,'avg'=>0,'share'=>0,'days'=>collect()];
    $takeaway = $types->firstWhere('type', 'takeaway') ?? ['label'=>'Takeaway','icon'=>'fa-bag-shopping','color'=>'amber','orders'=>0,'total'=>0,'avg'=>0,'share'=>0,'days'=>collect()];
    $delivery = $types->firstWhere('type', 'delivery') ?? ['label'=>'Delivery','icon'=>'fa-motorcycle','color'=>'blue','orders'=>0,'total'=>0,'avg'=>0,'share'=>0,'days'=>collect()];
    $express = $types->firstWhere('type', 'express');
@endphp
<style>
    .rpt-type-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }
    @media (max-width: 900px) { .rpt-type-grid { grid-template-columns: 1fr; } }
    .rpt-type-card {
        background: #fff;
        border: 1px solid #e7e5e4;
        border-radius: 18px;
        padding: 1.15rem 1.2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        min-height: 108px;
    }
    .rpt-type-icon {
        width: 56px; height: 56px; border-radius: 16px;
        display: grid; place-items: center; font-size: 1.35rem; flex-shrink: 0;
    }
    .rpt-type-icon.is-green { background: #ecfdf5; color: #059669; }
    .rpt-type-icon.is-amber { background: #fff7ed; color: #ea580c; }
    .rpt-type-icon.is-blue { background: #eff6ff; color: #0284c7; }
    .rpt-type-icon.is-violet { background: #f5f3ff; color: #7c3aed; }
    .rpt-type-card h4 { margin: 0; font-size: .95rem; font-weight: 800; color: #1c1917; }
    .rpt-type-card .val { margin-top: .25rem; font-size: 1.2rem; font-weight: 800; color: #292524; }
    .rpt-type-card .meta { margin-top: .2rem; font-size: .8rem; color: #a8a29e; font-weight: 600; }

    .rpt-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        align-items: start;
    }
    @media (max-width: 992px) { .rpt-split { grid-template-columns: 1fr; } }
    .rpt-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
    .rpt-panel.is-dine { border-color: #a7f3d0; }
    .rpt-panel.is-dine .rpt-panel-head { background: linear-gradient(90deg, #ecfdf5, #fff); }
    .rpt-panel.is-take { border-color: #fed7aa; }
    .rpt-panel.is-take .rpt-panel-head { background: linear-gradient(90deg, #fff7ed, #fff); }
    .rpt-panel.is-del { border-color: #bae6fd; }
    .rpt-panel.is-del .rpt-panel-head { background: linear-gradient(90deg, #eff6ff, #fff); }
    .rpt-panel-head .head-ico {
        width: 34px; height: 34px; border-radius: 10px;
        display: inline-grid; place-items: center; margin-right: .55rem;
        font-size: .95rem;
    }
    .rpt-panel-head .head-ico.is-green { background: #d1fae5; color: #047857; }
    .rpt-panel-head .head-ico.is-amber { background: #ffedd5; color: #c2410c; }
    .rpt-panel-head .head-ico.is-blue { background: #e0f2fe; color: #0369a1; }
</style>

<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-chart-line"></i> Sales</h3>
            <p class="rpt-sub">Dine in · Takeaway · Delivery side by side</p>
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
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['total_sales'], 2) }}</div>
            <div class="rpt-kpi-label">Total Sales</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['total_orders']) }}</div>
            <div class="rpt-kpi-label">Total Orders</div>
            <i class="fas fa-receipt rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['avg_order'] ?? 0, 2) }}</div>
            <div class="rpt-kpi-label">Avg Order</div>
            <i class="fas fa-chart-pie rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-area"></i> Sales trend</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="salesTrendChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-chart-pie"></i> Mix by type</h4>
            <div class="rpt-chart-wrap"><canvas id="salesTypeChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-type-grid">
        <div class="rpt-type-card">
            <div class="rpt-type-icon is-green"><i class="fas fa-utensils"></i></div>
            <div>
                <h4>Dine In</h4>
                <div class="val">{{ $cur }} {{ number_format($dineIn['total'], 2) }}</div>
                <div class="meta">{{ $dineIn['orders'] }} orders · {{ number_format($dineIn['share'], 1) }}%</div>
            </div>
        </div>
        <div class="rpt-type-card">
            <div class="rpt-type-icon is-amber"><i class="fas fa-bag-shopping"></i></div>
            <div>
                <h4>Takeaway</h4>
                <div class="val">{{ $cur }} {{ number_format($takeaway['total'], 2) }}</div>
                <div class="meta">{{ $takeaway['orders'] }} orders · {{ number_format($takeaway['share'], 1) }}%</div>
            </div>
        </div>
        <div class="rpt-type-card">
            <div class="rpt-type-icon is-blue"><i class="fas fa-motorcycle"></i></div>
            <div>
                <h4>Delivery</h4>
                <div class="val">{{ $cur }} {{ number_format($delivery['total'], 2) }}</div>
                <div class="meta">{{ $delivery['orders'] }} orders · {{ number_format($delivery['share'], 1) }}%</div>
            </div>
        </div>
    </div>

    <div class="rpt-split">
        {{-- LEFT: Dine In --}}
        <div class="rpt-side">
            <div class="rpt-panel is-dine">
                <div class="rpt-panel-head">
                    <h4>
                        <span class="head-ico is-green"><i class="fas fa-utensils"></i></span>
                        Dine In
                    </h4>
                    <span class="badge-soft success">{{ $dineIn['orders'] }} orders</span>
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total ({{ $cur }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dineIn['days'] as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d['date'] }}</td>
                                <td class="text-end">{{ $d['orders'] }}</td>
                                <td class="text-end fw-bold" style="color:#059669;">{{ number_format($d['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="rpt-empty"><i class="fas fa-utensils me-1"></i>No dine-in sales</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($dineIn['days']))
                        <tfoot>
                            <tr style="background:#f0fdf4;font-weight:800;">
                                <td>Total</td>
                                <td class="text-end">{{ $dineIn['orders'] }}</td>
                                <td class="text-end">{{ number_format($dineIn['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: Takeaway + Delivery --}}
        <div class="rpt-side">
            <div class="rpt-panel is-take">
                <div class="rpt-panel-head">
                    <h4>
                        <span class="head-ico is-amber"><i class="fas fa-bag-shopping"></i></span>
                        Takeaway
                    </h4>
                    <span class="badge-soft">{{ $takeaway['orders'] }} orders</span>
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total ({{ $cur }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($takeaway['days'] as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d['date'] }}</td>
                                <td class="text-end">{{ $d['orders'] }}</td>
                                <td class="text-end fw-bold" style="color:#ea580c;">{{ number_format($d['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="rpt-empty"><i class="fas fa-bag-shopping me-1"></i>No takeaway sales</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($takeaway['days']))
                        <tfoot>
                            <tr style="background:#fff7ed;font-weight:800;">
                                <td>Total</td>
                                <td class="text-end">{{ $takeaway['orders'] }}</td>
                                <td class="text-end">{{ number_format($takeaway['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="rpt-panel is-del">
                <div class="rpt-panel-head">
                    <h4>
                        <span class="head-ico is-blue"><i class="fas fa-motorcycle"></i></span>
                        Delivery
                    </h4>
                    <span class="badge-soft" style="background:#eff6ff;color:#0369a1;border-color:#bae6fd;">{{ $delivery['orders'] }} orders</span>
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total ({{ $cur }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($delivery['days'] as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d['date'] }}</td>
                                <td class="text-end">{{ $d['orders'] }}</td>
                                <td class="text-end fw-bold" style="color:#0284c7;">{{ number_format($d['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="rpt-empty"><i class="fas fa-motorcycle me-1"></i>No delivery sales</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($delivery['days']))
                        <tfoot>
                            <tr style="background:#eff6ff;font-weight:800;">
                                <td>Total</td>
                                <td class="text-end">{{ $delivery['orders'] }}</td>
                                <td class="text-end">{{ number_format($delivery['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            @if($express && ($express['orders'] ?? 0) > 0)
            <div class="rpt-panel">
                <div class="rpt-panel-head">
                    <h4>
                        <span class="head-ico" style="background:#ede9fe;color:#6d28d9;"><i class="fas fa-bolt"></i></span>
                        Express
                    </h4>
                    <span class="badge-soft">{{ $express['orders'] }} orders</span>
                </div>
                <div class="table-responsive">
                    <table class="table rpt-table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Total ({{ $cur }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($express['days'] as $d)
                            <tr>
                                <td class="fw-semibold">{{ $d['date'] }}</td>
                                <td class="text-end">{{ $d['orders'] }}</td>
                                <td class="text-end fw-bold">{{ number_format($d['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head">
            <h4>All sales (combined)</h4>
            <span class="badge-soft">{{ $from }} → {{ $to }}</span>
        </div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Total ({{ $cur }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $s)
                    <tr>
                        <td class="fw-semibold">{{ $s->date }}</td>
                        <td class="text-end">{{ $s->orders }}</td>
                        <td class="text-end fw-bold" style="color:#059669;">{{ number_format($s->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="rpt-empty">No sales in this period</td></tr>
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
    const trend = @json($charts['trend'] ?? ['labels'=>[], 'sales'=>[], 'orders'=>[]]);
    const types = @json($charts['types'] ?? ['labels'=>[], 'data'=>[], 'colors'=>[]]);

    const trendEl = document.getElementById('salesTrendChart');
    if (trendEl) {
        const ctx = trendEl.getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, 260);
        g.addColorStop(0, 'rgba(16,185,129,.28)');
        g.addColorStop(1, 'rgba(16,185,129,.02)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [
                    { label: 'Sales', data: trend.sales, borderColor: '#10b981', backgroundColor: g, fill: true, tension: .35, yAxisID: 'y' },
                    { label: 'Orders', data: trend.orders, borderColor: '#f59e0b', backgroundColor: 'transparent', tension: .35, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString() } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    const typeEl = document.getElementById('salesTypeChart');
    if (typeEl) {
        new Chart(typeEl, {
            type: 'doughnut',
            data: {
                labels: types.labels,
                datasets: [{ data: types.data, backgroundColor: types.colors || ['#10b981','#f59e0b','#0ea5e9','#8b5cf6'], borderWidth: 0 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: c => cur + ' ' + Number(c.parsed || 0).toLocaleString() } }
                }
            }
        });
    }
})();
</script>
@endpush
