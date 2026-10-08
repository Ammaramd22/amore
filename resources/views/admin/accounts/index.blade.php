@extends('layouts.admin')
@section('title', 'Accounts')
@section('page_title', 'Accounts')
@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp

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

<div class="acct-index">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card acct-stat h-100">
                <div class="card-body">
                    <div class="acct-stat-top">
                        <span class="acct-stat-icon cash"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="acct-stat-label">Cash balance</span>
                    </div>
                    <div class="acct-stat-value">
                        <span class="acct-stat-cur">{{ $currency }}</span>
                        {{ number_format($totals['cash'], 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card acct-stat h-100">
                <div class="card-body">
                    <div class="acct-stat-top">
                        <span class="acct-stat-icon bank"><i class="fas fa-university"></i></span>
                        <span class="acct-stat-label">Bank balance</span>
                    </div>
                    <div class="acct-stat-value">
                        <span class="acct-stat-cur">{{ $currency }}</span>
                        {{ number_format($totals['bank'], 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card acct-stat h-100 acct-stat-total">
                <div class="card-body">
                    <div class="acct-stat-top">
                        <span class="acct-stat-icon all"><i class="fas fa-layer-group"></i></span>
                        <span class="acct-stat-label">All accounts</span>
                    </div>
                    <div class="acct-stat-value">
                        <span class="acct-stat-cur">{{ $currency }}</span>
                        {{ number_format($totals['all'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-0">Chart of Accounts</h3>
                <p class="acct-index-sub mb-0">Cash, bank &amp; linked POS payment methods</p>
            </div>
            <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Add Account
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table acct-coa mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Linked payments</th>
                            <th class="text-end">Opening</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td><span class="acct-code">{{ $account->code }}</span></td>
                            <td>
                                <a href="{{ route('accounts.show', $account) }}" class="acct-link">{{ $account->name }}</a>
                                @if($account->is_system)<span class="badge-soft muted ms-1">System</span>@endif
                            </td>
                            <td>
                                <span class="badge-soft {{ $account->type === 'cash' ? 'success' : '' }}">{{ $account->typeLabel() }}</span>
                            </td>
                            <td>
                                <div class="acct-pay-tags">
                                    @forelse($account->payment_methods ?? [] as $m)
                                        <span>{{ str_replace('_', ' ', $m) }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-end acct-num">{{ $currency }} {{ number_format($account->opening_balance, 2) }}</td>
                            <td class="text-end acct-num fw-semibold">{{ $currency }} {{ number_format($account->current_balance, 2) }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge-soft success">Active</span>
                                @else
                                    <span class="badge-soft muted">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
                                <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-5">
                                    <i class="fas fa-university"></i>
                                    No accounts yet
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .acct-index-sub { font-size: 0.8rem; color: #78716c; margin-top: 0.25rem; font-weight: 500; }
    .acct-stat .card-body { padding: 1.15rem 1.25rem; }
    .acct-stat-top { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.65rem; }
    .acct-stat-icon {
        width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 0.9rem;
    }
    .acct-stat-icon.cash { background: #ecfdf5; color: #047857; }
    .acct-stat-icon.bank { background: #eff6ff; color: #1d4ed8; }
    .acct-stat-icon.all { background: #fff7ed; color: #c2410c; }
    .acct-stat-label {
        font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em;
        text-transform: uppercase; color: #78716c;
    }
    .acct-stat-value {
        font-size: 1.45rem; font-weight: 800; letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums; color: #1c1917; line-height: 1.15;
    }
    .acct-stat-cur { font-size: 0.7em; font-weight: 700; color: #78716c; margin-right: 0.2rem; }
    .acct-stat-total {
        background: linear-gradient(135deg, #fff 0%, #fff7ed 100%);
        border: 1px solid #ffedd5;
    }

    .acct-coa thead th {
        font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
        color: #78716c; font-weight: 700; background: #fafaf9; border-bottom: 1px solid #e7e5e4;
    }
    .acct-coa tbody td { vertical-align: middle; }
    .acct-coa tbody tr:hover { background: #fffbeb; }
    .acct-code {
        font-weight: 750; font-variant-numeric: tabular-nums; letter-spacing: 0.02em;
        color: #44403c; background: #f5f5f4; border: 1px solid #e7e5e4;
        padding: 0.15rem 0.45rem; border-radius: 6px; font-size: 0.8rem;
    }
    .acct-link { font-weight: 650; color: #1c1917; text-decoration: none; }
    .acct-link:hover { color: #ea580c; }
    .acct-pay-tags { display: flex; flex-wrap: wrap; gap: 0.3rem; }
    .acct-pay-tags span {
        font-size: 0.72rem; font-weight: 600; text-transform: capitalize;
        background: #fff; border: 1px solid #e7e5e4; color: #57534e;
        padding: 0.12rem 0.45rem; border-radius: 999px;
    }
    .acct-num { font-variant-numeric: tabular-nums; white-space: nowrap; }
</style>
@endpush
