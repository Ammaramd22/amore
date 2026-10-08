@extends('layouts.admin')
@section('title', 'Add Expense')
@section('page_title', 'Add Expense')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Expense</h3>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('expenses.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Paid from account</label>
                    <select name="account_id" class="form-select">
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" @selected($account->type === 'cash')>{{ $account->name }} ({{ $account->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
