@extends('layouts.admin')
@section('title', 'Billiards Desk')
@section('page_title', 'Billiards')

@section('content')
@include('admin.billiards._styles')
@php
    $cur = $currency;
    $busyByTable = $today->whereIn('status', ['booked','active'])->keyBy('billiard_table_id');
    $endingIds = $endingSoon->pluck('billiard_table_id')->all();
    $unpaidDue = $unpaid->sum(fn ($b) => $b->balanceDue());
    $endingCount = $endingSoon->count();
    $mixTotal = max(1, $poolCount + $snookerCount);
    $sourceLabels = [
        'walk_in' => ['Walk-in', 'fa-person-walking', 'green'],
        'phone' => ['Phone', 'fa-phone', 'sky'],
        'online' => ['Online', 'fa-globe', 'amber'],
        'admin' => ['Admin', 'fa-user-shield', 'emerald'],
    ];
    $sourceTotal = max(1, array_sum($sourceMix));
@endphp

<div class="dash bil-dash">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="bil-alert" id="bilEndAlert" role="alert">
        <i class="fas fa-exclamation-circle mt-1"></i>
        <div id="bilEndAlertText"></div>
    </div>

    {{-- Hero --}}
    <section class="dash-hero">
        <div class="dash-hero-copy" style="display:flex;align-items:center;gap:1rem">
            <img src="{{ asset('images/billiards-icon.png') }}" alt="" width="64" height="64" style="border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.35);flex-shrink:0">
            <div>
            <p class="dash-eyebrow">Billiards · Live floor</p>
            <h2 class="dash-title">Club desk</h2>
            <p class="dash-sub">
                <span class="bil-live"><i></i> Live</span>
                <span class="bil-clock" id="bilClock">—</span>
                · Alert {{ $endAlertMinutes }} min before end
                · {{ $tables->count() }} {{ Str::plural('table', $tables->count()) }}
            </p>
            </div>
        </div>
        <div class="dash-hero-actions">
            <a href="{{ route('billiards.pos') }}" class="dash-cta">
                <i class="fas fa-plus"></i>
                <span>New booking</span>
            </a>
            <a href="{{ route('billiards.display') }}" target="_blank" class="dash-cta" style="background:linear-gradient(135deg,#0f4a37,#16664c);box-shadow:0 10px 24px rgba(15,74,55,.35)">
                <i class="fas fa-tv"></i>
                <span>Display</span>
            </a>
        </div>
    </section>

    {{-- KPIs --}}
    <section class="dash-kpis">
        <article class="dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-blue"><i class="fas fa-table-tennis-paddle-ball"></i></span>
                <span class="dash-kpi-tag">Free</span>
            </div>
            <div class="dash-kpi-value">{{ $freeCount }}</div>
            <div class="dash-kpi-label">Ready to book</div>
        </article>
        <article class="dash-kpi {{ $endingCount > 0 ? 'dash-kpi--alert' : '' }}">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-amber"><i class="fas fa-bowling-ball"></i></span>
                <span class="dash-kpi-tag">In play</span>
            </div>
            <div class="dash-kpi-value">{{ $busyCount }}</div>
            <div class="dash-kpi-label">{{ $endingCount > 0 ? $endingCount.' ending soon' : 'Active sessions' }} · {{ $utilization }}% load</div>
        </article>
        <article class="dash-kpi dash-kpi--sales">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon"><i class="fas fa-coins"></i></span>
                <span class="dash-kpi-tag">Today</span>
            </div>
            <div class="dash-kpi-value">{{ $cur }} {{ number_format($todayRevenue, 0) }}</div>
            <div class="dash-kpi-label">{{ $today->count() }} sessions · {{ $todayHours }}h · booked {{ $cur }} {{ number_format($todayBooked, 0) }}</div>
        </article>
        <article class="dash-kpi {{ $unpaidDue > 0 ? 'dash-kpi--alert' : '' }}">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon is-fire"><i class="fas fa-receipt"></i></span>
                <span class="dash-kpi-tag">Unpaid</span>
            </div>
            <div class="dash-kpi-value" style="{{ $unpaidDue > 0 ? 'color:#dc2626' : '' }}">{{ $cur }} {{ number_format($unpaidDue, 0) }}</div>
            <div class="dash-kpi-label">{{ $unpaid->count() }} open bills</div>
        </article>
    </section>

    {{-- Icon menu --}}
    <section class="dash-shortcuts" aria-label="Billiards menu">
        <a href="{{ route('billiards.pos') }}" class="dash-shortcut"><i class="fas fa-plus-circle"></i>New booking</a>
        <a href="{{ route('billiards.bookings.index') }}" class="dash-shortcut"><i class="fas fa-calendar-check"></i>Bookings</a>
        <a href="{{ route('billiards.tables.index') }}" class="dash-shortcut"><i class="fas fa-th-large"></i>Tables</a>
        <a href="{{ route('billiards.reports') }}" class="dash-shortcut"><i class="fas fa-chart-line"></i>Reports</a>
        <a href="{{ route('billiards.settings') }}" class="dash-shortcut"><i class="fas fa-cog"></i>Settings</a>
        <a href="{{ route('billiards.display') }}" target="_blank" class="dash-shortcut"><i class="fas fa-tv"></i>Display</a>
    </section>

    {{-- Chart + mix --}}
    <section class="dash-grid-main">
        <div class="dash-panel dash-panel--chart">
            <div class="dash-panel-head">
                <div>
                    <h3>Revenue trend</h3>
                    <p>Last 7 days · collected payments</p>
                </div>
                <span class="dash-chip">{{ $cur }} {{ number_format($weekRevenue, 0) }} week</span>
            </div>
            <div class="dash-chart-wrap">
                <canvas id="bilRevenueChart"></canvas>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h3>Today’s mix</h3>
                    <p>Tables & booking sources</p>
                </div>
            </div>
            <div class="dash-types">
                @foreach([
                    ['label' => 'Pool', 'count' => $poolCount, 'icon' => 'fa-circle', 'tone' => 'green'],
                    ['label' => 'Snooker', 'count' => $snookerCount, 'icon' => 'fa-bullseye', 'tone' => 'amber'],
                ] as $t)
                    @php $pct = round(($t['count'] / $mixTotal) * 100); @endphp
                    <div class="dash-type">
                        <div class="dash-type-row">
                            <span class="dash-type-icon tone-{{ $t['tone'] }}"><i class="fas {{ $t['icon'] }}"></i></span>
                            <div class="dash-type-meta">
                                <strong>{{ $t['label'] }}</strong>
                                <span>{{ $pct }}% of sessions</span>
                            </div>
                            <div class="dash-type-count">{{ $t['count'] }}</div>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @endforeach
                @foreach($sourceLabels as $key => $meta)
                    @php
                        $cnt = (int) ($sourceMix[$key] ?? 0);
                        $pct = round(($cnt / $sourceTotal) * 100);
                    @endphp
                    @continue($cnt === 0)
                    <div class="dash-type">
                        <div class="dash-type-row">
                            <span class="dash-type-icon tone-{{ $meta[2] }}"><i class="fas {{ $meta[1] }}"></i></span>
                            <div class="dash-type-meta">
                                <strong>{{ $meta[0] }}</strong>
                                <span>{{ $pct }}% source mix</span>
                            </div>
                            <div class="dash-type-count">{{ $cnt }}</div>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- AI-style insights --}}
    <section class="dash-panel bil-insights">
        <div class="dash-panel-head">
            <div>
                <h3><i class="fas fa-magic" style="color:var(--dash-amber);margin-right:.35rem"></i>Floor insights</h3>
                <p>Smart cues from today’s play and collections</p>
            </div>
        </div>
        <div class="bil-insight-grid">
            @foreach($insights as $tip)
                <article class="bil-insight tone-{{ $tip['tone'] }}">
                    <span class="bil-insight-ico"><i class="fas {{ $tip['icon'] }}"></i></span>
                    <div>
                        <strong>{{ $tip['title'] }}</strong>
                        <p>{{ $tip['body'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Club floor --}}
    <div class="bil-head">
        <h2>Club floor</h2>
        <span>{{ $tables->count() }} {{ Str::plural('table', $tables->count()) }} · tap free to book</span>
    </div>

    <section class="bil-stage" aria-label="Tables">
        <div class="bil-stage-felt">
            <div class="bil-grid">
                @forelse($tables as $t)
                    @php
                        $b = $busyByTable->get($t->id);
                        $ending = in_array($t->id, $endingIds, true);
                        $state = $ending ? 'ending' : ($b ? 'busy' : 'free');
                        $href = $b
                            ? route('billiards.bookings.show', $b)
                            : route('billiards.pos', ['table' => $t->id]);
                        $progress = 0;
                        if ($b && $b->scheduled_start && $b->scheduled_end) {
                            $total = max(1, $b->scheduled_start->diffInSeconds($b->scheduled_end));
                            $done = max(0, $b->scheduled_start->diffInSeconds(now()));
                            $progress = min(100, round(($done / $total) * 100));
                        }
                        $minsLeft = $b?->minutesRemaining();
                        $endsMs = $b?->scheduled_end?->getTimestampMs();
                    @endphp
                    <a href="{{ $href }}" class="bil-table {{ $state }}" @if($endsMs && $b && $b->status === 'active') data-ends-ms="{{ $endsMs }}" @endif>
                        <span class="t-status">{{ $ending ? 'ENDING' : ($b ? strtoupper($b->status) : 'FREE') }}</span>
                        <div class="bil-table-felt" aria-hidden="true">
                            <span class="pocket tl"></span>
                            <span class="pocket tr"></span>
                            <span class="pocket tm"></span>
                            <span class="pocket bl"></span>
                            <span class="pocket br"></span>
                            <span class="pocket bm"></span>
                            <span class="ball"></span>
                        </div>
                        <div class="bil-table-body">
                            <div class="t-name">{{ $t->name }}</div>
                            <div class="t-type">{{ $t->typeLabel() }}</div>
                            <div class="t-rate">{{ $cur }} {{ number_format((float) $t->hourly_rate, 0) }}/hr</div>
                            @if($b)
                                <div class="t-guest">
                                    {{ $b->displayName() }}
                                    @if($b->status === 'active')
                                        · <span class="bil-countdown" style="font-variant-numeric:tabular-nums;font-weight:800;color:#f0d078">--:--</span>
                                    @elseif($minsLeft !== null)
                                        · {{ $minsLeft >= 0 ? $minsLeft.' min left' : 'overtime' }}
                                    @endif
                                </div>
                                <div class="t-bar" title="Session progress"><span style="width:{{ $progress }}%"></span></div>
                            @else
                                <div class="t-guest" style="opacity:.7">Tap to book</div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="bil-empty-floor">
                        No tables on the floor yet.<br>
                        <a href="{{ route('billiards.tables.index') }}">Add pool or snooker tables →</a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Session board + Collect --}}
    <div class="bil-boards">
        <section class="bil-board">
            <div class="bil-head" style="margin-bottom:.1rem">
                <h2>Session board</h2>
                <span>{{ $today->count() }} today</span>
            </div>
            @forelse($today as $b)
                <div class="bil-row">
                    <div>
                        <div class="title">{{ $b->table?->name ?? 'Table' }} · {{ $b->displayName() }}</div>
                        <div class="meta">
                            {{ $b->booking_number }} · {{ $b->scheduled_start?->format('H:i') }}–{{ $b->scheduled_end?->format('H:i') }}
                            · {{ rtrim(rtrim(number_format((float)$b->hours, 2), '0'), '.') }}h
                            · {{ $cur }} {{ number_format((float) $b->amount, 0) }}
                        </div>
                    </div>
                    <div class="right">
                        <span class="bil-chip bil-chip--{{ $b->status === 'active' ? 'ok' : ($b->status === 'booked' ? 'info' : 'mute') }}">{{ $b->statusLabel() }}</span>
                        <span class="bil-chip bil-chip--{{ $b->payment_status === 'paid' ? 'ok' : ($b->payment_status === 'partial' ? 'warn' : 'danger') }}">{{ ucfirst($b->payment_status) }}</span>
                        <a class="bil-link" href="{{ route('billiards.bookings.show', $b) }}">Open →</a>
                    </div>
                </div>
            @empty
                <div class="bil-empty">
                    <div class="ico"><i class="fas fa-bowling-ball"></i></div>
                    No sessions today.<br>Tap a free table or New booking to start.
                </div>
            @endforelse
        </section>

        <section class="bil-board">
            <div class="bil-head" style="margin-bottom:.1rem">
                <h2>Collect</h2>
                <span>Unpaid</span>
            </div>
            @forelse($unpaid as $b)
                <div class="bil-row">
                    <div>
                        <div class="title">{{ $b->displayName() }}</div>
                        <div class="meta">{{ $b->booking_number }} · {{ ucfirst(str_replace('_', ' ', $b->source)) }} · {{ $b->displayPhone() ?: 'no phone' }}</div>
                        <div class="meta" style="color:var(--bil-warn);font-weight:700">Due {{ $cur }} {{ number_format($b->balanceDue(), 2) }}</div>
                    </div>
                    <div class="right">
                        <a class="bil-btn bil-btn--primary" style="min-height:40px;padding:.35rem .85rem;font-size:.78rem" href="{{ route('billiards.bookings.show', $b) }}">Pay</a>
                        @if($smsReady && $b->displayPhone())
                            <form method="post" action="{{ route('billiards.bookings.remind', $b) }}">
                                @csrf
                                <button class="bil-btn bil-btn--ghost" style="min-height:36px;padding:.3rem .7rem;font-size:.74rem">SMS</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bil-empty">
                    <div class="ico"><i class="fas fa-check"></i></div>
                    All clear — nothing to collect.
                </div>
            @endforelse
        </section>
    </div>
