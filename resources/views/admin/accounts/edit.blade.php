@extends('layouts.admin')
@section('title', 'Edit Account')
@section('page_title', 'Edit Account')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit {{ $account->name }}</h3>
        <a href="{{ route('accounts.show', $account) }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('accounts.update', $account) }}">
            @csrf
            @method('PUT')
            @include('admin.accounts._form', ['account' => $account])
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                <a href="{{ route('accounts.show', $account) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
