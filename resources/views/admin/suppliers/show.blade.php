@extends('layouts.admin')
@section('title', 'Supplier Details')
@section('page_title', $supplier->name)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Supplier Info</h3>
                <div class="d-flex gap-2">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Contact:</strong> {{ $supplier->contact_person ?? 'N/A' }}</p>
                <p><strong>Phone:</strong> {{ $supplier->phone ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</p>
                <p><strong>Address:</strong> {{ $supplier->address ?? 'N/A' }}</p>
                <p><strong>City:</strong> {{ $supplier->city ?? 'N/A' }}</p>
                <p><strong>Tax #:</strong> {{ $supplier->tax_number ?? 'N/A' }}</p>
                <p><strong>Balance:</strong> LKR {{ number_format($supplier->balance, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Purchases</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Invoice</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($supplier->purchases()->latest()->limit(10)->get() as $p)
                        <tr><td>{{ $p->invoice_number ?? 'N/A' }}</td><td>LKR {{ number_format($p->total_amount, 2) }}</td><td>{{ $p->purchase_date?->format('Y-m-d') }}</td></tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No purchases yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
