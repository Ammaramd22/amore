@extends('layouts.admin')
@section('title', 'Low Stock Alert')
@section('page_title', 'Low Stock Alert')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ingredients Below Reorder Level</h3>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Ingredient</th><th>Code</th><th class="text-end">Current Stock</th><th class="text-end">Reorder Level</th><th>Unit</th></tr></thead>
                <tbody>
                    @forelse($ingredients as $ing)
                    <tr class="table-warning">
                        <td>{{ $ing->name }}</td>
                        <td>{{ $ing->code }}</td>
                        <td class="text-end">{{ number_format($ing->stock_quantity, 3) }}</td>
                        <td class="text-end">{{ number_format($ing->reorder_level, 3) }}</td>
                        <td>{{ $ing->unit }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">All stock levels are healthy</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
