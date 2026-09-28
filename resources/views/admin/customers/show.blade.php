@extends('layouts.admin')
@section('title', 'Customer Details')
@section('page_title', $customer->name)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customer Info</h3>
                <div class="d-flex gap-2">
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $customer->email ?? 'N/A' }}</p>
                <p><strong>Address:</strong> {{ $customer->address ?? 'N/A' }}</p>
                <p><strong>DOB:</strong> {{ $customer->date_of_birth?->format('Y-m-d') ?? 'N/A' }}</p>
                <p><strong>Anniversary:</strong> {{ $customer->anniversary_date?->format('Y-m-d') ?? 'N/A' }}</p>
                <p><strong>Loyalty Points:</strong> {{ $customer->loyalty_points }}</p>
                @if(\App\Services\LoyaltyService::enabled())
                <p><strong>Stamp card:</strong>
                    @if($customer->loyalty_joined)
                        {{ $customer->loyalty_stamps }}/{{ \App\Services\LoyaltyService::stampsRequired() }} stamps
                        · {{ $customer->loyalty_free_drinks }} free
                        <a href="{{ route('loyalty.show', $customer) }}" class="btn btn-sm btn-outline-primary ms-2">View card</a>
                    @else
                        <form method="POST" action="{{ route('loyalty.enroll', $customer) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-primary" type="submit">Join loyalty stamp card</button>
                        </form>
                    @endif
                </p>
                @endif
                <p><strong>Outstanding:</strong> LKR {{ number_format($customer->outstanding_balance, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Orders</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Order #</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($customer->orders()->latest()->limit(10)->get() as $order)
                        <tr><td><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td><td>LKR {{ number_format($order->total_amount, 2) }}</td><td>{{ $order->created_at->format('Y-m-d') }}</td></tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No orders yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
