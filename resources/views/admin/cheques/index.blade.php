@extends('layouts.admin')
@section('title', 'Cheques')
@section('page_title', 'Cheque Management')

@section('content')
@php
    $statusFilter = request('status');
@endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="chq-page">
    <div class="purch-toolbar">
        <div>
            <h3 class="purch-title">Supplier Cheques</h3>
            <p class="purch-sub mb-0">Pending, cleared &amp; returned — linked to purchases and accounts</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChequeModal">
            <i class="fas fa-plus me-1"></i>Add Cheque
        </button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon total"><i class="fas fa-money-check-alt"></i></span>
                        <span class="purch-stat-label">Pending total</span>
                    </div>
                    <div class="purch-stat-value"><span class="purch-cur">{{ $currency }}</span>{{ number_format($pendingTotal, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon items"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="purch-stat-label">Overdue</span>
                    </div>
                    <div class="purch-stat-value">{{ $overdueCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card purch-stat h-100">
                <div class="card-body">
                    <div class="purch-stat-top">
                        <span class="purch-stat-icon count"><i class="fas fa-list"></i></span>
                        <span class="purch-stat-label">Listed</span>
                    </div>
                    <div class="purch-stat-value">{{ $cheques->total() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card purch-filters mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cheque # / bank / supplier">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['pending','cleared','returned','cancelled'] as $st)
                        <option value="{{ $st }}" @selected($statusFilter===$st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Supplier</label>
                    <select name="supplier" class="form-select form-select-sm select2" data-placeholder="All suppliers">
                        <option value="">All suppliers</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier')==$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Due from</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Due to</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-lg-1 col-md-4">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table purch-table mb-0">
                    <thead>
                        <tr>
                            <th>Cheque #</th>
                            <th>Supplier</th>
                            <th>Purchase</th>
                            <th>Amount</th>
                            <th>Due</th>
                            <th>Bank</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                        <tr class="{{ $cheque->status==='pending' && $cheque->due_date->isPast() ? 'table-warning' : '' }}">
                            <td><a href="{{ route('cheques.show', $cheque) }}" class="purch-no">{{ $cheque->cheque_number }}</a></td>
                            <td>{{ $cheque->supplier?->name }}</td>
                            <td>
                                @if($cheque->purchase)
                                    <a href="{{ route('purchases.show', $cheque->purchase) }}">{{ $cheque->purchase->displayNumber() }}</a>
                                @else — @endif
                            </td>
                            <td class="purch-amt">{{ $currency }} {{ number_format($cheque->amount, 2) }}</td>
                            <td>
                                {{ $cheque->due_date->format('d M Y') }}
                                @if($cheque->isPending() && $cheque->due_date->isPast())
                                    <span class="badge-soft danger">Overdue</span>
                                @endif
                            </td>
                            <td>{{ $cheque->bank_name ?: '—' }}</td>
                            <td><span class="badge bg-{{ $cheque->statusBadgeClass() }}">{{ $cheque->statusLabel() }}</span></td>
                            <td class="text-end">
                                <x-row-actions>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('cheques.show', $cheque) }}">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </li>
                                    @if($cheque->status === 'pending')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('cheques.edit', $cheque) }}">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item"
                                            data-bs-toggle="modal"
                                            data-bs-target="#chequeStatusModal"
                                            data-cheque-id="{{ $cheque->id }}"
                                            data-cheque-number="{{ $cheque->cheque_number }}"
                                            data-cheque-amount="{{ number_format($cheque->amount, 2) }}"
                                            data-account-id="{{ $cheque->account_id }}"
                                            data-actions="pending">
                                            <i class="fas fa-exchange-alt"></i> Change status
                                        </button>
                                    </li>
                                    @elseif($cheque->status === 'cleared')
                                    <li>
                                        <button type="button" class="dropdown-item"
                                            data-bs-toggle="modal"
                                            data-bs-target="#chequeStatusModal"
                                            data-cheque-id="{{ $cheque->id }}"
                                            data-cheque-number="{{ $cheque->cheque_number }}"
                                            data-cheque-amount="{{ number_format($cheque->amount, 2) }}"
                                            data-actions="cleared">
                                            <i class="fas fa-exchange-alt"></i> Change status
                                        </button>
                                    </li>
                                    @endif
                                    @if($cheque->status !== 'cleared')
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('cheques.destroy', $cheque) }}" method="POST"
                                              onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </x-row-actions>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-5">
                                    <i class="fas fa-money-check-alt"></i>
                                    No cheques yet — tap <strong>Add Cheque</strong>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cheques->hasPages())
        <div class="card-footer">{{ $cheques->links() }}</div>
        @endif
    </div>
</div>

{{-- Change status modal --}}
<div class="modal fade" id="chequeStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chq-modal">
            <div class="modal-header respos">
                <h5 class="modal-title"><i class="fas fa-exchange-alt me-2" style="color:#f59e0b;"></i>Change status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 text-muted" id="chequeStatusHint">Update cheque status</p>
                <div id="chequeClearBlock" class="d-none">
                    <form id="chequeClearForm" method="POST" action="">
                        @csrf
                        <label class="form-label">Bank account (clear to)</label>
                        <select name="account_id" id="chequeStatusAccount" class="form-select chq-status-select2 mb-3" data-placeholder="Default bank">
                            <option value="">Default bank</option>
                            @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}{{ $a->code ? ' ('.$a->code.')' : '' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-success w-100" id="chequeClearBtn">
                            <i class="fas fa-check me-1"></i>Mark Cleared
                        </button>
                    </form>
                    <hr class="my-3">
                </div>
                <div id="chequeReturnBlock" class="d-none">
                    <form id="chequeReturnForm" method="POST" action="">
                        @csrf
                        <label class="form-label">Return reason</label>
                        <input type="text" name="returned_reason" class="form-control mb-3" placeholder="Insufficient funds, stop payment…">
                        <button type="submit" class="btn btn-danger w-100" id="chequeReturnBtn">
                            <i class="fas fa-undo me-1"></i>Mark Returned
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Cheque modal --}}
<div class="modal fade" id="addChequeModal" tabindex="-1" aria-labelledby="addChequeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content chq-modal">
            <div class="modal-header respos">
                <h5 class="modal-title" id="addChequeModalLabel">
                    <i class="fas fa-money-check-alt me-2" style="color:#f59e0b;"></i>New supplier cheque
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('cheques.store') }}" id="addChequeForm">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="chq_supplier_id">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="chq_supplier_id" class="form-select chq-select2" required data-placeholder="Search supplier…">
                                <option value=""></option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="chq_purchase_id">Link purchase <span class="text-muted fw-normal">(optional)</span></label>
                            <select name="purchase_id" id="chq_purchase_id" class="form-select chq-select2" data-placeholder="Search purchase…">
                                <option value=""></option>
                                @foreach($purchases as $p)
                                <option value="{{ $p->id }}"
                                    data-supplier="{{ $p->supplier_id }}"
                                    data-balance="{{ max(0, $p->total_amount - $p->paid_amount) }}"
                                    @selected(old('purchase_id')==$p->id)>
                                    {{ $p->displayNumber() }} · {{ $p->supplier?->name }} · bal {{ number_format(max(0,$p->total_amount-$p->paid_amount),2) }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="apply_to_purchase" value="1" id="apply_to_purchase" @checked(old('apply_to_purchase', true))>
                                <label class="form-check-label" for="apply_to_purchase">Apply amount to purchase balance</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="chq-section-label"><i class="fas fa-receipt me-1"></i> Cheque details</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cheque number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number') }}" required placeholder="e.g. 256488">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currency }}</span>
                                <input type="number" step="0.01" name="amount" id="chq_amount" class="form-control" value="{{ old('amount') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clear to account</label>
                            <select name="account_id" id="chq_account_id" class="form-select chq-select2" data-placeholder="Default bank">
                                <option value="">Default bank</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}" @selected(old('account_id')==$a->id)>{{ $a->name }} ({{ $a->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="e.g. Peoples Bank">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name') }}" placeholder="e.g. Kandy">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cheque date</label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ old('cheque_date', today()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due / deposit date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', today()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional note">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save as Pending
                    </button>
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
    .purch-stat-icon.items { background: #fff7ed; color: #b45309; }
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
    .purch-no:hover { background: #ffedd5; color: #9a3412; }
    .purch-amt { font-weight: 750; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .chq-section-label {
        font-size: 0.72rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
        color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px;
        padding: 0.45rem 0.75rem;
    }
    .chq-modal .modal-content { border: none; border-radius: 16px; overflow: hidden; }
    .chq-modal .form-label { font-weight: 600; color: #44403c; font-size: 0.85rem; }
    #addChequeModal .select2-container { width: 100% !important; z-index: 1065; }
    #addChequeModal .select2-dropdown { z-index: 1070 !important; }
    #chequeStatusModal .select2-container { width: 100% !important; z-index: 1065; }
    #chequeStatusModal .select2-dropdown { z-index: 1070 !important; }
    .row-actions .dropdown-item i { width: 1.1rem; margin-right: .35rem; color: #a8a29e; }
    .row-actions .dropdown-item.text-danger i { color: #dc2626; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const clearBase = @json(url('/cheques'));
    const currency = @json($currency);

    // Status change modal
    const statusModal = document.getElementById('chequeStatusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            const id = btn.getAttribute('data-cheque-id');
            const number = btn.getAttribute('data-cheque-number') || '';
            const amount = btn.getAttribute('data-cheque-amount') || '';
            const actions = btn.getAttribute('data-actions') || 'pending';
            const accountId = btn.getAttribute('data-account-id') || '';

            document.getElementById('chequeStatusHint').textContent =
                'Cheque ' + number + ' · ' + currency + ' ' + amount;

            document.getElementById('chequeClearForm').action = clearBase + '/' + id + '/clear';
            document.getElementById('chequeReturnForm').action = clearBase + '/' + id + '/return';

            const clearBlock = document.getElementById('chequeClearBlock');
            const returnBlock = document.getElementById('chequeReturnBlock');
            clearBlock.classList.toggle('d-none', actions !== 'pending');
            returnBlock.classList.toggle('d-none', !(actions === 'pending' || actions === 'cleared'));

            const accountSel = document.getElementById('chequeStatusAccount');
            if (accountSel) accountSel.value = accountId || '';
        });

        statusModal.addEventListener('shown.bs.modal', function () {
            if (typeof $ === 'undefined') return;
            const $modal = $('#chequeStatusModal');
            const $sel = $modal.find('.chq-status-select2');
            if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
            $sel.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $modal,
                placeholder: 'Search bank account…',
                allowClear: true,
            });
        });

        document.getElementById('chequeClearForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Clear this cheque?',
                text: 'Amount will be posted out of the bank account.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it',
                confirmButtonColor: '#059669',
                reverseButtons: true,
            }).then(r => { if (r.isConfirmed) form.submit(); });
        });

        document.getElementById('chequeReturnForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            Swal.fire({
                title: 'Return this cheque?',
                text: 'If cleared, ledger and purchase paid amount will roll back.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, mark returned',
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
            }).then(r => { if (r.isConfirmed) form.submit(); });
        });
    }

    const modalEl = document.getElementById('addChequeModal');
    if (!modalEl || typeof $ === 'undefined') return;

    function initChequeSelect2() {
        const $modal = $('#addChequeModal');
        $modal.find('.chq-select2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $modal,
                placeholder: $el.data('placeholder') || 'Search…',
                allowClear: true,
            });
        });
    }

    function filterPurchasesBySupplier() {
        const supplierId = $('#chq_supplier_id').val();
        const $purchase = $('#chq_purchase_id');
        const current = $purchase.val();
        $purchase.find('option').each(function () {
            const opt = $(this);
            if (!opt.val()) return;
            const match = !supplierId || String(opt.data('supplier')) === String(supplierId);
            opt.prop('disabled', !match);
            if (!match && opt.val() === current) {
                $purchase.val('').trigger('change');
            }
        });
        $purchase.select2('destroy');
        $purchase.select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#addChequeModal'),
            placeholder: 'Search purchase…',
            allowClear: true,
        });
    }

    $('#chq_supplier_id').on('change', filterPurchasesBySupplier);
    $('#chq_purchase_id').on('select2:select', function (e) {
        const bal = $(e.params.data.element).data('balance');
        const amount = document.getElementById('chq_amount');
        if (amount && bal && !amount.value) {
            amount.value = parseFloat(bal).toFixed(2);
        }
        const supplierId = $(e.params.data.element).data('supplier');
        if (supplierId) {
            $('#chq_supplier_id').val(String(supplierId)).trigger('change');
        }
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        initChequeSelect2();
        filterPurchasesBySupplier();
    });

    @if(!empty($openCreateModal))
    document.addEventListener('DOMContentLoaded', function () {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    });
    @endif
})();
</script>
@endpush
