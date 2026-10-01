@extends('layouts.admin')
@section('title', 'Option Set')
@section('page_title', 'Option Set')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $set->displayLabel() }}</h3>
        <div class="d-flex gap-2">
            @can('option-sets.edit')
            <a href="{{ route('option-sets.edit', $set) }}" class="btn btn-primary btn-sm">Edit</a>
            @endcan
            <a href="{{ route('option-sets.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $set->name }}</dd>
            <dt class="col-sm-3">Display name</dt><dd class="col-sm-9">{{ $set->display_name ?: '—' }}</dd>
            <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $set->type === 'text_color' ? 'Text and color' : 'Text' }}</dd>
        </dl>
        <h5>Options</h5>
        <ul class="list-group mb-3">
            @foreach($set->options as $opt)
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ $opt->name }}</span>
                <span class="text-muted small">{{ $opt->itemVariationUsageCount() }} item variations</span>
            </li>
            @endforeach
        </ul>
        <h5>Products</h5>
        @forelse($set->products as $p)
        <span class="badge bg-light text-dark border me-1">{{ $p->name }}</span>
        @empty
        <span class="text-muted">None</span>
        @endforelse
    </div>
</div>
@endsection