</div>

<a href="{{ route('billiards.pos') }}" class="bil-fab" aria-label="New booking">
    <i class="fas fa-plus"></i>
</a>
@endsection

@push('styles')
@include('partials.admin-dash-styles')
<style>
    .bil-dash {
        margin: 0;
        max-width: none;
    }
    .bil-dash .dash-sub {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.55rem 0.85rem;
    }
    .bil-dash .bil-live { color: #86efac; }
    .bil-dash .bil-clock { color: #fde68a; font-weight: 700; font-variant-numeric: tabular-nums; }
    .bil-insights { margin-top: 0; }
    .bil-insight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 0.75rem;
    }
    .bil-insight {
        display: flex;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        border-radius: 14px;
        border: 1px solid var(--dash-line);
        background: var(--dash-soft);
    }
    .bil-insight strong {
        display: block;
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
        color: var(--dash-ink);
    }
    .bil-insight p {
        margin: 0;
        font-size: 0.8rem;
        line-height: 1.4;
        color: var(--dash-muted);
    }
    .bil-insight-ico {
        width: 36px; height: 36px; flex-shrink: 0;
        border-radius: 10px;
        display: grid; place-items: center;
        background: #fff7ed;
        color: var(--dash-amber2);
    }
    .bil-insight.tone-warn { background: #fff7ed; border-color: #fed7aa; }
    .bil-insight.tone-warn .bil-insight-ico { background: #ffedd5; color: #c2410c; }
    .bil-insight.tone-danger { background: #fef2f2; border-color: #fecaca; }
    .bil-insight.tone-danger .bil-insight-ico { background: #fee2e2; color: #b91c1c; }
    .bil-insight.tone-ok { background: #f0fdf4; border-color: #bbf7d0; }
    .bil-insight.tone-ok .bil-insight-ico { background: #dcfce7; color: #15803d; }
    .bil-insight.tone-info .bil-insight-ico { background: #eff6ff; color: #2563eb; }
    .bil-dash .bil-head { margin-top: 0.25rem; }
    .bil-dash .bil-boards { margin-top: 0.25rem; }
    /* Keep floor/board styles when nested under dash (not bil-shell) */
    .bil-dash {
        --bil-ink: #10231c;
        --bil-muted: #5a6e64;
        --bil-soft: #8a9a92;
        --bil-felt: #0a3326;
        --bil-felt-2: #0f4a37;
        --bil-gold: #d4a017;
        --bil-gold-deep: #b8860b;
        --bil-paper: #f4f1ea;
        --bil-white: #fffcf7;
        --bil-line: rgba(16,35,28,.09);
        --bil-ok: #159a5a;
        --bil-warn: #c45c12;
        --bil-danger: #c62828;
        --bil-font: 'Instrument Sans', system-ui, sans-serif;
        --bil-display: 'Outfit', system-ui, sans-serif;
        --bil-radius: 18px;
        font-family: var(--bil-font);
        padding-bottom: 4.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('bilRevenueChart');
    if (el && window.Chart) {
        const ctx = el.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(245, 158, 11, 0.28)');
        gradient.addColorStop(1, 'rgba(245, 158, 11, 0.02)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($revenue_chart['labels']) !!},
                datasets: [{
                    label: 'Collected',
                    data: {!! json_encode($revenue_chart['revenue']) !!},
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
                            label: (c) => '{{ $currency }} ' + Number(c.parsed.y || 0).toLocaleString()
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(28, 25, 23, 0.05)' },
                        ticks: { color: '#a8a29e', callback: (v) => Number(v).toLocaleString() },
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
    }

    const clock = document.getElementById('bilClock');
    function tick(){
        if(!clock) return;
        clock.textContent = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    }
    tick(); setInterval(tick, 1000);

    function fmtLeft(secs){
        const over = secs < 0;
        const abs = Math.abs(Math.floor(secs));
        const m = Math.floor(abs / 60);
        const s = abs % 60;
        const pad = n => String(n).padStart(2,'0');
        return (over ? '+' : '') + pad(m) + ':' + pad(s);
    }
    function tickCountdowns(){
        document.querySelectorAll('.bil-table[data-ends-ms]').forEach(tile => {
            const ends = Number(tile.getAttribute('data-ends-ms'));
            if (!ends) return;
            const secs = Math.floor((ends - Date.now()) / 1000);
            const el = tile.querySelector('.bil-countdown');
            if (el) el.textContent = fmtLeft(secs);
            if (secs >= 0 && secs <= {{ (int) $endAlertMinutes }} * 60) tile.classList.add('ending');
            else if (secs >= 0) tile.classList.remove('ending');
        });
    }
    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    const box = document.getElementById('bilEndAlert');
    const text = document.getElementById('bilEndAlertText');
    async function poll(){
        try{
            const r = await fetch(@json(route('billiards.alerts')), {headers:{'Accept':'application/json'}});
            if(!r.ok) return;
            const data = await r.json();
            const rows = data.ending_soon || [];
            if(!rows.length){ box.classList.remove('show'); return; }
            text.innerHTML = '<strong>Ending soon</strong> — ' + rows.map(x =>
                (x.table||'Table') + ' · ' + (x.customer||'') + ' · ' + x.minutes + ' min (#' + x.number + ')'
            ).join(' · ');
            box.classList.add('show');
        }catch(e){}
    }
    poll();
    setInterval(poll, 20000);
})();
</script>
@endpush
