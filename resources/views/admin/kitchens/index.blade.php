@extends('layouts.admin')
@section('title', 'Kitchens')
@section('page_title', 'Kitchens')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Kitchens</h3>
        <a href="{{ route('kitchens.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Kitchen</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="kitchens" data-export="kitchens" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Kitchen</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Printer</th>
                        <th>Categories</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kitchens as $kitchen)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $kitchen->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($kitchens->currentPage() - 1) * $kitchens->perPage() + $loop->iteration) }}</td>
                        <td>
                            <span class="fw-semibold">{{ $kitchen->name }}</span>
                            @if($kitchen->description)
                                <br><small class="text-muted">{{ Str::limit($kitchen->description, 50) }}</small>
                            @endif
                        </td>
                        <td><code>{{ $kitchen->code }}</code></td>
                        <td>
                            <span class="badge bg-{{ $kitchen->type === 'bot' ? 'info' : 'warning' }} text-{{ $kitchen->type === 'bot' ? 'white' : 'dark' }}">
                                {{ strtoupper($kitchen->type ?? 'KOT') }}
                            </span>
                        </td>
                        <td>
                            @if(($kitchen->print_mode ?? 'preview') === 'direct')
                                <span class="badge bg-dark mb-1">Direct</span>
                            @endif
                            @if($kitchen->printer_name)
                                <div><i class="fas fa-print text-muted me-1"></i>{{ $kitchen->printer_name }}</div>
                            @endif
                            @if($kitchen->printer_ip)
                                <small class="text-muted">{{ $kitchen->printer_ip }}:{{ $kitchen->printer_port ?: 9100 }}</small>
                            @endif
                            @if(!$kitchen->printer_name && !$kitchen->printer_ip)
                                —
                            @endif
                        </td>
                        <td><span class="badge-soft">{{ $kitchen->categories_count }} categories</span></td>
                        <td>
                            <span class="badge-soft {{ $kitchen->is_active ? 'success' : 'muted' }}">
                                {{ $kitchen->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('kitchens.edit', $kitchen) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                <li>
                                    <form action="{{ route('kitchens.destroy', $kitchen) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9"><div class="empty-state"><i class="fas fa-fire"></i>No kitchens yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($kitchens->hasPages())
    <div class="card-footer">{{ $kitchens->links() }}</div>
    @endif
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle me-2 text-warning"></i>How Kitchen KOT Printing Works</h3>
    </div>
    <div class="card-body">
        <ul class="mb-0 text-muted">
            <li>Create kitchens (e.g., Pizza Station, Buns Corner, Kottu Lab)</li>
            <li>Assign categories to each kitchen</li>
            <li>Set each kitchen’s printer: <strong>Direct</strong> (Print Bridge / Windows name or IP) or <strong>Preview</strong></li>
            <li>When orders are placed, items route to the correct kitchen printer</li>
            <li>Download Print Bridge from Settings → POS → Printing (same ZIP as receipt)</li>
        </ul>
    </div>
</div>
@endsection
