@extends('layouts.admin')
@section('title', 'Inventory')
@section('page_title', 'Inventory')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Stock Overview</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.movements') }}" class="btn btn-secondary btn-sm"><i class="fas fa-exchange-alt me-1"></i>Movements</a>
            <a href="{{ route('inventory.low-stock') }}" class="btn btn-primary btn-sm"><i class="fas fa-exclamation-triangle me-1"></i>Low Stock</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-export="inventory" data-can-delete="0">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Ingredient</th>
                        <th>Code</th>
                        <th>Stock</th>
                        <th>Unit</th>
                        <th>Cost</th>
                        <th>Reorder</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ingredients as $ing)
                    <tr class="{{ $ing->isLowStock() ? 'table-warning' : '' }}">
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $ing->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($ingredients->currentPage() - 1) * $ingredients->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $ing->name }}</td>
                        <td>{{ $ing->code }}</td>
                        <td>{{ number_format($ing->stock_quantity, 3) }}</td>
                        <td>{{ $ing->unit }}</td>
                        <td>{{ number_format($ing->cost_per_unit, 4) }}</td>
                        <td>{{ number_format($ing->reorder_level, 3) }}</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><button type="button" class="dropdown-item" onclick="adjustStock({{ $ing->id }}, '{{ addslashes($ing->name) }}')"><i class="fas fa-sliders-h"></i> Adjust</button></li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9"><div class="empty-state"><i class="fas fa-warehouse"></i>No ingredients in stock</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $ingredients->links() }}</div>
</div>

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('inventory.adjust') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="ingredient_id" id="adjIngredientId">
                    <div class="mb-3">
                        <label class="form-label">Ingredient</label>
                        <input type="text" id="adjIngredientName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (+/-)</label>
                        <input type="number" step="0.001" name="quantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adjustStock(id, name) {
    document.getElementById('adjIngredientId').value = id;
    document.getElementById('adjIngredientName').value = name;
    new bootstrap.Modal(document.getElementById('adjustModal')).show();
}
</script>
@endsection
