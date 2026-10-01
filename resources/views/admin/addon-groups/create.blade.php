@extends('layouts.admin')
@section('title', 'Create Modifier')
@section('page_title', 'Create Modifier')
@section('content')
<div class="page-toolbar mb-3">
    <h2 class="toolbar-title mb-0">Create modifier</h2>
    <div class="toolbar-actions">
        <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
</div>

<form method="POST" action="{{ route('addon-groups.store') }}">
    @csrf
    @include('admin.addon-groups._form')
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
        <a href="{{ route('addon-groups.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
