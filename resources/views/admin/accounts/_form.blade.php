@php
    $account = $account ?? null;
    $methods = old('payment_methods', $account?->payment_methods ?? []);
@endphp
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $account->code ?? '') }}" required {{ !empty($account?->is_system) ? 'readonly' : '' }}>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $account->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['cash' => 'Cash', 'bank' => 'Bank', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $account->type ?? 'cash') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Opening balance</label>
        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}">
        <div class="form-text">Starting balance before tracked transactions.</div>
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $account->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label d-block">Link POS payment methods</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach(['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank transfer', 'online' => 'Online'] as $value => $label)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="payment_methods[]" value="{{ $value }}" id="pm_{{ $value }}" @checked(in_array($value, $methods, true))>
                    <label class="form-check-label" for="pm_{{ $value }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        <div class="form-text">Cash sales post to Cash A/C; card &amp; bank to Bank A/C.</div>
    </div>
    <div class="col-md-12 mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $account->notes ?? '') }}</textarea>
    </div>
</div>
