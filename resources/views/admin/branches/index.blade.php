@extends('layouts.admin')
@section('title', 'Branches')
@section('page_title', 'Branches')

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="card-title mb-0">Branches</h3>
            <small class="text-muted">{{ $branches->count() }} / {{ $maxBranches }} used · {{ $remaining }} remaining</small>
        </div>
        @if($canCreate)
        <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add branch
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Branch</th>
                    <th>Invoice name</th>
                    <th>Contact</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($branches as $b)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-semibold">{{ $b->name }}</div>
                            <small class="text-muted">{{ $b->code }}@if($b->is_main) · Main @endif</small>
                        </td>
                        <td>{{ $b->company_name ?: '—' }}</td>
                        <td>
                            <div>{{ $b->phone ?: '—' }}</div>
                            <small class="text-muted">{{ $b->address }}</small>
                        </td>
                        <td>{{ $b->assigned_users_count }}</td>
                        <td>
                            <span class="badge-soft {{ $b->is_active ? 'success' : 'muted' }}">
                                {{ $b->is_active ? 'Active' : 'Off' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-row-actions>
                                @can('branches.edit')
                                <li><a class="dropdown-item" href="{{ route('branches.edit', $b) }}"><i class="fas fa-edit"></i> Edit</a></li>
                                @endcan
                                @can('branches.delete')
                                @unless($b->is_main)
                                <li>
                                    <form action="{{ route('branches.destroy', $b) }}" method="POST"
                                          onsubmit="event.preventDefault(); confirmDelete(() => this.submit())">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </li>
                                @endunless
                                @endcan
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No branches yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
