@extends('layouts.admin')
@section('title', 'Purchase Details')
@section('page_title', 'Purchase ' . $purchase->displayNumber())
@section('content')
@php $currency = \App\Models\Setting::get('currency_symbol', 'LKR'); @endphp
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead><tr><th>Type</th><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Expiry</th></tr></thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                        <tr>
                            <td><span class="badge bg-{{ $item->product_id ? 'success' : 'info' }}">{{ $item->product_id ? 'Product' : 'Ingredient' }}</span></td>
                            <td>{{ $item->itemName() }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $currency }} {{ number_format($item->unit_price, 4) }}</td>
                            <td>{{ $currency }} {{ number_format($item->total_price, 2) }}</td>
                            <td>{{ $item->expiry_date?->format('d M Y') ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body">
                <p><strong>Purchase No:</strong> <span class="badge-soft">{{ $purchase->displayNumber() }}</span></p>
                <p><strong>Supplier invoice:</strong> {{ $purchase->invoice_number ?: '—' }}</p>
                <p><strong>Supplier:</strong> {{ $purchase->supplier?->name }}</p>
                <p><strong>Date:</strong> {{ $purchase->purchase_date?->format('d M Y') }}</p>
                <p><strong>Received:</strong> <span class="badge-soft {{ $purchase->status=='received'?'success':'' }}">{{ ucfirst($purchase->status) }}</span></p>
                <p><strong>Payment:</strong> <span class="badge-soft {{ $purchase->payment_status=='paid'?'success':($purchase->payment_status=='partial'?'':'danger') }}">{{ ucfirst($purchase->payment_status) }}</span></p>
                <p><strong>Subtotal:</strong> {{ $currency }} {{ number_format($purchase->subtotal, 2) }}</p>
                <p><strong>Total:</strong> {{ $currency }} {{ number_format($purchase->total_amount, 2) }}</p>
                <p><strong>Paid:</strong> {{ $currency }} {{ number_format($purchase->paid_amount, 2) }}</p>
                <p><strong>Balance:</strong> {{ $currency }} {{ number_format(max(0, $purchase->total_amount - $purchase->paid_amount), 2) }}</p>
                @if($purchase->notes)
                <p><strong>Notes:</strong> {{ $purchase->notes }}</p>
                @endif
            </div>
        </div>

        @if($purchase->payments->count())
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Payments</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Ref</th></tr></thead>
                    <tbody>
                        @foreach($purchase->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                            <td>{{ $currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->reference_number ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
