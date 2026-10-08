@extends('layouts.admin')
@section('title', 'Add User')
@section('page_title', 'Add User')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New User</h3>
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" maxlength="60" placeholder="cashier01">
                    <div class="form-text">Login with username + password.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Staff ID (4 digits) <span class="text-muted fw-normal">(for PIN login)</span></label>
                    <input type="text" name="login_code" class="form-control" value="{{ old('login_code') }}" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="e.g. 1001">
                    <div class="form-text">Required with PIN — PIN alone cannot sign in.</div>
                    @error('login_code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Login PIN (4 digits)</label>
                    <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="e.g. 1234" value="{{ old('pin') }}">
                    <div class="form-text">Optional. Used for keypad login on the floor.</div>
                    @error('pin')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm PIN</label>
                    <input type="password" name="pin_confirmation" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Repeat PIN">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee Code</label>
                    <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="cashier">Cashier</option>
                        <option value="waiter">Waiter</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="delivery">Delivery</option>
                        <option value="billiards">Billiards</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Branch access</label>
                    @if(!empty($multiBranch))
                        @php
                            $mode = old('branch_access', 'all');
                        @endphp
                        <select name="branch_access" id="branchAccess" class="form-select mb-2">
                            <option value="all" @selected($mode==='all')>All branches</option>
                            <option value="single" @selected($mode==='single')>One branch</option>
                            <option value="multi" @selected($mode==='multi')>Selected branches (both / multi)</option>
                        </select>
                        <div id="branchSingleWrap" class="d-none">
                            <select name="branch_id" class="form-select">
                                <option value="">Select branch</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id')==$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="branchMultiWrap" class="d-none">
                            <select name="branch_ids[]" class="form-select select2" multiple data-placeholder="Select branches">
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(collect(old('branch_ids', []))->contains($b->id))>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">User with multiple branches will choose one at login.</div>
                        </div>
                    @else
                        <select name="branch_id" class="form-select">
                            <option value="">All</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Roles</label>
                    <select name="roles[]" class="form-select select2" multiple>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@if(!empty($multiBranch))
@push('scripts')
<script>
(function(){
    const sel = document.getElementById('branchAccess');
    const single = document.getElementById('branchSingleWrap');
    const multi = document.getElementById('branchMultiWrap');
    function sync(){
        const v = sel.value;
        single.classList.toggle('d-none', v !== 'single');
        multi.classList.toggle('d-none', v !== 'multi');
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
@endif
@endsection
