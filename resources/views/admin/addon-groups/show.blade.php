@extends('layouts.admin')
@section('title', 'Modifier Details')
@section('page_title', 'Modifier Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $group->displayLabel() }}</h3>
        <div class="d-flex gap-2">
            @can('addons.edit')
            <a href="{{ route('addon-groups.edit', $group) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
            @endcan
            <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row mb-4">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $group->name }}</dd>
            <dt class="col-sm-3">Display name</dt>
            <dd class="col-sm-9">{{ $group->display_name ?: '—' }}</dd>
            <dt class="col-sm-3">Type</dt>
            <dd class="col-sm-9">{{ ucfirst($group->selection_type ?: 'list') }} modifier</dd>
            <dt class="col-sm-3">Rules</dt>
            <dd class="col-sm-9">
                {{ $group->require_selection ? 'Selection required' : 'Optional' }}
                · {{ $group->allow_multiple ? 'Multiple allowed' : 'Single only' }}
                @if($group->hide_on_receipt)· Hidden on receipt @endif
            </dd>
            <dt class="col-sm-3">POS channel</dt>
            <dd class="col-sm-9">{{ $group->show_in_pos ? 'Enabled' : 'Disabled' }}</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                @if($group->is_active)
                <span class="badge bg-success">Active</span>
                @else
                <span class="badge bg-secondary">Inactive</span>
                @endif
            </dd>
        </dl>

        <h5 class="mb-2">Modifiers</h5>
        <div class="table-responsive mb-4">
            <table class="table table-sm align-middle">
                <thead><tr><th>Name</th><th>Price</th><th>Pre-select</th><th>Available</th></tr></thead>
                <tbody>
                    @forelse($group->addons as $addon)
                    <tr>
                        <td>{{ $addon->name }}</td>
                        <td>+{{ number_format((float) $addon->price, 2) }}</td>
                        <td>{{ !empty($addon->pivot->is_preselected) ? 'Yes' : '—' }}</td>
                        <td>
                            @if($addon->is_active && ($addon->pivot->is_available ?? true))
                            <span class="badge bg-success">Available</span>
                            @else
                            <span class="badge bg-secondary">Unavailable</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-muted">No modifiers in this set.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h5 class="mb-2">Assigned products</h5>
        <div class="mb-3">
            @forelse($group->products as $product)
            <a href="{{ route('products.show', $product) }}" class="badge bg-light text-dark border me-1 mb-1">{{ $product->name }}</a>
            @empty
            <span class="text-muted">Not assigned to any products.</span>
            @endforelse
        </div>

        @if($group->relationLoaded('branches') && $group->branches->isNotEmpty())
        <h5 class="mb-2">Branches</h5>
        <div>
            @foreach($group->branches as $branch)
            <span class="badge bg-light text-dark border me-1">{{ $branch->name }}</span>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
