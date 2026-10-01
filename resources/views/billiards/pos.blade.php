@extends('layouts.billiards')

@section('title', 'Create Booking')

@push('styles')
<style>
.rpos {
    --ink: #1c1917; --muted: #78716c; --line: #e7e5e4; --card: #fff;
    --amber: #f59e0b; --amber2: #ea580c; --felt: #0a3326; --felt2: #0f4a37;
    padding: 12px 14px 18px; max-width: 100%;
}
.rpos-shell {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr) minmax(320px, 380px);
    gap: 12px;
    min-height: calc(100vh - 78px);
    align-items: stretch;
}
.rpos-rail {
    background: linear-gradient(180deg, #14110f 0%, #1c1917 100%);
    border-radius: 18px;
    padding: 14px 12px;
    display: flex; flex-direction: column; gap: 10px;
    color: #fff7ed;
    box-shadow: 0 10px 28px rgba(20,17,15,.2);
    min-height: 0; overflow: hidden;
}
.rpos-rail-title {
    font-size: .72rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: #fbbf24; padding: 0 6px 8px;
    border-bottom: 1px solid rgba(255,247,237,.12);
}
.rpos-cust label {
    font-size: .7rem; font-weight: 700; color: rgba(255,247,237,.65);
    display: block; margin-bottom: 4px;
}
.rpos-cust .form-control {
    border-radius: 12px; min-height: 44px; font-weight: 600;
    border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.08);
    color: #fff; font-family: inherit;
}
.rpos-cust .form-control::placeholder { color: rgba(255,247,237,.4); }
.rpos-cust .form-control:focus {
    border-color: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,.2);
    background: rgba(255,255,255,.12); color: #fff;
}
.rpos-cust-drop {
    position: absolute; left: 0; right: 0; top: 100%; z-index: 30;
    background: #fff; color: var(--ink); border-radius: 12px;
    max-height: 180px; overflow: auto; display: none;
    box-shadow: 0 12px 28px rgba(0,0,0,.2); border: 1px solid var(--line);
}
.rpos-cust-drop button {
    display: block; width: 100%; text-align: left; border: 0; background: #fff;
    padding: 10px 12px; border-bottom: 1px solid #f5f5f4; font-family: inherit; cursor: pointer;
}
.rpos-cust-drop button:hover { background: #fffbeb; }
.rpos-cats { display: flex; flex-direction: column; gap: 8px; flex: 1; overflow-y: auto; }
.rpos-cat {
    display: flex; align-items: center; gap: .65rem;
    min-height: 52px; padding: 10px 12px; border-radius: 14px;
    border: 1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.04);
    color: rgba(255,247,237,.85); font-weight: 700; font-family: inherit; cursor: pointer;
    text-align: left; transition: .15s ease;
}
.rpos-cat i { width: 20px; text-align: center; color: var(--amber); }
.rpos-cat:hover { background: rgba(245,158,11,.15); border-color: rgba(245,158,11,.35); }
.rpos-cat.active {
    background: linear-gradient(135deg, var(--amber), var(--amber2));
    border-color: transparent; color: #fff;
}
.rpos-cat.active i { color: #fff; }
.rpos-cat .cnt {
    margin-left: auto; font-size: .75rem; opacity: .8;
    background: rgba(0,0,0,.2); padding: 2px 8px; border-radius: 999px;
}

.rpos-mid {
    min-width: 0; display: flex; flex-direction: column; gap: 12px; min-height: 0;
}
.rpos-panel {
    background: var(--card); border-radius: 18px; border: 1px solid var(--line);
    box-shadow: 0 4px 20px rgba(28,25,23,.06); overflow: hidden;
    display: flex; flex-direction: column; min-height: 0;
}
.rpos-panel-head {
    padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
    border-bottom: 1px solid var(--line);
}
.rpos-panel-head h2 { margin: 0; font-size: 1rem; font-weight: 800; letter-spacing: -.02em; }
.rpos-panel-head .clock {
    font-variant-numeric: tabular-nums; font-weight: 800; color: var(--amber2); font-size: .95rem;
}
.rpos-alert {
    display: none; margin: 10px 14px 0; padding: 10px 12px; border-radius: 12px;
    background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-weight: 700; font-size: .85rem;
}
.rpos-alert.show { display: block; }
.floor-stage {
    margin: 12px; border-radius: 16px; padding: 10px;
    background: linear-gradient(145deg, #8d6238 0%, #5c3a1c 42%, #3f2712 100%);
    flex: 1; min-height: 220px;
}
.floor-felt {
    border-radius: 12px; min-height: 200px; padding: 12px; height: 100%;
    background:
        radial-gradient(ellipse 90% 70% at 50% 40%, rgba(255,255,255,.06), transparent 55%),
        linear-gradient(165deg, #1a7a58 0%, var(--felt2) 42%, var(--felt) 100%);
}
.bil-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 10px;
}
.btile {
    position: relative; background: rgba(4,22,16,.45); border: 1px solid rgba(255,255,255,.14);
    border-radius: 14px; padding: 0; cursor: pointer; text-align: left;
    transition: transform .15s ease, border-color .15s ease;
    min-height: 150px; display: flex; flex-direction: column;
    user-select: none; color: #f4fff9; font-family: inherit;
}
.btile:hover { transform: translateY(-2px); border-color: rgba(245,158,11,.55); }
.btile.selected { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.28); background: rgba(8,40,28,.72); }
.btile.ending { border-color: rgba(239,68,68,.75); animation: bilPulse 1.2s ease infinite; }
.btile .st {
    position: absolute; top: 12px; left: 14px; z-index: 2;
    font-size: .6rem; font-weight: 800; letter-spacing: .08em;
    padding: .22rem .5rem; border-radius: 999px; background: rgba(0,0,0,.35); color: #d8efe4;
}
.btile.free .st { background: rgba(21,154,90,.4); color: #b7f5d0; }
.btile.busy .st { background: rgba(232,163,23,.4); color: #ffe9a8; }
.btile.ending .st { background: rgba(198,40,40,.5); color: #ffd0d0; }
.btile .felt-mini {
    margin: 10px 10px 0; height: 58px; border-radius: 999px;
    background: linear-gradient(180deg, #218a63, #0d4735);
    border: 2px solid rgba(201,164,108,.55); position: relative;
}
.btile .felt-mini .ball {
    position: absolute; width: 10px; height: 10px; border-radius: 50%;
    left: 58%; top: 42%;
    background: radial-gradient(circle at 35% 30%, #fff, #bbb);
}
.btile .prod-img {
    margin: 10px 10px 0; height: 72px; border-radius: 12px;
    background: url('{{ asset('images/billiards-8ball.png') }}') center / contain no-repeat,
                radial-gradient(circle at 50% 40%, rgba(255,255,255,.12), transparent 60%),
                rgba(0,0,0,.2);
    border: 1px solid rgba(255,255,255,.12);
}
.btile .body { padding: .55rem .75rem .7rem; display: flex; flex-direction: column; gap: .1rem; flex: 1; }
.btile .name { font-weight: 800; font-size: 1rem; letter-spacing: -.02em; }
.btile .type { font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(200,230,216,.65); }
.btile .rate { font-size: .78rem; font-weight: 700; color: #f0d078; }
.btile .guest { font-size: .72rem; font-weight: 600; color: rgba(244,255,249,.88); }
.btile .cd { font-variant-numeric: tabular-nums; font-weight: 800; color: #f0d078; font-size: .85rem; }
@keyframes bilPulse { 50% { box-shadow: 0 0 0 6px rgba(239,68,68,.14); } }

.session-list { max-height: 200px; overflow: auto; }
.session-item {
    display: flex; justify-content: space-between; align-items: center; gap: 8px;
    padding: 11px 14px; border-bottom: 1px solid #f5f5f4; text-decoration: none; color: inherit;
}
.session-item:hover { background: #fafaf9; }
.session-item .t { font-weight: 800; font-size: .88rem; }
.session-item .m { font-size: .74rem; color: var(--muted); font-weight: 500; }

/* Cart — restaurant style */
.cart-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    display: flex; flex-direction: column; height: 100%; min-height: 0;
    border: 1px solid var(--line); overflow: hidden;
}
.cart-header {
    padding: 14px 16px;
    background: linear-gradient(135deg, var(--amber), var(--amber2));
    color: #fff; display: flex; justify-content: space-between; align-items: center;
}
.cart-header h5 { margin: 0; font-size: 1.05rem; font-weight: 800; }
.cart-body { flex: 1 1 auto; min-height: 100px; overflow-y: auto; padding: 0; }
.cart-empty { text-align: center; padding: 2.5rem 1rem; color: var(--muted); font-weight: 500; }
.cart-empty i { font-size: 2.5rem; opacity: .25; display: block; margin-bottom: .75rem; }
.cart-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 14px 16px; border-bottom: 1px solid #f1f5f9;
}
.cart-item .ci-info { flex: 1; min-width: 0; }
.cart-item .ci-name { font-weight: 800; font-size: .95rem; }
.cart-item .ci-meta { font-size: .75rem; color: var(--muted); font-weight: 600; margin-top: 2px; }
.cart-item .ci-price { font-weight: 800; color: var(--amber2); white-space: nowrap; }
.qty-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
.qty-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
    background: #fff; display: grid; place-items: center; cursor: pointer; font-weight: 800;
    font-family: inherit;
}
.qty-btn:hover { background: #fff7ed; border-color: #fde68a; }
.qty-val { font-weight: 800; min-width: 2.5rem; text-align: center; font-variant-numeric: tabular-nums; }
.cart-meta { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
.cart-meta label { font-size: .72rem; font-weight: 700; color: #57534e; margin-bottom: 4px; display: block; }
.cart-meta .form-control {
    border-radius: 10px; min-height: 42px; font-weight: 600; font-family: inherit; border-color: var(--line);
}
.source-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
.source-btn {
    min-height: 40px; border-radius: 10px; border: 1px solid var(--line);
    background: #fff; font-size: .7rem; font-weight: 700; cursor: pointer; font-family: inherit;
}
.source-btn.active { background: #1c1917; color: #fff; border-color: #1c1917; }
.cart-footer { padding: 14px 16px; background: #f8fafc; border-top: 1px solid #f1f5f9; }
.total-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; padding-top: 4px; }
.total-row .lbl { font-size: 1.05rem; font-weight: 800; }
.total-row .amt { font-size: 1.45rem; font-weight: 800; color: var(--amber); letter-spacing: -.03em; }
.cart-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.pos-btn {
    min-height: 54px; border: none; border-radius: 12px; font-weight: 800; font-size: .92rem;
    font-family: inherit; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
}
.pos-btn:disabled { opacity: .45; cursor: not-allowed; }
.pos-btn.start { background: #1c1917; color: #fff; }
.pos-btn.pay { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.pos-btn.full { grid-column: 1 / -1; }
.pos-btn.ghost { background: #fff; color: #44403c; border: 1px solid var(--line); }

.pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.pay-tile {
    min-height: 72px; border-radius: 12px; border: 2px solid var(--line);
    background: #fff; font-weight: 800; cursor: pointer; font-family: inherit;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
}
.pay-tile i { color: var(--amber2); font-size: 1.15rem; }
.pay-tile.active { border-color: var(--amber); background: #fffbeb; }

/* Booking modal */
.book-mode { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
.book-mode-btn {
    min-height: 48px; border-radius: 12px; border: 2px solid var(--line);
    background: #fff; font-weight: 800; font-family: inherit; cursor: pointer;
}
.book-mode-btn.active {
    border-color: transparent;
    background: linear-gradient(135deg, var(--amber), var(--amber2)); color: #fff;
}
.hour-chips { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 8px; }
.hour-chip {
    min-height: 42px; border-radius: 10px; border: 2px solid var(--line);
    background: #fff; font-weight: 800; font-family: inherit; cursor: pointer;
}
.hour-chip.active { border-color: transparent; background: #1c1917; color: #fff; }
.keypad {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px;
}
.keypad .touch-btn {
    min-height: 48px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: #fff; font-size: 1.1rem; font-weight: 700; font-family: inherit; cursor: pointer;
}
.keypad .touch-btn:active { transform: scale(.97); }
.keypad .touch-btn.warn { background: #fff7ed; border-color: #fde68a; color: #c2410c; }
.avail-ok { color: #047857; font-weight: 700; font-size: .85rem; }
.avail-bad { color: #b91c1c; font-weight: 700; font-size: .85rem; }
.book-field-focus { box-shadow: 0 0 0 3px rgba(245,158,11,.25) !important; border-color: #f59e0b !important; }

@media (max-width: 1199.98px) {
    .rpos-shell { grid-template-columns: 200px minmax(0, 1fr) minmax(300px, 340px); }
}
@media (max-width: 991.98px) {
    .rpos-shell { grid-template-columns: 1fr; }
    .rpos-rail { flex-direction: row; flex-wrap: wrap; align-items: flex-start; }
    .rpos-cats { flex-direction: row; flex-wrap: wrap; width: 100%; }
    .rpos-cat { flex: 1 1 calc(50% - 8px); }
    .rpos-cust { width: 100%; }
    .cart-card { position: static; max-height: none; }
}
</style>
@endpush

@section('content')
@php
    $cur = $currency;
    $busyByTable = $today->whereIn('status', ['booked','active'])->keyBy('billiard_table_id');
    $endingIds = $endingSoon->pluck('billiard_table_id')->all();
    $preselect = (int) request('table', 0);
    $poolCount = $tables->where('type', 'pool')->count();
    $snookerCount = $tables->where('type', 'snooker')->count();
    $freeCount = $tables->filter(fn ($t) => ! $busyByTable->has($t->id))->count();
@endphp

<div class="rpos">
    <div class="rpos-shell">
        {{-- LEFT: customer + categories --}}
        <aside class="rpos-rail">
            <div class="rpos-rail-title">Customer</div>
            <div class="rpos-cust position-relative">
                <label for="custSearch">Search / select</label>
                <input type="search" id="custSearch" class="form-control" placeholder="Name or phone" autocomplete="off">
                <div class="rpos-cust-drop" id="custDrop"></div>
            </div>
            <div class="rpos-cust">
                <label for="custName">Name</label>
                <input id="custName" class="form-control" placeholder="Guest name">
            </div>
            <div class="rpos-cust">
                <label for="custPhone">Phone</label>
                <input id="custPhone" class="form-control" placeholder="07…">
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:.78rem;font-weight:600;color:rgba(255,247,237,.75);padding:0 4px">
                <input type="checkbox" id="createCustomer" checked> Save as customer
            </label>

            <div class="rpos-rail-title" style="margin-top:6px">Tables</div>
            <div class="rpos-cats" id="catRail">
                <button type="button" class="rpos-cat active" data-filter="all">
                    <i class="fas fa-border-all"></i> All
                    <span class="cnt">{{ $tables->count() }}</span>
                </button>
                <button type="button" class="rpos-cat" data-filter="pool">
                    <i class="fas fa-circle"></i> Pool
                    <span class="cnt">{{ $poolCount }}</span>
                </button>
                <button type="button" class="rpos-cat" data-filter="snooker">
                    <i class="fas fa-bullseye"></i> Snooker
                    <span class="cnt">{{ $snookerCount }}</span>
                </button>
                <button type="button" class="rpos-cat" data-filter="free">
                    <i class="fas fa-check-circle"></i> Free
                    <span class="cnt">{{ $freeCount }}</span>
                </button>
            </div>
        </aside>

        {{-- MIDDLE: tables + sessions --}}
        <div class="rpos-mid">
            <div class="rpos-panel" style="flex:1.4">
                <div class="rpos-panel-head">
                    <h2>Tables</h2>
                    <span class="clock" id="clockLive">—</span>
                </div>
                <div class="rpos-alert" id="endAlert"></div>
                <div class="floor-stage">
                    <div class="floor-felt">
                        <div class="bil-grid" id="tableGrid">
                            @forelse($tables as $t)
                                @php
                                    $b = $busyByTable->get($t->id);
                                    $ending = in_array($t->id, $endingIds, true);
                                    $state = $ending ? 'ending' : ($b ? 'busy' : 'free');
                                @endphp
                                <button type="button"
                                    class="btile {{ $state }} {{ $preselect === (int)$t->id ? 'selected' : '' }}"
                                    data-id="{{ $t->id }}"
                                    data-name="{{ $t->name }}"
                                    data-type="{{ $t->type }}"
                                    data-rate="{{ $t->hourly_rate }}"
                                    data-state="{{ $state }}"
                                    @if($b) data-booking="{{ $b->id }}" data-ends="{{ $b->scheduled_end?->getTimestampMs() }}" data-status="{{ $b->status }}" @endif
                                >
                                    <span class="st">{{ $ending ? 'ENDING' : ($b ? strtoupper($b->status) : 'FREE') }}</span>
                                    <div class="prod-img" aria-hidden="true"></div>
                                    <div class="body">
                                        <div class="name">{{ $t->name }}</div>
                                        <div class="type">{{ $t->typeLabel() }}</div>
                                        <div class="rate">{{ $cur }} {{ number_format((float)$t->hourly_rate, 0) }}/hr</div>
                                        @if($b)
                                            <div class="guest">{{ $b->displayName() }}</div>
                                            @if($b->status === 'active')
                                                <div class="cd" data-cd>—</div>
                                            @endif
                                        @else
                                            <div class="guest" style="opacity:.65">Tap to book</div>
                                        @endif
                                    </div>
                                </button>
                            @empty
                                <div style="grid-column:1/-1;padding:2rem;text-align:center;color:rgba(244,255,249,.85);font-weight:600">
                                    No tables. <a href="{{ route('billiards.tables.index') }}" style="color:#f0d078">Add tables</a>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="rpos-panel">
                <div class="rpos-panel-head">
                    <h2>Live sessions</h2>
                    <a href="{{ route('billiards.bookings.index') }}" style="font-size:.8rem;font-weight:700;color:#b45309;text-decoration:none">All →</a>
                </div>
                <div class="session-list">
                    @forelse($today as $b)
                        <a class="session-item" href="{{ route('billiards.bookings.show', $b) }}">
                            <div>
                                <div class="t">{{ $b->table?->name }} · {{ $b->displayName() }}</div>
                                <div class="m">{{ $b->booking_number }} · {{ \App\Models\Setting::formatDateTime($b->scheduled_start, 'H:i') }}–{{ \App\Models\Setting::formatDateTime($b->scheduled_end, 'H:i') }} · {{ ucfirst($b->payment_status) }}</div>
                            </div>
                            <span style="font-weight:800;font-size:.85rem;color:#c45c12">{{ $cur }} {{ number_format((float)$b->amount,0) }}</span>
                        </a>
                    @empty
                        <div style="padding:1.25rem;text-align:center;color:var(--muted);font-weight:500">No sessions today</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: cart --}}
        <aside>
            <div class="cart-card">
                <div class="cart-header">
                    <h5><i class="fas fa-shopping-cart me-1"></i> Cart <span class="badge bg-white text-dark ms-1" id="cartCount">0</span></h5>
                    <button type="button" class="btn btn-sm text-white" id="btnClearForm" title="Clear" style="opacity:.9"><i class="fas fa-trash"></i></button>
                </div>

                <input type="hidden" id="tableId" value="{{ $preselect ?: '' }}">
                <input type="hidden" id="customerId" value="">
                <input type="hidden" id="source" value="walk_in">
                <input type="hidden" id="hours" value="1">
                <input type="hidden" id="scheduledStart" value="">
                <input type="hidden" id="bookMode" value="instant">

                <div class="cart-body" id="cartBody">
                    <div class="cart-empty" id="emptyCart">
                        <img src="{{ asset('images/billiards-8ball.png') }}" alt="" width="56" height="56" style="opacity:.85;margin-bottom:.5rem;border-radius:50%">
                        <div>Tap a table · set hours &amp; time</div>
                    </div>
                    <div class="cart-item d-none" id="cartLine">
                        <img src="{{ asset('images/billiards-8ball.png') }}" alt="" width="40" height="40" style="border-radius:50%;flex-shrink:0">
                        <div class="ci-info">
                            <div class="ci-name" id="cartTableName">—</div>
                            <div class="ci-meta" id="cartTableMeta">—</div>
                            <div class="ci-meta" id="cartWhenMeta">—</div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnEditSlot" style="font-weight:700;border-radius:10px">
                                <i class="fas fa-pen me-1"></i>Change time
                            </button>
                        </div>
                        <div class="ci-price" id="cartLinePrice">{{ $cur }} 0.00</div>
                    </div>
                </div>

                <div class="cart-meta">
                    <label>Source</label>
                    <div class="source-row mb-2" id="sourceRow">
                        @foreach(['walk_in'=>'Walk-in','phone'=>'Phone','online'=>'Online','admin'=>'Admin'] as $k=>$l)
                            <button type="button" class="source-btn {{ $k==='walk_in'?'active':'' }}" data-s="{{ $k }}">{{ $l }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="cart-footer">
                    <div class="total-row">
                        <span class="lbl">Total</span>
                        <span class="amt" id="totalAmt">{{ $cur }} 0.00</span>
                    </div>
                    <div class="cart-actions mb-2">
                        <button type="button" class="pos-btn start" id="btnBookStart" disabled><i class="fas fa-play"></i> Start</button>
                        <button type="button" class="pos-btn pay" id="btnBookPay" disabled><i class="fas fa-coins"></i> Pay</button>
                    </div>
                    <button type="button" class="pos-btn ghost full" id="btnBookOnly" disabled>Book only (pay later)</button>
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- Booking details popup --}}
<div class="modal fade" id="bookModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:18px;overflow:hidden;border:0">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#0a3326,#0f4a37);color:#fff">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/billiards-8ball.png') }}" alt="" width="44" height="44" style="border-radius:50%;box-shadow:0 4px 12px rgba(0,0,0,.35)">
            <div>
                <h5 class="modal-title fw-bold mb-0" id="bookModalTitle">Book table</h5>
                <small style="opacity:.8" id="bookModalRate">—</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="book-mode">
            <button type="button" class="book-mode-btn active" data-mode="instant"><i class="fas fa-bolt me-1"></i> Instant (now)</button>
            <button type="button" class="book-mode-btn" data-mode="future"><i class="fas fa-calendar-alt me-1"></i> Future booking</button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <label class="form-label fw-bold">Date</label>
                <input type="date" id="bookDate" class="form-control" style="border-radius:12px;min-height:46px;font-weight:700">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Start time</label>
                <input type="time" id="bookTime" class="form-control" style="border-radius:12px;min-height:46px;font-weight:700">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Hours</label>
                <input type="number" id="bookHours" class="form-control" min="0.25" max="24" step="0.25" value="1" style="border-radius:12px;min-height:46px;font-weight:700">
            </div>
        </div>
        <div class="hour-chips" id="hourChips">
            @foreach([1, 1.5, 2, 3] as $h)
                <button type="button" class="hour-chip {{ $h==1?'active':'' }}" data-h="{{ $h }}">{{ $h }}h</button>
            @endforeach
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="text-muted small fw-semibold">Ends</div>
                <div class="fw-bold" id="bookEndLabel">—</div>
            </div>
            <div class="text-end">
                <div class="text-muted small fw-semibold">Total</div>
                <div class="fw-bold" style="color:#c45c12;font-size:1.2rem" id="bookTotalLabel">{{ $cur }} 0.00</div>
            </div>
        </div>
        <div id="availMsg" class="mb-2"></div>
        <div class="small text-muted mb-1 fw-semibold">Numeric keypad · tap a field, then type</div>
        <div class="keypad" id="bookKeypad">
            <button type="button" class="touch-btn" data-k="1">1</button>
            <button type="button" class="touch-btn" data-k="2">2</button>
            <button type="button" class="touch-btn" data-k="3">3</button>
            <button type="button" class="touch-btn" data-k="4">4</button>
            <button type="button" class="touch-btn" data-k="5">5</button>
            <button type="button" class="touch-btn" data-k="6">6</button>
            <button type="button" class="touch-btn" data-k="7">7</button>
            <button type="button" class="touch-btn" data-k="8">8</button>
            <button type="button" class="touch-btn" data-k="9">9</button>
            <button type="button" class="touch-btn warn" data-k="C">C</button>
            <button type="button" class="touch-btn" data-k="0">0</button>
            <button type="button" class="touch-btn warn" data-k="DEL"><i class="fas fa-backspace"></i></button>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="pos-btn ghost" data-bs-dismiss="modal" style="min-height:48px;padding:0 16px">Cancel</button>
        <button type="button" class="pos-btn pay" id="btnAddToCart" style="min-height:48px;padding:0 18px;background:linear-gradient(135deg,#f59e0b,#ea580c)">
            <i class="fas fa-cart-plus me-1"></i>Add to cart
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Pay popup — restaurant style --}}
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px;overflow:hidden;border:0">
      <div class="modal-header border-0" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff">
        <h5 class="modal-title fw-bold"><i class="fas fa-cash-register me-2"></i>Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
            <div style="font-size:.78rem;font-weight:700;color:#78716c;letter-spacing:.06em">AMOUNT DUE</div>
            <div style="font-size:2rem;font-weight:800;color:#059669" id="payDue">—</div>
            <div style="font-size:.82rem;font-weight:600;color:#78716c" id="payTableHint"></div>
        </div>
        <label class="form-label fw-bold">Payment method</label>
        <div class="pay-grid mb-3" id="payMethods">
            @foreach(['cash'=>'Cash','card'=>'Card','bank_transfer'=>'Bank','online'=>'Online','credit'=>'Credit'] as $m=>$label)
                <button type="button" class="pay-tile {{ $m==='cash'?'active':'' }}" data-method="{{ $m }}">
                    <i class="fas fa-{{ $m==='cash'?'money-bill-wave':($m==='card'?'credit-card':($m==='online'?'mobile-alt':($m==='credit'?'hand-holding-usd':'university'))) }}"></i>
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <label class="form-label fw-bold">Amount</label>
        <input type="number" step="0.01" min="0.01" class="form-control form-control-lg mb-2" id="payAmount" style="border-radius:12px;font-weight:700">
        <input type="text" class="form-control" id="payRef" placeholder="Reference (optional)" style="border-radius:12px">
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="pos-btn ghost" data-bs-dismiss="modal" style="min-height:48px;padding:0 16px">Cancel</button>
        <button type="button" class="pos-btn pay" id="btnConfirmPay" style="min-height:48px;padding:0 20px"><i class="fas fa-check me-1"></i>Confirm pay</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CUR = @json($cur);
    const LOOKUP = @json(route('billiards.customers.lookup'));
    const STORE = @json(route('billiards.bookings.store'));
    const AVAIL = @json(route('billiards.availability'));
    const SHOW_BASE = @json(url('/billiards/bookings'));
    const ALERTS = @json(route('billiards.alerts'));
    const ALERT_MIN = {{ (int) $endAlertMinutes }};
    const PRINT_ASK = @json($printAsk ?? true);
    const SMS_ON_PAY = @json($smsOnPay ?? false);
    const AUTO_START_PAY = @json($autoStartOnPay ?? true);
    const SMS_READY = @json($smsReady ?? false);

    let selectedRate = 0;
    let selectedName = '';
    let selectedType = '';
    let selectedMethod = 'cash';
    let pendingBookingId = null;
    let payModal, bookModal;
    let hoursVal = 1;
    let keypadTarget = 'hours'; // hours | time
    let pendingTableBtn = null;
    let isAvailable = true;

    const el = (id) => document.getElementById(id);
    function money(n){ return CUR + ' ' + Number(n||0).toFixed(2); }
    function pad(n){ return String(n).padStart(2,'0'); }

    function nowParts() {
        const d = new Date();
        return {
            date: d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()),
            time: pad(d.getHours()) + ':' + pad(d.getMinutes()),
        };
    }

    function parseStart() {
        const date = el('bookDate').value;
        const time = el('bookTime').value || '00:00';
        if (!date) return null;
        return new Date(date + 'T' + time + ':00');
    }

    function endFromStart(start, hours) {
        return new Date(start.getTime() + hours * 60 * 60 * 1000);
    }

    function fmtWhen(start, end) {
        const optsD = { day:'2-digit', month:'short' };
        const optsT = { hour:'2-digit', minute:'2-digit' };
        return start.toLocaleDateString([], optsD) + ' '
            + start.toLocaleTimeString([], optsT) + '–'
            + end.toLocaleTimeString([], optsT);
    }

    function toLocalInput(dt) {
        return dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate())
            + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
    }

    function setMode(mode) {
        el('bookMode').value = mode;
        document.querySelectorAll('.book-mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
        const now = nowParts();
        if (mode === 'instant') {
            el('bookDate').value = now.date;
            el('bookTime').value = now.time;
            el('bookDate').disabled = true;
            el('bookTime').disabled = true;
        } else {
            el('bookDate').disabled = false;
            el('bookTime').disabled = false;
            if (!el('bookDate').value) el('bookDate').value = now.date;
            if (!el('bookTime').value) el('bookTime').value = now.time;
        }
        refreshBookPreview();
    }

    function setBookHours(h) {
        hoursVal = Math.max(0.25, Math.min(24, Math.round(Number(h) * 4) / 4));
        el('bookHours').value = hoursVal;
        document.querySelectorAll('.hour-chip').forEach(c => c.classList.toggle('active', Number(c.dataset.h) === hoursVal));
        refreshBookPreview();
    }

    async function checkAvailability() {
        const start = parseStart();
        const tableId = pendingTableBtn?.dataset.id || el('tableId').value;
        const box = el('availMsg');
        if (!start || !tableId) {
            box.innerHTML = '';
            isAvailable = false;
            return;
        }
        box.innerHTML = '<span class="text-muted fw-semibold">Checking availability…</span>';
        try {
            const qs = new URLSearchParams({
                billiard_table_id: tableId,
                scheduled_start: toLocalInput(start),
                hours: String(hoursVal),
            });
            const r = await fetch(AVAIL + '?' + qs.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            isAvailable = !!data.available;
            box.innerHTML = isAvailable
                ? '<div class="avail-ok"><i class="fas fa-check-circle me-1"></i>' + (data.message || 'Free') + '</div>'
                : '<div class="avail-bad"><i class="fas fa-ban me-1"></i>' + (data.message || 'Already booked') + '</div>';
            el('btnAddToCart').disabled = !isAvailable;
        } catch (e) {
            box.innerHTML = '<div class="avail-bad">Could not check availability</div>';
            isAvailable = false;
            el('btnAddToCart').disabled = true;
        }
    }

    let availTimer = null;
    function refreshBookPreview() {
        const start = parseStart();
        const h = parseFloat(el('bookHours').value) || hoursVal;
        hoursVal = h;
        if (start) {
            const end = endFromStart(start, h);
            el('bookEndLabel').textContent = end.toLocaleString([], { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
        } else {
            el('bookEndLabel').textContent = '—';
        }
        el('bookTotalLabel').textContent = money(h * selectedRate);
        clearTimeout(availTimer);
        availTimer = setTimeout(checkAvailability, 280);
    }

    function openBookModal(btn) {
        pendingTableBtn = btn;
        selectedRate = parseFloat(btn.dataset.rate) || 0;
        selectedName = btn.dataset.name || '';
        selectedType = (btn.dataset.type || '').toUpperCase();
        el('bookModalTitle').textContent = 'Book · ' + selectedName;
        el('bookModalRate').textContent = selectedType + ' · ' + money(selectedRate) + '/hr';
        setBookHours(1);
        setMode('instant');
        bookModal = bootstrap.Modal.getOrCreateInstance(el('bookModal'));
        bookModal.show();
        setTimeout(() => { el('bookHours').focus(); keypadTarget = 'hours'; markFocus(); }, 250);
    }

    function markFocus() {
        [el('bookHours'), el('bookTime'), el('bookDate')].forEach(i => i.classList.remove('book-field-focus'));
        if (keypadTarget === 'hours') el('bookHours').classList.add('book-field-focus');
        if (keypadTarget === 'time') el('bookTime').classList.add('book-field-focus');
        if (keypadTarget === 'date') el('bookDate').classList.add('book-field-focus');
    }

    function applyToCart() {
        if (!isAvailable || !pendingTableBtn) {
            Swal.fire({ icon:'error', title:'Already booked', text:'This table is taken for the selected time. Pick another slot.' });
            return;
        }
        const start = parseStart();
        if (!start) return;
        const end = endFromStart(start, hoursVal);
        document.querySelectorAll('.btile').forEach(b => b.classList.remove('selected'));
        pendingTableBtn.classList.add('selected');
        el('tableId').value = pendingTableBtn.dataset.id;
        el('hours').value = hoursVal;
        el('scheduledStart').value = toLocalInput(start);
        el('cartTableName').textContent = selectedName;
        el('cartTableMeta').textContent = selectedType + ' · ' + money(selectedRate) + '/hr · ' + hoursVal + 'h';
        el('cartWhenMeta').textContent = fmtWhen(start, end)
            + (el('bookMode').value === 'instant' ? ' · Instant' : ' · Future');
        el('cartLinePrice').textContent = money(hoursVal * selectedRate);
        el('totalAmt').textContent = money(hoursVal * selectedRate);
        showCartLine(true);
        calc();
        bookModal.hide();
    }

    function calc(){
        const h = parseFloat(el('hours').value) || 0;
        const total = h * selectedRate;
        el('totalAmt').textContent = money(total);
        el('cartLinePrice').textContent = money(total);
        const ok = !!el('tableId').value && selectedRate >= 0 && h > 0 && !!el('scheduledStart').value;
        el('btnBookStart').disabled = !ok;
        el('btnBookPay').disabled = !ok;
        el('btnBookOnly').disabled = !ok;
        el('cartCount').textContent = ok ? '1' : '0';
        // Start only makes sense for near-now slots
        const start = el('scheduledStart').value ? new Date(el('scheduledStart').value) : null;
        const nearNow = start && Math.abs(start.getTime() - Date.now()) < 15 * 60 * 1000;
        el('btnBookStart').disabled = !ok || !nearNow;
    }

    function showCartLine(show) {
        el('emptyCart').classList.toggle('d-none', show);
        el('cartLine').classList.toggle('d-none', !show);
    }

    function selectTable(btn){
        if (btn.dataset.booking && btn.dataset.state !== 'free') {
            // Currently playing/booked now — open session, or allow future via long-press? Offer choice.
            Swal.fire({
                title: btn.dataset.name || 'Table busy',
                text: 'This table has a live/booked session. Open it, or book a different time?',
                icon: 'info',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: 'Open session',
                denyButtonText: 'Book another time',
                confirmButtonColor: '#0f4a37',
                denyButtonColor: '#ea580c',
            }).then(res => {
                if (res.isConfirmed) window.location.href = SHOW_BASE + '/' + btn.dataset.booking;
                if (res.isDenied) openBookModal(btn);
            });
            return;
        }
        openBookModal(btn);
    }

    function clearCart() {
        document.querySelectorAll('.btile').forEach(b => b.classList.remove('selected'));
        el('tableId').value = '';
        el('scheduledStart').value = '';
        el('customerId').value = '';
        el('custName').value = '';
        el('custPhone').value = '';
        el('custSearch').value = '';
        selectedRate = 0;
        selectedName = '';
        selectedType = '';
        hoursVal = 1;
        el('hours').value = 1;
        showCartLine(false);
        calc();
    }

    document.querySelectorAll('.btile').forEach(btn => {
        btn.addEventListener('click', () => selectTable(btn));
    });

    const pre = document.querySelector('.btile.selected');
    if (pre && !pre.dataset.booking) openBookModal(pre);

    document.querySelectorAll('.rpos-cat').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.rpos-cat').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const f = tab.dataset.filter;
            document.querySelectorAll('.btile').forEach(b => {
                const type = b.dataset.type;
                const state = b.dataset.state;
                let show = true;
                if (f === 'pool' || f === 'snooker') show = type === f;
                if (f === 'free') show = state === 'free';
                b.style.display = show ? '' : 'none';
            });
        });
    });

    document.querySelectorAll('.book-mode-btn').forEach(btn => {
        btn.addEventListener('click', () => setMode(btn.dataset.mode));
    });
    document.querySelectorAll('.hour-chip').forEach(btn => {
        btn.addEventListener('click', () => setBookHours(btn.dataset.h));
    });
    ['bookDate','bookTime','bookHours'].forEach(id => {
        el(id).addEventListener('input', refreshBookPreview);
        el(id).addEventListener('focus', () => {
            keypadTarget = id === 'bookHours' ? 'hours' : (id === 'bookTime' ? 'time' : 'date');
            markFocus();
        });
    });
    el('btnAddToCart').addEventListener('click', applyToCart);
    el('btnEditSlot').addEventListener('click', () => {
        const btn = document.querySelector('.btile.selected') || pendingTableBtn;
        if (btn) openBookModal(btn);
    });
    el('btnClearForm').addEventListener('click', clearCart);

    // Keypad
    el('bookKeypad').addEventListener('click', e => {
        const btn = e.target.closest('[data-k]');
        if (!btn) return;
        const k = btn.dataset.k;
        if (keypadTarget === 'hours') {
            let v = String(el('bookHours').value || '');
            if (k === 'C') v = '';
            else if (k === 'DEL') v = v.slice(0, -1);
            else v = (v + k).replace(/^0+(?=\d)/, '').slice(0, 4);
            const num = parseFloat(v);
            if (v === '' || v === '.') el('bookHours').value = v;
            else if (!isNaN(num)) el('bookHours').value = Math.min(24, num);
            setBookHours(el('bookHours').value || 0.25);
            return;
        }
        if (keypadTarget === 'time') {
            let digits = (el('bookTime').value || '').replace(/\D/g, '');
            if (k === 'C') digits = '';
            else if (k === 'DEL') digits = digits.slice(0, -1);
            else if (/\d/.test(k)) digits = (digits + k).slice(0, 4);
            while (digits.length < 4) digits += '0';
            let hh = Math.min(23, parseInt(digits.slice(0,2), 10) || 0);
            let mm = Math.min(59, parseInt(digits.slice(2,4), 10) || 0);
            // progressive: if fewer than 4 typed, show partial
            const typed = (el('bookTime').dataset.typed || '');
            let t = typed;
            if (k === 'C') t = '';
            else if (k === 'DEL') t = t.slice(0, -1);
            else if (/\d/.test(k)) t = (t + k).slice(0, 4);
            el('bookTime').dataset.typed = t;
            if (t.length === 0) {
                el('bookTime').value = nowParts().time;
            } else {
                const padded = (t + '0000').slice(0, 4);
                hh = Math.min(23, parseInt(padded.slice(0,2), 10));
                mm = Math.min(59, parseInt(padded.slice(2,4), 10));
                el('bookTime').value = pad(hh) + ':' + pad(mm);
            }
            refreshBookPreview();
        }
    });

    document.querySelectorAll('.source-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.source-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            el('source').value = btn.dataset.s;
        });
    });

    let tSearch = null;
    const drop = el('custDrop');
    el('custSearch').addEventListener('input', () => {
        clearTimeout(tSearch);
        const q = el('custSearch').value.trim();
        if (q.length < 2) { drop.style.display = 'none'; return; }
        tSearch = setTimeout(async () => {
            const r = await fetch(LOOKUP + '?q=' + encodeURIComponent(q));
            const rows = await r.json();
            drop.innerHTML = rows.length
                ? rows.map(c => `<button type="button" data-id="${c.id}" data-name="${c.name||''}" data-phone="${c.phone||''}"><strong>${c.name||'—'}</strong><br><small>${c.phone||''}</small></button>`).join('')
                : '<div class="p-2 text-muted">No matches</div>';
            drop.style.display = 'block';
        }, 220);
    });
    drop.addEventListener('click', e => {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        el('customerId').value = btn.dataset.id;
        el('custName').value = btn.dataset.name;
        el('custPhone').value = btn.dataset.phone;
        el('custSearch').value = btn.dataset.name;
        drop.style.display = 'none';
    });

    function payload(extra = {}) {
        return {
            billiard_table_id: el('tableId').value,
            hours: el('hours').value,
            hourly_rate: selectedRate,
            source: el('source').value,
            scheduled_start: el('scheduledStart').value,
            customer_id: el('customerId').value || null,
            customer_name: el('custName').value || null,
            customer_phone: el('custPhone').value || null,
            create_customer: el('createCustomer').checked ? 1 : 0,
            ...extra
        };
    }

    async function createBooking(extra = {}) {
        const res = await fetch(STORE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(payload(extra)),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = data.message || (data.errors && Object.values(data.errors).flat()[0]) || 'Could not create booking';
            throw new Error(msg);
        }
        return data;
    }

    function handleBookError(e) {
        const already = /already booked/i.test(e.message || '');
        Swal.fire({
            icon: 'error',
            title: already ? 'Already booked' : 'Failed',
            text: e.message || 'Could not create booking',
        });
    }

    el('btnBookOnly').addEventListener('click', async () => {
        try {
            const data = await createBooking({ start_now: 0 });
            Swal.fire({ icon:'success', title:'Booked', text: data.booking?.booking_number || 'Saved', timer: 1100, showConfirmButton:false });
            setTimeout(() => { window.location.href = SHOW_BASE + '/' + data.booking?.id; }, 700);
        } catch (e) { handleBookError(e); }
    });

    el('btnBookStart').addEventListener('click', async () => {
        try {
            const data = await createBooking({ start_now: 1 });
            Swal.fire({ icon:'success', title:'Started', text: data.booking?.booking_number || 'Session live', timer: 1100, showConfirmButton:false });
            setTimeout(() => { window.location.href = SHOW_BASE + '/' + data.booking?.id; }, 700);
        } catch (e) { handleBookError(e); }
    });

    el('btnBookPay').addEventListener('click', async () => {
        try {
            const instant = el('bookMode').value === 'instant'
                || (el('scheduledStart').value && Math.abs(new Date(el('scheduledStart').value).getTime() - Date.now()) < 15 * 60 * 1000);
            const data = await createBooking({ start_now: (instant && AUTO_START_PAY) ? 1 : 0 });
            pendingBookingId = data.booking?.id;
            const due = Number(data.booking?.amount || (hoursVal * selectedRate));
            el('payDue').textContent = money(due);
            el('payAmount').value = due.toFixed(2);
            el('payTableHint').textContent = (selectedName || 'Table') + ' · ' + hoursVal + 'h';
            selectedMethod = 'cash';
            document.querySelectorAll('.pay-tile').forEach(t => t.classList.toggle('active', t.dataset.method === 'cash'));
            payModal = bootstrap.Modal.getOrCreateInstance(el('payModal'));
            payModal.show();
        } catch (e) { handleBookError(e); }
    });

    document.querySelectorAll('.pay-tile').forEach(tile => {
        tile.addEventListener('click', () => {
            document.querySelectorAll('.pay-tile').forEach(t => t.classList.remove('active'));
            tile.classList.add('active');
            selectedMethod = tile.dataset.method;
        });
    });

    el('btnConfirmPay').addEventListener('click', async () => {
        if (!pendingBookingId) return;
        try {
            const res = await fetch(SHOW_BASE + '/' + pendingBookingId + '/pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    method: selectedMethod,
                    amount: el('payAmount').value,
                    reference: el('payRef').value || null,
                }),
            });
            if (!res.ok) {
                const fd = new FormData();
                fd.append('_token', CSRF);
                fd.append('method', selectedMethod);
                fd.append('amount', el('payAmount').value);
                if (el('payRef').value) fd.append('reference', el('payRef').value);
                const r2 = await fetch(SHOW_BASE + '/' + pendingBookingId + '/pay', { method:'POST', body: fd, headers:{'Accept':'application/json'} });
                if (!r2.ok && r2.status !== 302) {
                    const err = await r2.json().catch(()=>({}));
                    throw new Error(err.message || 'Payment failed');
                }
            }
            payModal.hide();
            let q = [];
            if (PRINT_ASK) q.push('print=1');
            if (SMS_ON_PAY && SMS_READY) q.push('sms=1');
            const qs = q.length ? ('?' + q.join('&')) : '';
            Swal.fire({ icon:'success', title:'Paid', timer:900, showConfirmButton:false });
            setTimeout(() => { window.location.href = SHOW_BASE + '/' + pendingBookingId + qs; }, 600);
        } catch (e) {
            Swal.fire({ icon:'error', title:'Payment failed', text: e.message });
        }
    });

    function fmtLeft(secs){
        const over = secs < 0; const a = Math.abs(Math.floor(secs));
        return (over?'+':'') + pad(Math.floor(a/60)) + ':' + pad(a%60);
    }
    function tick(){
        el('clockLive').textContent = (window.BusinessClock ? BusinessClock.formatTime(true) : new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
        document.querySelectorAll('.btile[data-ends]').forEach(tile => {
            const ends = Number(tile.dataset.ends);
            if (!ends) return;
            const secs = Math.floor((ends - Date.now())/1000);
            const cd = tile.querySelector('[data-cd]');
            if (cd) cd.textContent = fmtLeft(secs);
            if (secs >= 0 && secs <= ALERT_MIN * 60) tile.classList.add('ending');
        });
    }
    tick(); setInterval(tick, 1000);

    async function pollAlerts(){
        try {
            const r = await fetch(ALERTS, {headers:{'Accept':'application/json'}});
            if (!r.ok) return;
            const data = await r.json();
            const box = el('endAlert');
            const rows = data.ending_soon || [];
            if (!rows.length) { box.classList.remove('show'); return; }
            box.textContent = 'Ending soon: ' + rows.map(x => (x.table||'') + ' ' + x.minutes + 'm').join(' · ');
            box.classList.add('show');
        } catch(e){}
    }
    pollAlerts(); setInterval(pollAlerts, 20000);

    calc();
})();
</script>
@endpush
