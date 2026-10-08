@extends('layouts.admin')
@section('title', 'Customers')
@section('page_title', 'Customers')
@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">All Customers</h3>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fas fa-plus me-1"></i>Add
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="customers" data-export="customers" data-can-delete="1">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th>
                        <th class="col-num">#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Points</th>
                        <th>Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $c->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $c->name }}</td>
                        <td>{{ $c->phone ?? '—' }}</td>
                        <td>{{ $c->loyalty_points }}</td>
                        <td>{{ $currency }} {{ number_format($c->outstanding_balance, 2) }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('customers.show', $c) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('customers.edit', $c) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('customers.destroy', $c) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7"><div class="empty-state"><i class="fas fa-user-friends"></i>No customers yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $customers->links() }}</div>
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title" id="addCustomerModalLabel"><i class="fas fa-user-plus me-2"></i>Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="customer_name" class="form-control" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_phone">Phone</label>
                            <input type="text" name="phone" id="customer_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_email">Email</label>
                            <input type="email" name="email" id="customer_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_credit_limit">Credit limit</label>
                            <input type="number" step="0.01" name="credit_limit" id="customer_credit_limit" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_dob">Date of birth</label>
                            <input type="date" name="date_of_birth" id="customer_dob" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_anniversary">Anniversary</label>
                            <input type="date" name="anniversary_date" id="customer_anniversary" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="customer_address">Address</label>
                            <textarea name="address" id="customer_address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="customer_notes">Notes</label>
                            <textarea name="notes" id="customer_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
