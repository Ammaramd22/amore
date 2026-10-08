@extends('layouts.admin')
@section('title', 'Edit Order')
@section('page_title', 'Edit Order ' . $order->order_number)
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Update order status</h3>
        <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Order status</label>
                    <select name="status" class="form-select" required>
                        @foreach(['pending','preparing','ready','served','completed','cancelled'] as $status)
                        <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Payment status</label>
                    <select name="payment_status" class="form-select" required>
                        @foreach(['unpaid','partial','paid','refunded'] as $status)
                        <option value="{{ $status }}" @selected($order->payment_status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Delivery status</label>
                    <select name="delivery_status" class="form-select">
                        <option value="">—</option>
                        @foreach(['pending','assigned','picked_up','delivered','cancelled'] as $status)
                        <option value="{{ $status }}" @selected($order->delivery_status === $status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Order</button>
                <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
