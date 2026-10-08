@extends('layouts.admin')
@section('title', 'Edit Supplier')
@section('page_title', 'Edit Supplier')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Supplier</h3>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $supplier->name }}" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control" value="{{ $supplier->contact_person }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="{{ $supplier->phone }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $supplier->email }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="{{ $supplier->city }}"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Tax Number</label><input type="text" name="tax_number" class="form-control" value="{{ $supplier->tax_number }}"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2">{{ $supplier->address }}</textarea></div>
                <div class="col-md-12 mb-3"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $supplier->is_active ? 'checked' : '' }} id="ia"><label class="form-check-label" for="ia">Active</label></div></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
