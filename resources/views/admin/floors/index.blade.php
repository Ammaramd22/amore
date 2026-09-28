@extends('layouts.admin')
@section('title', 'Floors & Tables')
@section('page_title', 'Floors & Tables')
@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Floors</h3>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFloorModal"><i class="fas fa-plus me-1"></i>Add</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="bulk-table table mb-0" data-resource="floors" data-export="floors" data-can-delete="1">
                        <thead><tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th><th>Name</th><th>Tables</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($floors as $floor)
                            <tr>
                                <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $floor->id }}" aria-label="Select row"></td>
                                <td class="col-num">{{ (($floors->currentPage() - 1) * $floors->perPage() + $loop->iteration) }}</td>
                                <td class="fw-semibold">{{ $floor->name }}</td>
                                <td>{{ $floor->tables_count }}</td>
                                <td>
                                    <span class="badge-soft {{ $floor->is_active ? 'success' : 'muted' }}">
                                        {{ $floor->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <x-row-actions>
                                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editFloor{{ $floor->id }}"><i class="fas fa-edit"></i> Edit</button></li>
                                        <li>
                                            <form action="{{ route('floors.destroy', $floor) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </li>
                                    </x-row-actions>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6"><div class="empty-state py-4"><i class="fas fa-layer-group"></i>No floors yet</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($floors->hasPages())
            <div class="card-footer">{{ $floors->links() }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tables overview</h3>
                <a href="{{ route('tables.index') }}" class="btn btn-primary btn-sm">Manage tables</a>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @forelse(App\Models\RestaurantTable::with('floor')->active()->get() as $t)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card card-table table-{{ $t->status }} p-3 text-center mb-0">
                            <h6 class="mb-1 fw-bold">{{ $t->name }}</h6>
                            <small class="text-muted d-block mb-2">{{ $t->floor?->name }}</small>
                            <span class="badge bg-{{ $t->status=='available'?'success':($t->status=='occupied'?'danger':'warning') }}">{{ ucfirst($t->status) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="col-12"><div class="empty-state"><i class="fas fa-chair"></i>No tables yet</div></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addFloorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('floors.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($floors as $floor)
<div class="modal fade" id="editFloor{{ $floor->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Edit Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('floors.update', $floor) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $floor->name }}" required></div>
                    <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="{{ $floor->display_order ?? 0 }}"></div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="floorActive{{ $floor->id }}" {{ $floor->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="floorActive{{ $floor->id }}">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
