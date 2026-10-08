@extends('layouts.admin')
@section('title', 'Modifiers')
@section('page_title', 'Modifiers')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Modifier sets</h3>
        @can('addons.create')
        <a href="{{ route('addon-groups.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Create Modifier</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Name" value="{{ request('q') }}">
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
                <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
                @endif
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">Create a modifier set with its options, prices, and selection rules — then apply it to products.</p>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Display</th>
                    <th>Modifiers</th>
                    <th>Products</th>
                    <th>Rules</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                <tr>
                    <td class="fw-semibold">{{ $group->name }}</td>
                    <td>{{ $group->display_name ?: '—' }}</td>
                    <td>{{ $group->addons_count }}</td>
                    <td>{{ $group->products_count }}</td>
                    <td class="small">
                        @if($group->require_selection)<span class="badge bg-warning text-dark">Required</span>@endif
                        @if($group->allow_multiple)<span class="badge bg-light text-dark border">Multi</span>@else<span class="badge bg-light text-dark border">Single</span>@endif
                    </td>
                    <td>
                        @if($group->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('addon-groups.show', $group) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-eye"></i></a>
                        @can('addons.edit')
                        <a href="{{ route('addon-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                        @endcan
                        @can('addons.delete')
                        <form action="{{ route('addon-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this modifier?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No modifiers yet. Click Create Modifier to add a set with options.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($groups->hasPages())
    <div class="card-footer">{{ $groups->links() }}</div>
    @endif
</div>
@endsection
