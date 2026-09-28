@extends('layouts.admin')
@section('title', 'Subcategory Details')
@section('page_title', 'Subcategory Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $subcategory->name }}</h3>
        <div>
            <a href="{{ route('subcategories.edit', $subcategory) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
            <a href="{{ route('subcategories.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block">Name</small>
                <span class="fw-semibold">{{ $subcategory->name }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Slug</small>
                <span class="fw-semibold">{{ $subcategory->slug ?? '-' }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Category</small>
                <span class="fw-semibold">{{ $subcategory->category?->name ?? '-' }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Display Order</small>
                <span class="fw-semibold">{{ $subcategory->display_order }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Products</small>
                <span class="fw-semibold">{{ $subcategory->products_count }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Status</small>
                <span class="badge bg-{{ $subcategory->is_active ? 'success' : 'secondary' }}">{{ $subcategory->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="col-12">
                <small class="text-muted d-block">Description</small>
                <span>{{ $subcategory->description ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
