@extends('layouts.admin')
@section('title', 'Categories')
@section('page_title', 'Categories')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">All Categories</h3>
        <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Category</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end index-filter-form">
            <div class="col-md-3">
                <label class="form-label" for="filter_q">Search</label>
                <input type="text" id="filter_q" name="q" class="form-control form-control-sm" placeholder="Category name" value="{{ request('q') }}">
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
            <div class="col-md-3">
                <label class="form-label" for="filter_kitchen">Kitchen / Bar</label>
                <select id="filter_kitchen" name="kitchen" class="form-select form-select-sm">
                    <option value="">All Kitchens</option>
                    @foreach($kitchens as $k)
                    <option value="{{ $k->id }}" {{ request('kitchen') == $k->id ? 'selected' : '' }}>{{ $k->name }} ({{ strtoupper($k->type) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                @if(request()->hasAny(['q','type','kitchen']))
                <a href="{{ route('categories.index') }}" class="btn btn-secondary btn-sm flex-fill"><i class="fas fa-undo"></i></a>
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
                    <div class="stat-value">{{ $categories->total() }}</div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-icon bg-primary text-white"><i class="fas fa-tags"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $categories->sum('products_count') }}</div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-icon bg-success text-white"><i class="fas fa-box"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value">{{ $categories->where('is_active', true)->count() }}</div>
                    <div class="stat-label">Active Categories</div>
                </div>
                <div class="stat-icon bg-info text-white"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-striped table-black-borders mb-0" data-resource="categories" data-export="categories" data-can-delete="1">
                <thead><tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Kitchen/Bar</th><th scope="col">Products</th><th scope="col">Status</th><th scope="col" class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $category->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($category->hasImageFile())
                                    <img src="{{ $category->imageUrl() }}" alt="" class="cat-thumb" onerror="this.style.display='none'">
                                @else
                                    <span class="cat-thumb cat-thumb-empty" style="background:{{ $category->color ?? '#e2e8f0' }}"></span>
                                @endif
                                <span class="badge" style="background:{{ $category->color }}">{{ $category->name }}</span>
                            </div>
                        </td>
                        <td><span class="badge bg-{{ $category->type === 'kot' ? 'warning text-dark' : ($category->type === 'bot' ? 'info' : 'success') }}">{{ strtoupper($category->type) }}</span></td>
                        <td>{{ $category->kitchen?->name ?? '-' }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td><span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('categories.show', $category) }}"><i class="fas fa-eye"></i> View</a></li>
                                <li><a class="dropdown-item" href="{{ route('categories.edit', $category) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $categories->links() }}</div>
</div>
<style>
.cat-thumb { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; flex-shrink: 0; border: 1px solid #e2e8f0; }
.cat-thumb-empty { display: inline-block; }
</style>
@endsection
