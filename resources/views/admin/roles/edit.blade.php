@extends('layouts.admin')
@section('title', 'Role Permissions')
@section('page_title', 'Permissions — ' . ucwords(str_replace('_', ' ', $role->name)))
@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>All Roles
    </a>
    @if($canEdit)
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="rolePermSelect(true)">Select all</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="rolePermSelect(false)">Clear all</button>
    </div>
    @endif
</div>

<form method="POST" action="{{ route('roles.update', $role) }}" id="rolePermForm">
    @csrf
    @method('PUT')

    <div class="row g-3">
        @foreach($grouped as $group => $permissions)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 role-perm-card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h3 class="card-title mb-0" style="font-size:0.95rem;">{{ $group }}</h3>
                    @if($canEdit)
                    <button type="button" class="btn btn-link btn-sm p-0" onclick="rolePermGroup(this, true)">All</button>
                    @endif
                </div>
                <div class="card-body py-2">
                    @foreach($permissions as $perm)
                    <div class="form-check mb-2">
                        <input class="form-check-input role-perm-check"
                               type="checkbox"
                               name="permissions[]"
                               value="{{ $perm->name }}"
                               id="perm_{{ md5($perm->name) }}"
                               @checked(in_array($perm->name, $assigned, true))
                               @disabled(! $canEdit)>
                        <label class="form-check-label" for="perm_{{ md5($perm->name) }}">
                            <code style="font-size:0.78rem;">{{ $perm->name }}</code>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($canEdit)
    <div class="settings-actions mt-4 d-flex justify-content-end gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Save Permissions
        </button>
    </div>
    @endif
</form>
@endsection

@push('styles')
<style>
    .role-perm-card .form-check-label { cursor: pointer; }
    .role-perm-card code { color: #44403c; background: #f5f5f4; padding: 1px 6px; border-radius: 4px; }
</style>
@endpush

@push('scripts')
<script>
function rolePermSelect(on) {
    document.querySelectorAll('.role-perm-check:not(:disabled)').forEach(el => { el.checked = !!on; });
}
function rolePermGroup(btn, on) {
    const card = btn.closest('.role-perm-card');
    if (!card) return;
    card.querySelectorAll('.role-perm-check:not(:disabled)').forEach(el => { el.checked = !!on; });
}
</script>
@endpush
