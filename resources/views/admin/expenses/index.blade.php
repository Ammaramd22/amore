@extends('layouts.admin')
@section('title', 'Expenses')
@section('page_title', 'Expenses')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Expenses</h3>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="expenses" data-export="expenses" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Account</th>
                        <th>Date</th>
                        <th>By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $e->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($expenses->currentPage() - 1) * $expenses->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $e->title }}</td>
                        <td><span class="badge-soft muted">{{ $e->category }}</span></td>
                        <td>LKR {{ number_format($e->amount, 2) }}</td>
                        <td>{{ $e->account?->name ?? '—' }}</td>
                        <td>{{ $e->expense_date?->format('Y-m-d') }}</td>
                        <td>{{ $e->creator?->name ?? '—' }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('expenses.edit', $e) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('expenses.destroy', $e) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9"><div class="empty-state"><i class="fas fa-receipt"></i>No expenses yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $expenses->links() }}</div>
</div>
@endsection
