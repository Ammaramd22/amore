@extends('layouts.admin')
@section('title', 'Edit Recipe')
@section('page_title', 'Edit Recipe')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Recipe</h3>
        <a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('recipes.update', $recipe) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Wastage %</label><input type="number" step="0.01" name="wastage_percentage" class="form-control" value="{{ $recipe->wastage_percentage }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Yield Qty</label><input type="number" name="yield_quantity" class="form-control" value="{{ $recipe->yield_quantity }}"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Instructions</label><textarea name="instructions" class="form-control" rows="3">{{ $recipe->instructions }}</textarea></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
