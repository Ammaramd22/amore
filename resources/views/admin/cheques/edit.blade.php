@extends('layouts.admin')
@section('title', 'Edit Cheque')
@section('page_title', 'Edit Cheque '.$cheque->cheque_number)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <form method="POST" action="{{ route('cheques.update', $cheque) }}">
                @csrf @method('PUT')
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Cheque number</label>
                        <input type="text" name="cheque_number" class="form-control" value="{{ old('cheque_number', $cheque->cheque_number) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clear to account</label>
                        <select name="account_id" class="form-select">
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
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('cheques.show', $cheque) }}" class="btn btn-secondary">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
