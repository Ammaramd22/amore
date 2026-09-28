@extends('layouts.admin')
@section('title', 'Edit Ingredient')
@section('page_title', 'Edit Ingredient')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Ingredient</h3>
        <a href="{{ route('ingredients.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('ingredients.update', $ingredient) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $ingredient->name }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="{{ $ingredient->code }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Unit</label>
                    <select name="unit" class="form-select"><option value="kg" {{ $ingredient->unit=='kg'?'selected':'' }}>kg</option><option value="g" {{ $ingredient->unit=='g'?'selected':'' }}>g</option><option value="litre" {{ $ingredient->unit=='litre'?'selected':'' }}>litre</option><option value="ml" {{ $ingredient->unit=='ml'?'selected':'' }}>ml</option><option value="pcs" {{ $ingredient->unit=='pcs'?'selected':'' }}>pcs</option><option value="pack" {{ $ingredient->unit=='pack'?'selected':'' }}>pack</option><option value="bottle" {{ $ingredient->unit=='bottle'?'selected':'' }}>bottle</option></select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Stock Qty</label><input type="number" step="0.001" name="stock_quantity" class="form-control" value="{{ $ingredient->stock_quantity }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reorder Level</label><input type="number" step="0.001" name="reorder_level" class="form-control" value="{{ $ingredient->reorder_level }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Cost Per Unit</label><input type="number" step="0.0001" name="cost_per_unit" class="form-control" value="{{ $ingredient->cost_per_unit }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Storage Location</label><input type="text" name="storage_location" class="form-control" value="{{ $ingredient->storage_location }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control" value="{{ $ingredient->expiry_date?->format('Y-m-d') }}"></div>
                <div class="col-md-12 mb-3"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $ingredient->is_active ? 'checked' : '' }} id="ia"><label class="form-check-label" for="ia">Active</label></div></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
