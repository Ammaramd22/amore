@extends('layouts.admin')
@section('title', 'Order Details')
@section('page_title', 'Order ' . $order->order_number)
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">Order {{ $order->order_number }}</h2>
    <div class="toolbar-actions">
        <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Items</h3>
                <span class="badge-soft {{ $order->status=='completed'?'success':($order->status=='cancelled'?'danger':'') }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>LKR {{ number_format($item->unit_price, 2) }}</td>
                                <td>LKR {{ number_format($item->total_price, 2) }}</td>
                                <td><span class="badge-soft muted">{{ ucfirst($item->status) }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5"><div class="empty-state py-4"><i class="fas fa-utensils"></i>No items</div></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Payments</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->payments as $payment)
                            <tr>
                                <td>{{ ucfirst($payment->method) }}</td>
                                <td>LKR {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->reference_number ?? '—' }}</td>
                                <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4"><div class="empty-state py-4"><i class="fas fa-credit-card"></i>No payments</div></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Order Summary</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong>LKR {{ number_format($order->subtotal, 2) }}</strong></div>
                @if($order->discount_amount > 0)
                <div class="d-flex justify-content-between mb-2"><span>Discount</span><strong class="text-danger">-LKR {{ number_format($order->discount_amount, 2) }}</strong></div>
                @endif
                @if(\App\Models\Setting::get('tax_enabled', false))
                <div class="d-flex justify-content-between mb-2"><span>{{ \App\Models\Setting::get('tax_name', 'Tax') }}</span><strong>LKR {{ number_format($order->tax_amount, 2) }}</strong></div>
                @endif
                @if($order->service_charge > 0)
                <div class="d-flex justify-content-between mb-2"><span>Service Charge</span><strong>LKR {{ number_format($order->service_charge, 2) }}</strong></div>
                @endif
                <div class="d-flex justify-content-between mb-2"><span>Delivery</span><strong>LKR {{ number_format($order->delivery_charge, 2) }}</strong></div>
                <hr>
                <div class="d-flex justify-content-between"><span class="h5 mb-0">Total</span><strong class="h4 text-primary mb-0">LKR {{ number_format($order->total_amount, 2) }}</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Paid</span><strong>LKR {{ number_format($order->paid_amount, 2) }}</strong></div>
                @if($order->change_amount > 0)
                <div class="d-flex justify-content-between"><span>Change</span><strong>LKR {{ number_format($order->change_amount, 2) }}</strong></div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Order Type:</strong> {{ ucfirst(str_replace('_',' ',$order->order_type)) }}</p>
                <p class="mb-2"><strong>Table:</strong> {{ $order->table?->name ?? 'N/A' }}</p>
                <p class="mb-2"><strong>Customer:</strong> {{ $order->customer?->name ?? 'Walk-in' }}</p>
                <p class="mb-2"><strong>Waiter:</strong> {{ $order->waiter?->name ?? 'N/A' }}</p>
                <p class="mb-2"><strong>Cashier:</strong> {{ $order->cashier?->name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Notes:</strong> {{ $order->order_notes ?? 'None' }}</p>
            </div>
        </div>

        <div class="form-actions mt-3 border-0 pt-0">
            <a href="{{ route('pos.print-receipt', $order) }}" target="_blank" class="btn btn-secondary w-100"><i class="fas fa-print me-1"></i>Print Receipt</a>
            <a href="{{ route('pos.print-kot', $order) }}" target="_blank" class="btn btn-primary w-100"><i class="fas fa-print me-1"></i>Print KOT</a>
        </div>
    </div>
</div>
@endsection
