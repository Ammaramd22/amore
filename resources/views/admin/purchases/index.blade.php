@extends('layouts.admin')
@section('title', 'Purchases')
@section('page_title', 'Purchases')
@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="purch-page">
    <div class="purch-toolbar">
        <div>
            <h3 class="purch-title">All Purchases</h3>
            <p class="purch-sub mb-0">Stock receipts from suppliers</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Purchase
        </a>
    </div>

    <div class="card purch-filters mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="filter_q">Search</label>
                    <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Purchase no / invoice / supplier" value="{{ request('q') }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="filter_supplier">Supplier</label>
                    <select id="filter_supplier" name="supplier" class="form-select form-select-sm">
                        <option value="">All suppliers</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="filter_status">Received</label>
                    <select id="filter_status" name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') == $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="filter_payment_status">Payment</label>
                    <select id="filter_payment_status" name="payment_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($paymentStatuses as $ps)
                        <option value="{{ $ps }}" @selected(request('payment_status') == $ps)>{{ ucfirst($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 col-md-4">
                    <label class="form-label" for="filter_from">From</label>
                    <input type="date" id="filter_from" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-lg-1 col-md-4">
                    <label class="form-label" for="filter_to">To</label>
                    <input type="date" id="filter_to" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                    @if(request()->hasAny(['q','supplier','status','payment_status','from','to']))
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm" title="Reset"><i class="fas fa-undo"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon total"><i class="fas fa-wallet"></i></span>
                        <span class="purch-stat-label">Total purchases</span>
                    </div>
                    <div class="purch-stat-value">
                        <span class="purch-cur">{{ $currency }}</span>{{ number_format($totalAmount, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon count"><i class="fas fa-file-invoice"></i></span>
                        <span class="purch-stat-label">Purchase records</span>
                    </div>
                    <div class="purch-stat-value">{{ $purchases->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon items"><i class="fas fa-boxes"></i></span>
                        <span class="purch-stat-label">Total items</span>
                    </div>
                    <div class="purch-stat-value">{{ $purchases->sum('items_count') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="bulk-table table purch-table mb-0" data-resource="purchases" data-export="purchases" data-can-delete="1">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th>
                            <th class="col-num">#</th>
                            <th>Purchase No</th>
                            <th>Supplier invoice</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Payment</th>
                            <th>Received</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $p)
                        @php $balance = max(0, (float) $p->total_amount - (float) $p->paid_amount); @endphp
                        <tr>
                            <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $p->id }}" aria-label="Select row"></td>
                            <td class="col-num">{{ (($purchases->currentPage() - 1) * $purchases->perPage() + $loop->iteration) }}</td>
                            <td>
                                <a href="{{ route('purchases.show', $p) }}" class="purch-no">{{ $p->displayNumber() }}</a>
                            </td>
                            <td class="purch-inv">{{ $p->invoice_number ?: '—' }}</td>
                            <td class="fw-semibold">{{ $p->supplier?->name }}</td>
                            <td class="purch-date">{{ $p->purchase_date?->format('d M Y') }}</td>
                            <td class="text-center"><span class="purch-items">{{ $p->items_count }}</span></td>
                            <td class="text-end purch-amt">{{ $currency }} {{ number_format($p->total_amount, 2) }}</td>
                            <td class="text-end purch-paid">{{ $currency }} {{ number_format($p->paid_amount, 2) }}</td>
                            <td class="text-end {{ $balance > 0 ? 'purch-bal-due' : 'text-muted' }}">{{ $currency }} {{ number_format($balance, 2) }}</td>
                            <td>
                                <span class="badge-soft {{ $p->payment_status === 'paid' ? 'success' : ($p->payment_status === 'partial' ? '' : 'danger') }}">
                                    {{ ucfirst($p->payment_status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-soft {{ $p->status === 'received' ? 'success' : ($p->status === 'pending' ? '' : 'muted') }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <x-row-actions>
                                    <li><a class="dropdown-item" href="{{ route('purchases.show', $p) }}"><i class="fas fa-eye"></i> View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('purchases.edit', $p) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                    @if($p->payment_status != 'paid')
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addPaymentModal"
                                            onclick="setPaymentPurchase({{ $p->id }}, {{ $p->total_amount }}, {{ $p->paid_amount }})">
                                            <i class="fas fa-money-bill"></i> Add Payment
                                        </button>
                                    </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('purchases.destroy', $p) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </li>
                                </x-row-actions>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13">
                                <div class="empty-state py-5">
                                    <i class="fas fa-shopping-basket"></i>
                                    No purchases found
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($purchases->hasPages())
        <div class="card-footer">{{ $purchases->links() }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title"><i class="fas fa-money-bill me-2"></i>Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="paymentTotalAmount" class="form-label">Total amount</label>
                            <input type="text" id="paymentTotalAmount" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentBalance" class="form-label">Balance</label>
                            <input type="text" id="paymentBalance" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentMethod" class="form-label">Method <span class="text-danger">*</span></label>
                            <select name="method" id="paymentMethod" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentReference" class="form-label">Reference</label>
                            <input type="text" name="reference_number" id="paymentReference" class="form-control" placeholder="Ref #">
                        </div>
                        <div class="col-md-6">
                            <label for="paymentDate" class="form-label">Payment date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="paymentDate" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    @if(\App\Models\Cheque::managementEnabled())
                    <div id="chequePaymentFields" class="row g-3 mt-1 d-none">
                        <div class="col-12"><div class="small fw-semibold text-muted">Cheque details</div></div>
                        <div class="col-md-6">
                            <label class="form-label">Cheque number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_number" id="chequeNumber" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due date <span class="text-danger">*</span></label>
                            <input type="date" name="cheque_due_date" id="chequeDueDate" class="form-control" value="{{ today()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank name</label>
                            <input type="text" name="cheque_bank_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cheque date</label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ today()->format('Y-m-d') }}">
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .purch-toolbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .purch-title { margin: 0; font-size: 1.2rem; font-weight: 800; letter-spacing: -0.02em; color: #1c1917; }
    .purch-sub { font-size: 0.85rem; color: #78716c; margin-top: 0.2rem; }
    .purch-stat-top { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.55rem; }
    .purch-stat-icon { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 0.9rem; }
    .purch-stat-icon.total { background: #fff7ed; color: #c2410c; }
    .purch-stat-icon.count { background: #ecfdf5; color: #047857; }
    .purch-stat-icon.items { background: #eff6ff; color: #1d4ed8; }
    .purch-stat-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #78716c; }
    .purch-stat-value { font-size: 1.4rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; color: #1c1917; }
    .purch-cur { font-size: 0.7em; font-weight: 700; color: #78716c; margin-right: 0.2rem; }
    .purch-table thead th {
        font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;
        color: #78716c; font-weight: 700; background: #fafaf9; border-bottom: 1px solid #e7e5e4; white-space: nowrap;
    }
    .purch-table tbody td { vertical-align: middle; }
    .purch-table tbody tr:hover { background: #fffbeb; }
    .purch-no {
        font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: 0.02em;
        color: #1c1917; text-decoration: none; background: #fff7ed; border: 1px solid #fed7aa;
        padding: 0.2rem 0.55rem; border-radius: 8px; font-size: 0.85rem; display: inline-block;
    }
    .purch-no:hover { color: #ea580c; background: #ffedd5; }
    .purch-inv { font-size: 0.85rem; color: #57534e; font-variant-numeric: tabular-nums; }
    .purch-date { white-space: nowrap; font-size: 0.88rem; color: #44403c; }
    .purch-items {
        display: inline-grid; place-items: center; min-width: 28px; height: 28px;
        border-radius: 8px; background: #f5f5f4; font-weight: 700; font-size: 0.82rem;
    }
    .purch-amt { font-weight: 750; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .purch-paid { color: #059669; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .purch-bal-due { color: #dc2626; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
</style>
@endpush

@push('scripts')
<script>
function setPaymentPurchase(id, total, paid) {
    const form = document.getElementById('paymentForm');
    form.action = @json(url('/purchases')) + '/' + id + '/payment';
    document.getElementById('paymentTotalAmount').value = @json($currency) + ' ' + parseFloat(total).toFixed(2);
    const balance = Math.max(0, total - paid);
    document.getElementById('paymentBalance').value = @json($currency) + ' ' + balance.toFixed(2);
    document.getElementById('paymentAmount').value = balance.toFixed(2);
}

(function () {
    const method = document.getElementById('paymentMethod');
    const box = document.getElementById('chequePaymentFields');
    if (!method || !box) return;
    const sync = () => box.classList.toggle('d-none', method.value !== 'cheque');
    method.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
