@extends('layouts.admin')
@section('title', 'Option Sets')
@section('page_title', 'Option Sets')
@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Option Sets</h3>
        @can('option-sets.create')
        <a href="{{ route('option-sets.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Create Option Set</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Milk Type, Sugar…" value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filter_status">Status</label>
                <select id="filter_status" name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(request('status')==='active')>Active</option>
                    <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','status']))
                <a href="{{ route('option-sets.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
                @endif
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">Option sets are preference choices (Milk, Sugar, Ice, Spice) — separate from Variations (price/size) and Modifiers (priced extras).</p>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Display</th>
                    <th>Type</th>
                    <th>Options</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sets as $set)
                <tr>
                    <td class="fw-semibold">{{ $set->name }}</td>
                    <td>{{ $set->display_name ?: '—' }}</td>
                    <td>{{ $set->type === 'text_color' ? 'Text and color' : 'Text' }}</td>
                    <td>{{ $set->options_count }}</td>
                    <td>{{ $set->products_count }}</td>
                    <td>
                        @if($set->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('option-sets.edit')
                        <a href="{{ route('option-sets.edit', $set) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        @endcan
                        @can('option-sets.delete')
                        <form action="{{ route('option-sets.destroy', $set) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this option set?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No option sets yet. Create Milk Type, Sugar Level, Ice Level, etc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sets->hasPages())
    <div class="card-footer">{{ $sets->links() }}</div>
    @endif
</div>
@endsection
