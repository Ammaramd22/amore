@extends('layouts.admin')
@section('title', 'Ingredients')
@section('page_title', 'Ingredients')
@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">All Ingredients</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importIngredientsModal">
                <i class="fas fa-file-csv me-1"></i>Import CSV
            </button>
            <a href="{{ route('ingredients.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-4">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Name / code" value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="filter_low_stock" {{ request('low_stock') ? 'checked' : '' }}>
                    <label class="form-check-label" for="filter_low_stock">Show low stock only</label>
                </div>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','low_stock']))
                <a href="{{ route('ingredients.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $ingredients->total() }}</div>
                    <div class="stat-label">Ingredients</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-flask"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $ingredients->filter(fn ($i) => $i->isLowStock())->count() }}</div>
                    <div class="stat-label">Low Stock</div>
                </div>
                <div class="stat-icon bg-warning text-white"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-striped table-black-borders mb-0" data-resource="ingredients" data-export="ingredients" data-can-delete="1">
                <thead><tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th><th scope="col">Name</th><th scope="col">Code</th><th scope="col">Stock</th><th scope="col">Unit</th><th scope="col">Cost</th><th scope="col">Reorder</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse($ingredients as $ing)
                    <tr class="{{ $ing->isLowStock() ? 'table-warning' : '' }}">
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $ing->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($ingredients->currentPage() - 1) * $ingredients->perPage() + $loop->iteration) }}</td>
                        <td>{{ $ing->name }}</td>
                        <td>{{ $ing->code }}</td>
                        <td class="text-end">{{ number_format($ing->stock_quantity,3) }}</td>
                        <td>{{ $ing->unit }}</td>
                        <td class="text-end">LKR {{ number_format($ing->cost_per_unit,4) }}</td>
                        <td class="text-end">{{ number_format($ing->reorder_level,3) }}</td>
                        <td><span class="badge bg-{{ $ing->is_active?'success':'secondary' }}">{{ $ing->is_active?'Active':'Inactive' }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('ingredients.edit', $ing) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('ingredients.destroy', $ing) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No ingredients found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $ingredients->links() }}</div>
</div>

@if(session('import_errors'))
<div class="alert alert-warning mt-3">
    <strong>Some rows were skipped:</strong>
    <ul class="mb-0 mt-2">
        @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="modal fade" id="importIngredientsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Import Ingredients CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('ingredients.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Required: <code>name</code>, <code>unit</code>.
                        Optional <code>code</code> — if blank, a code is auto-generated.
                        Matching <code>code</code> updates an existing ingredient.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="ingredients_csv_file">CSV file</label>
                        <input type="file" name="csv_file" id="ingredients_csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <a href="{{ route('ingredients.import.template') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-download me-1"></i>Download template
                    </a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
