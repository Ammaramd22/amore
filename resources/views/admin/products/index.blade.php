@extends('layouts.admin')
@section('title', 'Products')
@section('page_title', 'Products')
@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif
@if(session('import_errors'))
<div class="alert alert-warning">
    <strong>Import row details:</strong>
    <ul class="mb-0 mt-2">
        @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">All Products</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importProductsModal">
                <i class="fas fa-file-csv me-1"></i>Import CSV
            </button>
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Product</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-3">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Name / code" value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filter_category">Category</label>
                <select id="filter_category" name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filter_type">Type</label>
                <select id="filter_type" name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="kot" {{ request('type') == 'kot' ? 'selected' : '' }}>KOT</option>
                    <option value="direct" {{ request('type') == 'direct' ? 'selected' : '' }}>Direct</option>
                    <option value="bot" {{ request('type') == 'bot' ? 'selected' : '' }}>BOT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="filter_status">Status</label>
                <select id="filter_status" name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','category','type','status']))
                <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
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
                    <div class="stat-value">{{ $products->total() }}</div>
                    <div class="stat-label">Products</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-box"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">LKR {{ number_format($totalValue, 2) }}</div>
                    <div class="stat-label">Stock Value</div>
                </div>
                <div class="stat-icon bg-success text-white"><i class="fas fa-coins"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $products->where('is_available', true)->count() }}</div>
                    <div class="stat-label">Active Products</div>
                </div>
                <div class="stat-icon bg-info text-white"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-striped table-black-borders mb-0" data-resource="products" data-export="products" data-can-delete="1">
                <thead><tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th><th scope="col">Image</th><th scope="col">Name</th><th scope="col">Code</th><th scope="col">Category</th><th scope="col">Type</th><th scope="col">Price</th><th scope="col">Stock</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $product->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($products->currentPage() - 1) * $products->perPage() + $loop->iteration) }}</td>
                        <td>
                            <img src="{{ $product->imageUrl() }}"
                                 alt="{{ $product->name }}"
                                 style="width:40px;height:40px;object-fit:cover;border-radius:8px;background:#f5f5f4;"
                                 onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->category?->name }}</td>
                        <td><span class="badge bg-{{ ($product->category?->type ?? 'kot') === 'kot' ? 'warning text-dark' : (($product->category?->type ?? 'kot') === 'bot' ? 'info' : 'success') }}">{{ strtoupper($product->category?->type ?? 'KOT') }}</span></td>
                        <td class="text-end">LKR {{ number_format($product->selling_price, 2) }}</td>
                        <td class="text-end">{{ $product->track_stock ? number_format($product->stock_quantity, 3) : '-' }}</td>
                        <td><span class="badge bg-{{ $product->is_available ? 'success' : 'secondary' }}">{{ $product->is_available ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('products.show', $product) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('products.edit', $product) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $products->links() }}</div>
</div>

<div class="modal fade" id="importProductsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Import Products CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Required: <code>name</code>, <code>selling_price</code>, <code>category_name</code>.
                        <code>category_name</code> must match an <strong>existing</strong> category (case-insensitive). Missing categories will fail that row with a clear error.
                        Optional <code>subcategory_name</code> must also already exist under that category when provided.
                        Matching <code>code</code> updates an existing product; blank code auto-generates.
                        Boolean columns use <code>0</code>/<code>1</code>.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="products_csv_file">CSV file</label>
                        <input type="file" name="csv_file" id="products_csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <a href="{{ route('products.import.template') }}" class="btn btn-outline-secondary btn-sm">
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
