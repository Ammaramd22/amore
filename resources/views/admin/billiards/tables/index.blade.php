@extends('layouts.admin')
@section('title', 'Billiards Tables')
@section('page_title', 'Tables Management')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Pool &amp; snooker tables</h3>
            <small class="text-muted">Hourly rates · floor order</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('billiards.pos') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-plus-circle me-1"></i>Create booking
            </a>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTableModal">
                <i class="fas fa-plus me-1"></i>Add table
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Rate / hr</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tables as $t)
                    <tr>
                        <td class="col-num">{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $t->name }}</td>
                        <td>{{ $t->typeLabel() }}</td>
                        <td>{{ $currency }} {{ number_format((float) $t->hourly_rate, 2) }}</td>
                        <td>{{ $t->sort_order }}</td>
                        <td>
                            <span class="badge-soft {{ $t->is_active ? 'success' : 'muted' }}">
                                {{ $t->is_active ? 'Active' : 'Off' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-row-actions>
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editTable{{ $t->id }}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('billiards.pos', ['table' => $t->id]) }}">
                                        <i class="fas fa-calendar-plus"></i> Book now
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('billiards.tables.destroy', $t) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-4">
                                <i class="fas fa-border-all"></i>
                                No tables yet — add your first pool or snooker table
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add modal --}}
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('billiards.tables.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" class="form-control" placeholder="Pool 1" required autofocus>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="pool">Pool</option>
                                <option value="snooker">Snooker</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Rate / hr <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Display order</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addActive" checked>
                                <label class="form-check-label" for="addActive">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($tables as $t)
<div class="modal fade" id="editTable{{ $t->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit {{ $t->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="{{ route('billiards.tables.update', $t) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input name="name" class="form-control" value="{{ $t->name }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="pool" @selected($t->type === 'pool')>Pool</option>
                                <option value="snooker" @selected($t->type === 'snooker')>Snooker</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Rate / hr <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" value="{{ $t->hourly_rate }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Display order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $t->sort_order }}">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $t->id }}" @checked($t->is_active)>
                                <label class="form-check-label" for="editActive{{ $t->id }}">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
