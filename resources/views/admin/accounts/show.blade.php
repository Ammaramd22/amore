@extends('layouts.admin')
@section('title', $account->name)
@section('page_title', $account->name)
@section('content')
@php
    $currency = \App\Models\Setting::get('currency_symbol', 'LKR');
    $typeIcon = match ($account->type) {
        'cash' => 'fa-money-bill-wave',
        'bank' => 'fa-university',
        default => 'fa-wallet',
    };
@endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="acct-page">
    <div class="acct-hero">
        <div class="acct-hero-main">
            <a href="{{ route('accounts.index') }}" class="acct-back">
                <i class="fas fa-arrow-left"></i>
                <span>Accounts</span>
            </a>
            <div class="acct-identity">
                <div class="acct-icon" data-type="{{ $account->type }}">
                    <i class="fas {{ $typeIcon }}"></i>
                </div>
                <div>
                    <div class="acct-title-row">
                        <h2 class="acct-name">{{ $account->name }}</h2>
                        <span class="badge-soft">{{ $account->code }}</span>
                        <span class="badge-soft">{{ $account->typeLabel() }}</span>
                        @if($account->is_system)<span class="badge-soft muted">System</span>@endif
                        @if($account->is_active)
                            <span class="badge-soft success">Active</span>
                        @else
                            <span class="badge-soft muted">Inactive</span>
                        @endif
                    </div>
                    @if($account->payment_methods)
                    <div class="acct-methods">
                        @foreach($account->payment_methods as $m)
                            <span>{{ str_replace('_', ' ', $m) }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="acct-hero-side">
            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-pen me-1"></i>Edit
            </a>
            <div class="acct-balance-card">
                <div class="acct-balance-label">Current balance</div>
                <div class="acct-balance-value {{ $account->current_balance >= 0 ? 'is-positive' : 'is-negative' }}">
                    <span class="acct-currency">{{ $currency }}</span>
                    {{ number_format($account->current_balance, 2) }}
                </div>
                <div class="acct-balance-meta">
                    Opening {{ $currency }} {{ number_format($account->opening_balance, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card acct-action-card h-100">
                <div class="card-header acct-action-head">
                    <div class="acct-action-title">
                        <span class="acct-action-icon in"><i class="fas fa-plus"></i></span>
                        <div>
                            <h3 class="card-title mb-0">Add transaction</h3>
                            <p class="acct-action-sub mb-0">Manual money in or out</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('accounts.transactions.store', $account) }}" class="acct-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label class="form-label" for="tx_direction">Type</label>
                                <select name="direction" id="tx_direction" class="form-select" required>
                                    <option value="in">Money in</option>
                                    <option value="out">Money out</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="tx_amount">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currency }}</span>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="tx_amount" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="tx_reference">Reference</label>
                                <input type="text" name="reference" id="tx_reference" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tx_description">Description</label>
                                <input type="text" name="description" id="tx_description" class="form-control" placeholder="What is this for?" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Record
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card acct-action-card h-100">
                <div class="card-header acct-action-head">
                    <div class="acct-action-title">
                        <span class="acct-action-icon transfer"><i class="fas fa-exchange-alt"></i></span>
                        <div>
                            <h3 class="card-title mb-0">Transfer</h3>
                            <p class="acct-action-sub mb-0">Move funds to another account</p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($otherAccounts->isEmpty())
                        <div class="acct-empty-inline">
                            <i class="fas fa-random"></i>
                            <p class="mb-0">Create another account to transfer funds.</p>
                        </div>
                    @else
                    <form method="POST" action="{{ route('accounts.transfer') }}" class="acct-form">
                        @csrf
                        <input type="hidden" name="from_account_id" value="{{ $account->id }}">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="to_account_id">To account</label>
                                <select name="to_account_id" id="to_account_id" class="form-select" required>
                                    @foreach($otherAccounts as $other)
                                        <option value="{{ $other->id }}">{{ $other->name }} ({{ $other->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="tr_amount">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currency }}</span>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="tr_amount" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="tr_description">Description</label>
                                <input type="text" name="description" id="tr_description" class="form-control" placeholder="Optional note">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-exchange-alt me-1"></i>Transfer
                                </button>
                            </div>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card acct-ledger">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-0">Ledger</h3>
                <p class="acct-action-sub mb-0 mt-1">{{ $transactions->total() }} transaction{{ $transactions->total() === 1 ? '' : 's' }}</p>
            </div>
            <form method="GET" class="acct-filters">
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" aria-label="From date">
                <span class="acct-filter-sep">to</span>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" aria-label="To date">
                <select name="direction" class="form-select form-select-sm" aria-label="Direction">
                    <option value="">All</option>
                    <option value="in" @selected(request('direction')==='in')>Money in</option>
                    <option value="out" @selected(request('direction')==='out')>Money out</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table acct-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Method</th>
                            <th>Ref</th>
                            <th class="text-end">In</th>
                            <th class="text-end">Out</th>
                            <th class="text-end">Balance</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                        <tr>
                            <td class="acct-date">
                                <span class="acct-date-day">{{ $tx->transacted_at?->format('d M Y') }}</span>
                                <span class="acct-date-time">{{ \App\Models\Setting::formatDateTime($tx->transacted_at, 'H:i') }}</span>
                            </td>
                            <td>
                                <div class="acct-desc">{{ $tx->description }}</div>
                            </td>
                            <td>
                                @if($tx->payment_method)
                                    <span class="acct-method">{{ str_replace('_', ' ', $tx->payment_method) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="acct-ref">{{ $tx->reference ?: '—' }}</td>
                            <td class="text-end acct-amt in">
                                @if($tx->direction === 'in')
                                    +{{ number_format($tx->amount, 2) }}
                                @endif
                            </td>
                            <td class="text-end acct-amt out">
                                @if($tx->direction === 'out')
                                    −{{ number_format($tx->amount, 2) }}
                                @endif
                            </td>
                            <td class="text-end acct-bal">{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="acct-by">{{ $tx->creator?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-5">
                                    <i class="fas fa-book-open"></i>
                                    No transactions yet
                                    <div class="text-muted small mt-1">Record a payment or transfer to start the ledger.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .acct-page { --acct-ink: #1c1917; --acct-muted: #78716c; --acct-line: #f5f5f4; --acct-in: #059669; --acct-out: #dc2626; }
    .acct-hero {
        display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1.25rem;
        align-items: flex-start; margin-bottom: 1.25rem;
        padding: 1.25rem 1.35rem; border-radius: 16px;
        background: linear-gradient(135deg, #fff 0%, #fffaf5 55%, #fff7ed 100%);
        border: 1px solid #ffedd5;
        box-shadow: 0 1px 2px rgba(28,25,23,0.04), 0 8px 24px rgba(28,25,23,0.04);
    }
    .acct-back {
        display: inline-flex; align-items: center; gap: 0.45rem;
        color: var(--acct-muted); text-decoration: none; font-size: 0.82rem; font-weight: 600;
        margin-bottom: 0.85rem; transition: color 0.15s;
    }
    .acct-back:hover { color: #ea580c; }
    .acct-identity { display: flex; gap: 0.9rem; align-items: flex-start; }
    .acct-icon {
        width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center;
        font-size: 1.15rem; flex-shrink: 0; background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;
    }
    .acct-icon[data-type="cash"] { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .acct-icon[data-type="bank"] { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .acct-name {
        margin: 0; font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; color: var(--acct-ink);
        line-height: 1.2;
    }
    .acct-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem 0.5rem; }
    .acct-methods { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.55rem; }
    .acct-methods span {
        font-size: 0.72rem; font-weight: 600; text-transform: capitalize;
        color: #9a3412; background: #fff7ed; border: 1px solid #fed7aa;
        padding: 0.15rem 0.5rem; border-radius: 999px;
    }
    .acct-hero-side { display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem; }
    .acct-balance-card {
        text-align: right; min-width: 200px; padding: 0.85rem 1rem;
        background: #fff; border-radius: 14px; border: 1px solid #e7e5e4;
    }
    .acct-balance-label {
        font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em;
        text-transform: uppercase; color: var(--acct-muted);
    }
    .acct-balance-value {
        font-size: clamp(1.45rem, 2.5vw, 1.85rem); font-weight: 800;
        letter-spacing: -0.03em; font-variant-numeric: tabular-nums; line-height: 1.15; margin-top: 0.15rem;
    }
    .acct-balance-value.is-positive { color: #0f172a; }
    .acct-balance-value.is-negative { color: var(--acct-out); }
    .acct-currency { font-size: 0.72em; font-weight: 700; color: var(--acct-muted); margin-right: 0.15rem; }
    .acct-balance-meta { font-size: 0.8rem; color: var(--acct-muted); margin-top: 0.25rem; }

    .acct-action-head { align-items: flex-start !important; }
    .acct-action-title { display: flex; gap: 0.75rem; align-items: flex-start; }
    .acct-action-icon {
        width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center;
        font-size: 0.9rem; flex-shrink: 0;
    }
    .acct-action-icon.in { background: #ecfdf5; color: #047857; }
    .acct-action-icon.transfer { background: #fff7ed; color: #c2410c; }
    .acct-action-sub { font-size: 0.8rem; color: var(--acct-muted); font-weight: 500; }
    .acct-form .input-group-text {
        background: #fafaf9; border-color: #e7e5e4; color: var(--acct-muted);
        font-weight: 700; font-size: 0.8rem;
    }
    .acct-empty-inline {
        display: flex; align-items: center; gap: 0.75rem; color: var(--acct-muted);
        padding: 1.25rem 0.25rem; font-size: 0.92rem;
    }
    .acct-empty-inline i { font-size: 1.25rem; color: #d6d3d1; }

    .acct-filters {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem;
    }
    .acct-filters .form-control,
    .acct-filters .form-select { width: auto; min-width: 0; }
    .acct-filter-sep { font-size: 0.75rem; color: var(--acct-muted); font-weight: 600; }

    .acct-table { font-size: 0.9rem; }
    .acct-table thead th {
        font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--acct-muted); font-weight: 700; background: #fafaf9;
        border-bottom: 1px solid #e7e5e4; white-space: nowrap; padding-top: 0.85rem; padding-bottom: 0.85rem;
    }
    .acct-table tbody td {
        vertical-align: middle; padding-top: 0.9rem; padding-bottom: 0.9rem;
        border-color: #f5f5f4;
    }
    .acct-table tbody tr:hover { background: #fffbeb; }
    .acct-date { white-space: nowrap; }
    .acct-date-day { display: block; font-weight: 650; color: var(--acct-ink); }
    .acct-date-time { display: block; font-size: 0.75rem; color: var(--acct-muted); }
    .acct-desc { font-weight: 600; color: var(--acct-ink); max-width: 280px; }
    .acct-method {
        display: inline-block; font-size: 0.72rem; font-weight: 650; text-transform: capitalize;
        background: #f5f5f4; color: #57534e; border: 1px solid #e7e5e4;
        padding: 0.15rem 0.5rem; border-radius: 999px; white-space: nowrap;
    }
    .acct-ref { font-size: 0.8rem; color: var(--acct-muted); font-variant-numeric: tabular-nums; }
    .acct-amt { font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; }
    .acct-amt.in { color: var(--acct-in); }
    .acct-amt.out { color: var(--acct-out); }
    .acct-bal { font-variant-numeric: tabular-nums; font-weight: 750; color: var(--acct-ink); white-space: nowrap; }
    .acct-by { font-size: 0.8rem; color: var(--acct-muted); white-space: nowrap; }

    @media (max-width: 767px) {
        .acct-hero-side { width: 100%; align-items: stretch; }
        .acct-balance-card { text-align: left; }
        .acct-filters { width: 100%; }
        .acct-filters .form-control,
        .acct-filters .form-select { flex: 1 1 120px; }
        .acct-desc { max-width: 180px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .acct-back { transition: none; }
    }
</style>
@endpush
