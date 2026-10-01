@extends('layouts.admin')
@section('title', $booking->booking_number)
@section('page_title', 'Booking')

@push('styles')
<style>
.show-wrap { max-width: 1100px; margin: 0 auto; }
.show-hero {
    display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end;
    gap: 14px; margin-bottom: 14px;
    padding: 1.25rem 1.35rem;
    border-radius: 22px;
    background:
        radial-gradient(ellipse 70% 120% at 100% 0%, rgba(232,163,23,.3), transparent 55%),
        linear-gradient(135deg, #0a3326 0%, #0f4a37 42%, #14110f 100%);
    color: #f4fff9;
    box-shadow: 0 16px 36px rgba(10,51,38,.28);
}
.show-hero .eyebrow {
    margin: 0 0 .3rem; font-size: .7rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase; color: #f0d078;
}
.show-hero h1 {
    margin: 0; font-size: clamp(1.4rem, 2.5vw, 1.85rem); font-weight: 800;
    letter-spacing: -.03em;
}
.show-hero .meta {
    margin: .4rem 0 0; opacity: .8; font-size: .9rem; font-weight: 500;
}
.show-badges { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .75rem; }
.show-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .35rem .75rem; border-radius: 999px;
    font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
}
.show-badge.ok { background: rgba(16,185,129,.25); color: #a7f3d0; }
.show-badge.warn { background: rgba(232,163,23,.25); color: #fde68a; }
.show-badge.danger { background: rgba(239,68,68,.28); color: #fecaca; }
.show-badge.mute { background: rgba(255,255,255,.1); color: rgba(255,247,237,.75); }

.action-dock {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}
.dock-btn {
    min-height: 72px; border-radius: 16px; border: 1px solid #e7e5e4;
    background: #fff; color: #1c1917;
    font-family: inherit; font-weight: 800; font-size: .82rem;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem;
    text-decoration: none; cursor: pointer;
    box-shadow: 0 4px 14px rgba(20,17,15,.05);
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.dock-btn i { font-size: 1.1rem; color: #c45c12; }
.dock-btn:hover { transform: translateY(-2px); border-color: #fcd34d; color: inherit; text-decoration: none; box-shadow: 0 10px 22px rgba(20,17,15,.08); }
.dock-btn.dark { background: #14110f; color: #fff; border-color: #14110f; }
.dock-btn.dark i { color: #f0d078; }
.dock-btn.dark:hover { color: #fff; }
.dock-btn.green {
    background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-color: transparent;
}
.dock-btn.green i { color: #fff; }
.dock-btn.green:hover { color: #fff; }
.dock-btn:disabled, .dock-btn.is-disabled {
    opacity: .5; cursor: not-allowed; transform: none !important;
}

.show-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(280px, .85fr);
    gap: 14px;
    align-items: start;
}
.show-card {
    background: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 20px;
    padding: 1.15rem 1.2rem;
    box-shadow: 0 8px 24px rgba(20,17,15,.05);
}
.show-card + .show-card { margin-top: 14px; }
.show-card h2 {
    margin: 0 0 .85rem; font-size: 1rem; font-weight: 800; letter-spacing: -.02em;
}

.due-panel {
    padding: 1.15rem 1.2rem;
    border-radius: 18px;
    background:
        radial-gradient(ellipse 80% 100% at 100% 0%, rgba(255,255,255,.35), transparent 50%),
        linear-gradient(160deg, #fff7ed, #fff);
    border: 1px solid #fed7aa;
    margin-bottom: 1rem;
}
.due-panel .lbl {
    font-size: .72rem; font-weight: 700; color: #9a3412;
    letter-spacing: .1em; text-transform: uppercase;
}
.due-panel .amt {
    font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 800;
    color: #c45c12; letter-spacing: -.04em; font-variant-numeric: tabular-nums;
    line-height: 1.1; margin-top: .15rem;
}
.due-panel .sub { font-size: .8rem; font-weight: 600; color: #9a3412; margin-top: .35rem; }
.due-panel.is-paid {
    background: linear-gradient(160deg, #ecfdf5, #fff);
    border-color: #a7f3d0;
}
.due-panel.is-paid .lbl, .due-panel.is-paid .sub { color: #047857; }
.due-panel.is-paid .amt { color: #059669; }

.info-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
}
.info-cell {
    padding: .75rem .85rem; border-radius: 14px;
    background: #fafaf9; border: 1px solid #f5f5f4;
}
.info-cell .k {
    font-size: .68rem; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #78716c;
}
.info-cell .v { font-size: .95rem; font-weight: 700; margin-top: .2rem; }
.info-cell.wide { grid-column: 1 / -1; }

.pay-terminal {
    border-radius: 20px; overflow: hidden;
    border: 1px solid #e7e5e4;
    background: #fff;
    box-shadow: 0 12px 32px rgba(20,17,15,.08);
    position: sticky; top: 76px;
}
.pay-terminal-head {
    padding: 1rem 1.15rem;
    background: linear-gradient(135deg, #14110f, #292524);
    color: #fff7ed;
}
.pay-terminal-head h2 { margin: 0; font-size: 1.05rem; font-weight: 800; }
.pay-terminal-head p { margin: .25rem 0 0; font-size: .8rem; opacity: .7; font-weight: 500; }
.pay-terminal-body { padding: 1.1rem 1.15rem 1.2rem; }

.pay-opt {
    border: 2px solid #e7e5e4; background: #fff;
    border-radius: 12px; cursor: pointer; font-weight: 800; font-size: .78rem;
    min-height: 56px; display: flex; align-items: center; justify-content: center;
    transition: border-color .15s ease, background .15s ease;
}
.pay-opt.is-on { border-color: #e8a317; background: #fffbeb; }

.pay-list-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .7rem 0; border-bottom: 1px solid #f5f5f4;
    font-size: .9rem;
}
.pay-list-row:last-child { border-bottom: 0; }

.ghost-danger {
    min-height: 44px; border-radius: 12px; font-weight: 700; font-family: inherit;
    border: 1px solid #fecaca; background: #fff; color: #b91c1c; padding: 0 14px;
    cursor: pointer;
}
.ghost-mute {
    min-height: 44px; border-radius: 12px; font-weight: 700; font-family: inherit;
    border: 1px solid #e7e5e4; background: #fff; color: #44403c; padding: 0 14px;
    cursor: pointer;
}
.record-btn {
    width: 100%; min-height: 54px; border: 0; border-radius: 14px;
    background: linear-gradient(135deg, #10b981, #059669); color: #fff;
    font-weight: 800; font-family: inherit; font-size: .95rem;
    box-shadow: 0 10px 22px rgba(5,150,105,.3); cursor: pointer;
}
.record-btn:hover { filter: brightness(1.03); }
.bil-pay-tile {
    min-height: 72px; border: 2px solid #e7e5e4 !important; border-radius: 12px !important;
    background: #fff !important; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 2px;
}
.bil-pay-tile.active {
    border-color: #f59e0b !important; background: #fffbeb !important;
}
.bil-pay-key:active, .bil-pay-quick:active { transform: scale(.97); }

@media (max-width: 991.98px) {
    .show-grid { grid-template-columns: 1fr; }
    .pay-terminal { position: static; }
}
@media (max-width: 575.98px) {
    .action-dock { grid-template-columns: 1fr 1fr; }
    .info-grid { grid-template-columns: 1fr; }
    .info-cell.wide { grid-column: auto; }
}
</style>
@endpush

@section('content')
@php
    $cur = $currency;
    $due = $booking->balanceDue();
    $statusTone = match($booking->status) {
        'active' => 'ok',
        'booked' => 'warn',
        'completed' => 'mute',
        'cancelled', 'no_show' => 'danger',
        default => 'mute',
    };
    $payTone = match($booking->payment_status) {
        'paid' => 'ok',
        'partial' => 'warn',
        default => 'danger',
    };
@endphp

<div class="show-wrap">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="show-hero">
        <div>
            <p class="eyebrow">Session · {{ $booking->table?->typeLabel() ?? 'Table' }}</p>
            <h1>{{ $booking->booking_number }}</h1>
            <p class="meta">
                {{ $booking->table?->name ?? 'Table' }}
                · {{ ucfirst(str_replace('_', ' ', $booking->source)) }}
                · {{ $booking->displayName() }}
            </p>
            <div class="show-badges">
                <span class="show-badge {{ $statusTone }}">{{ $booking->statusLabel() }}</span>
                <span class="show-badge {{ $payTone }}">{{ ucfirst($booking->payment_status) }}</span>
                @if($booking->status === 'active' && $booking->scheduled_end)
                    <span class="show-badge warn" id="liveLeft">— left</span>
                @endif
            </div>
        </div>
        <div class="due-panel {{ $due <= 0 ? 'is-paid' : '' }}" style="margin:0;min-width:min(100%,220px)">
            <div class="lbl">{{ $due > 0 ? 'Amount due' : 'Settled' }}</div>
            <div class="amt">{{ $cur }} {{ number_format($due > 0 ? $due : (float)$booking->amount, 2) }}</div>
            <div class="sub">Paid {{ $cur }} {{ number_format((float)$booking->paid_amount, 2) }}</div>
        </div>
    </section>

    <div class="action-dock">
        <button type="button" class="dock-btn dark" onclick="openBilPrint(@json(route('billiards.bookings.print', $booking).'?format=html'), true)">
            <i class="fas fa-print"></i> Print 80mm
        </button>
        @if($smsReady)
            <form method="post" action="{{ route('billiards.bookings.ebill', $booking) }}" class="m-0">@csrf
                <button class="dock-btn w-100" type="submit"><i class="fas fa-sms"></i> eBill SMS</button>
            </form>
        @else
            <a href="{{ route('billiards.settings') }}" class="dock-btn"><i class="fas fa-sms"></i> SMS off</a>
        @endif
        @if($booking->status === 'booked')
            <form method="post" action="{{ route('billiards.bookings.start', $booking) }}" class="m-0">@csrf
                <button class="dock-btn green w-100" type="submit"><i class="fas fa-play"></i> Start</button>
            </form>
        @elseif($booking->status === 'active')
            <form method="post" action="{{ route('billiards.bookings.complete', $booking) }}" class="m-0">@csrf
                <button class="dock-btn dark w-100" type="submit"><i class="fas fa-flag-checkered"></i> Complete</button>
            </form>
        @else
            <button class="dock-btn is-disabled" type="button" disabled><i class="fas fa-check"></i> {{ $booking->statusLabel() }}</button>
        @endif
        @if($due > 0 && !in_array($booking->status, ['cancelled']))
            <button type="button" class="dock-btn green" id="btnOpenPay" onclick="openBilPayModal()">
                <i class="fas fa-cash-register"></i> Pay
            </button>
        @else
            <a href="{{ route('billiards.display') }}" target="_blank" class="dock-btn"><i class="fas fa-tv"></i> Display</a>
        @endif
    </div>

    <div class="show-grid">
        <div>
            <div class="show-card">
                <h2>Booking details</h2>
                <div class="info-grid">
                    <div class="info-cell">
                        <div class="k">Customer</div>
                        <div class="v">{{ $booking->displayName() }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="k">Phone</div>
                        <div class="v">{{ $booking->displayPhone() ?: '—' }}</div>
                    </div>
                    <div class="info-cell wide">
                        <div class="k">When</div>
                        <div class="v">{{ \App\Models\Setting::formatDateTime($booking->scheduled_start, 'd M Y H:i') }} → {{ \App\Models\Setting::formatDateTime($booking->scheduled_end, 'H:i') }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="k">Hours</div>
                        <div class="v">{{ rtrim(rtrim(number_format((float)$booking->hours, 2), '0'), '.') }}h</div>
                    </div>
                    <div class="info-cell">
                        <div class="k">Rate</div>
                        <div class="v">{{ $cur }} {{ number_format((float)$booking->hourly_rate, 0) }}/hr</div>
                    </div>
                    <div class="info-cell">
                        <div class="k">Total</div>
                        <div class="v">{{ $cur }} {{ number_format((float)$booking->amount, 2) }}</div>
                    </div>
                    <div class="info-cell">
                        <div class="k">Table</div>
                        <div class="v">{{ $booking->table?->name }} · {{ $booking->table?->typeLabel() }}</div>
                    </div>
                </div>

                @if(in_array($booking->status, ['booked','active']))
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form method="post" action="{{ route('billiards.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">@csrf
                        <button class="ghost-danger" type="submit">Cancel booking</button>
                    </form>
                    @if($booking->payment_status !== 'paid' && $smsReady && $booking->displayPhone())
                        <form method="post" action="{{ route('billiards.bookings.remind', $booking) }}">@csrf
                            <button class="ghost-mute" type="submit">Remind SMS</button>
                        </form>
                    @endif
                </div>
                @endif
            </div>

            <div class="show-card">
                <h2>Payments</h2>
                @forelse($booking->payments as $p)
                    <div class="pay-list-row">
                        <span>{{ $p->methodLabel() }} · {{ $p->created_at?->format('d M H:i') }}</span>
                        <strong>{{ $cur }} {{ number_format((float)$p->amount, 2) }}</strong>
                    </div>
                @empty
                    <div style="color:#78716c;font-weight:500;padding:.35rem 0">No payments yet.</div>
                @endforelse
            </div>
        </div>

        <div>
            @if($due > 0 && !in_array($booking->status, ['cancelled']))
            <div class="show-card text-center" style="padding:1.35rem">
                <img src="{{ asset('images/billiards-8ball.png') }}" alt="" width="56" height="56" style="border-radius:50%;margin-bottom:.75rem">
                <h2 style="margin-bottom:.35rem">Collect in POS</h2>
                <p style="margin:0 0 1rem;color:#78716c;font-weight:500;font-size:.9rem">
                    Due {{ $cur }} {{ number_format($due, 2) }} · same payment screen as restaurant POS
                </p>
                <button type="button" class="record-btn" onclick="openBilPayModal()" style="max-width:280px;margin:0 auto">
                    <i class="fas fa-cash-register me-1"></i> Open payment
                </button>
            </div>
            @else
            <div class="show-card text-center" style="padding:1.5rem">
                <div style="width:52px;height:52px;border-radius:50%;background:#ecfdf5;color:#059669;display:grid;place-items:center;margin:0 auto .75rem;font-size:1.2rem">
                    <i class="fas fa-check"></i>
                </div>
                <h2 style="margin-bottom:.35rem">{{ $booking->status === 'cancelled' ? 'Cancelled' : 'All settled' }}</h2>
                <p style="margin:0;color:#78716c;font-weight:500;font-size:.9rem">
                    {{ $booking->status === 'cancelled' ? 'No payment needed.' : 'Nothing left to collect on this booking.' }}
                </p>
                <a href="{{ route('billiards.pos') }}" class="dock-btn dark mt-3" style="display:inline-flex;min-width:180px;min-height:48px">
                    <i class="fas fa-plus"></i> New booking
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- POS-style payment modal --}}
@if($due > 0 && !in_array($booking->status, ['cancelled']))
<div class="modal fade" id="bilPayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:420px">
        <div class="modal-content" style="border-radius:18px;overflow:hidden;border:0">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff">
                <h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2"></i>Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="d-inline-block px-3 py-2 rounded-3" style="background:#fff7ed;border:1px solid #fed7aa">
                        <span class="text-muted small d-block">Total to pay</span>
                        <div class="fw-bold" style="font-size:1.75rem;color:#ea580c" id="bilPayTotal">{{ $cur }} {{ number_format($due, 2) }}</div>
                    </div>
                    <div class="d-flex justify-content-center gap-4 mt-2">
                        <div>
                            <div class="small text-muted">Paid</div>
                            <div class="fw-bold" id="bilPayPaid">{{ $cur }} 0.00</div>
                        </div>
                        <div>
                            <div class="small text-muted">Balance</div>
                            <div class="fw-bold text-danger" id="bilPayBalance">{{ $cur }} {{ number_format($due, 2) }}</div>
                        </div>
                    </div>
                </div>

                <label class="form-label fw-semibold text-muted small">Tap method</label>
                <div class="row g-2 mb-3" id="bilPayMethods">
                    @foreach([
                        'cash' => ['Cash', 'fa-money-bill-wave', '#10b981'],
                        'card' => ['Card', 'fa-credit-card', '#f59e0b'],
                        'bank_transfer' => ['Bank', 'fa-university', '#06b6d4'],
                        'online' => ['Online', 'fa-mobile-alt', '#ea580c'],
                        'credit' => ['Credit', 'fa-hand-holding-usd', '#f59e0b'],
                    ] as $m => $meta)
                    <div class="col-4">
                        <button type="button" class="btn w-100 bil-pay-tile {{ $m==='cash'?'active':'' }}" data-method="{{ $m }}">
                            <i class="fas {{ $meta[1] }} d-block mb-1" style="color:{{ $meta[2] }}"></i>
                            <small class="fw-semibold">{{ $meta[0] }}</small>
                        </button>
                    </div>
                    @endforeach
                    <div class="col-4">
                        <button type="button" class="btn w-100 bil-pay-tile" id="bilPayExact">
                            <i class="fas fa-equals d-block mb-1" style="color:#2563eb"></i>
                            <small class="fw-semibold">Exact</small>
                        </button>
                    </div>
                </div>

                <label class="form-label fw-semibold text-muted small">Amount</label>
                <div class="input-group mb-2">
                    <span class="input-group-text fw-bold" style="background:#f59e0b;color:#fff;border:0">{{ $cur }}</span>
                    <input type="text" id="bilPayAmount" class="form-control fw-bold text-end" readonly
                           style="font-size:1.1rem;min-height:48px;border-radius:0 10px 10px 0"
                           value="{{ number_format($due, 2, '.', '') }}">
                </div>

                <div class="row g-1 mb-2">
                    @foreach(['7','8','9','4','5','6','1','2','3','.','0','back'] as $k)
                    <div class="col-4">
                        <button type="button" class="btn w-100 bil-pay-key" data-k="{{ $k }}" style="min-height:48px;font-weight:800;border:1px solid #e2e8f0;border-radius:10px;background:#fff">
                            @if($k==='back')<i class="fas fa-backspace"></i>@else{{ $k }}@endif
                        </button>
                    </div>
                    @endforeach
                </div>

                <div class="row g-1 mb-2">
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="exact" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #e2e8f0">Exact</button></div>
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="100" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #e2e8f0">+100</button></div>
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="500" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #e2e8f0">+500</button></div>
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="1000" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #e2e8f0">+1000</button></div>
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="round" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #e2e8f0">Round</button></div>
                    <div class="col-4"><button type="button" class="btn w-100 bil-pay-quick" data-q="clear" style="min-height:40px;font-weight:700;border-radius:10px;border:1px solid #fecaca;color:#b91c1c">Clear</button></div>
                </div>

                <div class="text-center py-2 rounded-3 mb-3" style="background:#ecfdf5">
                    <span class="text-muted small">Change</span>
                    <div class="fw-bold" id="bilPayChange" style="color:#059669;font-size:1.15rem">{{ $cur }} 0.00</div>
                </div>

                <label class="form-label fw-semibold text-muted small">Reference / notes</label>
                <input type="text" id="bilPayRef" class="form-control" placeholder="Transaction ref…" style="border-radius:10px">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn" data-bs-dismiss="modal" style="background:#f1f5f9;color:#64748b;border:0;border-radius:10px;font-weight:600">Cancel</button>
                <button type="button" class="btn fw-bold text-white" id="bilPayConfirm" style="background:linear-gradient(135deg,#10b981,#059669);border:0;border-radius:12px;min-height:48px;padding:0 1.25rem">
                    <i class="fas fa-check me-1"></i>Confirm pay
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="bilPrintModal" tabindex="-1" aria-hidden="true" style="z-index:1080;">
  <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
    <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden">
      <div class="modal-header" style="background:linear-gradient(135deg,#e8a317,#c45c12);color:#fff;">
        <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i>80mm preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="background:#f8fafc;">
        <iframe id="bilPrintFrame" title="Print preview" src="about:blank" style="width:100%;height:520px;border:none;display:block;"></iframe>
      </div>
      <div class="modal-footer" style="background:#fff;border-top:1px solid #e2e8f0;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn fw-bold text-white" id="bilPrintBtn" style="background:#f59e0b;border:none;" onclick="bilDoPrint()">
          <i class="fas fa-print me-2"></i>Print
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const BIL_PRINT_URL = @json(route('billiards.bookings.print', $booking).'?format=html');
let bilPrintReady = false;
let bilPrintAuto = false;

function bilDoPrint() {
    const frame = document.getElementById('bilPrintFrame');
    if (!frame || !frame.contentWindow) return;
    try {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    } catch (e) {
        window.open(BIL_PRINT_URL + '&autoprint=1', '_blank', 'noopener');
    }
}

function openBilPrint(url, autoPrint) {
    const modalEl = document.getElementById('bilPrintModal');
    const frame = document.getElementById('bilPrintFrame');
    const btn = document.getElementById('bilPrintBtn');
    if (!modalEl || !frame) {
        window.open((url || BIL_PRINT_URL) + (String(url || '').includes('autoprint') ? '' : '&autoprint=1'), '_blank', 'noopener');
        return;
    }

    bilPrintReady = false;
    bilPrintAuto = !!autoPrint;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading…';
    }

    const target = url || BIL_PRINT_URL;
    frame.onload = function () {
        bilPrintReady = true;
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-print me-2"></i>Print';
        }
        if (bilPrintAuto) {
            bilPrintAuto = false;
            setTimeout(bilDoPrint, 350);
        }
    };
    frame.onerror = function () {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-print me-2"></i>Print';
        }
        window.open(target + (target.includes('autoprint') ? '' : '&autoprint=1'), '_blank', 'noopener');
    };
    frame.src = target;

    const openModals = document.querySelectorAll('.modal.show').length;
    const baseZ = 1060 + (openModals * 20);
    modalEl.style.zIndex = String(baseZ);
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });
    const onShown = () => {
        modalEl.removeEventListener('shown.bs.modal', onShown);
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const lastBackdrop = backdrops[backdrops.length - 1];
        if (lastBackdrop) lastBackdrop.style.zIndex = String(baseZ - 5);
    };
    const onHidden = () => {
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
        frame.src = 'about:blank';
        modalEl.style.zIndex = '';
        bilPrintReady = false;
        bilPrintAuto = false;
    };
    modalEl.addEventListener('shown.bs.modal', onShown);
    modalEl.addEventListener('hidden.bs.modal', onHidden);
    modal.show();
}

@if($due > 0 && !in_array($booking->status, ['cancelled']))
const BIL_CUR = @json($cur);
const BIL_DUE = {{ number_format($due, 2, '.', '') }};
const BIL_PAY_URL = @json(route('billiards.bookings.pay', $booking));
const BIL_CSRF = @json(csrf_token());
let bilMethod = 'cash';
let bilAmountStr = String(BIL_DUE);

function bilMoney(n){ return BIL_CUR + ' ' + Number(n || 0).toFixed(2); }

function bilSyncPayUI() {
    const amt = parseFloat(bilAmountStr) || 0;
    const paid = Math.min(amt, BIL_DUE);
    const change = Math.max(0, amt - BIL_DUE);
    const bal = Math.max(0, BIL_DUE - paid);
    document.getElementById('bilPayAmount').value = bilAmountStr;
    document.getElementById('bilPayPaid').textContent = bilMoney(paid);
    document.getElementById('bilPayBalance').textContent = bilMoney(bal);
    document.getElementById('bilPayChange').textContent = bilMoney(change);
}

function openBilPayModal() {
    bilMethod = 'cash';
    bilAmountStr = Number(BIL_DUE).toFixed(2);
    document.querySelectorAll('.bil-pay-tile[data-method]').forEach(t => {
        t.classList.toggle('active', t.dataset.method === 'cash');
    });
    document.getElementById('bilPayRef').value = '';
    bilSyncPayUI();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('bilPayModal')).show();
}

document.querySelectorAll('.bil-pay-tile[data-method]').forEach(tile => {
    tile.addEventListener('click', () => {
        document.querySelectorAll('.bil-pay-tile[data-method]').forEach(t => t.classList.remove('active'));
        tile.classList.add('active');
        bilMethod = tile.dataset.method;
    });
});

document.getElementById('bilPayExact')?.addEventListener('click', () => {
    bilAmountStr = Number(BIL_DUE).toFixed(2);
    bilMethod = 'cash';
    document.querySelectorAll('.bil-pay-tile[data-method]').forEach(t => {
        t.classList.toggle('active', t.dataset.method === 'cash');
    });
    bilSyncPayUI();
});

document.querySelectorAll('.bil-pay-key').forEach(btn => {
    btn.addEventListener('click', () => {
        const k = btn.dataset.k;
        if (k === 'back') {
            bilAmountStr = bilAmountStr.slice(0, -1);
        } else if (k === '.') {
            if (!bilAmountStr.includes('.')) bilAmountStr += '.';
        } else {
            if (bilAmountStr === '0' || bilAmountStr === '0.00') bilAmountStr = k;
            else if (bilAmountStr.includes('.') && bilAmountStr.split('.')[1].length >= 2) return;
            else bilAmountStr += k;
        }
        bilSyncPayUI();
    });
});

document.querySelectorAll('.bil-pay-quick').forEach(btn => {
    btn.addEventListener('click', () => {
        const q = btn.dataset.q;
        const cur = parseFloat(bilAmountStr) || 0;
        if (q === 'exact') bilAmountStr = Number(BIL_DUE).toFixed(2);
        else if (q === 'clear') bilAmountStr = '';
        else if (q === 'round') bilAmountStr = String(Math.ceil(BIL_DUE / 100) * 100);
        else bilAmountStr = String(cur + Number(q));
        bilSyncPayUI();
    });
});

document.getElementById('bilPayConfirm')?.addEventListener('click', async () => {
    const amount = parseFloat(bilAmountStr);
    if (!amount || amount <= 0) {
        if (window.Swal) Swal.fire({ icon:'warning', title:'Enter amount' });
        else alert('Enter amount');
        return;
    }
    try {
        const fd = new FormData();
        fd.append('_token', BIL_CSRF);
        fd.append('method', bilMethod);
        fd.append('amount', amount.toFixed(2));
        const ref = document.getElementById('bilPayRef').value.trim();
        if (ref) fd.append('reference', ref);
        const res = await fetch(BIL_PAY_URL, {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok && res.status !== 302) {
            // form redirect success often returns HTML 200/302; try anyway
            const err = await res.json().catch(() => ({}));
            if (res.status >= 400) throw new Error(err.message || 'Payment failed');
        }
        bootstrap.Modal.getInstance(document.getElementById('bilPayModal'))?.hide();
        if (window.Swal) {
            await Swal.fire({ icon:'success', title:'Paid', timer:900, showConfirmButton:false });
        }
        const printAsk = @json((bool) \App\Models\Setting::get('billiards_print_ask', true));
        const next = @json(route('billiards.bookings.show', $booking)) + (printAsk ? '?print=1' : '');
        window.location.href = next;
    } catch (e) {
        if (window.Swal) Swal.fire({ icon:'error', title:'Payment failed', text: e.message });
        else alert(e.message);
    }
});
@else
function openBilPayModal() {}
@endif

(function(){
    const params = new URLSearchParams(location.search);
    if (params.get('print') === '1') {
        setTimeout(() => openBilPrint(BIL_PRINT_URL, true), 400);
        history.replaceState({}, '', location.pathname);
    }
    if (params.get('sms') === '1') {
        @if($smsReady && $booking->displayPhone())
        setTimeout(() => {
            if (confirm('Send eBill SMS to {{ $booking->displayPhone() }}?')) {
                const f = document.createElement('form');
                f.method = 'POST';
                f.action = @json(route('billiards.bookings.ebill', $booking));
                const t = document.createElement('input');
                t.type = 'hidden'; t.name = '_token'; t.value = @json(csrf_token());
                f.appendChild(t);
                document.body.appendChild(f);
                f.submit();
            }
        }, 600);
        @endif
    }
    if (params.get('pay') === '1') {
        setTimeout(() => openBilPayModal(), 350);
    }

    @if($booking->status === 'active' && $booking->scheduled_end)
    const ends = {{ $booking->scheduled_end->getTimestampMs() }};
    const el = document.getElementById('liveLeft');
    function pad(n){ return String(n).padStart(2,'0'); }
    function tick(){
        if (!el) return;
        const secs = Math.floor((ends - Date.now()) / 1000);
        const over = secs < 0;
        const a = Math.abs(secs);
        el.textContent = (over ? '+' : '') + pad(Math.floor(a/60)) + ':' + pad(a % 60) + (over ? ' overtime' : ' left');
    }
    tick(); setInterval(tick, 1000);
    @endif
})();
</script>
@endpush
