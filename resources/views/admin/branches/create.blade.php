@extends('layouts.admin')
@section('title', 'Add Branch')
@section('page_title', 'Add Branch')

@section('content')
<div class="card" style="max-width:720px">
    <div class="card-header">
        <h3 class="card-title mb-0">New branch</h3>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <form method="post" action="{{ route('branches.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="alert alert-light border">Slots left: <strong>{{ $remaining }}</strong> of {{ $maxBranches }}</div>
            @include('admin.branches._form')
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Create</button>
        </div>
    </form>
</div>
@endsection
