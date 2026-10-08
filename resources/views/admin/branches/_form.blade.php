@php $b = $branch ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Branch name</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $b?->name) }}" placeholder="Colombo Fort">
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Code</label>
        <input type="text" name="code" class="form-control" required maxlength="20" value="{{ old('code', $b?->code) }}" placeholder="CLT">
        @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Invoice / print name</label>
        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $b?->company_name) }}" placeholder="Leave empty to use main business name">
        <div class="form-text">Shown on 80mm receipts for this branch.</div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $b?->address) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $b?->phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $b?->email) }}">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Receipt footer</label>
        <input type="text" name="receipt_footer" class="form-control" maxlength="200"
               value="{{ old('receipt_footer', $b?->receipt_footer) }}"
               placeholder="Thanks for visiting our Colombo branch!">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Invoice logo</label>
        <input type="file" name="invoice_logo" class="form-control" accept="image/*">
        @if($b?->invoice_logo)
            <div class="d-flex align-items-center gap-3 mt-2">
                <img src="{{ \App\Services\BranchService::branchLogoUrl($b) }}" alt="" style="max-height:48px">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_invoice_logo" value="1" id="rmLogo">
                    <label class="form-check-label" for="rmLogo">Remove logo</label>
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActive"
                   @checked(old('is_active', $b?->is_active ?? true))>
            <label class="form-check-label fw-semibold" for="isActive">Active</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="is_main" value="1" id="isMain"
                   @checked(old('is_main', $b?->is_main ?? false))>
            <label class="form-check-label fw-semibold" for="isMain">Main branch</label>
        </div>
    </div>
</div>
