@extends('layouts.admin')
@section('title', 'Users')
@section('page_title', 'Users & Roles')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Users</h3>
        <div class="d-flex gap-2">
            @can('roles.view')
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-key me-1"></i>Roles</a>
            @endcan
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add User</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table mb-0" data-resource="users" data-export="users" data-can-delete="1">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>PIN</th>
                        <th>Branch</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $u->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($users->currentPage() - 1) * $users->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->phone ?? '—' }}</td>
                        <td><span class="badge-soft">{{ ucfirst($u->user_type) }}</span></td>
                        <td>
                            @if($u->pin)
                                <span class="badge-soft success">Set</span>
                            @else
                                <span class="badge-soft muted">None</span>
                            @endif
                        </td>
                        <td>
                            @if($u->branches->isNotEmpty())
                                {{ $u->branches->pluck('name')->join(', ') }}
                            @else
                                {{ $u->branch?->name ?? 'All' }}
                            @endif
                        </td>
                        <td>{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>
                            <span class="badge-soft {{ $u->is_active ? 'success' : 'muted' }}">
                                {{ $u->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-row-actions>
                                <li><a class="dropdown-item" href="{{ route('users.edit', $u) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                @if($u->id !== auth()->id())
                                <li>
                                    <form action="{{ route('users.destroy', $u) }}" method="POST" onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                                @endif
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11"><div class="empty-state"><i class="fas fa-users"></i>No users yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
@endsection
