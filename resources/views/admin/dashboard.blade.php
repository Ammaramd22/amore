@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
@php
    $orderTypeTotal = max(1, $dine_in_orders + $takeaway_orders + $delivery_orders);
    $maxBest = max(1, (float) ($best_selling->max('total_qty') ?? 1));
    $rangePills = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'Week',
        'this_month' => 'Month',
        'custom' => 'Custom',
    ];
@endphp

<div class="dash">
    {{-- Hero --}}
    <section class="dash-hero">
        <div class="dash-hero-copy">
            <p class="dash-eyebrow">ResPOS overview</p>
            <h2 class="dash-title">{{ $range_label }} at a glance</h2>
            <p class="dash-sub">Sales, kitchen load, and what guests are ordering — keep the floor moving.</p>
        </div>
        <div class="dash-hero-actions">
            <form method="GET" class="dash-range" id="dashRangeForm">
                @if(!empty($branchFilter['enabled']))
                <div class="mb-2" style="min-width:180px">
                    @include('partials.branch-filter', ['branchFilter' => $branchFilter, 'branchFilterAutoSubmit' => true])
                </div>
                @endif
                <div class="dash-pills" role="tablist">
                    @foreach($rangePills as $key => $label)
                        <button type="submit" name="range" value="{{ $key }}"
                            class="dash-pill {{ $range === $key ? 'active' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
                @if($range === 'custom')
                <div class="dash-custom-dates">
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" onchange="this.form.submit()">
                    <span class="text-muted">to</span>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" onchange="this.form.submit()">
                </div>
                @endif
            </form>
            <a href="{{ route('pos.index') }}" class="dash-cta">
                <i class="fas fa-cash-register"></i>
                <span>Open POS</span>
            </a>
        </div>
    </section>

    {{-- KPIs --}}
    <section class="dash-kpis">
        <article class="dash-kpi dash-kpi--sales">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon"><i class="fas fa-coins"></i></span>
                <span class="dash-kpi-tag">Sales</span>
            </div>
            <div class="dash-kpi-value">{{ $currency }} {{ number_format($today_sales, 0) }}</div>
            <div class="dash-kpi-label">Completed sales · {{ $range_label }}</div>
        </article>
        <article class="dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-amber"><i class="fas fa-receipt"></i></span>
                <span class="dash-kpi-tag">Orders</span>
            </div>
            <div class="dash-kpi-value">{{ $today_orders }}</div>
            <div class="dash-kpi-label">Avg {{ $currency }} {{ number_format($avg_order, 0) }} / paid bill</div>
        </article>
        <article class="dash-kpi {{ $pending_kitchen > 0 ? 'dash-kpi--alert' : '' }}">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-fire"><i class="fas fa-fire"></i></span>
                <span class="dash-kpi-tag">Kitchen</span>
            </div>
            <div class="dash-kpi-value">{{ $pending_kitchen }}</div>
            <div class="dash-kpi-label">Pending / preparing now</div>
        </article>
        <article class="dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-blue"><i class="fas fa-users"></i></span>
                <span class="dash-kpi-tag">Guests</span>
            </div>
            <div class="dash-kpi-value">{{ $active_customers }}</div>
            <div class="dash-kpi-label">Active customers in system</div>
        </article>
        @if(!empty($cheque_management_enabled))
        <article class="dash-kpi {{ ($cheque_overdue_count ?? 0) > 0 ? 'dash-kpi--alert' : '' }}">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-amber"><i class="fas fa-money-check-alt"></i></span>
                <span class="dash-kpi-tag">Cheques</span>
            </div>
            <div class="dash-kpi-value">{{ $cheque_pending_count ?? 0 }}</div>
            <div class="dash-kpi-label">
                {{ $currency }} {{ number_format($cheque_pending_total ?? 0, 0) }} pending
                @if(($cheque_overdue_count ?? 0) > 0)
                    · {{ $cheque_overdue_count }} overdue
                @endif
            </div>
        </article>
        @endif
        @if(!empty($billiards_enabled))
        <article class="dash-kpi {{ ($billiards_ending_soon ?? collect())->isNotEmpty() ? 'dash-kpi--alert' : '' }}">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-amber"><i class="fas fa-bowling-ball"></i></span>
                <span class="dash-kpi-tag">Billiards</span>
            </div>
            <div class="dash-kpi-value">{{ $billiards_today_count ?? 0 }}</div>
            <div class="dash-kpi-label">
                Today · {{ $billiards_active_count ?? 0 }} playing
                @if(($billiards_unpaid_count ?? 0) > 0)
                    · {{ $billiards_unpaid_count }} unpaid
                @endif
            </div>
        </article>
        @endif
    </section>

    {{-- Quick links --}}
    <section class="dash-shortcuts">
        <a href="{{ route('pos.index') }}" class="dash-shortcut"><i class="fas fa-cash-register"></i>POS Billing</a>
        <a href="{{ route('waiter.index') }}" class="dash-shortcut"><i class="fas fa-user-tie"></i>Waiter Panel</a>
        <a href="{{ route('kds.index') }}" class="dash-shortcut"><i class="fas fa-fire"></i>Kitchen</a>
        @if(!empty($billiards_enabled))
        <a href="{{ route('billiards.desk') }}" class="dash-shortcut"><i class="fas fa-bowling-ball"></i>Billiards</a>
        @endif
        <a href="{{ route('reports.index') }}" class="dash-shortcut"><i class="fas fa-chart-line"></i>Reports</a>
        <a href="{{ route('orders.index') }}" class="dash-shortcut"><i class="fas fa-list"></i>Orders</a>
        <a href="{{ route('settings.index') }}" class="dash-shortcut"><i class="fas fa-cog"></i>Settings</a>
    </section>

    {{-- Chart + types --}}
    <section class="dash-grid-main">
        <div class="dash-panel dash-panel--chart">
            <div class="dash-panel-head">
                <div>
                    <h3>Sales trend</h3>
                    <p>{{ $range_label }} · completed order totals</p>
                </div>
                <span class="dash-chip">{{ $completed_orders }} paid</span>
            </div>
            <div class="dash-chart-wrap">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h3>Order mix</h3>
                    <p>How guests ordered</p>
                </div>
            </div>
            <div class="dash-types">
                @php
                    $types = [
                        ['label' => 'Dine-in', 'count' => $dine_in_orders, 'icon' => 'fa-utensils', 'tone' => 'green'],
                        ['label' => 'Takeaway', 'count' => $takeaway_orders, 'icon' => 'fa-bag-shopping', 'tone' => 'sky'],
                        ['label' => 'Delivery', 'count' => $delivery_orders, 'icon' => 'fa-motorcycle', 'tone' => 'amber'],
                    ];
                @endphp
                @foreach($types as $t)
                    @php $pct = round(($t['count'] / $orderTypeTotal) * 100); @endphp
                    <div class="dash-type">
                        <div class="dash-type-row">
                            <span class="dash-type-icon tone-{{ $t['tone'] }}"><i class="fas {{ $t['icon'] }}"></i></span>
                            <div class="dash-type-meta">
                                <strong>{{ $t['label'] }}</strong>
                                <span>{{ $pct }}% of mix</span>
                            </div>
                            <div class="dash-type-count">{{ $t['count'] }}</div>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @endforeach
                <div class="dash-type dash-type--done">
                    <div class="dash-type-row">
                        <span class="dash-type-icon tone-emerald"><i class="fas fa-circle-check"></i></span>
                        <div class="dash-type-meta">
                            <strong>Completed</strong>
                            <span>Paid in this range</span>
                        </div>
                        <div class="dash-type-count">{{ $completed_orders }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Best sellers + stock --}}
    <section class="dash-grid-two">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h3>Best sellers</h3>
                    <p>Top items by quantity</p>
                </div>
            </div>
            <div class="dash-rank">
                @forelse($best_selling as $i => $item)
                    @php $w = round(((float) $item->total_qty / $maxBest) * 100); @endphp
                    <div class="dash-rank-row">
                        <span class="dash-rank-n">{{ $i + 1 }}</span>
                        <div class="dash-rank-body">
                            <div class="dash-rank-top">
                                <strong>{{ $item->name }}</strong>
                                <span>{{ (float) $item->total_qty }}</span>
                            </div>
                            <div class="dash-bar"><span style="width: {{ $w }}%"></span></div>
                        </div>
                    </div>
                @empty
                    <div class="dash-empty">No sales in this range yet</div>
                @endforelse
            </div>
        </div>

        <div class="dash-panel {{ $low_stock_items->count() ? 'dash-panel--warn' : '' }}">
            <div class="dash-panel-head">
                <div>
                    <h3>Stock watch</h3>
                    <p>
                        @if($low_stock_items->count())
                            {{ $low_stock_items->count() }} item{{ $low_stock_items->count() === 1 ? '' : 's' }} at or below reorder
                        @else
                            Inventory looks healthy
                        @endif
                    </p>
                </div>
                <a href="{{ route('ingredients.index') }}" class="dash-link">Manage</a>
            </div>
            <div class="dash-stock">
                @forelse($low_stock_items->take(8) as $item)
                    <div class="dash-stock-row">
                        <div>
                            <strong>{{ $item->name }}</strong>
                            <span>Reorder at {{ $item->reorder_level }} {{ $item->unit }}</span>
                        </div>
                        <span class="dash-stock-qty">{{ $item->stock_quantity }} {{ $item->unit }}</span>
                    </div>
                @empty
                    <div class="dash-empty dash-empty--ok">
                        <i class="fas fa-check-circle"></i>
                        All stock levels above reorder
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="dash-grid-two mb-3">
        <div class="dash-panel {{ ($expiring_items ?? collect())->count() ? 'dash-panel--warn' : '' }}">
            <div class="dash-panel-head">
                <div>
                    <h3>Expiry watch</h3>
                    <p>
                        @php $expCount = ($expiring_items ?? collect())->count(); @endphp
                        @if($expCount)
                            {{ $expCount }} item{{ $expCount === 1 ? '' : 's' }} expired or within {{ (int) ($expiry_remind_days ?? 7) }} day{{ (int) ($expiry_remind_days ?? 7) === 1 ? '' : 's' }}
                        @else
                            No products or ingredients due soon
                        @endif
                    </p>
                </div>
                <a href="{{ route('settings.index') }}#pos" class="dash-link">Remind days</a>
            </div>
            <div class="dash-stock dash-expiry-list">
                @forelse(($expiring_items ?? collect())->take(10) as $item)
                    @php
                        $daysLeft = $item->expiry_date ? now()->startOfDay()->diffInDays($item->expiry_date->copy()->startOfDay(), false) : null;
                        $badge = $daysLeft === null ? '' : ($daysLeft < 0 ? 'Expired' : ($daysLeft === 0 ? 'Today' : $daysLeft.'d left'));
                        $urgent = $daysLeft !== null && $daysLeft <= 1;
                        $rowClass = $urgent ? 'dash-expiry-row dash-expiry-row--blink' : 'dash-expiry-row';
                        $qty = number_format((float) ($item->stock ?? 0), (fmod((float) ($item->stock ?? 0), 1) == 0.0) ? 0 : 3);
                        $unit = $item->unit ?? 'qty';
                    @endphp
                    <div class="{{ $rowClass }}">
                        <img src="{{ $item->image ?? '/images/product-placeholder.svg' }}"
                             alt="{{ $item->name }}"
                             class="dash-expiry-photo"
                             onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                        <div class="dash-expiry-meta">
                            <strong>{{ $item->name }}</strong>
                            <span>
                                {{ $item->type === 'product' ? 'Product' : 'Ingredient' }}
                                · {{ optional($item->expiry_date)->format('d M Y') }}
                                @if($badge) · {{ $badge }} @endif
                                · <strong class="dash-expiry-qty">{{ $qty }} {{ $unit }} left</strong>
                            </span>
                        </div>
                        <div class="dash-expiry-actions">
                            <span class="dash-expiry-badge {{ $urgent ? 'dash-expiry-badge--urgent' : '' }}">
                                {{ $badge ?: '—' }}
                            </span>
                            <a href="{{ $item->url }}" class="dash-expiry-update">Update</a>
                        </div>
                    </div>
                @empty
                    <div class="dash-empty dash-empty--ok">
                        <i class="fas fa-check-circle"></i>
                        Nothing expiring in the reminder window
                    </div>
                @endforelse
            </div>
        </div>

        @if(!empty($cheque_management_enabled))
        <div class="dash-panel {{ ($cheque_overdue_count ?? 0) > 0 ? 'dash-panel--warn' : '' }}">
            <div class="dash-panel-head">
                <div>
                    <h3>Cheque dues</h3>
                    <p>
                        @if(($cheque_pending_count ?? 0) > 0)
                            {{ $cheque_pending_count }} pending · {{ $currency }} {{ number_format($cheque_pending_total ?? 0, 2) }}
                            @if(($cheque_overdue_count ?? 0) > 0) · {{ $cheque_overdue_count }} overdue @endif
                        @else
                            No pending supplier cheques
                        @endif
                    </p>
                </div>
                <a href="{{ route('cheques.index') }}" class="dash-link">Manage cheques</a>
            </div>
            <div class="table-responsive">
                <table class="table dash-table mb-0">
                    <thead>
                        <tr>
                            <th>Cheque</th>
                            <th>Supplier</th>
                            <th>Due</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheque_dues as $chq)
                        <tr>
                            <td><a href="{{ route('cheques.show', $chq) }}" class="dash-order-link">{{ $chq->cheque_number }}</a></td>
                            <td>{{ $chq->supplier?->name ?? '—' }}</td>
                            <td>
                                {{ $chq->due_date->format('d M Y') }}
                                @if($chq->due_date->lt(now()->startOfDay()))
                                    <span class="dash-badge tone-amber">Overdue</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $currency }} {{ number_format($chq->amount, 2) }}</td>
                            <td><span class="dash-badge tone-amber">Pending</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5"><div class="dash-empty dash-empty--ok"><i class="fas fa-check-circle"></i> No cheque dues</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h3>Cheque dues</h3>
                    <p>Cheque management is off</p>
                </div>
            </div>
            <div class="dash-empty">Enable cheques in settings to track supplier dues here</div>
        </div>
        @endif

        @if(!empty($billiards_enabled))
        <div class="dash-panel {{ ($billiards_ending_soon ?? collect())->isNotEmpty() ? 'dash-panel--warn' : '' }}">
            <div class="dash-panel-head">
                <div>
                    <h3>Today’s billiards</h3>
                    <p>
                        {{ $billiards_today_count ?? 0 }} bookings
                        · {{ $billiards_active_count ?? 0 }} playing
                        @if(($billiards_unpaid_count ?? 0) > 0) · {{ $billiards_unpaid_count }} unpaid @endif
                        @if(($billiards_ending_soon ?? collect())->isNotEmpty())
                            · {{ $billiards_ending_soon->count() }} ending soon
                        @endif
                    </p>
                </div>
                <a href="{{ route('billiards.desk') }}" class="dash-link">Open desk</a>
            </div>
            <div class="table-responsive">
                <table class="table dash-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Table</th>
                            <th>Customer</th>
                            <th>Time</th>
                            <th>Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billiards_today as $b)
                        <tr>
                            <td><a href="{{ route('billiards.bookings.show', $b) }}" class="dash-order-link">{{ $b->booking_number }}</a></td>
                            <td>{{ $b->table?->name ?? '—' }}</td>
                            <td>{{ $b->displayName() }}</td>
                            <td>{{ $b->scheduled_start?->format('H:i') }}–{{ $b->scheduled_end?->format('H:i') }}</td>
                            <td><span class="dash-badge {{ $b->payment_status === 'paid' ? 'tone-ok' : 'tone-amber' }}">{{ ucfirst($b->payment_status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5"><div class="dash-empty dash-empty--ok"><i class="fas fa-check-circle"></i> No billiards bookings today</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </section>

    {{-- Recent orders --}}
    <section class="dash-panel dash-panel--table">
        <div class="dash-panel-head">
            <div>
                <h3>Recent orders</h3>
                <p>Latest activity across the floor</p>
            </div>
            <a href="{{ route('orders.index') }}" class="dash-link">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table dash-table mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Table / Guest</th>
                        <th>Waiter</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="dash-order-link">{{ $order->order_number }}</a>
                        </td>
                        <td>
                            <span class="dash-badge tone-{{ $order->order_type === 'dine_in' ? 'green' : ($order->order_type === 'takeaway' ? 'sky' : 'amber') }}">
                                {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}
                            </span>
                        </td>
                        <td>{{ $order->table?->name ?? $order->customer?->name ?? 'Walk-in' }}</td>
                        <td class="text-muted">{{ $order->waiter?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ $currency }} {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span class="dash-badge tone-{{ $order->status === 'completed' ? 'green' : ($order->status === 'cancelled' ? 'red' : 'amber') }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $order->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No orders yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('styles')
@include('partials.admin-dash-styles')
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('salesChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(245, 158, 11, 0.28)');
    gradient.addColorStop(1, 'rgba(245, 158, 11, 0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($sales_chart['labels']) !!},
            datasets: [{
                label: 'Sales',
                data: {!! json_encode($sales_chart['data']) !!},
                borderColor: '#f59e0b',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointBackgroundColor: '#ea580c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1c1917',
                    titleColor: '#fff7ed',
                    bodyColor: '#fbbf24',
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => '{{ $currency }} ' + Number(ctx.parsed.y || 0).toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(28, 25, 23, 0.05)' },
                    ticks: {
                        color: '#a8a29e',
                        callback: (v) => Number(v).toLocaleString()
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#a8a29e', maxRotation: 0 },
                    border: { display: false }
                }
            }
        }
    });
})();
</script>
@endpush
