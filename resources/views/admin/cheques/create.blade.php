@extends('layouts.admin')
@section('title', 'Add Cheque')
@section('page_title', 'Add Cheque')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">New supplier cheque</h3></div>
            <form method="POST" action="{{ route('cheques.store') }}">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link purchase (optional)</label>
                            <select name="purchase_id" id="purchase_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($purchases as $p)
                                <option value="{{ $p->id }}" data-supplier="{{ $p->supplier_id }}" @selected(old('purchase_id')==$p->id)>
                                    {{ $p->displayNumber() }} · {{ $p->supplier?->name }} · bal {{ number_format(max(0,$p->total_amount-$p->paid_amount),2) }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="apply_to_purchase" value="1" id="apply_to_purchase" checked>
                                <label class="form-check-label" for="apply_to_purchase">Apply amount to purchase balance</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cheque number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank account (clear to)</label>
                            <select name="account_id" class="form-select">
                                <option value="">Default bank</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}" @selected(old('account_id')==$a->id)>{{ $a->name }} ({{ $a->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cheque date</label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ old('cheque_date', today()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due / deposit date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', today()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('cheques.index') }}" class="btn btn-secondary">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save as Pending</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
