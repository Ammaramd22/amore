@extends('layouts.admin')
@section('title', 'Add Delivery Partner')
@section('page_title', 'Add Delivery Partner')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Delivery Partner</h3>
        <a href="{{ route('delivery-partners.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('delivery-partners.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Partner Name *</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Code *</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g., UBER, PICKME">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="commission_rate" class="form-label">Commission Rate *</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control @error('commission_rate') is-invalid @enderror" id="commission_rate" name="commission_rate" value="{{ old('commission_rate', 15) }}" required>
                        <select class="form-select" name="commission_type" style="max-width: 120px;">
                            <option value="percentage" {{ old('commission_type') === 'percentage' ? 'selected' : '' }}>%</option>
                            <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>LKR</option>
                        </select>
                    </div>
                    @error('commission_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label d-block">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="collection_type" class="form-label">Who collects payment? *</label>
                    <select name="collection_type" id="collection_type" class="form-select" required>
                        <option value="partner" selected>Partner / app (Uber, PickMe…) — pays us later</option>
                        <option value="own">Our delivery / COD — we collect (daily)</option>
                    </select>
                    <div class="form-text">
                        <strong>Partner:</strong> no money at POS — due goes to Partner Ledger.<br>
                        <strong>Our delivery:</strong> Pay now or Cash on delivery.
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="settlement_cycle" class="form-label">When partner remits</label>
                    <select name="settlement_cycle" id="settlement_cycle" class="form-select">
                        <option value="weekly" selected>Once a week</option>
                        <option value="on_receive">When we receive</option>
                        <option value="daily">Daily</option>
                        <option value="per_order">Per order</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label d-block">Settlement ledger</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="tracks_settlement" name="tracks_settlement" value="1" checked>
                        <label class="form-check-label" for="tracks_settlement">Track dues & receive payments in ledger</label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('delivery-partners.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
