@extends('layouts.admin')
@section('title', 'Recipes')
@section('page_title', 'Recipes')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Recipes</h3>
        <a href="{{ route('recipes.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="recipes" data-export="recipes" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Product</th>
                        <th>Total Cost</th>
                        <th>Wastage %</th>
                        <th>Yield</th>
                        <th>Profit Margin</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipes as $recipe)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $recipe->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($recipes->currentPage() - 1) * $recipes->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $recipe->product?->name }}</td>
                        <td>LKR {{ number_format($recipe->total_cost, 4) }}</td>
                        <td>{{ $recipe->wastage_percentage }}%</td>
                        <td>{{ $recipe->yield_quantity }}</td>
                        <td>{{ number_format($recipe->profit_margin, 2) }}%</td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('recipes.show', $recipe) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('recipes.edit', $recipe) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('recipes.destroy', $recipe) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8"><div class="empty-state"><i class="fas fa-book-open"></i>No recipes yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $recipes->links() }}</div>
</div>
@endsection
