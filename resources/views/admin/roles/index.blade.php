@extends('layouts.admin')
@section('title', 'Roles')
@section('page_title', 'Roles & Permissions')
@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0">Roles</h3>
            <p class="mb-0 text-muted small mt-1">Assign permissions to each role</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users me-1"></i>Users
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td>
                            <span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $role->name) }}</span>
                            @if($role->name === 'software_owner')
                                <span class="badge-soft ms-1">Owner</span>
                            @endif
                        </td>
                        <td><span class="badge-soft">{{ $role->permissions_count }}</span></td>
                        <td>{{ $role->users_count }}</td>
                        <td class="text-end">
                            @can('roles.edit')
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-key me-1"></i>Assign permissions
                            </a>
                            @else
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
