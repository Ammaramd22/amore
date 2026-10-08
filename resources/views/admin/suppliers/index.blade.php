@extends('layouts.admin')
@section('title', 'Suppliers')
@section('page_title', 'Suppliers')
@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">All Suppliers</h3>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="fas fa-plus me-1"></i>Add
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Name / phone / city" value="{{ request('q') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->filled('q'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $suppliers->total() }}</div>
                    <div class="stat-label">Suppliers</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-truck"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $currency }} {{ number_format($suppliers->sum('balance'), 2) }}</div>
                    <div class="stat-label">Total Balance</div>
                </div>
                <div class="stat-icon bg-success text-white"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-striped table-black-borders mb-0" data-resource="suppliers" data-export="suppliers" data-can-delete="1">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th>
                        <th class="col-num">#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $s->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($suppliers->currentPage() - 1) * $suppliers->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $s->name }}</td>
                        <td>{{ $s->contact_person ?? '—' }}</td>
                        <td>{{ $s->phone ?? '—' }}</td>
                        <td>{{ $s->city ?? '—' }}</td>
                        <td class="text-end fw-bold">{{ $currency }} {{ number_format($s->balance, 2) }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('suppliers.show', $s) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('suppliers.edit', $s) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('suppliers.destroy', $s) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $suppliers->links() }}</div>
</div>

<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title" id="addSupplierModalLabel"><i class="fas fa-truck me-2"></i>Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="supplier_name" class="form-control" required autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_contact">Contact person</label>
                            <input type="text" name="contact_person" id="supplier_contact" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_phone">Phone</label>
                            <input type="text" name="phone" id="supplier_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_email">Email</label>
                            <input type="email" name="email" id="supplier_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_city">City</label>
                            <input type="text" name="city" id="supplier_city" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="supplier_tax">Tax number</label>
                            <input type="text" name="tax_number" id="supplier_tax" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="supplier_address">Address</label>
                            <textarea name="address" id="supplier_address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
