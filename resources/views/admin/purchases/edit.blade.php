@extends('layouts.admin')
@section('title', 'Edit Purchase')
@section('page_title', 'Edit Purchase')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Edit Purchase — {{ $purchase->displayNumber() }}</h3>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('purchases.update', $purchase) }}" id="purchaseEditForm">
            @csrf
            @method('PUT')
            @if($errors->has('error'))
            <div class="alert alert-danger">{{ $errors->first('error') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Purchase Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label">Purchase No</label>
                            <input type="text" class="form-control" value="{{ $purchase->displayNumber() }}" readonly>
                        </div>
                        <div class="col-lg-3">
                            <label for="supplier_name" class="form-label">Supplier</label>
                            <input type="text" id="supplier_name" class="form-control" value="{{ $purchase->supplier?->name }}" readonly>
                        </div>
                        <div class="col-lg-3">
                            <label for="invoice_number" class="form-label">Supplier Invoice</label>
                            <input type="text" id="invoice_number" class="form-control" value="{{ $purchase->invoice_number ?? '—' }}" readonly>
                        </div>
                        <div class="col-lg-3">
                            <label for="purchase_date" class="form-label">Purchase Date</label>
                            <input type="text" id="purchase_date" class="form-control" value="{{ $purchase->purchase_date?->format('Y-m-d') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Received status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="pending" @selected($purchase->status == 'pending')>Pending</option>
                                <option value="partial" @selected($purchase->status == 'partial')>Partial</option>
                                <option value="received" @selected($purchase->status == 'received')>Received</option>
                                <option value="cancelled" @selected($purchase->status == 'cancelled')>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="payment_status" class="form-label">Payment status <span class="text-danger">*</span></label>
                            <select id="payment_status" name="payment_status" class="form-select" required>
                                <option value="unpaid" @selected($purchase->payment_status == 'unpaid')>Unpaid</option>
                                <option value="partial" @selected($purchase->payment_status == 'partial')>Partial</option>
                                <option value="paid" @selected($purchase->payment_status == 'paid')>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes', $purchase->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-list-ul me-2 text-primary"></i>Purchase Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->items as $item)
                                <tr>
                                    <td><span class="badge bg-{{ $item->product_id ? 'success' : 'info' }}">{{ $item->product_id ? 'Product' : 'Ingredient' }}</span></td>
                                    <td>{{ $item->itemName() }}</td>
                                    <td>{{ number_format($item->quantity, 3) }}</td>
                                    <td>LKR {{ number_format($item->unit_price, 4) }}</td>
                                    <td>LKR {{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th scope="row" colspan="4" class="text-end">Grand Total</th>
                                    <th scope="row">LKR {{ number_format($purchase->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('purchases.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Update Purchase</button>
            </div>
        </form>
    </div>
</div>
@endsection
