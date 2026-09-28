@extends('layouts.admin')
@section('title', 'Add Supplier')
@section('page_title', 'Add Supplier')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Supplier</h3>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">City</label><input type="text" name="city" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Tax Number</label><input type="text" name="tax_number" class="form-control"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
