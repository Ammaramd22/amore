@extends('layouts.admin')
@section('title', 'Create Recipe')
@section('page_title', 'Create Recipe')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Recipe</h3>
        <a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('recipes.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Product</label>
                    <select name="product_id" class="form-select select2" required>
                        <option value="">Select</option>
                        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Wastage %</label><input type="number" step="0.01" name="wastage_percentage" class="form-control" value="0"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Yield Qty</label><input type="number" name="yield_quantity" class="form-control" value="1"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Instructions</label><textarea name="instructions" class="form-control" rows="3"></textarea></div>
            </div>
            <h5 class="mb-3">Ingredients</h5>
            <table class="table table-sm" id="itemsTable">
                <thead><tr><th>Ingredient</th><th>Qty</th><th>Unit</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><select name="items[0][ingredient_id]" class="form-select form-select-sm" required>@foreach($ingredients as $ing)<option value="{{ $ing->id }}">{{ $ing->name }}</option>@endforeach</select></td>
                        <td><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm" required></td>
                        <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" required></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addItem()">+ Add Ingredient</button>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
let idx = 1;
function addItem() {
    const html = `<tr><td><select name="items[${idx}][ingredient_id]" class="form-select form-select-sm" required>@foreach($ingredients as $ing)<option value="{{ $ing->id }}">{{ $ing->name }}</option>@endforeach</select></td><td><input type="number" step="0.001" name="items[${idx}][quantity]" class="form-control form-control-sm" required></td><td><input type="text" name="items[${idx}][unit]" class="form-control form-control-sm" required></td><td><button type="button" class="btn btn-sm btn-link text-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td></tr>`;
    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
    idx++;
}
</script>
@endsection
