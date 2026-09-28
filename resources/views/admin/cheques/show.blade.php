@extends('layouts.admin')
@section('title', 'Cheque '.$cheque->cheque_number)
@section('page_title', 'Cheque details')

@section('content')
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

<div class="chq-show">
    <div class="purch-toolbar">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="purch-no">{{ $cheque->cheque_number }}</span>
                <span class="badge bg-{{ $cheque->statusBadgeClass() }}">{{ $cheque->statusLabel() }}</span>
                @if($cheque->isPending() && $cheque->due_date->isPast())
                    <span class="badge-soft danger">Overdue</span>
                @endif
            </div>
            <p class="purch-sub mb-0">
                {{ $cheque->supplier?->name ?? 'Supplier' }}
                · {{ $currency }} {{ number_format($cheque->amount, 2) }}
                · due {{ $cheque->due_date->format('d M Y') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cheques.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
            @if($cheque->status === 'pending')
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editChequeModal">
                <i class="fas fa-edit me-1"></i>Edit
            </button>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card chq-card">
                <div class="chq-card-head">
                    <h5 class="mb-0"><i class="fas fa-money-check-alt me-2" style="color:#f59e0b;"></i>Cheque details</h5>
                </div>
                <div class="card-body">
                    <div class="chq-detail-grid">
                        <div class="chq-detail">
                            <span class="chq-k">Cheque #</span>
                            <span class="chq-v"><span class="purch-no">{{ $cheque->cheque_number }}</span></span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Supplier</span>
                            <span class="chq-v">{{ $cheque->supplier?->name ?? '—' }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Purchase</span>
                            <span class="chq-v">
                                @if($cheque->purchase)
                                    <a href="{{ route('purchases.show', $cheque->purchase) }}" class="purch-no">{{ $cheque->purchase->displayNumber() }}</a>
                                @else — @endif
                            </span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Amount</span>
                            <span class="chq-v chq-amt">{{ $currency }} {{ number_format($cheque->amount, 2) }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Bank / Branch</span>
                            <span class="chq-v">{{ $cheque->bank_name ?: '—' }}{{ $cheque->branch_name ? ' · '.$cheque->branch_name : '' }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Cheque date</span>
                            <span class="chq-v">{{ $cheque->cheque_date?->format('d M Y') ?: '—' }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Due date</span>
                            <span class="chq-v">
                                {{ $cheque->due_date->format('d M Y') }}
                                @if($cheque->isPending() && $cheque->due_date->isPast())
                                    <span class="badge-soft danger ms-1">Overdue</span>
                                @endif
                            </span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Clear to account</span>
                            <span class="chq-v">{{ $cheque->account?->name ?: 'Default bank' }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Cleared</span>
                            <span class="chq-v">{{ $cheque->cleared_at?->format('d M Y H:i') ?: '—' }}</span>
                        </div>
                        <div class="chq-detail">
                            <span class="chq-k">Returned</span>
                            <span class="chq-v">
                                {{ $cheque->returned_at?->format('d M Y H:i') ?: '—' }}
                                @if($cheque->returned_reason)<div class="text-muted small mt-1">{{ $cheque->returned_reason }}</div>@endif
                            </span>
                        </div>
                        <div class="chq-detail chq-detail--full">
                            <span class="chq-k">Notes</span>
                            <span class="chq-v">{{ $cheque->notes ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($cheque->status === 'pending')
            <div class="card chq-card chq-action chq-action--clear mb-3">
                <div class="chq-card-head chq-card-head--success">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Clear cheque</h5>
                </div>
                <form method="POST" action="{{ route('cheques.clear', $cheque) }}">
                    @csrf
                    <div class="card-body">
                        <p class="chq-hint">Posts this amount out of the bank account and marks the cheque <strong>cleared</strong>.</p>
                        <label class="form-label" for="clear_account_id">Bank account</label>
                        <select name="account_id" id="clear_account_id" class="form-select chq-select2" data-placeholder="Search bank account…">
                            <option value="">{{ $cheque->account?->name ?: 'Default bank' }}</option>
                            @foreach($accounts as $a)
                            <option value="{{ $a->id }}" @selected($cheque->account_id==$a->id)>{{ $a->name }}{{ $a->code ? ' ('.$a->code.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-success w-100" type="submit" data-swal-confirm="clear">
                            <i class="fas fa-check me-1"></i>Mark Cleared
                        </button>
                    </div>
                </form>
            </div>
            @endif

            @if(in_array($cheque->status, ['pending','cleared'], true))
            <div class="card chq-card chq-action chq-action--return mb-3">
                <div class="chq-card-head chq-card-head--danger">
                    <h5 class="mb-0"><i class="fas fa-undo me-2"></i>Return cheque</h5>
                </div>
                <form method="POST" action="{{ route('cheques.return', $cheque) }}">
                    @csrf
                    <div class="card-body">
                        <p class="chq-hint">If already cleared, reverses the account posting and rolls back the purchase paid amount.</p>
                        <label class="form-label">Reason</label>
                        <input type="text" name="returned_reason" class="form-control" placeholder="Insufficient funds, stop payment…">
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-danger w-100" type="submit" data-swal-confirm="return">
                            <i class="fas fa-undo me-1"></i>Mark Returned
                        </button>
                    </div>
                </form>
            </div>
            @endif

            @if($cheque->status !== 'cleared')
            <form method="POST" action="{{ route('cheques.destroy', $cheque) }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger w-100" type="submit" data-swal-confirm="delete">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

@if($cheque->status === 'pending')
<div class="modal fade" id="editChequeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content chq-modal">
            <div class="modal-header respos">
                <h5 class="modal-title"><i class="fas fa-edit me-2" style="color:#f59e0b;"></i>Edit cheque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('cheques.update', $cheque) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cheque number</label>
                            <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number', $cheque->cheque_number) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Clear to account</label>
                            <select name="account_id" id="edit_account_id" class="form-select chq-select2" data-placeholder="Default bank">
                                <option value="">Default bank</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}" @selected(old('account_id', $cheque->account_id)==$a->id)>{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $cheque->bank_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name', $cheque->branch_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cheque date</label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ old('cheque_date', optional($cheque->cheque_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $cheque->due_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $cheque->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    .purch-toolbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .purch-sub { font-size: 0.85rem; color: #78716c; }
    .purch-no {
        font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: 0.02em;
        color: #1c1917; text-decoration: none; background: #fff7ed; border: 1px solid #fed7aa;
        padding: 0.2rem 0.55rem; border-radius: 8px; font-size: 0.85rem; display: inline-block;
    }
    .purch-no:hover { background: #ffedd5; color: #9a3412; }
    .chq-card { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; }
    .chq-card-head {
        background: linear-gradient(135deg, #1c1917, #292524); color: #fff7ed;
        border-bottom: 2px solid #f59e0b; padding: 14px 18px;
    }
    .chq-card-head h5 { font-size: 0.95rem; font-weight: 700; }
    .chq-card-head--success { background: linear-gradient(135deg, #065f46, #047857); border-bottom-color: #34d399; }
    .chq-card-head--danger { background: linear-gradient(135deg, #7f1d1d, #b91c1c); border-bottom-color: #f87171; }
    .chq-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
    .chq-detail--full { grid-column: 1 / -1; }
    .chq-k { display: block; font-size: 0.68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #78716c; margin-bottom: 4px; }
    .chq-v { font-weight: 600; color: #1c1917; }
    .chq-amt { font-size: 1.15rem; font-weight: 800; color: #c2410c; font-variant-numeric: tabular-nums; }
    .chq-hint { font-size: 0.82rem; color: #78716c; margin-bottom: 0.85rem; }
    .chq-modal .modal-content { border: none; border-radius: 16px; overflow: hidden; }
    #editChequeModal .select2-container,
    .chq-show .select2-container { width: 100% !important; }
    #editChequeModal .select2-container { z-index: 1065; }
    #editChequeModal .select2-dropdown { z-index: 1070 !important; }
    @media (max-width: 767px) {
        .chq-detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (typeof $ === 'undefined') return;

    function initSelect2In($root) {
        $root.find('.chq-select2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $root.is('.modal') || $root.hasClass('modal') ? $root : $(document.body),
                placeholder: $el.data('placeholder') || 'Search…',
                allowClear: true,
            });
        });
    }

    initSelect2In($('.chq-show'));

    const editModal = document.getElementById('editChequeModal');
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', function () {
            initSelect2In($('#editChequeModal'));
        });
        @if(session('open_edit'))
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(editModal).show();
        });
        @endif
    }

    const swalConfigs = {
        clear: {
            title: 'Clear this cheque?',
            text: 'Amount will be posted out of the bank account and marked cleared.',
            icon: 'question',
            confirmButtonText: 'Yes, clear it',
            confirmButtonColor: '#059669',
        },
        return: {
            title: 'Return this cheque?',
            text: 'If cleared, the account posting will reverse and purchase paid amount will roll back.',
            icon: 'warning',
            confirmButtonText: 'Yes, mark returned',
            confirmButtonColor: '#dc2626',
        },
        delete: {
            title: 'Delete this cheque?',
            text: 'This removes the cheque record. This cannot be undone.',
            icon: 'warning',
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc2626',
        },
    };

    document.querySelectorAll('[data-swal-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = btn.closest('form');
            if (!form || typeof Swal === 'undefined') {
                form?.submit();
                return;
            }
            const cfg = swalConfigs[btn.dataset.swalConfirm] || {
                title: 'Are you sure?',
                icon: 'question',
                confirmButtonText: 'Yes',
                confirmButtonColor: '#f59e0b',
            };
            Swal.fire({
                ...cfg,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            }).then(result => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
})();
</script>
@endpush
