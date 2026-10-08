@extends('layouts.admin')
@section('title', 'Edit Option Set')
@section('page_title', 'Edit Option Set')
@section('content')
<div class="page-toolbar mb-3">
    <h2 class="toolbar-title mb-0">Edit option set</h2>
    <div class="toolbar-actions">
        <a href="{{ route('option-sets.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
<form method="POST" action="{{ route('option-sets.update', $set) }}">
    @csrf
    @method('PUT')
    @include('admin.option-sets._form')
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        <a href="{{ route('option-sets.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
