@extends('layouts.admin')
@section('title', 'Edit User')
@section('page_title', 'Edit User')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit User</h3>
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" maxlength="60" placeholder="cashier01">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Staff ID (4 digits)</label>
                    <input type="text" name="login_code" class="form-control" value="{{ old('login_code', $user->login_code) }}" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="e.g. 1001">
                    <div class="form-text">Used with PIN — PIN alone cannot sign in.</div>
                    @error('login_code')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ $user->phone }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password (leave blank to keep)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Login PIN (4 digits)</label>
                    <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="{{ $user->pin ? '•••• (leave blank to keep)' : 'Set a PIN' }}">
                    <div class="form-text">
                        @if($user->pin)
                            PIN is set. Enter a new one to replace, or clear below.
                        @else
                            No PIN set yet — optional keypad login.
                        @endif
                    </div>
                    @error('pin')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm PIN</label>
                    <input type="password" name="pin_confirmation" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="Repeat new PIN">
                    @if($user->pin)
                    <div class="form-check mt-2">
                        <input type="checkbox" name="clear_pin" value="1" class="form-check-input" id="clearPin">
                        <label class="form-check-label" for="clearPin">Remove PIN</label>
                    </div>
                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee Code</label>
                    <input type="text" name="employee_code" class="form-control" value="{{ $user->employee_code }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="admin" {{ $user->user_type=='admin'?'selected':'' }}>Admin</option>
                        <option value="manager" {{ $user->user_type=='manager'?'selected':'' }}>Manager</option>
                        <option value="cashier" {{ $user->user_type=='cashier'?'selected':'' }}>Cashier</option>
                        <option value="waiter" {{ $user->user_type=='waiter'?'selected':'' }}>Waiter</option>
                        <option value="kitchen" {{ $user->user_type=='kitchen'?'selected':'' }}>Kitchen</option>
                        <option value="delivery" {{ $user->user_type=='delivery'?'selected':'' }}>Delivery</option>
                        <option value="billiards" {{ $user->user_type=='billiards'?'selected':'' }}>Billiards</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Branch access</label>
                    @if(!empty($multiBranch))
                        @php
                            $assignedIds = $user->branches->pluck('id')->all();
                            $mode = old('branch_access', empty($assignedIds) && !$user->branch_id ? 'all' : (count($assignedIds) > 1 ? 'multi' : 'single'));
                        @endphp
                        <select name="branch_access" id="branchAccess" class="form-select mb-2">
                            <option value="all" @selected($mode==='all')>All branches</option>
                            <option value="single" @selected($mode==='single')>One branch</option>
                            <option value="multi" @selected($mode==='multi')>Selected branches (both / multi)</option>
                        </select>
                        <div id="branchSingleWrap" class="{{ $mode==='single' ? '' : 'd-none' }}">
                            <select name="branch_id" class="form-select">
                                <option value="">Select branch</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id', $user->branch_id)==$b->id)>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="branchMultiWrap" class="{{ $mode==='multi' ? '' : 'd-none' }}">
                            <select name="branch_ids[]" class="form-select select2" multiple data-placeholder="Select branches">
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(collect(old('branch_ids', $assignedIds))->contains($b->id))>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">User with multiple branches will choose one at login.</div>
                        </div>
                    @else
                        <select name="branch_id" class="form-select">
                            <option value="">All</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $user->branch_id==$b->id?'selected':'' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Roles</label>
                    <select name="roles[]" class="form-select select2" multiple>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $user->hasRole($role->name)?'selected':'' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $user->is_active?'checked':'' }} id="ia">
                        <label class="form-check-label" for="ia">Active</label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
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
