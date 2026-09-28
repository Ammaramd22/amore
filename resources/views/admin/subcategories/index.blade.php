@extends('layouts.admin')
@section('title', 'Subcategories')
@section('page_title', 'Subcategories')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">All Subcategories</h3>
        @can('subcategories.create')
        <a href="{{ route('subcategories.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Subcategory</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Subcategory name" value="{{ request('q') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="filter_category_id">Category</label>
                <select id="filter_category_id" name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','category_id']))
                <a href="{{ route('subcategories.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $subcategories->total() }}</div>
                    <div class="stat-label">Subcategories</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-sitemap"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $subcategories->sum('products_count') }}</div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-icon bg-success text-white"><i class="fas fa-box"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $subcategories->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Active (this page)</div>
                </div>
                <div class="stat-icon bg-info text-white"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-striped table-black-borders mb-0" data-resource="subcategories" data-export="subcategories" data-can-delete="1">
                <thead>
                    <tr>
                        <th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th>
                        <th class="col-num">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Products</th>
                        <th scope="col">Order</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subcategories as $subcategory)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $subcategory->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($subcategories->currentPage() - 1) * $subcategories->perPage() + $loop->iteration) }}</td>
                        <td>
                            <div class="fw-semibold">{{ $subcategory->name }}</div>
                            <small class="text-muted">{{ $subcategory->slug }}</small>
                        </td>
                        <td>{{ $subcategory->category?->name ?? '-' }}</td>
                        <td>{{ $subcategory->products_count }}</td>
                        <td>{{ $subcategory->display_order }}</td>
                        <td><span class="badge bg-{{ $subcategory->is_active ? 'success' : 'secondary' }}">{{ $subcategory->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('subcategories.show', $subcategory) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('subcategories.edit', $subcategory) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('subcategories.destroy', $subcategory) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No subcategories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $subcategories->links() }}</div>
</div>
@endsection
