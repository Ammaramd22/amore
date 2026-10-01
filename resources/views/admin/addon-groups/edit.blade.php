@extends('layouts.admin')
@section('title', 'Edit Modifier')
@section('page_title', 'Edit Modifier')
@section('content')
<div class="page-toolbar mb-3">
    <h2 class="toolbar-title mb-0">Edit modifier</h2>
    <div class="toolbar-actions">
        <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('addon-groups.update', $group) }}">
    @csrf
    @method('PUT')
    @include('admin.addon-groups._form')
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
        <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
