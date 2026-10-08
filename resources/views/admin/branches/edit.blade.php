@extends('layouts.admin')
@section('title', 'Edit Branch')
@section('page_title', 'Edit Branch')

@section('content')
<div class="card" style="max-width:720px">
    <div class="card-header">
        <h3 class="card-title mb-0">{{ $branch->name }}</h3>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <form method="post" action="{{ route('branches.update', $branch) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card-body">
            @include('admin.branches._form', ['branch' => $branch])
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        </div>
    </form>
</div>
@endsection
