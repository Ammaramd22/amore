@php
    $deliveryPartners = $deliveryPartners ?? \App\Models\DeliveryPartner::active()->orderBy('name')->get();
    $partnerPriceMap = isset($product) ? $product->partnerPrices->pluck('price', 'delivery_partner_id') : collect();
@endphp
@if($deliveryPartners->isNotEmpty())
<div class="pf-partners mt-4">
    <div class="pf-partners-head">
        <div>
            <strong><i class="fas fa-motorcycle me-1"></i>Delivery partner prices</strong>
            <div class="text-muted small">Optional. Empty = use selling price. Type once in Fill all, then tweak any partner.</div>
        </div>
    </div>
    <div class="row g-2 align-items-end mb-3">
        <div class="col-md-5">
            <label class="form-label" for="partner_price_fill_all">Fill all partners</label>
            <input type="number" step="0.01" min="0" id="partner_price_fill_all" class="form-control" placeholder="Same price for every partner">
        </div>
        <div class="col-md-7 d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" id="partnerPriceFillBtn"><i class="fas fa-magic me-1"></i>Apply to all</button>
            <button type="button" class="btn btn-outline-secondary" id="partnerPriceFromSellingBtn">Use selling price</button>
        </div>
    </div>
    <div class="row g-3">
        @foreach($deliveryPartners as $partner)
        <div class="col-md-4">
            <label class="form-label" for="partner_price_{{ $partner->id }}">{{ $partner->name }}</label>
            <input type="number" step="0.01" min="0"
                   class="form-control partner-price-input"
                   id="partner_price_{{ $partner->id }}"
                   name="partner_prices[{{ $partner->id }}]"
                   value="{{ old('partner_prices.'.$partner->id, $partnerPriceMap[$partner->id] ?? '') }}"
                   placeholder="Selling price">
        </div>
        @endforeach
    </div>
</div>
@push('styles')
<style>
.pf-partners {
    border-top: 1px dashed #e7e5e4;
    padding-top: 1.15rem;
}
.pf-partners-head {
    margin-bottom: 0.85rem;
}
.pf-partners-head strong {
    display: block;
    font-size: 0.9rem;
    color: #292524;
}
</style>
@endpush
@push('scripts')
<script>
(function () {
    const fill = document.getElementById('partner_price_fill_all');
    const apply = document.getElementById('partnerPriceFillBtn');
    const fromSell = document.getElementById('partnerPriceFromSellingBtn');
    const inputs = () => document.querySelectorAll('.partner-price-input');
    apply?.addEventListener('click', () => {
        const v = fill?.value;
        if (v === '' || v == null) return;
        inputs().forEach(i => { i.value = v; });
    });
    fromSell?.addEventListener('click', () => {
        const sell = document.getElementById('selling_price')?.value || '';
        if (fill) fill.value = sell;
        inputs().forEach(i => { i.value = sell; });
    });
    fill?.addEventListener('input', () => {
        const v = fill.value;
        inputs().forEach(i => { i.value = v; });
    });
})();
</script>
@endpush
@endif
