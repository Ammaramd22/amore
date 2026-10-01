@extends('layouts.admin')
@section('title', 'Modifier Details')
@section('page_title', 'Modifier Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $addon->name }}</h3>
        <div class="d-flex gap-2">
            @can('addons.edit')
            <a href="{{ route('addons.edit', $addon) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
            @endcan
            <a href="{{ route('addons.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $addon->name }}</dd>
            <dt class="col-sm-3">Additional price</dt>
            <dd class="col-sm-9">{{ number_format((float) $addon->price, 2) }}</dd>
            <dt class="col-sm-3">Display Order</dt>
            <dd class="col-sm-9">{{ $addon->display_order }}</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                @if($addon->is_active)
                <span class="badge bg-success">Active</span>
                @else
                <span class="badge bg-secondary">Inactive</span>
                @endif
            </dd>
            <dt class="col-sm-3">Products</dt>
            <dd class="col-sm-9">
                @forelse($addon->products as $product)
                <a href="{{ route('products.show', $product) }}" class="badge bg-light text-dark border me-1 mb-1">{{ $product->name }}</a>
                @empty
                <span class="text-muted">Not assigned directly to any products.</span>
                @endforelse
            </dd>
        </dl>
    </div>
</div>
@endsection
