@extends('layouts.admin')
@section('title', 'Category Details')
@section('page_title', 'Category Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $category->name }}</h3>
        <div>
            <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted d-block">Name</small>
                <span class="fw-semibold">{{ $category->name }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Slug</small>
                <span class="fw-semibold">{{ $category->slug ?? '-' }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Color</small>
                <span class="badge" style="background:{{ $category->color }}">{{ $category->color }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Display Order</small>
                <span class="fw-semibold">{{ $category->display_order }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Kitchen</small>
                <span class="fw-semibold">{{ $category->kitchen?->name ?? '-' }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted d-block">Products</small>
                <span class="fw-semibold">{{ $category->products()->count() }}</span>
            </div>
            <div class="col-12">
                <small class="text-muted d-block">Description</small>
                <span>{{ $category->description ?? '-' }}</span>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                <span class="badge bg-{{ $category->show_in_pos ? 'info' : 'secondary' }}">{{ $category->show_in_pos ? 'In POS' : 'Hidden from POS' }}</span>
                <span class="badge bg-{{ $category->show_in_qr ? 'info' : 'secondary' }}">{{ $category->show_in_qr ? 'In QR Menu' : 'Hidden from QR' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
