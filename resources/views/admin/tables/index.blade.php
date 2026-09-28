@extends('layouts.admin')
@section('title', 'Tables')
@section('page_title', 'Tables')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Tables</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('floors.index') }}" class="btn btn-secondary btn-sm">Floors</a>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTableModal"><i class="fas fa-plus me-1"></i>Add</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="tables" data-export="tables" data-can-delete="1">
                <thead><tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th><th>Name</th><th>Number</th><th>QR Code</th><th>Floor</th><th>Capacity</th><th>Status</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse($tables as $table)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $table->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($tables->currentPage() - 1) * $tables->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $table->name }}</td>
                        <td>{{ $table->number }}</td>
                        <td>
                            <span class="badge bg-dark" style="letter-spacing:.15em;font-size:.95rem;">{{ $table->qr_code ?: '—' }}</span>
                        </td>
                        <td>{{ $table->floor?->name }}</td>
                        <td>{{ $table->capacity }}</td>
                        <td><span class="badge bg-{{ $table->status=='available'?'success':($table->status=='occupied'?'danger':'warning') }}">{{ ucfirst($table->status) }}</span></td>
                        <td><span class="badge-soft {{ $table->is_active ? 'success' : 'muted' }}">{{ $table->is_active ? 'Yes' : 'No' }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('tables.qr-card', $table) }}" target="_blank"><i class="fas fa-qrcode"></i> Print / generate QR</a></li>
                                <li><button type="button" class="dropdown-item" onclick="navigator.clipboard.writeText(@json(route('qr.menu', $table))).then(()=>alert('Menu link copied'))"><i class="fas fa-link"></i> Copy menu link</button></li>
                                <li><a class="dropdown-item" href="https://wa.me/?text={{ rawurlencode(\App\Models\Setting::get('company_name', 'Restaurant').' — '.$table->name.' menu: '.route('qr.menu', $table).($table->qr_code ? ' Code: '.$table->qr_code : '')) }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> Share WhatsApp</a></li>
                                <li><a class="dropdown-item" href="{{ route('qr.menu', $table) }}" target="_blank"><i class="fas fa-external-link-alt"></i> Open menu</a></li>
                                <li>
                                    <form action="{{ route('tables.regenerate-code', $table) }}" method="POST" onsubmit="return confirm('Generate a new 4-digit code for {{ $table->name }}? Reprint the table card after.')">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="fas fa-sync"></i> New code</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editTable{{ $table->id }}"><i class="fas fa-edit"></i> Edit</button></li>
                                <li>
                                    <form action="{{ route('tables.destroy', $table) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10"><div class="empty-state"><i class="fas fa-chair"></i>No tables yet</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $tables->links() }}</div>
</div>

<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Add Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tables.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Floor</label>
                        <select name="floor_id" class="form-select" required>
                            @foreach($floors as $f)<option value="{{ $f->id }}">{{ $f->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Number</label><input type="text" name="number" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="4" min="1" required></div>
                    <div class="mb-3"><label class="form-label">Shape</label>
                        <select name="shape" class="form-select"><option value="square">Square</option><option value="round">Round</option><option value="rectangle">Rectangle</option></select>
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

@foreach($tables as $table)
<div class="modal fade" id="editTable{{ $table->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header respos">
                <h5 class="modal-title">Edit {{ $table->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tables.update', $table) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Floor</label>
                        <select name="floor_id" class="form-select" required>
                            @foreach($floors as $f)
                            <option value="{{ $f->id }}" @selected($table->floor_id == $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $table->name }}" required></div>
                    <div class="mb-3"><label class="form-label">Number</label><input type="text" name="number" class="form-control" value="{{ $table->number }}" required></div>
                    <div class="mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="{{ $table->capacity }}" min="1" required></div>
                    <div class="mb-3"><label class="form-label">Shape</label>
                        <select name="shape" class="form-select">
                            @foreach(['square','round','rectangle'] as $shape)
                            <option value="{{ $shape }}" @selected(($table->shape ?? '') === $shape)>{{ ucfirst($shape) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="tableActive{{ $table->id }}" {{ $table->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="tableActive{{ $table->id }}">Active</label>
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
