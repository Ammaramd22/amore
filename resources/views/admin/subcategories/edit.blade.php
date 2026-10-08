@extends('layouts.admin')
@section('title', 'Edit Subcategory')
@section('page_title', 'Edit Subcategory')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Subcategory</h3>
        <a href="{{ route('subcategories.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('subcategories.update', $subcategory) }}" class="category-form">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $subcategory->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $subcategory->name) }}" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $subcategory->slug) }}">
                @error('slug')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $subcategory->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" class="form-control" value="{{ old('display_order', $subcategory->display_order) }}">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="ia" @checked(old('is_active', $subcategory->is_active))>
                <label class="form-check-label" for="ia">Active</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                <a href="{{ route('subcategories.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    .category-form .form-control,
    .category-form .form-select {
        border: 2px solid #000;
    }
</style>
@endsection
