@extends('layouts.admin')
@section('title', 'Add Product')
@section('page_title', 'Add Product')
@section('content')
<div class="page-toolbar">
    <div>
        <h2 class="toolbar-title mb-0">Add Product</h2>
        <div class="text-muted small mt-1">Name it, price it, choose where it shows — then save.</div>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <strong>Please fix:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="product-form" id="productForm">
    @csrf

    <div class="row g-3">
        <div class="col-xl-8">
            {{-- Basic --}}
            <section class="pf-section">
                <header class="pf-section-head">
                    <span class="pf-step">1</span>
                    <div>
                        <h3>Basic info</h3>
                        <p>What cashiers and the menu will see.</p>
                    </div>
                </header>
                <div class="pf-section-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Product name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control form-control-lg" value="{{ old('name') }}" required placeholder="e.g. Chicken Biryani" autofocus>
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text"
                                       id="code"
                                       name="code"
                                       class="form-control"
                                       value="{{ old('code', $nextProductCode ?? '') }}"
                                       required
                                       placeholder="P-0001"
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="btnRefreshProductCode" title="Load next number">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Next to save: <strong id="nextProductCodeHint">{{ $nextProductCode ?? 'P-0001' }}</strong>
                                <span class="text-muted">· auto number (you can edit)</span>
                            </div>
                            @if(($recentProductCodes ?? collect())->isNotEmpty())
                            <div class="product-code-history mt-2">
                                <div class="small text-muted mb-1">Previously saved codes</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($recentProductCodes as $recent)
                                        <span class="badge rounded-pill text-bg-light border product-code-chip"
                                              title="{{ $recent->name }} · {{ optional($recent->created_at)->format('d M Y') }}">
                                            {{ $recent->code }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="category_id" name="category_id" class="form-select select2" required>
                                <option value="">Select category</option>
                                @foreach(App\Models\Category::active()->get() as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id) data-type="{{ $cat->type }}" data-kitchen="{{ $cat->kitchen_id ?? '' }}">
                                    {{ $cat->name }} ({{ strtoupper($cat->type) }})
                                </option>
                                @endforeach
                            </select>
                            <div id="categoryTypeHint" class="form-text">Category type sets KOT, BOT, or direct stock.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="subcategory_id" class="form-label">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                            <select id="subcategory_id" name="subcategory_id" class="form-select">
                                <option value="">Select category first</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="barcode" class="form-label">Barcode <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="barcode" name="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="Scan or type">
                        </div>
                        @if(!empty($multiBranch) && $branches->isNotEmpty())
                        <div class="col-12">
                            <label for="branch_ids" class="form-label">Branches</label>
                            <select id="branch_ids" name="branch_ids[]" class="form-select select2" multiple data-placeholder="All branches">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" @selected(collect(old('branch_ids', $defaultBranchIds ?? []))->contains($b->id))>{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Default: all branches. Remove any you don’t want this product on.</div>
                        </div>
                        @endif
                        @if(($suppliers ?? collect())->isNotEmpty())
                        <div class="col-12">
                            <label for="supplier_ids" class="form-label">Suppliers</label>
                            <select id="supplier_ids" name="supplier_ids[]" class="form-select select2" multiple data-placeholder="Select suppliers">
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(collect(old('supplier_ids', []))->contains($s->id))>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Select suppliers who supply this product. Required for it to appear on their purchases.</div>
                        </div>
                        @endif
                        <div class="col-12">
                            <label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="description" name="description" class="form-control" rows="2" placeholder="Short note for staff or QR menu">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Pricing --}}
            <section class="pf-section">
                <header class="pf-section-head">
                    <span class="pf-step">2</span>
                    <div>
                        <h3>Pricing</h3>
                        <p>Restaurant price first. Partner prices are optional overrides.</p>
                    </div>
                </header>
                <div class="pf-section-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="selling_price" class="form-label">Selling price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg pf-price-main">
                                <span class="input-group-text">{{ \App\Models\Setting::get('currency_symbol', 'LKR') }}</span>
                                <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control" value="{{ old('selling_price') }}" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="cost_price" class="form-label">Cost price</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ \App\Models\Setting::get('currency_symbol', 'LKR') }}</span>
                                <input type="number" step="0.01" id="cost_price" name="cost_price" class="form-control" value="{{ old('cost_price', 0) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="tax_rate" class="form-label">Tax rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" id="tax_rate" name="tax_rate" class="form-control" value="{{ old('tax_rate', 0) }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    @include('admin.products.partials.partner-prices')
                </div>
            </section>

            {{-- Portions --}}
            <section class="pf-section">
                <header class="pf-section-head">
                    <span class="pf-step">3</span>
                    <div>
                        <h3>Variations &amp; modifiers</h3>
                        <p>Optional. Sizes/portions set the base sellable price; modifiers are extras.</p>
                    </div>
                </header>
                <div class="pf-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="pf-list-card">
                                <div class="pf-list-head">
                                    <strong>Variations</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()"><i class="fas fa-plus me-1"></i>Add</button>
                                </div>
                                <div id="variantRows" class="pf-list-body">
                                    <div class="pf-row variant-row">
                                        <input type="text" name="variants[0][name]" class="form-control" placeholder="e.g. Small">
                                        <input type="number" step="0.01" name="variants[0][price_adjustment]" class="form-control variant-adj" value="0" placeholder="Adj." title="Price adjustment" oninput="updateVariantFinalHints()">
                                        <span class="variant-final text-muted small text-nowrap" title="Final sell price">= 0.00</span>
                                        <button type="button" class="btn btn-icon danger" onclick="this.closest('.variant-row').remove()" aria-label="Remove"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border mt-2" onclick="addHalfFull()">Half + Full presets</button>
                                <div class="form-text mt-1">Final price = product selling price + adjustment. Example: selling 700, Medium +200 → Medium shows as 900.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="pf-list-card">
                                <div class="pf-list-head">
                                    <strong>Modifier sets</strong>
                                    <a href="{{ route('addon-groups.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Manage</a>
                                </div>
                                <div class="pf-list-body" style="max-height:160px;overflow:auto;">
                                    @php $selectedGroups = old('addon_group_ids', []); @endphp
                                    @forelse(($catalogAddonGroups ?? collect()) as $group)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="addon_group_ids[]" value="{{ $group->id }}" id="addon_group_{{ $group->id }}"
                                            {{ in_array($group->id, $selectedGroups) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="addon_group_{{ $group->id }}">
                                            {{ $group->name }} <span class="text-muted">({{ $group->addons_count }} modifiers)</span>
                                        </label>
                                    </div>
                                    @empty
                                    <div class="text-muted small">No modifier sets yet. Create them under Menu Management → Modifiers.</div>
                                    @endforelse
                                </div>
                                <div class="pf-list-head border-top">
                                    <strong>Option sets</strong>
                                    <a href="{{ route('option-sets.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Manage</a>
                                </div>
                                <div class="pf-list-body" style="max-height:160px;overflow:auto;">
                                    @php $selectedOpts = old('option_set_ids', []); @endphp
                                    @forelse(($catalogOptionSets ?? collect()) as $os)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="option_set_ids[]" value="{{ $os->id }}" id="option_set_{{ $os->id }}"
                                            {{ in_array($os->id, $selectedOpts) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="option_set_{{ $os->id }}">
                                            {{ $os->name }} <span class="text-muted">({{ $os->options_count }} options)</span>
                                        </label>
                                    </div>
                                    @empty
                                    <div class="text-muted small">No option sets yet. Create Milk / Sugar / Ice under Menu Management → Option Sets.</div>
                                    @endforelse
                                </div>
                                <div class="pf-list-head border-top">
                                    <strong>Individual modifiers</strong>
                                    <a href="{{ route('addon-groups.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-external-link-alt me-1"></i>Create</a>
                                </div>
                                <div class="pf-list-body" style="max-height:160px;overflow:auto;">
                                    @php $selectedShared = old('shared_addon_ids', []); @endphp
                                    @forelse(($catalogAddons ?? collect()) as $ca)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="shared_addon_ids[]" value="{{ $ca->id }}" id="shared_addon_{{ $ca->id }}"
                                            {{ in_array($ca->id, $selectedShared) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="shared_addon_{{ $ca->id }}">
                                            {{ $ca->name }} <span class="text-muted">(+{{ number_format((float) $ca->price, 2) }})</span>
                                        </label>
                                    </div>
                                    @empty
                                    <div class="text-muted small">No modifiers yet. Create them under Menu Management → Modifiers.</div>
                                    @endforelse
                                </div>
                                <div class="form-text px-2 pb-2">Or quick-add a one-off below (also saved to the shared catalog).</div>
                                <div class="pf-list-head border-top">
                                    <strong>Quick add</strong>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAddonRow()"><i class="fas fa-plus me-1"></i>Add</button>
                                </div>
                                <div id="addonRows" class="pf-list-body">
                                    <div class="pf-row addon-row">
                                        <input type="text" name="addons[0][name]" class="form-control" placeholder="e.g. Extra cheese">
                                        <input type="number" step="0.01" name="addons[0][price]" class="form-control" value="0" placeholder="Price">
                                        <button type="button" class="btn btn-icon danger" onclick="this.closest('.addon-row').remove()" aria-label="Remove"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-4">
            <div class="pf-side">
                {{-- Photo & stock --}}
                <section class="pf-section">
                    <header class="pf-section-head">
                        <span class="pf-step">4</span>
                        <div>
                            <h3>Photo &amp; stock</h3>
                            <p>Image for POS / QR. Stock only if tracked.</p>
                        </div>
                    </header>
                    <div class="pf-section-body">
                        <label for="image" class="pf-upload">
                            <div class="pf-upload-preview" id="imagePreview">
                                <i class="fas fa-camera"></i>
                                <span>Click to upload photo</span>
                            </div>
                            <input type="file" id="image" name="image" accept="image/*" class="d-none" onchange="previewProductImage(this)">
                        </label>
                        <div class="mt-3">
                            <label for="stock_quantity" class="form-label">Opening / stock quantity</label>
                            <input type="number" step="0.001" id="stock_quantity" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 0) }}">
                            <div class="form-text">For direct/stock items. KOT/BOT usually leave 0.</div>
                        </div>
                        <div class="mt-3">
                            <label for="expiry_date" class="form-label">Expiry date</label>
                            <input type="date" id="expiry_date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                            <div class="form-text">For direct products with opening stock. Dashboard reminds before this date.</div>
                        </div>
                    </div>
                </section>

                {{-- Visibility --}}
                <section class="pf-section">
                    <header class="pf-section-head">
                        <span class="pf-step">5</span>
                        <div>
                            <h3>Visibility</h3>
                            <p>Where this product appears.</p>
                        </div>
                    </header>
                    <div class="pf-section-body">
                        <div class="pf-toggles">
                            @php $hadOld = session()->hasOldInput(); @endphp
                            <label class="pf-toggle">
                                <input type="checkbox" name="is_available" value="1" id="ia" @checked($hadOld ? (bool) old('is_available') : true)>
                                <span class="pf-toggle-ui">
                                    <i class="fas fa-check-circle"></i>
                                    <span>
                                        <strong>Available</strong>
                                        <small>Can be ordered now</small>
                                    </span>
                                </span>
                            </label>
                            <label class="pf-toggle">
                                <input type="checkbox" name="show_in_pos" value="1" id="sip" @checked($hadOld ? (bool) old('show_in_pos') : true)>
                                <span class="pf-toggle-ui">
                                    <i class="fas fa-cash-register"></i>
                                    <span>
                                        <strong>Show in POS</strong>
                                        <small>Cashier screen</small>
                                    </span>
                                </span>
                            </label>
                            <label class="pf-toggle">
                                <input type="checkbox" name="show_in_qr" value="1" id="siq" @checked($hadOld ? (bool) old('show_in_qr') : true)>
                                <span class="pf-toggle-ui">
                                    <i class="fas fa-qrcode"></i>
                                    <span>
                                        <strong>Show in QR menu</strong>
                                        <small>Customer self-order</small>
                                    </span>
                                </span>
                            </label>
                            <label class="pf-toggle">
                                <input type="checkbox" name="track_stock" value="1" id="ts" @checked($hadOld ? (bool) old('track_stock') : false)>
                                <span class="pf-toggle-ui">
                                    <i class="fas fa-boxes"></i>
                                    <span>
                                        <strong>Track stock</strong>
                                        <small>Deduct quantity on sale</small>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </section>

                <div class="pf-actions">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-save me-2"></i>Save product
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-light border w-100 mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
.product-form .pf-section {
    background: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(28,25,23,.04);
    margin-bottom: 1rem;
    overflow: hidden;
}
.product-form .pf-section-head {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid #f5f5f4;
    background: linear-gradient(180deg, #fffbeb 0%, #fff 100%);
}
.product-form .pf-step {
    flex: 0 0 2rem;
    width: 2rem; height: 2rem;
    border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.85rem;
    color: #fff;
    background: linear-gradient(135deg, #f59e0b, #ea580c);
}
.product-form .pf-section-head h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 750;
    color: #1c1917;
    letter-spacing: -0.01em;
}
.product-form .pf-section-head p {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: #78716c;
}
.product-form .pf-section-body { padding: 1.15rem 1.25rem 1.35rem; }
.product-form .form-control-lg { font-size: 1.05rem; padding: 0.75rem 1rem; }
.product-form .pf-price-main .form-control {
    font-weight: 700;
    font-size: 1.15rem;
    border-color: #fdba74;
    background: #fffbeb;
}
.product-form .pf-price-main .input-group-text {
    background: #fff7ed;
    border-color: #fdba74;
    color: #c2410c;
    font-weight: 700;
}
.product-form .pf-list-card {
    background: #fafaf9;
    border: 1px solid #e7e5e4;
    border-radius: 14px;
    padding: 0.9rem;
}
.product-form .pf-list-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 0.65rem;
}
.product-form .pf-list-head strong { font-size: 0.9rem; color: #292524; }
.product-form .pf-row {
    display: grid;
    grid-template-columns: 1fr 6.5rem 2.25rem;
    gap: 0.45rem;
    margin-bottom: 0.45rem;
    align-items: center;
}
.product-form .pf-upload { display: block; cursor: pointer; }
.product-form .pf-upload-preview {
    border: 2px dashed #d6d3d1;
    border-radius: 14px;
    background: #fafaf9;
    min-height: 160px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 0.5rem; color: #a8a29e; text-align: center; padding: 1rem;
    transition: border-color .2s, background .2s;
    overflow: hidden;
}
.product-form .pf-upload-preview:hover { border-color: #fdba74; background: #fffbeb; color: #c2410c; }
.product-form .pf-upload-preview i { font-size: 1.5rem; }
.product-form .pf-upload-preview span { font-size: 0.82rem; font-weight: 600; }
.product-form .pf-upload-preview.has-image { border-style: solid; padding: 0; background: #fff; }
.product-form .pf-upload-preview.has-image img { width: 100%; height: 180px; object-fit: cover; display: block; }
.product-form .pf-toggles { display: flex; flex-direction: column; gap: 0.55rem; }
.product-form .pf-toggle { margin: 0; cursor: pointer; }
.product-form .pf-toggle input { position: absolute; opacity: 0; pointer-events: none; }
.product-form .pf-toggle-ui {
    display: flex; align-items: center; gap: 0.75rem;
    border: 1px solid #e7e5e4; border-radius: 12px;
    padding: 0.7rem 0.85rem; background: #fff;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.product-form .pf-toggle-ui i {
    width: 2rem; height: 2rem; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f5f5f4; color: #a8a29e; flex-shrink: 0;
}
.product-form .pf-toggle-ui strong { display: block; font-size: 0.88rem; color: #292524; }
.product-form .pf-toggle-ui small { display: block; color: #a8a29e; font-size: 0.75rem; }
.product-form .pf-toggle input:checked + .pf-toggle-ui {
    border-color: #fdba74; background: #fffbeb;
    box-shadow: 0 0 0 3px rgba(245,158,11,.1);
}
.product-form .pf-toggle input:checked + .pf-toggle-ui i {
    background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;
}
.product-form .pf-toggle input:focus-visible + .pf-toggle-ui {
    outline: 2px solid #f59e0b; outline-offset: 2px;
}
.product-form .pf-actions {
    position: sticky; bottom: 1rem;
    background: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 16px;
    padding: 1rem;
    box-shadow: 0 10px 30px rgba(28,25,23,.08);
    margin-bottom: 1rem;
}
.product-form .pf-side { position: sticky; top: 1rem; }
.product-code-history .product-code-chip {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
    color: #44403c;
    background: #fafaf9;
}
@media (max-width: 1199.98px) {
    .product-form .pf-side { position: static; }
    .product-form .pf-actions { position: static; }
}
</style>
@endpush

@push('scripts')
<script>
let variantIdx = 1;
let addonIdx = 1;

function addVariantRow(name = '', adj = 0) {
    const wrap = document.getElementById('variantRows');
    const i = variantIdx++;
    const base = parseFloat(document.getElementById('selling_price')?.value || 0);
    const final = (base + parseFloat(adj || 0)).toFixed(2);
    const row = document.createElement('div');
    row.className = 'pf-row variant-row';
    row.innerHTML = `
        <input type="text" name="variants[${i}][name]" class="form-control" value="${name}" placeholder="e.g. Small / Medium">
        <input type="number" step="0.01" name="variants[${i}][price_adjustment]" class="form-control variant-adj" value="${adj}" placeholder="Adj." oninput="updateVariantFinalHints()">
        <span class="variant-final text-muted small text-nowrap" title="Final sell price">= ${final}</span>
        <button type="button" class="btn btn-icon danger" onclick="this.closest('.variant-row').remove()" aria-label="Remove"><i class="fas fa-times"></i></button>`;
    wrap.appendChild(row);
}

function updateVariantFinalHints() {
    const base = parseFloat(document.getElementById('selling_price')?.value || 0);
    document.querySelectorAll('#variantRows .variant-row').forEach(row => {
        const adj = parseFloat(row.querySelector('.variant-adj')?.value || 0);
        const hint = row.querySelector('.variant-final');
        if (hint) hint.textContent = '= ' + (base + adj).toFixed(2);
    });
}

document.getElementById('selling_price')?.addEventListener('input', updateVariantFinalHints);
updateVariantFinalHints();

function addAddonRow(name = '', price = 0) {
    const wrap = document.getElementById('addonRows');
    const i = addonIdx++;
    const row = document.createElement('div');
    row.className = 'pf-row addon-row';
    row.innerHTML = `
        <input type="text" name="addons[${i}][name]" class="form-control" value="${name}" placeholder="e.g. Extra mayo">
        <input type="number" step="0.01" name="addons[${i}][price]" class="form-control" value="${price}" placeholder="Price">
        <button type="button" class="btn btn-icon danger" onclick="this.closest('.addon-row').remove()" aria-label="Remove"><i class="fas fa-times"></i></button>`;
    wrap.appendChild(row);
}

function addHalfFull() {
    const price = parseFloat(document.getElementById('selling_price').value || 0);
    const halfAdj = price ? -(price / 2) : 0;
    document.getElementById('variantRows').innerHTML = '';
    variantIdx = 0;
    addVariantRow('Half', halfAdj);
    addVariantRow('Full', 0);
}

function previewProductImage(input) {
    const box = document.getElementById('imagePreview');
    if (!input.files?.[0] || !box) return;
    const url = URL.createObjectURL(input.files[0]);
    box.classList.add('has-image');
    box.innerHTML = '<img src="' + url + '" alt="Preview">';
}

document.getElementById('btnRefreshProductCode')?.addEventListener('click', async () => {
    const input = document.getElementById('code');
    const hint = document.getElementById('nextProductCodeHint');
    try {
        const res = await fetch(@json(route('products.next-code')), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.code) {
            if (input) input.value = data.code;
            if (hint) hint.textContent = data.code;
        }
    } catch (e) {
        // keep current value
    }
});

(function () {
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const selectedSubId = @json(old('subcategory_id'));
    const urlTemplate = @json(url('/products/subcategories-by-category/__ID__'));

    async function loadSubcategories(categoryId, preferId = null) {
        if (!subcategorySelect) return;
        subcategorySelect.innerHTML = '<option value="">Loading…</option>';
        if (!categoryId) {
            subcategorySelect.innerHTML = '<option value="">Select category first</option>';
            return;
        }
        try {
            const res = await fetch(urlTemplate.replace('__ID__', categoryId), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const items = await res.json();
            let html = '<option value="">None</option>';
            (items || []).forEach(s => {
                const sel = preferId && String(preferId) === String(s.id) ? ' selected' : '';
                html += `<option value="${s.id}"${sel}>${s.name}</option>`;
            });
            subcategorySelect.innerHTML = html;
        } catch (e) {
            subcategorySelect.innerHTML = '<option value="">Unable to load</option>';
        }
    }

    if (categorySelect) {
        $(categorySelect).on('change', function () {
            loadSubcategories(this.value);
        });
        if (categorySelect.value) {
            loadSubcategories(categorySelect.value, selectedSubId);
        }
    }
})();
</script>
@endpush
