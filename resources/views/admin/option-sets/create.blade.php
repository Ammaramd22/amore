@extends('layouts.admin')
@section('title', 'Create Option Set')
@section('page_title', 'Create Option Set')
@section('content')
<div class="page-toolbar mb-3">
    <h2 class="toolbar-title mb-0">Create option set</h2>
    <div class="toolbar-actions">
        <a href="{{ route('option-sets.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
</div>
<form method="POST" action="{{ route('option-sets.store') }}">
    @csrf
    @include('admin.option-sets._form')
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        <a href="{{ route('option-sets.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
