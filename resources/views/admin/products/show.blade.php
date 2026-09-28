@extends('layouts.admin')
@section('title', 'Product Details')
@section('page_title', 'Product Details')
@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="{{ $product->imageUrl() }}"
                     style="width:100%;max-width:240px;height:auto;aspect-ratio:1;object-fit:cover;border-radius:12px;background:#f5f5f4;"
                     alt="{{ $product->name }}"
                     onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                <h4 class="mt-3 mb-1">{{ $product->name }}</h4>
                <p class="text-muted mb-2">{{ $product->category?->name }}{{ $product->subcategory ? ' / ' . $product->subcategory->name : '' }}</p>
                <span class="badge bg-{{ $product->is_available ? 'success' : 'secondary' }}">{{ $product->is_available ? 'Available' : 'Unavailable' }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Details</h3>
                <div>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><small class="text-muted d-block">Code</small><span class="fw-semibold">{{ $product->code ?? '-' }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Barcode</small><span class="fw-semibold">{{ $product->barcode ?? '-' }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Tax Rate</small><span class="fw-semibold">{{ $product->tax_rate }}% ({{ $product->tax_inclusive ? 'Inclusive' : 'Exclusive' }})</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Cost Price</small><span class="fw-semibold">LKR {{ number_format($product->cost_price, 2) }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Selling Price</small><span class="fw-semibold">LKR {{ number_format($product->selling_price, 2) }}</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Final Price</small><span class="fw-semibold">LKR {{ number_format($product->final_price, 2) }}</span></div>
                    <div class="col-12"><small class="text-muted d-block">Description</small><span>{{ $product->description ?? '-' }}</span></div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $product->show_in_pos ? 'info' : 'secondary' }}">{{ $product->show_in_pos ? 'In POS' : 'Hidden from POS' }}</span>
                        <span class="badge bg-{{ $product->show_in_qr ? 'info' : 'secondary' }}">{{ $product->show_in_qr ? 'In QR Menu' : 'Hidden from QR' }}</span>
                        <span class="badge bg-{{ $product->track_stock ? 'warning' : 'secondary' }}">{{ $product->track_stock ? 'Stock Tracked' : 'No Stock Tracking' }}</span>
                    </div>
                </div>

                @if($product->variants->count())
                <hr>
                <h6 class="fw-semibold">Portions / Sizes</h6>
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>Price adjustment</th></tr></thead>
                    <tbody>
                        @foreach($product->variants as $variant)
                        <tr><td>{{ $variant->name }}</td><td>LKR {{ number_format($variant->price_adjustment, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                @if($product->addons->count())
                <hr>
                <h6 class="fw-semibold">Add-ons</h6>
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>Price</th></tr></thead>
                    <tbody>
                        @foreach($product->addons as $addon)
                        <tr><td>{{ $addon->name }}</td><td>LKR {{ number_format($addon->price, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
