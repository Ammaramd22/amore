@extends('layouts.admin')
@section('title', 'Partner Ledger')
@section('page_title', 'Partner Ledger')

@section('content')
@php
    $cur = $currency ?? 'LKR';
    $balance = $partner ? (float) ($balances[$partner->id] ?? 0) : 0;
    $cycleLabel = [
        'weekly' => 'Pays weekly',
        'on_receive' => 'Pays when received',
        'per_order' => 'Pays per order',
    ];
@endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="dpl-shell">
    <div class="dpl-toolbar">
        <div>
            <h3 class="dpl-title"><i class="fas fa-book me-2 text-warning"></i>Partner ledger</h3>
            <p class="dpl-sub mb-0">Outstanding dues · receive settlements · account deposit</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('delivery-partners.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-motorcycle me-1"></i>All partners</a>
            @if($partner)
            <a href="{{ route('delivery-partners.edit', $partner) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog me-1"></i>Partner settings</a>
            @endif
        </div>
    </div>

    @if($partners->isEmpty())
        <div class="dpl-empty-card">
            <i class="fas fa-motorcycle"></i>
            <h4>No delivery partners yet</h4>
            <p>Add Uber Eats, PickMe, etc. then open ledger from Actions.</p>
            <a href="{{ route('delivery-partners.create') }}" class="btn btn-warning">Add partner</a>
        </div>
    @else
        <div class="dpl-partner-rail" role="tablist">
            @foreach($partners as $p)
            @php $b = (float) ($balances[$p->id] ?? 0); @endphp
            <a href="{{ route('delivery-partners.ledger', ['partner_id' => $p->id]) }}"
               class="dpl-chip {{ $partner && $partner->id === $p->id ? 'is-active' : '' }}">
                <span class="dpl-chip-name">{{ $p->name }}</span>
                <span class="dpl-chip-amt {{ $b > 0.009 ? 'is-due' : 'is-clear' }}">{{ $cur }} {{ number_format($b, 2) }}</span>
            </a>
            @endforeach
        </div>

        @if($partner)
        <div class="dpl-hero">
            <div class="dpl-hero-main">
                <div class="dpl-hero-kicker">{{ $partner->code }}</div>
                <h2 class="dpl-hero-name">{{ $partner->name }}</h2>
                <div class="dpl-hero-meta">
                    <span><i class="fas fa-calendar-week me-1"></i>{{ $cycleLabel[$partner->settlement_cycle ?? 'weekly'] ?? 'Pays weekly' }}</span>
                    <span><i class="fas fa-percent me-1"></i>{{ number_format($partner->commission_rate, 2) }}{{ $partner->commission_type === 'percentage' ? '%' : ' '.$cur }} commission</span>
                    <span class="badge-soft {{ $partner->is_active ? 'success' : 'muted' }}">{{ $partner->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <div class="dpl-hero-balance {{ $balance > 0.009 ? 'is-due' : 'is-clear' }}">
                <div class="dpl-bal-label">They owe you</div>
                <div class="dpl-bal-value">{{ $cur }} {{ number_format($balance, 2) }}</div>
                <div class="dpl-bal-hint">{{ $balance > 0.009 ? 'Collect via Receive payment' : 'Settled — nothing outstanding' }}</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="dpl-card dpl-receive">
                    <div class="dpl-card-head">
                        <div>
                            <div class="dpl-card-title"><i class="fas fa-hand-holding-usd me-2"></i>Receive payment</div>
                            <div class="dpl-card-sub">When {{ $partner->name }} remits (weekly / transfer)</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('delivery-partners.payments.store', $partner) }}" class="dpl-card-body">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount *</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">{{ $cur }}</span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $balance > 0 ? number_format($balance, 2, '.', '') : '') }}" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Date *</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Method *</label>
                                <select name="method" class="form-select" required>
                                    <option value="bank_transfer">Bank</option>
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deposit to</label>
                            <select name="account_id" class="form-select">
                                <option value="">Auto (default bank/cash)</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="UTR / slip no.">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Week 28 settlement"></textarea>
                        </div>
                        <button class="btn btn-warning btn-lg w-100 fw-bold" type="submit">
                            <i class="fas fa-check-circle me-1"></i>Receive & post
                        </button>
                        <p class="dpl-form-hint mb-0">Clears oldest pending COD orders first · posts into Accounts</p>
                    </form>
                </div>

                <div class="dpl-card mt-3">
                    <div class="dpl-card-head">
                        <div class="dpl-card-title"><i class="fas fa-clock me-2"></i>Pending orders</div>
                    </div>
                    <div class="dpl-pending-list">
                        @forelse($pendingOrders as $o)
                        <div class="dpl-pending-row">
                            <div>
                                <div class="fw-semibold">{{ $o->order_number }}</div>
                                <div class="small text-muted">{{ $o->created_at?->format('d M Y') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ number_format(max(0, $o->partner_due_amount - $o->partner_settled_amount), 2) }}</div>
                                <span class="dpl-pill {{ $o->partner_settlement_status === 'partial' ? 'info' : 'warn' }}">{{ $o->partner_settlement_status }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="dpl-quiet">No pending dues — place COD delivery orders with this partner to see balances here.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="dpl-card mb-3">
                    <div class="dpl-card-head dpl-card-head-split">
                        <div>
                            <div class="dpl-card-title"><i class="fas fa-list me-2"></i>Ledger</div>
                            <div class="dpl-card-sub">Debit = they owe · Credit = you received</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table dpl-table mb-0">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $e)
                                <tr>
                                    <td class="text-nowrap">{{ $e->created_at?->format('d M Y · H:i') }}</td>
                                    <td>
                                        <span class="dpl-type dpl-type-{{ $e->type }}">{{ $e->type }}</span>
                                    </td>
                                    <td>
                                        {{ $e->note }}
                                        @if($e->order)<div class="small text-muted">{{ $e->order->order_number }}</div>@endif
                                    </td>
                                    <td class="text-end">{{ $e->debit > 0 ? number_format($e->debit, 2) : '—' }}</td>
                                    <td class="text-end text-success fw-semibold">{{ $e->credit > 0 ? number_format($e->credit, 2) : '—' }}</td>
                                    <td class="text-end fw-bold">{{ number_format($e->balance_after, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="dpl-quiet py-4">
                                            No ledger entries yet.<br>
                                            <span class="small">COD deliveries with {{ $partner->name }} will appear as dues here.</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($entries, 'links') && $entries->hasPages())
                    <div class="dpl-card-foot">{{ $entries->links() }}</div>
                    @endif
                </div>

                <div class="dpl-card">
                    <div class="dpl-card-head">
                        <div class="dpl-card-title"><i class="fas fa-receipt me-2"></i>Recent payments received</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table dpl-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th>Method</th>
                                    <th>Account</th>
                                    <th>Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $pay)
                                <tr>
                                    <td>{{ $pay->payment_date?->format('d M Y') }}</td>
                                    <td class="text-end fw-bold text-success">{{ $cur }} {{ number_format($pay->amount, 2) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $pay->method)) }}</td>
                                    <td>{{ $pay->account?->name ?? '—' }}</td>
                                    <td>{{ $pay->reference ?: '—' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5"><div class="dpl-quiet py-3">No settlements recorded yet</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
.dpl-shell { max-width: 1200px; }
.dpl-toolbar { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1.1rem; }
.dpl-title { margin:0; font-size:1.25rem; font-weight:800; color:#1c1917; }
.dpl-sub { font-size:.85rem; color:#78716c; }
.dpl-partner-rail {
    display:flex; gap:.6rem; overflow-x:auto; padding-bottom:.4rem; margin-bottom:1rem;
    scrollbar-width: thin;
}
.dpl-chip {
    flex:0 0 auto; display:flex; flex-direction:column; gap:.15rem;
    min-width:140px; padding:.7rem .9rem; border-radius:14px;
    background:#fff; border:1px solid #e7e5e4; text-decoration:none; color:#1c1917;
    transition: border-color .15s, box-shadow .15s, transform .15s;
}
.dpl-chip:hover { border-color:#fdba74; box-shadow:0 6px 16px rgba(245,158,11,.12); transform:translateY(-1px); }
.dpl-chip.is-active { border-color:#f59e0b; background:linear-gradient(160deg,#fff7ed,#ffedd5); box-shadow:0 0 0 2px rgba(245,158,11,.2); }
.dpl-chip-name { font-weight:700; font-size:.9rem; }
.dpl-chip-amt { font-size:.78rem; font-weight:700; }
.dpl-chip-amt.is-due { color:#b91c1c; }
.dpl-chip-amt.is-clear { color:#059669; }

.dpl-hero {
    display:flex; justify-content:space-between; gap:1.25rem; flex-wrap:wrap;
    background:linear-gradient(135deg,#1c1917 0%,#44403c 55%,#292524 100%);
    color:#fafaf9; border-radius:18px; padding:1.25rem 1.4rem; margin-bottom:1.1rem;
    box-shadow:0 16px 40px rgba(28,25,23,.25);
}
.dpl-hero-kicker { font-size:.7rem; letter-spacing:.14em; text-transform:uppercase; opacity:.55; font-weight:700; }
.dpl-hero-name { margin:.2rem 0 .55rem; font-size:1.55rem; font-weight:800; }
.dpl-hero-meta { display:flex; flex-wrap:wrap; gap:.65rem 1rem; font-size:.82rem; opacity:.85; align-items:center; }
.dpl-hero-balance { text-align:right; min-width:180px; }
.dpl-bal-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; opacity:.55; }
.dpl-bal-value { font-size:1.85rem; font-weight:800; line-height:1.15; margin:.2rem 0; }
.dpl-hero-balance.is-due .dpl-bal-value { color:#fdba74; }
.dpl-hero-balance.is-clear .dpl-bal-value { color:#6ee7b7; }
.dpl-bal-hint { font-size:.78rem; opacity:.65; }

.dpl-card {
    background:#fff; border:1px solid #e7e5e4; border-radius:16px; overflow:hidden;
    box-shadow:0 1px 2px rgba(28,25,23,.04);
}
.dpl-receive { border-color:#fed7aa; }
.dpl-card-head { padding:.95rem 1.1rem; border-bottom:1px solid #f5f5f4; background:#fafaf9; }
.dpl-receive .dpl-card-head { background:linear-gradient(90deg,#fff7ed,#fffbeb); }
.dpl-card-head-split { display:flex; justify-content:space-between; align-items:center; gap:1rem; }
.dpl-card-title { font-weight:800; color:#1c1917; font-size:.98rem; }
.dpl-card-sub { font-size:.78rem; color:#78716c; margin-top:.15rem; }
.dpl-card-body { padding:1.1rem; }
.dpl-card-foot { padding:.75rem 1rem; border-top:1px solid #f5f5f4; }
.dpl-form-hint { margin-top:.75rem; font-size:.75rem; color:#a8a29e; text-align:center; }

.dpl-pending-list { max-height:320px; overflow:auto; }
.dpl-pending-row {
    display:flex; justify-content:space-between; gap:1rem; padding:.75rem 1.1rem;
    border-bottom:1px solid #f5f5f4;
}
.dpl-pending-row:last-child { border-bottom:0; }
.dpl-pill {
    display:inline-block; font-size:.65rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.04em; padding:.2rem .45rem; border-radius:999px;
}
.dpl-pill.warn { background:#ffedd5; color:#c2410c; }
.dpl-pill.info { background:#e0f2fe; color:#0369a1; }

.dpl-table thead th {
    font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:#78716c;
    background:#fafaf9; border-bottom:1px solid #e7e5e4; white-space:nowrap;
}
.dpl-table td { vertical-align:middle; font-size:.9rem; }
.dpl-type {
    display:inline-block; font-size:.68rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.04em; padding:.25rem .5rem; border-radius:8px;
}
.dpl-type-due { background:#fee2e2; color:#b91c1c; }
.dpl-type-payment { background:#d1fae5; color:#047857; }
.dpl-type-adjustment { background:#e7e5e4; color:#44403c; }

.dpl-quiet { text-align:center; color:#a8a29e; padding:1.25rem 1rem; font-size:.9rem; line-height:1.45; }
.dpl-empty-card {
    text-align:center; padding:3rem 1.5rem; background:#fff; border:1px dashed #d6d3d1;
    border-radius:18px; color:#78716c;
}
.dpl-empty-card i { font-size:2rem; color:#f59e0b; margin-bottom:.75rem; display:block; }
.dpl-empty-card h4 { color:#1c1917; font-weight:800; }
</style>
@endpush
