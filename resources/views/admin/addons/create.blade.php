@extends('layouts.admin')
@section('title', 'Add Add-on')
@section('page_title', 'Add Add-on')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">New Add-on</h3>
        <a href="{{ route('addons.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('addons.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', '0') }}" required>
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" min="0" name="display_order" class="form-control" value="{{ old('display_order', 0) }}">
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', '1') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="mb-3">
                <label class="form-label">Assign to products</label>
                <div class="border rounded p-3" style="max-height: 320px; overflow:auto;">
                    @forelse($products as $product)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="product_ids[]" value="{{ $product->id }}" id="prod_{{ $product->id }}"
                            {{ in_array($product->id, old('product_ids', [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="prod_{{ $product->id }}">{{ $product->name }}</label>
                    </div>
                    @empty
                    <div class="text-muted">No products available.</div>
                    @endforelse
                </div>
                <div class="form-text">One add-on can be linked to many products. Products can also have multiple add-ons.</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('addons.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
