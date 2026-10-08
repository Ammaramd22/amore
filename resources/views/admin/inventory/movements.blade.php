@extends('layouts.admin')
@section('title', 'Stock Movements')
@section('page_title', 'Stock Movements')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Stock Movements</h3>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <select name="ingredient" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($ingredients as $ing)<option value="{{ $ing->id }}" {{ request('ingredient')==$ing->id?'selected':'' }}>{{ $ing->name }}</option>@endforeach
                </select>
            </form>
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Date</th><th>Ingredient</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>By</th></tr></thead>
                <tbody>
                    @forelse($movements as $m)
                    <tr>
                        <td>{{ \App\Models\Setting::formatDateTime($m->created_at, 'Y-m-d H:i') }}</td>
                        <td>{{ $m->ingredient?->name }}</td>
                        <td><span class="badge bg-secondary">{{ $m->type }}</span></td>
                        <td>{{ number_format($m->quantity, 3) }}</td>
                        <td>{{ number_format($m->stock_before, 3) }}</td>
                        <td>{{ number_format($m->stock_after, 3) }}</td>
                        <td>{{ $m->creator?->name }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No movements found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $movements->links() }}</div>
</div>
@endsection
