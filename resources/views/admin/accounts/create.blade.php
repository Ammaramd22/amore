@extends('layouts.admin')
@section('title', 'Add Account')
@section('page_title', 'Add Account')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Account</h3>
        <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('accounts.store') }}">
            @csrf
            @include('admin.accounts._form')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
