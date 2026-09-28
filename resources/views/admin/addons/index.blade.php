@extends('layouts.admin')
@section('title', 'Add-ons')
@section('page_title', 'Add-ons')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">All Add-ons</h3>
        @can('addons.create')
        <a href="{{ route('addons.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Add-on</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Add-on name" value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filter_status">Status</label>
                <select id="filter_status" name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('addons.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
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
                    <div class="stat-value">{{ $addons->total() }}</div>
                    <div class="stat-label">Add-ons</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-plus-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $addons->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Active (this page)</div>
                </div>
                <div class="stat-icon bg-success text-white"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $addons->sum('products_count') }}</div>
                    <div class="stat-label">Product links (this page)</div>
                </div>
                <div class="stat-icon bg-info text-white"><i class="fas fa-link"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Products</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($addons as $addon)
                <tr>
                    <td class="fw-semibold">{{ $addon->name }}</td>
                    <td>{{ number_format((float) $addon->price, 2) }}</td>
                    <td>{{ $addon->products_count }}</td>
                    <td>{{ $addon->display_order }}</td>
                    <td>
                        @if($addon->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('addons.show', $addon) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                        @can('addons.edit')
                        <a href="{{ route('addons.edit', $addon) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        @endcan
                        @can('addons.delete')
                        <form action="{{ route('addons.destroy', $addon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this add-on?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No add-ons yet. Create one and assign it to products.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($addons->hasPages())
    <div class="card-footer">{{ $addons->links() }}</div>
    @endif
</div>
@endsection
