@extends('layouts.admin')
@section('title', 'Add Ingredient')
@section('page_title', 'Add Ingredient')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Ingredient</h3>
        <a href="{{ route('ingredients.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('ingredients.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <option value="kg">kg</option><option value="g">g</option><option value="litre">litre</option>
                        <option value="ml">ml</option><option value="pcs">pcs</option><option value="pack">pack</option><option value="bottle">bottle</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Stock Qty</label><input type="number" step="0.001" name="stock_quantity" class="form-control" value="0"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reorder Level</label><input type="number" step="0.001" name="reorder_level" class="form-control" value="0"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Cost Per Unit</label><input type="number" step="0.0001" name="cost_per_unit" class="form-control" value="0"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Storage Location</label><input type="text" name="storage_location" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('ingredients.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
