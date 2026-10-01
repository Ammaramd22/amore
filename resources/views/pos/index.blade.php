@extends('layouts.pos')

{{-- amore-pos-build: 20261001-modifiers-v5 --}}

@section('title', 'POS Billing')

@php
    $headerPosUi = $settings['pos_ui_mode'] ?? 'restaurant';
    $headerIsRestaurant = ! in_array($headerPosUi, ['bakery', 'ice_cream'], true);
@endphp

@if($headerIsRestaurant)
@section('topbar-tools')
<div class="pos-header-bar" aria-label="POS tools">
    <div class="pos-header-last-sale" id="lastSalePanel" onclick="reprintLastInvoice()" role="button" title="Reprint last invoice">
        <span class="phls-label">Last sale</span>
        <strong id="lastSaleInvoice">{{ $lastSale->order_number ?? '—' }}</strong>
        <span id="lastSaleAmount">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->total_amount ?? 0), 2) }}</span>
        <span class="phls-change">Chg <strong id="lastSaleChange">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->change_amount ?? 0), 2) }}</strong></span>
        <span class="d-none" id="lastSalePaid">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->paid_amount ?? 0), 2) }}</span>
        <span class="d-none" id="lastSaleInvoiceMobile">{{ $lastSale->order_number ?? '—' }}</span>
        <span class="d-none" id="lastSaleAmountMobile">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->total_amount ?? 0), 2) }}</span>
        <span class="d-none" id="lastSaleChangeMobile">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->change_amount ?? 0), 2) }}</span>
    </div>
    <button type="button" class="pos-header-tool-btn" onclick="reprintLastInvoice()" title="Reprint Last Invoice">
        <i class="fas fa-receipt"></i><span>Invoice</span>
    </button>
    <button type="button" class="pos-header-tool-btn" onclick="reprintLastKOT()" title="Reprint Last KOT">
        <i class="fas fa-print"></i><span>KOT</span>
    </button>
    <button type="button" class="pos-header-tool-btn" onclick="reprintLastBOT()" title="Reprint Last BOT">
        <i class="fas fa-cocktail"></i><span>BOT</span>
    </button>
    <button type="button" class="pos-header-tool-btn" onclick="showRecentOrders()" title="Recent Transactions">
        <i class="fas fa-history"></i><span>Recent</span>
    </button>
    <button type="button" class="pos-header-tool-btn" onclick="showWaiterReport()" title="Waiter Report">
        <i class="fas fa-user-tie"></i><span>Waiters</span>
    </button>
    <button type="button" class="pos-header-tool-btn" data-shift-label="report" onclick="showShiftReport()" title="Shift Report">
        <i class="fas fa-chart-pie"></i><span>Shift</span>
    </button>
</div>
@endsection
@endif

@section('content')

<!-- Critical Functions - Must Load First -->
<script>
let cart = [];
let currentProduct = null;
let selectedMethod = 'cash';
let paymentLines = []; // [{id, method, amount}]
let paymentLineSeq = 1;
let orderNotes = '';
let billDiscount = 0;
let billDiscountType = 'fixed';
let taxEnabled = {{ $settings['tax_enabled'] ? 'true' : 'false' }};
let taxRate = {{ $settings['tax_enabled'] ? $settings['tax_rate'] : 0 }};
let taxName = @json($settings['tax_name'] ?? 'VAT');
let serviceChargeRate = {{ $settings['service_charge_rate'] }};
let serviceChargeEnabled = {{ $settings['service_charge_enabled'] ? 'true' : 'false' }};
let priceRoundingEnabled = {{ !empty($settings['price_rounding_enabled']) ? 'true' : 'false' }};
let priceRoundingMode = @json($settings['price_rounding_mode'] ?? 'up');
let priceRoundingUnit = {{ (float) ($settings['price_rounding_unit'] ?? 1) }};
let cardSurchargeEnabled = {{ !empty($settings['card_surcharge_enabled']) ? 'true' : 'false' }};
let cardSurchargePercent = {{ (float) ($settings['card_surcharge_percent'] ?? 3) }};
let canPosComp = {{ auth()->user()?->can('pos.comp') || auth()->user()?->can('orders.comp') ? 'true' : 'false' }};
let canPosRefund = {{ auth()->user()?->can('pos.refund') || auth()->user()?->can('orders.refund') ? 'true' : 'false' }};
let canPosVoid = {{ auth()->user()?->can('pos.void') || auth()->user()?->can('orders.void') ? 'true' : 'false' }};
let tableSelectionRequired = {{ $settings['table_selection_required'] ? 'true' : 'false' }};
let currencySymbol = '{{ $settings['currency_symbol'] }}';
let printAskBefore = {{ !empty($settings['print_ask_before']) ? 'true' : 'false' }};
let autoPrintKot = {{ !empty($settings['auto_print_kot']) ? 'true' : 'false' }};
let autoPrintReceipt = {{ !empty($settings['auto_print_receipt']) ? 'true' : 'false' }};
let posBridgePrintPendingKot = {{ !empty($settings['pos_bridge_print_pending_kot']) ? 'true' : 'false' }};
let receiptPrintMode = @json($settings['receipt_print_mode'] ?? 'preview');
let receiptPrintBridgeUrl = @json($settings['receipt_print_bridge_url'] ?? 'http://127.0.0.1:18181');
let receiptPrinterIp = @json($settings['receipt_printer_ip'] ?? '');
let receiptPrinterPort = {{ (int) ($settings['receipt_printer_port'] ?? 9100) }};
let receiptPrinterName = @json($settings['receipt_printer_name'] ?? 'XP-80C');
let openDrawerAfterPrint = {{ !empty($settings['open_drawer_after_print']) ? 'true' : 'false' }};
let lastSaleOrderId = {{ (int) ($lastSale->id ?? 0) }} || null;
let showScreenNumbersKeyboard = {{ $settings['show_screen_numbers_keyboard'] ? 'true' : 'false' }};
let posShortcuts = {
    search: @json($settings['shortcut_focus_search'] ?? 'F2'),
    place_order: @json($settings['shortcut_place_order'] ?? 'F6'),
    pay_now: @json($settings['shortcut_pay_now'] ?? 'F7'),
    open_bills: @json($settings['shortcut_open_bills'] ?? 'F8'),
};
let pendingQtyFocusIndex = null;
let settleOrderId = null;
let settleTotal = 0;
let openBillId = null;
let openBillSignature = ''; // cart state as last loaded/saved, to detect unsaved edits
let cartBroadcastSeq = 0;
let loyaltyEnabled = {{ \App\Services\LoyaltyService::enabled() ? 'true' : 'false' }};
let loyaltyStampsRequired = {{ \App\Services\LoyaltyService::stampsRequired() }};
let loyaltyRewardLabel = @json(\App\Services\LoyaltyService::rewardLabel());
let loyaltyCategoryIds = @json(\App\Services\LoyaltyService::categoryIds());
let currentLoyalty = null;
let pendingLoyaltyRedeem = false;
let loyaltyRedeemValue = 0;
let posUiMode = @json($settings['pos_ui_mode'] ?? 'restaurant');
let customerDisplayMode = @json($settings['customer_display_mode'] ?? 'digital');
let customerDisplayProtocol = @json($settings['customer_display_protocol'] ?? 'plain');
let customerDisplayBaud = {{ (int) ($settings['customer_display_baud'] ?? 9600) }};
let isIceCreamUi = posUiMode === 'ice_cream';
let isBakeryUi = posUiMode === 'bakery' || isIceCreamUi;
let bakeryShowDineIn = {{ !empty($settings['bakery_show_dine_in']) ? 'true' : 'false' }};
let bakeryShowDelivery = {{ !empty($settings['bakery_show_delivery']) ? 'true' : 'false' }};
let bakeryShowExpress = {{ !empty($settings['bakery_show_express']) ? 'true' : 'false' }};
let bakeryDisableKot = {{ !empty($settings['bakery_disable_kot']) ? 'true' : 'false' }};
let bakeryDirectBilling = {{ !empty($settings['bakery_direct_billing']) ? 'true' : 'false' }};
let bakeryShowOrdersDisplay = {{ !empty($settings['bakery_show_orders_display']) ? 'true' : 'false' }};
if (isBakeryUi && bakeryDirectBilling) {
    bakeryDisableKot = true;
    bakeryShowDineIn = false;
}

function moneyLabel(amount) {
    return currencySymbol + ' ' + Number(amount || 0).toFixed(2);
}

function shortcutSuffix(action) {
    const key = (posShortcuts[action] || '').toString().trim().toUpperCase();
    if (!key) return '';
    return ` <small class="pos-fkey">(${key})</small>`;
}

function focusProductSearch(force = false) {
    const input = document.getElementById('productSearch');
    if (!input) return;
    if (!force && document.querySelector('.modal.show')) return;
    // Don't steal focus while typing into cart qty / notes / other fields
    const active = document.activeElement;
    if (!force && active && active !== input && active !== document.body && active !== document.documentElement) {
        if (active.matches('input, textarea, select, [contenteditable="true"]')
            && !active.classList.contains('search-input')
            && active.id !== 'productSearch') {
            return;
        }
    }
    try {
        input.focus({ preventScroll: true });
    } catch (e) {
        input.focus();
    }
}

function keepPosSearchReady() {
    focusProductSearch(true);
    // Beat Select2 / layout focus steals after POS load
    [50, 150, 400].forEach(ms => setTimeout(() => focusProductSearch(true), ms));
}

function runPosShortcutAction(action) {
    if (action === 'search') {
        focusProductSearch(true);
        return true;
    }
    if (action === 'place_order') {
        const btn = document.querySelector('.place-order-btn:not(.d-none)')
            || document.querySelector('.mobile-place-order-btn:not(.d-none)');
        if (btn && !btn.disabled) {
            btn.click();
            return true;
        }
        return false;
    }
    if (action === 'pay_now') {
        const btn = Array.from(document.querySelectorAll('.pay-btn')).find(b => !b.classList.contains('d-none') && !b.disabled);
        if (btn) {
            btn.click();
            return true;
        }
        return false;
    }
    if (action === 'open_bills') {
        if (typeof openBillTableModal === 'function') {
            openBillTableModal();
            return true;
        }
    }
    return false;
}

function resolvePosShortcutKey(e) {
    const code = e.code || '';
    if (/^F([1-9]|1[0-2])$/.test(code)) {
        return code;
    }
    const key = (e.key || '').toUpperCase();
    if (/^F([1-9]|1[0-2])$/.test(key)) {
        return key;
    }
    return null;
}

function handlePosShortcut(e) {
    const pressed = resolvePosShortcutKey(e);
    if (!pressed) return;

    const actions = {
        search: String(posShortcuts.search || '').toUpperCase(),
        place_order: String(posShortcuts.place_order || '').toUpperCase(),
        pay_now: String(posShortcuts.pay_now || '').toUpperCase(),
        open_bills: String(posShortcuts.open_bills || '').toUpperCase(),
    };

    const action = Object.keys(actions).find(name => actions[name] === pressed);
    if (!action) return;

    e.preventDefault();
    e.stopPropagation();
    runPosShortcutAction(action);
}

window.addEventListener('keydown', handlePosShortcut, true);

function focusCartQty(index) {
    pendingQtyFocusIndex = index;
    setTimeout(() => {
        const inputs = document.querySelectorAll('.cart-qty-input[data-index="' + index + '"]');
        const input = inputs[inputs.length - 1] || inputs[0];
        if (input) {
            input.focus();
            input.select();
        }
        pendingQtyFocusIndex = null;
    }, 30);
}

function applyCartQty(index, raw) {
    if (!cart[index]) return;
    let qty = parseFloat(raw);
    if (!Number.isFinite(qty) || qty <= 0) {
        removeItem(index);
        return;
    }
    cart[index].quantity = Math.round(qty * 1000) / 1000;
    updateCart();
}

function onCartQtyKeydown(event, index) {
    if (event.key === 'Enter') {
        event.preventDefault();
        event.target.dataset.skipBlur = '1';
        applyCartQty(index, event.target.value);
        focusProductSearch();
    } else if (event.key === 'Escape') {
        event.preventDefault();
        event.target.dataset.skipBlur = '1';
        updateCart();
        focusProductSearch();
    }
}

function setCartQtyFromInput(index, raw, el) {
    if (el?.dataset?.skipBlur === '1') {
        delete el.dataset.skipBlur;
        return;
    }
    applyCartQty(index, raw);
    focusProductSearch();
}

function updateLastSale(data) {
    if (!data) return;
    if (data.order_id) lastSaleOrderId = data.order_id;
    const invoice = data.order_number || '—';
    const total = moneyLabel(data.total_amount);
    const paid = moneyLabel(data.paid_amount ?? data.total_amount);
    const change = moneyLabel(data.change_amount);

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    setText('lastSaleInvoice', invoice);
    setText('lastSaleAmount', total);
    setText('lastSalePaid', paid);
    setText('lastSaleChange', change);
    setText('lastSaleInvoiceMobile', invoice);
    setText('lastSaleAmountMobile', total);
    setText('lastSaleChangeMobile', change);

    document.getElementById('lastSalePanel')?.classList.add('has-sale');
    document.getElementById('lastSalePanelMobile')?.classList.add('has-sale');
}
let openBillNumber = null;
let currentEditOrderId = null;

// Product options
let pendingProduct = null;
let selectedVariant = null;
let selectedAddons = [];
let selectedOptions = [];
let editingCartIndex = null;

// Kitchen notification system
let notifiedReadyOrders = [];
const POS_READY_SOUND = @json((bool) \App\Models\Setting::get('pos_ready_sound_alert', true));

function playKitchenBell() {
    if (!POS_READY_SOUND) return;
    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3');
    audio.volume = 0.55;
    audio.play().catch(() => {});
}

function showKitchenNotification(orderNumber) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: linear-gradient(135deg, #00b894, #00a085);
        color: white;
        padding: 20px 30px;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,184,148,0.4);
        z-index: 9999;
        font-size: 1.2rem;
        font-weight: 600;
        animation: slideInRight 0.5s ease;
        display: flex;
        align-items: center;
        gap: 15px;
    `;
    notification.innerHTML = `
        <i class="fas fa-bell" style="font-size: 1.5rem;"></i>
        <div>
            <div style="font-size: 0.9rem; opacity: 0.9;">Order Ready!</div>
            <div style="font-size: 1.4rem;">${orderNumber}</div>
        </div>
    `;
    document.body.appendChild(notification);
    
    playKitchenBell();
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

function checkKitchenNotifications() {
    fetch('/customer-display/orders', { headers: { 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            const readyOrders = data.ready || [];
            readyOrders.forEach(order => {
                if (!notifiedReadyOrders.includes(order.order_number)) {
                    notifiedReadyOrders.push(order.order_number);
                    showKitchenNotification(order.order_number);
                }
            });
        })
        .catch(() => {});
}

function smartPosInterval(fn, ms) {
    return setInterval(function () {
        if (document.hidden) return;
        fn();
    }, ms);
}
// Check every 5 seconds (paused when tab hidden)
smartPosInterval(checkKitchenNotifications, 5000);

function productCategoryId(productId) {
    const el = document.querySelector(`.product-item[data-id="${productId}"]`);
    return el ? Number(el.dataset.category || 0) : 0;
}

function handleProductClick(productId, name, price, hasVariants, hasAddons, options = {}) {
    editingCartIndex = null;
    const extras = (window.__POS_PRODUCT_EXTRAS && window.__POS_PRODUCT_EXTRAS[productId])
        ? window.__POS_PRODUCT_EXTRAS[productId]
        : null;
    let embedded = null;
    if (extras) {
        embedded = {
            id: productId,
            name,
            price: Number(price),
            variants: null,
            addons: extras.addons || [],
            modifier_sets: extras.modifier_sets || [],
            option_sets: extras.option_sets || [],
        };
    }
    const hasExtras = !!(hasAddons
        || (embedded && ((embedded.modifier_sets || []).length || (embedded.option_sets || []).length || (embedded.addons || []).length)));

    if (hasExtras || hasVariants) {
        pendingProduct = { productId, name, price, hasVariants, hasAddons: hasExtras, category_id: productCategoryId(productId) };
        selectedAddons = [];
        selectedOptions = [];
        selectedVariant = null;
        showProductOptionsModal(productId, name, price, hasVariants, hasExtras, {
            productSeed: embedded,
            keepSearchFocus: !!(options && options.keepSearchFocus),
        });
        return;
    }

    addToCart(productId, name, price, false, false, options);
}

function cartItemBaseName(item) {
    if (!item) return '';
    if (item.base_name) return item.base_name;
    let name = String(item.name || '');
    if (item.variant_name) {
        const suffix = ` (${item.variant_name})`;
        if (name.endsWith(suffix)) name = name.slice(0, -suffix.length);
    }
    return name;
}

function editCartItemOptions(index) {
    const item = cart[index];
    if (!item || item.order_item_id) return;
    if (item.is_custom_item) return; // Custom items don't have product options

    fetch(`/pos/products?id=${item.product_id}`)
        .then(r => r.json())
        .then(data => {
            const product = (data.products || []).find(p => p.id === item.product_id) || (data.products || [])[0];
            if (!product) {
                if (typeof showToast === 'function') showToast('error', 'Could not load item options');
                return;
            }

            const variants = product.variants || [];
            const addons = product.addons || [];
            const optionSets = product.option_sets || [];
            const modifierSets = product.modifier_sets || [];
            const baseName = cartItemBaseName(item);
            const basePrice = Number(product.price ?? product.selling_price ?? item.base_price ?? item.price);
            const hasExtras = addons.length > 0 || optionSets.length > 0 || modifierSets.length > 0 || !!product.has_addons;

            editingCartIndex = index;
            selectedAddons = (item.addons || []).map(a => ({
                id: a.id,
                name: a.name,
                price: Number(a.price || 0),
                shared: a.shared !== false,
                hide_on_receipt: !!a.hide_on_receipt,
                group_id: a.group_id || null,
            }));
            selectedOptions = (item.options || []).map(o => ({
                id: o.id || o.option_id,
                name: o.name || o.option_name,
                option_set_id: o.option_set_id,
                option_set_name: o.option_set_name || o.set_name || 'Option',
            }));
            selectedVariant = item.variant_id
                ? {
                    id: item.variant_id,
                    name: item.variant_name || '',
                    price_adjustment: Number(item.variant_adj || 0),
                }
                : null;

            pendingProduct = {
                productId: item.product_id,
                name: baseName,
                price: basePrice,
                hasVariants: variants.length > 0,
                hasAddons: hasExtras,
            };

            showProductOptionsModal(
                item.product_id,
                baseName,
                basePrice,
                variants.length > 0,
                hasExtras,
                {
                    product,
                    instructions: item.special_instructions || '',
                    edit: true,
                }
            );
        })
        .catch(() => {
            if (typeof showToast === 'function') showToast('error', 'Could not load item options');
        });
}

function updateOptionsTotal() {
    if (!pendingProduct) return;
    const base = Number(pendingProduct.price || 0);
    const adj = selectedVariant ? Number(selectedVariant.price_adjustment || 0) : 0;
    const addonTotal = selectedAddons.reduce((sum, a) => sum + Number(a.price || 0), 0);
    const el = document.getElementById('addonTotal');
    if (el) el.textContent = `LKR ${(base + adj + addonTotal).toFixed(2)}`;
}

function selectVariant(variantId, name, priceAdjustment, element) {
    selectedVariant = { id: variantId, name, price_adjustment: Number(priceAdjustment || 0) };
    document.querySelectorAll('#variantList .variant-option').forEach(el => {
        el.style.background = '#fff';
        el.style.borderColor = '#e2e8f0';
        const radio = el.querySelector('input[type="radio"]');
        if (radio) radio.checked = false;
    });
    if (element) {
        element.style.background = '#fff7ed';
        element.style.borderColor = '#f59e0b';
        const radio = element.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }
    updateOptionsTotal();
}

function showProductOptionsModal(productId, name, price, hasVariants, hasAddons, opts = {}) {
    const isEdit = !!(opts.edit || editingCartIndex != null);
    document.getElementById('optionsModalTitle').innerHTML = `<i class="fas fa-${isEdit ? 'pen' : 'utensils'} me-2"></i>${name}`;

    const confirmBtn = document.getElementById('optionsConfirmBtn');
    if (confirmBtn) {
        confirmBtn.innerHTML = isEdit
            ? '<i class="fas fa-check me-2"></i>Update Item'
            : '<i class="fas fa-plus me-2"></i>Add to Cart';
    }

    let html = '';

    html += `<div class="mb-3 p-3 rounded-3" style="background: #f0fdf4;">
        <span class="text-muted">Base Price</span>
        <h5 class="mb-0 text-success fw-bold">LKR ${parseFloat(price).toFixed(2)}</h5>
    </div>`;

    if (hasVariants) {
        html += `<div class="mb-3">
            <label class="form-label fw-semibold text-muted">Select Variation</label>
            <div id="variantList" class="d-grid gap-2">Loading...</div>
        </div>`;
    }

    // Always reserve Options / Modifiers slots — shown after product API load.
    // Do not rely only on the product-card hasAddons flag (modifier sets can be missed).
    html += '<div class="mb-3 d-none" id="optionSetsSection"><label class="form-label fw-semibold text-muted">Options</label><div id="optionSetList" class="d-grid gap-2">Loading...</div></div>';
    html += '<div class="mb-3 d-none" id="modifiersSection"><label class="form-label fw-semibold text-muted">Select Modifiers</label><div id="addonList" class="d-grid gap-2">Loading...</div></div>';

    html += `<div class="mb-3">
        <label class="form-label fw-semibold text-muted">Special Instructions</label>
        <textarea id="specialInstructions" class="form-control" rows="2" placeholder="Any special requests..." style="border-radius: 12px; border: 1px solid #e2e8f0;">${escapeAttr(opts.instructions || '')}</textarea>
    </div>`;

    html += `<div class="p-3 rounded-3" style="background: #fff7ed;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Item total</span>
            <h4 class="mb-0 fw-bold" style="color: #f59e0b;" id="addonTotal">LKR ${parseFloat(price).toFixed(2)}</h4>
        </div>
    </div>`;

    document.getElementById('optionsModalBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('productOptionsModal')).show();

    const applyProductOptions = (product) => {
        if (!product) return;

        if (hasVariants) {
            const variants = product.variants || [];
            const list = document.getElementById('variantList');
            if (list) {
                if (!variants.length) {
                    list.innerHTML = '<p class="text-muted text-center mb-0">No variations available</p>';
                } else {
                    const prefId = selectedVariant?.id || null;
                    list.innerHTML = variants.map((v) => {
                        const adj = Number(v.price_adjustment || 0);
                        const finalPrice = Number(v.final_price != null ? v.final_price : (Number(price) + adj));
                        const safeName = String(v.name).replace(/'/g, "\\'");
                        const checked = prefId ? (v.id === prefId) : false;
                        return `<div class="variant-option d-flex align-items-center p-3 rounded-3 border cursor-pointer" style="background:#fff;border-color:#e2e8f0;"
                             onclick="selectVariant(${v.id}, '${safeName}', ${adj}, this)">
                            <input type="radio" name="productVariant" class="form-check-input me-3" style="width:20px;height:20px;" ${checked ? 'checked' : ''}>
                            <div class="flex-grow-1"><div class="fw-semibold">${v.name}</div></div>
                            <div class="fw-bold" style="color:#f59e0b;">LKR ${finalPrice.toFixed(2)}</div>
                        </div>`;
                    }).join('');

                    let pick = prefId ? variants.find(v => v.id === prefId) : variants[0];
                    if (!pick) pick = variants[0];
                    const el = [...list.querySelectorAll('.variant-option')].find((_, i) => variants[i].id === pick.id)
                        || list.querySelector('.variant-option');
                    selectVariant(pick.id, pick.name, pick.price_adjustment, el);
                }
            }
        }

        {
            const addons = product.addons || [];
            const sets = product.modifier_sets || [];
            const optionSets = product.option_sets || [];
            window._pendingModifierSets = sets;
            window._pendingOptionSets = optionSets;

            const optSection = document.getElementById('optionSetsSection');
            const optList = document.getElementById('optionSetList');
            if (optList && optSection) {
                if (!optionSets.length) {
                    optSection.classList.add('d-none');
                    optList.innerHTML = '';
                    selectedOptions = [];
                } else {
                    optSection.classList.remove('d-none');
                    const selectedBySet = {};
                    (selectedOptions || []).forEach(o => { selectedBySet[o.option_set_id] = o.id; });
                    optList.innerHTML = optionSets.map(set => {
                        const label = set.display_name || set.name;
                        const req = set.require_selection ? ' <span class="badge bg-warning text-dark">Required</span>' : '';
                        const rows = (set.options || []).map(o => {
                            const on = selectedBySet[set.id] ? (selectedBySet[set.id] === o.id) : false;
                            const safeName = String(o.name).replace(/'/g, "\\'");
                            const safeSet = String(label).replace(/'/g, "\\'");
                            const colorDot = (set.type === 'text_color' && o.color)
                                ? `<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${o.color};border:1px solid #ccc;margin-right:8px;"></span>`
                                : '';
                            return `<div class="d-flex align-items-center p-3 rounded-3 border cursor-pointer option-choice" style="background:${on ? '#ecfdf5' : '#fff'};border-color:${on ? '#10b981' : '#e2e8f0'};"
                                onclick="selectProductOption(${set.id}, '${safeSet}', ${o.id}, '${safeName}', this)">
                                <input type="radio" name="optset_${set.id}" class="form-check-input me-3" style="width:20px;height:20px;pointer-events:none;" ${on ? 'checked' : ''}>
                                ${colorDot}
                                <div class="flex-grow-1 fw-semibold">${o.name}</div>
                            </div>`;
                        }).join('');
                        return `<div class="mb-2 option-set-block" data-set-id="${set.id}" data-require="${set.require_selection ? 1 : 0}"><div class="small fw-semibold text-muted mb-1">${label}${req}</div><div class="d-grid gap-2">${rows}</div></div>`;
                    }).join('');
                    // Keep only still-valid selections
                    const validIds = new Set(optionSets.flatMap(s => (s.options || []).map(o => o.id)));
                    selectedOptions = (selectedOptions || []).filter(o => validIds.has(o.id));
                }
            }

            const modSection = document.getElementById('modifiersSection');
            const list = document.getElementById('addonList');
            if (list) {
                if (!addons.length && !sets.length) {
                    if (modSection) modSection.classList.add('d-none');
                    list.innerHTML = '';
                } else {
                    if (modSection) modSection.classList.remove('d-none');
                    const selectedIds = new Set(selectedAddons.map(a => a.id));
                    const isEditMode = selectedIds.size > 0;
                    const renderAddonRow = (addon, set) => {
                        const safeName = String(addon.name).replace(/'/g, "\\'");
                        const allowMulti = set ? (set.allow_multiple !== false) : true;
                        const groupId = set ? set.id : 0;
                        const hideReceipt = !!(set && set.hide_on_receipt) || !!addon.hide_on_receipt;
                        let on = selectedIds.has(addon.id);
                        if (!isEditMode && !on && addon.is_preselected) on = true;
                        const inputType = allowMulti ? 'checkbox' : 'radio';
                        const priceLabel = Number(addon.price) > 0
                            ? `+LKR ${parseFloat(addon.price).toFixed(2)}`
                            : 'LKR 0.00';
                        return `<div class="d-flex align-items-center p-3 rounded-3 border cursor-pointer modifier-option" data-group-id="${groupId}" data-allow-multiple="${allowMulti ? 1 : 0}" style="background:${on ? '#ecfdf5' : '#fff'};border-color:${on ? '#10b981' : '#e2e8f0'};"
                             onclick="toggleAddon(${addon.id}, '${safeName}', ${addon.price}, this, true, ${groupId}, ${hideReceipt ? 'true' : 'false'}, ${allowMulti ? 'true' : 'false'})">
                            <input type="${inputType}" name="modset_${groupId}" class="form-check-input me-3" style="width:20px;height:20px;cursor:pointer;pointer-events:none;" ${on ? 'checked' : ''}>
                            <div class="flex-grow-1"><div class="fw-semibold">${addon.name}</div></div>
                            <div class="fw-bold" style="color:#f59e0b;">${priceLabel}</div>
                        </div>`;
                    };

                    if (sets.length) {
                        const shown = new Set();
                        let htmlSets = sets.map(set => {
                            const label = set.display_name || set.name;
                            const req = set.require_selection ? ' <span class="badge bg-warning text-dark">Required</span>' : '';
                            const rows = (set.addons || []).map(a => {
                                shown.add(a.id);
                                return renderAddonRow(a, set);
                            }).join('');
                            return `<div class="mb-2 modifier-set-block" data-set-id="${set.id}" data-require="${set.require_selection ? 1 : 0}" data-allow-multiple="${set.allow_multiple !== false ? 1 : 0}"><div class="small fw-semibold text-muted mb-1">${label}${req}</div><div class="d-grid gap-2">${rows}</div></div>`;
                        }).join('');
                        const extras = addons.filter(a => !shown.has(a.id));
                        if (extras.length) {
                            htmlSets += `<div class="mb-2"><div class="small fw-semibold text-muted mb-1">Other modifiers</div><div class="d-grid gap-2">${extras.map(a => renderAddonRow(a, null)).join('')}</div></div>`;
                        }
                        list.innerHTML = htmlSets;
                    } else {
                        list.innerHTML = addons.map(a => renderAddonRow(a, null)).join('');
                    }

                    // Sync selectedAddons from checked rows (includes preselect)
                    selectedAddons = [];
                    list.querySelectorAll('.modifier-option').forEach(el => {
                        const input = el.querySelector('input');
                        if (!input || !input.checked) return;
                        const onclick = el.getAttribute('onclick') || '';
                        const m = onclick.match(/toggleAddon\((\d+),\s*'((?:\\'|[^'])*)',\s*([-\d.]+),\s*this,\s*(true|false),\s*(\d+),\s*(true|false)/);
                        if (m) {
                            selectedAddons.push({
                                id: Number(m[1]),
                                name: m[2].replace(/\\'/g, "'"),
                                price: Number(m[3]),
                                shared: true,
                                group_id: Number(m[5]) || null,
                                hide_on_receipt: m[6] === 'true',
                            });
                        }
                    });
                }
            }
            updateOptionsTotal();
        }
    };

    if (opts.product) {
        applyProductOptions(opts.product);
    } else {
        // Use embedded card data immediately so modifiers show even if API is stale/cached
        if (opts.productSeed) {
            applyProductOptions(opts.productSeed);
        }
        fetch(`/pos/products?id=${productId}&_=${Date.now()}`)
            .then(r => r.json())
            .then(data => {
                const product = (data.products || []).find(p => p.id === productId) || (data.products || [])[0];
                if (!product) return;
                // Merge: prefer API variants; keep modifiers from API if present else seed
                const seed = opts.productSeed || {};
                const merged = {
                    ...seed,
                    ...product,
                    variants: product.variants || [],
                    modifier_sets: (product.modifier_sets && product.modifier_sets.length)
                        ? product.modifier_sets
                        : (seed.modifier_sets || []),
                    option_sets: (product.option_sets && product.option_sets.length)
                        ? product.option_sets
                        : (seed.option_sets || []),
                    addons: (product.addons && product.addons.length)
                        ? product.addons
                        : (seed.addons || []),
                };
                if (typeof console !== 'undefined') {
                    console.log('[Amore POS] product options', {
                        id: productId,
                        modifier_sets: (merged.modifier_sets || []).length,
                        option_sets: (merged.option_sets || []).length,
                        addons: (merged.addons || []).length,
                        extras_map: !!(window.__POS_PRODUCT_EXTRAS && window.__POS_PRODUCT_EXTRAS[productId]),
                    });
                }
                window.__POS_PRODUCT_EXTRAS = window.__POS_PRODUCT_EXTRAS || {};
                window.__POS_PRODUCT_EXTRAS[productId] = {
                    modifier_sets: merged.modifier_sets || [],
                    option_sets: merged.option_sets || [],
                    addons: merged.addons || [],
                    variants: merged.variants || [],
                };
                applyProductOptions(merged);
            })
            .catch(() => {
                if (!opts.productSeed) {
                    const v = document.getElementById('variantList');
                    const a = document.getElementById('addonList');
                    if (v) v.innerHTML = '<p class="text-muted text-center mb-0">Failed to load variations</p>';
                    if (a) a.innerHTML = '<p class="text-muted text-center mb-0">Failed to load modifiers</p>';
                    updateOptionsTotal();
                }
            });
    }
}

function escapeAttr(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function selectProductOption(setId, setName, optionId, optionName, element) {
    const block = element.closest('.option-set-block');
    if (block) {
        block.querySelectorAll('.option-choice').forEach(el => {
            el.style.background = '#fff';
            el.style.borderColor = '#e2e8f0';
            const inp = el.querySelector('input');
            if (inp) inp.checked = false;
        });
    }
    element.style.background = '#ecfdf5';
    element.style.borderColor = '#10b981';
    const inp = element.querySelector('input');
    if (inp) inp.checked = true;

    selectedOptions = (selectedOptions || []).filter(o => Number(o.option_set_id) !== Number(setId));
    selectedOptions.push({
        id: optionId,
        option_id: optionId,
        name: optionName,
        option_name: optionName,
        option_set_id: setId,
        option_set_name: setName,
    });
}

function toggleAddon(addonId, name, price, element, shared = true, groupId = 0, hideOnReceipt = false, allowMultiple = true) {
    const checkbox = element.querySelector('input');
    if (!checkbox) return;

    if (!allowMultiple) {
        // Single-select within this set: clear others in the same group
        const block = element.closest('.modifier-set-block') || element.parentElement;
        (block ? block.querySelectorAll('.modifier-option') : []).forEach(el => {
            if (el === element) return;
            const inp = el.querySelector('input');
            if (inp) inp.checked = false;
            el.style.background = '#fff';
            el.style.borderColor = '#e2e8f0';
        });
        selectedAddons = selectedAddons.filter(a => Number(a.group_id || 0) !== Number(groupId || 0));
        checkbox.checked = true;
    } else {
        if (document.activeElement !== checkbox) {
            checkbox.checked = !checkbox.checked;
        }
    }

    const isSelected = checkbox.checked;

    if (isSelected) {
        element.style.background = '#ecfdf5';
        element.style.borderColor = '#10b981';
        selectedAddons = selectedAddons.filter(a => a.id !== addonId);
        selectedAddons.push({
            id: addonId,
            name,
            price: Number(price),
            shared: !!shared,
            group_id: groupId || null,
            hide_on_receipt: !!hideOnReceipt,
        });
    } else {
        element.style.background = '#fff';
        element.style.borderColor = '#e2e8f0';
        selectedAddons = selectedAddons.filter(a => a.id !== addonId);
    }

    updateOptionsTotal();
}

function confirmAddToCart() {
    if (!pendingProduct) return;

    if (pendingProduct.hasVariants && !selectedVariant) {
        if (typeof showToast === 'function') showToast('warning', 'Select a variation');
        return;
    }

    const sets = window._pendingModifierSets || [];
    for (const set of sets) {
        if (!set.require_selection) continue;
        const ids = new Set((set.addons || []).map(a => a.id));
        const picked = selectedAddons.some(a => ids.has(a.id));
        if (!picked) {
            const label = set.display_name || set.name || 'modifiers';
            if (typeof showToast === 'function') showToast('warning', 'Select required: ' + label);
            return;
        }
        if (set.allow_multiple === false) {
            const count = selectedAddons.filter(a => ids.has(a.id)).length;
            if (count > 1) {
                if (typeof showToast === 'function') showToast('warning', 'Only one option allowed in ' + (set.display_name || set.name));
                return;
            }
        }
    }

    const optionSets = window._pendingOptionSets || [];
    for (const set of optionSets) {
        if (!set.require_selection) continue;
        const picked = (selectedOptions || []).some(o => Number(o.option_set_id) === Number(set.id));
        if (!picked) {
            if (typeof showToast === 'function') showToast('warning', 'Select required: ' + (set.display_name || set.name));
            return;
        }
    }

    const instructions = document.getElementById('specialInstructions')?.value || '';
    const basePrice = Number(pendingProduct.price);
    const adj = selectedVariant ? Number(selectedVariant.price_adjustment || 0) : 0;
    const unitPrice = basePrice + adj;
    const productId = pendingProduct.productId;
    const displayName = selectedVariant
        ? `${pendingProduct.name} (${selectedVariant.name})`
        : pendingProduct.name;

    // Edit existing cart line
    if (editingCartIndex != null && cart[editingCartIndex] && !cart[editingCartIndex].order_item_id) {
        const item = cart[editingCartIndex];
        item.product_id = productId;
        item.base_name = pendingProduct.name;
        item.base_price = basePrice;
        item.name = displayName;
        item.price = unitPrice;
        item.variant_id = selectedVariant?.id || null;
        item.variant_name = selectedVariant?.name || null;
        item.addons = [...selectedAddons];
        item.options = [...(selectedOptions || [])];
        item.has_options = !!(selectedVariant || selectedAddons.length || (selectedOptions || []).length);
        item.special_instructions = instructions;

        updateCart();
        bootstrap.Modal.getInstance(document.getElementById('productOptionsModal'))?.hide();

        if (typeof showToast === 'function') showToast('success', displayName + ' updated');
        setTimeout(() => focusProductSearch(true), 30);

        pendingProduct = null;
        selectedAddons = [];
        selectedOptions = [];
        selectedVariant = null;
        editingCartIndex = null;
        return;
    }

    const existing = cart.find(item =>
        !item.order_item_id &&
        item.product_id === productId &&
        JSON.stringify(item.addons || []) === JSON.stringify(selectedAddons) &&
        JSON.stringify(item.options || []) === JSON.stringify(selectedOptions || []) &&
        (item.variant_id || null) === (selectedVariant?.id || null) &&
        (item.special_instructions || '') === instructions
    );

    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            product_id: productId,
            category_id: pendingProduct?.category_id || productCategoryId(productId),
            base_name: pendingProduct.name,
            base_price: basePrice,
            name: displayName,
            price: unitPrice,
            quantity: 1,
            variant_id: selectedVariant?.id || null,
            variant_name: selectedVariant?.name || null,
            variant_adj: selectedVariant ? Number(selectedVariant.price_adjustment || 0) : 0,
            addons: [...selectedAddons],
            options: [...(selectedOptions || [])],
            discount: 0,
            has_options: !!(selectedVariant || selectedAddons.length || (selectedOptions || []).length),
            special_instructions: instructions,
            loyalty_free: false,
        });
    }

    updateCart();
    bootstrap.Modal.getInstance(document.getElementById('productOptionsModal')).hide();

    if (typeof showToast === 'function') {
        const extras = [];
        if (selectedVariant) extras.push(selectedVariant.name);
        if ((selectedOptions || []).length) extras.push(`${selectedOptions.length} option${selectedOptions.length > 1 ? 's' : ''}`);
        if (selectedAddons.length) extras.push(`${selectedAddons.length} add-on${selectedAddons.length > 1 ? 's' : ''}`);
        showToast('success', displayName + ' added' + (extras.join(', ') ? ` (${extras.join(', ')})` : ''));
    }

    pendingProduct = null;
    selectedAddons = [];
    selectedOptions = [];
    selectedVariant = null;
    editingCartIndex = null;
    setTimeout(() => focusProductSearch(true), 30);
}

function addToCart(productId, name, price, hasVariants, hasAddons, options = {}) {
    const keepSearchFocus = options.keepSearchFocus !== false;
    const existing = cart.find(item =>
        !item.order_item_id &&
        item.product_id === productId &&
        !item.variant_id &&
        !(item.addons || []).length &&
        !item.has_options
    );
    if (existing && !existing.has_options) {
        existing.quantity += 1;
        updateCart();
        if (typeof showToast === 'function') showToast('success', name + ' added (Qty: ' + existing.quantity + ')');
        if (keepSearchFocus) {
            setTimeout(() => focusProductSearch(true), 30);
        } else {
            focusCartQty(cart.indexOf(existing));
        }
        return;
    }
    cart.push({
        product_id: productId,
        category_id: productCategoryId(productId),
        base_name: name,
        base_price: Number(price),
        name: name,
        price: price,
        quantity: 1,
        variant_id: null,
        variant_name: null,
        variant_adj: 0,
        addons: [],
        discount: 0,
        has_options: hasVariants || hasAddons,
        special_instructions: '',
        loyalty_free: false,
        is_comp: false,
        comp_reason: '',
    });
    updateCart();
    if (typeof showToast === 'function') showToast('success', name + ' added to cart');
    if (keepSearchFocus) {
        setTimeout(() => focusProductSearch(true), 30);
    } else {
        focusCartQty(cart.length - 1);
    }
}

function getCartItemsHTML() {
    if (cart.length === 0) {
        return '<div id="emptyCart" class="text-center py-3 text-muted"><i class="fas fa-utensils fa-2x mb-2 opacity-25"></i><p class="mb-0 small">Tap a product to add</p></div>';
    }
    return cart.map((item, index) => {
        const locked = !!item.order_item_id;
        const addonTotal = (item.addons || []).reduce((s, a) => s + a.price, 0);
        return `
        <div class="cart-item ${locked ? 'cart-item-locked' : ''} ${item.loyalty_free ? 'cart-item-loyalty-free' : ''}">
            <div class="flex-grow-1">
                <div class="fw-medium">${item.name}${item.is_comp ? ' <span class="badge bg-dark" style="font-size:0.65rem;">COMP</span>' : ''}${item.loyalty_free ? ' <span class="badge bg-success" style="font-size:0.65rem;">FREE</span>' : ''}${locked && !item.loyalty_free && !item.is_comp ? ' <span class="badge bg-secondary" style="font-size:0.65rem;">On bill</span>' : (!locked && !item.loyalty_free && !item.is_comp ? ' <span class="badge bg-success" style="font-size:0.65rem;">New</span>' : '')}</div>
                ${item.variant_name ? `<div class="text-muted small">${item.variant_name}</div>` : ''}
                ${(item.options || []).length ? item.options.map(o => `<div class="text-muted small">${o.option_set_name || 'Option'}: ${o.name || o.option_name}</div>`).join('') : ''}
                ${(item.addons || []).length ? item.addons.map(a => `<div class="text-muted small">+ ${a.name}</div>`).join('') : ''}
                ${item.special_instructions ? `<div class="text-muted small"><i class="fas fa-comment-dots me-1"></i>${item.special_instructions}</div>` : ''}
                ${(item.discount || 0) > 0 && !item.is_comp ? `<div class="text-danger small mt-1"><i class="fas fa-percent me-1"></i>Item discount −${currencySymbol} ${Number(item.discount).toFixed(2)}</div>` : ''}
                <div class="text-primary fw-bold mt-1">${item.is_comp ? '<span class="text-dark">COMP</span>' : (item.loyalty_free ? '<span class="text-success">FREE</span> <span class="text-decoration-line-through text-muted small">' + currencySymbol + ' ' + Number(item.original_price || 0).toFixed(2) + '</span>' : (currencySymbol + ' ' + ((item.price + addonTotal) * item.quantity - (item.discount || 0)).toFixed(2)))}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                ${canPosComp && !item.loyalty_free ? `<button type="button" class="qty-btn ${item.is_comp ? 'has-discount' : ''}" title="Comp (free / not discount)" onclick="toggleCartComp(${index})"><i class="fas fa-gift" style="font-size:0.65rem;"></i></button>` : ''}
                ${!item.loyalty_free && !item.is_comp ? `<button type="button" class="qty-btn qty-btn-discount ${(item.discount || 0) > 0 ? 'has-discount' : ''}" title="Item discount" onclick="applyItemDiscount(${index})"><i class="fas fa-percent" style="font-size:0.65rem;"></i></button>` : ''}
                ${!item.is_custom_item && !locked ? `<button type="button" class="qty-btn qty-btn-edit" title="Edit size / add-ons" onclick="editCartItemOptions(${index})"><i class="fas fa-pen" style="font-size:0.65rem;"></i></button>` : ''}
                <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                <input type="number"
                       class="cart-qty-input fw-bold"
                       data-index="${index}"
                       value="${item.quantity}"
                       min="0.001"
                       step="1"
                       inputmode="decimal"
                       onfocus="this.select()"
                       onkeydown="onCartQtyKeydown(event, ${index})"
                       onblur="setCartQtyFromInput(${index}, this.value, this)">
                <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                <button class="btn btn-link text-danger p-0 ms-1" onclick="removeItem(${index})"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>`;
    }).join('');
}

async function toggleCartComp(index) {
    if (!canPosComp) {
        showToast('error', 'Not allowed to comp');
        return;
    }
    const item = cart[index];
    if (!item) return;
    if (item.is_comp) {
        item.is_comp = false;
        item.comp_reason = '';
        updateCart();
        return;
    }
    let reason = 'Comp';
    if (typeof Swal !== 'undefined') {
        const { value, isConfirmed } = await Swal.fire({
            title: 'Comp this item?',
            input: 'text',
            inputLabel: 'Reason',
            inputValue: 'Comp',
            showCancelButton: true,
            confirmButtonText: 'Comp',
        });
        if (!isConfirmed) return;
        reason = (value || 'Comp').trim() || 'Comp';
    }
    item.is_comp = true;
    item.comp_reason = reason;
    item.discount = 0;
    updateCart();
}

function getNewCartItems() {
    return cart.filter(item => !item.order_item_id);
}

function cartSignature() {
    const lines = cart.map(item => [
        item.order_item_id || 'new',
        item.product_id || 0,
        item.name,
        Number(item.quantity),
        Number(item.price),
        Number(item.discount || 0),
        item.special_instructions || '',
    ].join('|')).join(';;');

    return [lines, billDiscount, billDiscountType, orderNotes].join('##');
}

/** True when the cart no longer matches the order as stored (items, discount or notes). */
function hasUnsavedBillEdits() {
    return !!openBillId && cartSignature() !== openBillSignature;
}

function updateOpenBillUI() {
    const banners = document.querySelectorAll('.open-bill-banner');
    banners.forEach(el => {
        if (openBillId) {
            el.classList.remove('d-none');
            el.querySelector('.open-bill-label').textContent = 'Editing ' + openBillNumber;
        } else {
            el.classList.add('d-none');
        }
    });

    const placeBtns = document.querySelectorAll('.place-order-btn, .mobile-place-order-btn');
    const type = getActiveOrderType();
    let showPlace = openBillId
        || type === 'dine_in'
        || type === 'takeaway'
        || type === 'express'
        || (type === 'delivery' && (deliveryPayMode === 'cod' || deliveryPayMode === 'partner'));
    // Bakery direct billing: pay & finish only — no Place Order / open table bills
    if (isBakeryUi && bakeryDirectBilling && !openBillId && !settleOrderId) {
        showPlace = false;
    }
    placeBtns.forEach(btn => {
        btn.classList.toggle('d-none', !showPlace);
        let iconLabel = '<i class="fas fa-utensils me-2"></i>Place Order' + shortcutSuffix('place_order');
        if (openBillId) {
            iconLabel = '<i class="fas fa-plus me-2"></i>Update Bill' + shortcutSuffix('place_order');
        } else if (type === 'delivery' && deliveryPayMode === 'partner') {
            iconLabel = '<i class="fas fa-motorcycle me-2"></i>Place Partner Order' + shortcutSuffix('place_order');
        } else if (type === 'delivery' && deliveryPayMode === 'cod') {
            iconLabel = '<i class="fas fa-motorcycle me-2"></i>Place COD Order' + shortcutSuffix('place_order');
        }
        btn.innerHTML = iconLabel;
        if (type === 'delivery' && (deliveryPayMode === 'cod' || deliveryPayMode === 'partner') && !openBillId) {
            btn.setAttribute('onclick', 'placeDeliveryCodOrder()');
        } else {
            btn.setAttribute('onclick', 'placeDineInOrder()');
        }
    });

    // Pay Now visibility / label
    document.querySelectorAll('.pay-btn').forEach(btn => {
        if (type === 'delivery' && (deliveryPayMode === 'partner' || deliveryPayMode === 'cod') && !openBillId && !settleOrderId) {
            btn.classList.add('d-none');
            return;
        }
        btn.classList.remove('d-none');
        if (type === 'delivery' && deliveryPayMode === 'prepaid') {
            btn.innerHTML = '<i class="fas fa-university me-2"></i>Pay Now (Bank/Cash)' + shortcutSuffix('pay_now');
        } else {
            btn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now' + shortcutSuffix('pay_now');
        }
    });

    if (typeof refreshOpenBillsBadge === 'function') {
        refreshOpenBillsBadge();
    } else {
        document.querySelectorAll('.open-bills-btn').forEach(btn => {
            btn.innerHTML = '<i class="fas fa-receipt me-2"></i>Open Bills' + shortcutSuffix('open_bills');
        });
    }

    updateTransferTableBtn();
}

function exitOpenBill(clearItems = true) {
    openBillId = null;
    openBillNumber = null;
    openBillSignature = '';
    settleOrderId = null;
    settleTotal = 0;
    if (clearItems) {
        cart = [];
        billDiscount = 0;
        orderNotes = '';
        if (typeof paymentLines !== 'undefined') paymentLines = [];
    }
    updateOpenBillUI();
    updateCart();
    if (clearItems) {
        clearCustomerDisplay();
        clearTakeawayCustomerFields();
    }
}

function updateCart() {
    const container = document.getElementById('cartBodyContainer');
    const mobileContainer = document.getElementById('mobileCartBody');
    const countBadge = document.getElementById('cartCount');
    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (countBadge) countBadge.textContent = itemCount;

    const html = getCartItemsHTML();
    if (container) container.innerHTML = html;
    if (mobileContainer) mobileContainer.innerHTML = html;

    const mobileCartCount = document.getElementById('mobileCartCount');
    if (mobileCartCount) mobileCartCount.textContent = itemCount + ' item' + (itemCount !== 1 ? 's' : '');

    calculateTotals();
    broadcastCart();
}

function clearCustomerDisplay() {
    const seq = ++cartBroadcastSeq;
    if (typeof pushAnalogLedFromCart === 'function') {
        pushAnalogLedFromCart({ active: false, items: [], totals: { total: 0 }, payment: {} });
    }
    return fetch('/pos/cart/broadcast', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            seq,
            active: false,
            items: [],
            totals: { subtotal: 0, tax: 0, discount: 0, total: 0 },
            meta: {},
            payment: { paying: 0, change: 0, balance: 0 },
        }),
        keepalive: true,
    }).catch(() => {});
}

/* ---- Analog rear LED (physical POS customer display via Web Serial) ---- */
const AnalogLed = {
    port: null,
    writer: null,
    connecting: false,
    lastPayload: '',

    enabled() {
        return String(customerDisplayMode || '') === 'analog';
    },

    supported() {
        return typeof navigator !== 'undefined' && !!navigator.serial;
    },

    setStatus(connected) {
        const label = document.getElementById('analogLedConnectLabel');
        const btn = document.getElementById('analogLedConnectBtn');
        if (label) label.textContent = connected ? 'LED ON' : 'LED';
        if (btn) {
            btn.style.background = connected ? 'rgba(16,185,129,0.45)' : 'rgba(255,255,255,0.15)';
            btn.title = connected
                ? 'Rear LED connected — click to reconnect'
                : 'Connect rear LED customer display';
        }
    },

    amt(n) {
        return Number(n || 0).toFixed(2);
    },

    pad(str, len) {
        const s = String(str || '').slice(0, len);
        return s + ' '.repeat(Math.max(0, len - s.length));
    },

    buildBytes(state) {
        const protocol = String(customerDisplayProtocol || 'plain');
        const total = this.amt(state.total);
        const price = this.amt(state.price);
        const change = this.amt(state.change);
        const paying = this.amt(state.paying);
        const mode = state.mode || 'cart';
        const enc = new TextEncoder();

        // Single-line rear LEDs (show 0.00) — send the amount customers should see
        if (protocol === 'plain') {
            let value = total;
            if (mode === 'idle' || mode === 'thanks') value = '0.00';
            else if (mode === 'pay' && Number(state.change) > 0) value = change;
            else if (mode === 'pay' && Number(state.paying) > 0 && Number(state.change) <= 0) value = paying;
            // Many LEDs expect right-aligned 8-char field + CR
            const line = value.padStart(8, ' ') + '\r\n';
            return enc.encode(line);
        }

        if (protocol === 'dsp800') {
            const line1 = this.pad(
                mode === 'pay' && Number(state.change) > 0
                    ? 'CHANGE'
                    : (state.itemName || 'TOTAL').toUpperCase(),
                20
            );
            const line2 = this.pad(
                mode === 'pay' && Number(state.change) > 0 ? change : total,
                20
            );
            return enc.encode('\x0C' + line1 + line2);
        }

        // ESC/POS customer display (common VFD)
        const l1 = this.pad('PRICE ' + price, 20);
        const l2 = this.pad(
            mode === 'pay' && Number(state.change) > 0 ? ('CHANGE ' + change) : ('TOTAL ' + total),
            20
        );
        return enc.encode('\x1B\x40\x0C' + l1 + l2);
    },

    async openPort(port) {
        await port.open({
            baudRate: Number(customerDisplayBaud) || 9600,
            dataBits: 8,
            stopBits: 1,
            parity: 'none',
            flowControl: 'none',
        });
        this.port = port;
        this.writer = port.writable.getWriter();
        this.setStatus(true);
    },

    async connect(forcePicker = false) {
        if (!this.enabled()) return false;
        if (!this.supported()) {
            if (typeof showToast === 'function') {
                showToast('warning', 'Use Chrome or Edge on this POS PC to connect the rear LED');
            }
            return false;
        }
        if (this.connecting) return false;
        this.connecting = true;
        try {
            if (this.writer) {
                try { this.writer.releaseLock(); } catch (e) {}
                this.writer = null;
            }
            if (this.port) {
                try { await this.port.close(); } catch (e) {}
                this.port = null;
            }

            let port = null;
            if (!forcePicker) {
                const ports = await navigator.serial.getPorts();
                if (ports.length === 1) port = ports[0];
                else if (ports.length > 1) {
                    // Prefer previously used; otherwise ask
                    port = ports[0];
                }
            }
            if (!port) {
                port = await navigator.serial.requestPort();
            }
            await this.openPort(port);
            await this.writeState({
                mode: 'idle',
                total: 0,
                price: 0,
                change: 0,
                paying: 0,
                itemName: '',
            });
            if (typeof showToast === 'function') showToast('success', 'Rear LED connected');
            // Refresh with current cart if any
            if (typeof broadcastCart === 'function' && cart.length) broadcastCart();
            return true;
        } catch (err) {
            this.setStatus(false);
            if (err && err.name === 'NotFoundError') {
                // user cancelled picker
            } else if (typeof showToast === 'function') {
                showToast('error', 'LED connect failed: ' + (err.message || 'check COM/USB driver'));
            }
            return false;
        } finally {
            this.connecting = false;
        }
    },

    async ensureConnected() {
        if (!this.enabled() || !this.supported()) return false;
        if (this.writer) return true;
        try {
            const ports = await navigator.serial.getPorts();
            if (!ports.length) return false;
            await this.openPort(ports[0]);
            return true;
        } catch (e) {
            return false;
        }
    },

    async writeState(state) {
        if (!this.enabled()) return;
        const ok = await this.ensureConnected();
        if (!ok || !this.writer) return;
        const bytes = this.buildBytes(state);
        const key = Array.from(bytes).join(',');
        if (key === this.lastPayload) return;
        this.lastPayload = key;
        try {
            await this.writer.write(bytes);
        } catch (e) {
            this.lastPayload = '';
            this.setStatus(false);
            try { this.writer.releaseLock(); } catch (err) {}
            this.writer = null;
            try { if (this.port) await this.port.close(); } catch (err) {}
            this.port = null;
        }
    },

    fromCartPayload(payload) {
        const active = !!(payload && payload.active);
        const items = (payload && payload.items) || [];
        const totals = (payload && payload.totals) || {};
        const payment = (payload && payload.payment) || {};
        if (!active || !items.length) {
            return {
                mode: 'idle',
                total: 0,
                price: 0,
                change: 0,
                paying: 0,
                itemName: '',
            };
        }
        const last = items[items.length - 1];
        const paying = Number(payment.paying || 0);
        const change = Number(payment.change || 0);
        return {
            mode: paying > 0 ? 'pay' : 'cart',
            total: Number(totals.total || 0),
            price: Number(last.lineTotal ?? last.unitPrice ?? 0),
            change,
            paying,
            itemName: String(last.name || ''),
        };
    },
};

function connectAnalogLedDisplay() {
    return AnalogLed.connect(true);
}

function testAnalogLedDisplay() {
    if (!AnalogLed.enabled()) {
        if (typeof showToast === 'function') showToast('info', 'Set Customer Display Type to Analog in Settings → POS');
        return;
    }
    AnalogLed.connect(false).then((ok) => {
        if (!ok && !AnalogLed.writer) {
            return AnalogLed.connect(true);
        }
        return ok;
    }).then(() => AnalogLed.writeState({
        mode: 'cart',
        total: 123.45,
        price: 123.45,
        change: 0,
        paying: 0,
        itemName: 'TEST',
    })).then(() => {
        if (typeof showToast === 'function') showToast('success', 'Sent 123.45 to LED — check rear screen');
    });
}

function pushAnalogLedFromCart(payload) {
    if (!AnalogLed.enabled()) return;
    AnalogLed.writeState(AnalogLed.fromCartPayload(payload));
}

let broadcastCartTimer = null;
function broadcastCart(immediate) {
    if (immediate === true) {
        clearTimeout(broadcastCartTimer);
        return broadcastCartNow();
    }
    clearTimeout(broadcastCartTimer);
    broadcastCartTimer = setTimeout(broadcastCartNow, 220);
}
function broadcastCartNow() {
    const hasItems = cart.length > 0;
    if (!hasItems) {
        return clearCustomerDisplay();
    }

    const seq = ++cartBroadcastSeq;

    let subtotal = cart.reduce((sum, item) => {
        const addonTotal = (item.addons || []).reduce((s, a) => s + a.price, 0);
        return sum + ((item.price + addonTotal) * item.quantity) - (item.discount || 0);
    }, 0);
    let discountAmt = billDiscountType === 'percentage' ? subtotal * (billDiscount / 100) : billDiscount;
    discountAmt = Math.max(0, Number(discountAmt) || 0);
    let afterDiscount = Math.max(0, subtotal - discountAmt);
    let effectiveTaxRate = (typeof taxEnabled !== 'undefined' && taxEnabled) ? taxRate : 0;
    let tax = afterDiscount * (effectiveTaxRate / 100);
    let serviceCharge = (typeof serviceChargeEnabled !== 'undefined' && serviceChargeEnabled)
        ? afterDiscount * ((serviceChargeRate || 0) / 100)
        : 0;
    let total = afterDiscount + tax + serviceCharge;

    const totals = {
        subtotal: Math.round(subtotal * 100) / 100,
        tax: Math.round(tax * 100) / 100,
        discount: Math.round(discountAmt * 100) / 100,
        total: Math.round(total * 100) / 100,
    };

    const items = cart.map(item => {
        const addonTotal = (item.addons || []).reduce((s, a) => s + a.price, 0);
        const discount = Number(item.discount || 0);
        return {
            name: item.name,
            qty: item.quantity,
            unitPrice: item.price + addonTotal,
            discount,
            lineTotal: ((item.price + addonTotal) * item.quantity) - discount
        };
    });

    const custEl = document.getElementById('customerSelect');
    let customerName = 'Walk-in Customer';
    if (custEl && custEl.value) {
        const opt = custEl.options[custEl.selectedIndex];
        const label = (opt?.text || '').trim();
        if (label && !/^select/i.test(label) && label !== '—') customerName = label.split('—')[0].trim() || label;
    }

    const tableEl = document.getElementById('tableSelect');
    let tableName = null;
    if (tableEl && tableEl.value) {
        const opt = tableEl.options[tableEl.selectedIndex];
        tableName = (opt?.text || '').trim() || null;
        const label = document.getElementById('selectedTableLabel');
        if (label && label.textContent.trim()) tableName = label.textContent.trim();
    }

    const orderType = (typeof getActiveOrderType === 'function') ? getActiveOrderType() : 'dine_in';

    let paying = 0;
    if (typeof paymentLines !== 'undefined' && paymentLines.length) {
        paying = paymentLines.reduce((s, p) => s + Number(p.amount || 0), 0);
    } else {
        paying = parseFloat(document.getElementById('cashReceived')?.value || 0) || 0;
    }
    const change = Math.max(0, Math.round((paying - totals.total) * 100) / 100);
    const balance = Math.max(0, Math.round((totals.total - paying) * 100) / 100);

    const payload = {
        seq,
        items,
        totals,
        meta: {
            customer: customerName,
            order_type: orderType,
            table: orderType === 'dine_in' ? tableName : null,
            invoice: openBillNumber || null,
        },
        payment: { paying, change, balance },
        active: true,
    };

    if (typeof pushAnalogLedFromCart === 'function') {
        pushAnalogLedFromCart(payload);
    }

    return fetch('/pos/cart/broadcast', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(payload)
    }).catch(() => {});
}

function openMobileCart() {
    const drawer = document.getElementById('mobileCartDrawer');
    const backdrop = document.getElementById('mobileCartBackdrop');
    const mobileSlot = document.getElementById('mobileMetaSlot');
    const desktopSlot = document.getElementById('desktopMetaSlot');
    if (mobileSlot && desktopSlot) {
        while (desktopSlot.firstChild) mobileSlot.appendChild(desktopSlot.firstChild);
    }
    if (drawer) drawer.classList.add('open');
    if (backdrop) backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeMobileCart() {
    const drawer = document.getElementById('mobileCartDrawer');
    const backdrop = document.getElementById('mobileCartBackdrop');
    const mobileSlot = document.getElementById('mobileMetaSlot');
    const desktopSlot = document.getElementById('desktopMetaSlot');
    if (mobileSlot && desktopSlot) {
        while (mobileSlot.firstChild) desktopSlot.appendChild(mobileSlot.firstChild);
    }
    if (drawer) drawer.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
    document.body.style.overflow = '';
}

function updateQty(index, delta) {
    if (!cart[index]) return;
    const nextQty = cart[index].quantity + delta;
    if (nextQty <= 0) {
        removeItem(index);
        return;
    }
    cart[index].quantity = nextQty;
    updateCart();
}

function removeItem(index) {
    const item = cart[index];
    if (!item) return;

    // Lines already on the bill were sent to the kitchen — confirm before dropping them
    if (item.order_item_id) {
        const targetId = item.order_item_id;
        Swal.fire({
            title: 'Remove from bill?',
            text: `"${item.name}" is already on ${openBillNumber || 'this bill'} and will be removed when you save.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Keep',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
        }).then(result => {
            if (!result.isConfirmed) {
                updateCart();
                return;
            }
            const idx = cart.findIndex(line => line.order_item_id === targetId);
            if (idx > -1) dropCartLine(idx);
        });
        return;
    }

    dropCartLine(index);
}

function dropCartLine(index) {
    if (cart[index]?.loyalty_free) {
        pendingLoyaltyRedeem = false;
        loyaltyRedeemValue = 0;
        if (currentLoyalty) {
            currentLoyalty.free_drinks = Number(currentLoyalty.free_drinks || 0) + 1;
            currentLoyalty.can_redeem = true;
            renderLoyaltyPanel(currentLoyalty);
        }
    }
    cart.splice(index, 1);
    updateCart();
}

function clearCart() {
    if (!cart.length && !openBillId) return;
    const wasOpenBill = !!openBillId;
    Swal.fire({
        title: wasOpenBill ? 'Close Bill?' : 'Clear Cart?',
        text: wasOpenBill ? 'Exit this unpaid bill without paying. The bill stays open.' : 'All items will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: wasOpenBill ? 'Yes, Close' : 'Yes, Clear',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
    }).then((result) => {
        if (result.isConfirmed) {
            exitOpenBill(true);
            showToast('success', wasOpenBill ? 'Returned to new sale' : 'Cart cleared');
        }
    });
}

function calculateTotals() {
    let subtotal = cart.reduce((sum, item) => {
        if (item.is_comp) return sum;
        return sum + ((item.price + (item.addons || []).reduce((s,a)=>s+a.price,0)) * item.quantity) - (item.discount || 0);
    }, 0);
    let discountAmt = billDiscountType === 'percentage' ? subtotal * (billDiscount / 100) : billDiscount;
    let afterDiscount = Math.max(0, subtotal - discountAmt);
    let effectiveTaxRate = (typeof taxEnabled !== 'undefined' && taxEnabled) ? taxRate : 0;
    let tax = afterDiscount * (effectiveTaxRate / 100);
    let serviceCharge = serviceChargeEnabled ? afterDiscount * (serviceChargeRate / 100) : 0;
    let total = afterDiscount + tax + serviceCharge;
    let roundingAmt = 0;
    if (priceRoundingEnabled && priceRoundingUnit > 0) {
        const unit = Number(priceRoundingUnit) || 1;
        const rounded = Math.ceil((total / unit) - 1e-9) * unit;
        roundingAmt = Math.round((rounded - total) * 100) / 100;
        total = Math.round(rounded * 100) / 100;
    }

    const subtotalEl = document.getElementById('subtotal');
    if (subtotalEl) subtotalEl.textContent = 'LKR ' + subtotal.toFixed(2);
    const mobileSubtotalEl = document.getElementById('mobileSubtotal');
    if (mobileSubtotalEl) mobileSubtotalEl.textContent = 'LKR ' + subtotal.toFixed(2);

    const discountRow = document.getElementById('discountRow');
    if (discountRow) {
        discountRow.classList.toggle('d-none', discountAmt <= 0);
        discountRow.classList.toggle('d-flex', discountAmt > 0);
    }
    const mobileDiscountRow = document.getElementById('mobileDiscountRow');
    if (mobileDiscountRow) {
        mobileDiscountRow.classList.toggle('d-none', discountAmt <= 0);
        mobileDiscountRow.classList.toggle('d-flex', discountAmt > 0);
    }
    const discountAmountEl = document.getElementById('discountAmount');
    if (discountAmountEl) discountAmountEl.textContent = '-LKR ' + discountAmt.toFixed(2);
    const mobileDiscountAmountEl = document.getElementById('mobileDiscountAmount');
    if (mobileDiscountAmountEl) mobileDiscountAmountEl.textContent = '-LKR ' + discountAmt.toFixed(2);
    const discountLabelEl = document.getElementById('discountLabel');
    const freeLabel = pendingLoyaltyRedeem && loyaltyRedeemValue > 0
        ? ((loyaltyRewardLabel || 'Free drink') + ':')
        : (billDiscountType === 'percentage' && billDiscount > 0 ? 'Discount (' + billDiscount.toFixed(0) + '%):' : 'Discount:');
    if (discountLabelEl) discountLabelEl.textContent = freeLabel;
    const mobileDiscountLabelEl = document.getElementById('mobileDiscountLabel');
    if (mobileDiscountLabelEl) mobileDiscountLabelEl.textContent = freeLabel;

    const taxAmountEl = document.getElementById('taxAmount');
    if (taxAmountEl) taxAmountEl.textContent = 'LKR ' + tax.toFixed(2);
    const mobileTaxAmountEl = document.getElementById('mobileTaxAmount');
    if (mobileTaxAmountEl) mobileTaxAmountEl.textContent = 'LKR ' + tax.toFixed(2);

    if (serviceChargeEnabled) {
        const serviceChargeEl = document.getElementById('serviceCharge');
        if (serviceChargeEl) serviceChargeEl.textContent = 'LKR ' + serviceCharge.toFixed(2);
        const mobileServiceChargeEl = document.getElementById('mobileServiceCharge');
        if (mobileServiceChargeEl) mobileServiceChargeEl.textContent = 'LKR ' + serviceCharge.toFixed(2);
    }

    let roundingRow = document.getElementById('roundingRow');
    if (!roundingRow) {
        const totalRow = document.getElementById('totalAmount')?.closest('.d-flex');
        if (totalRow && totalRow.parentElement) {
            roundingRow = document.createElement('div');
            roundingRow.id = 'roundingRow';
            roundingRow.className = 'd-flex justify-content-between small text-muted';
            roundingRow.innerHTML = '<span>Rounding:</span><span id="roundingAmount">LKR 0.00</span>';
            totalRow.parentElement.insertBefore(roundingRow, totalRow);
        }
    }
    if (roundingRow) {
        roundingRow.classList.toggle('d-none', Math.abs(roundingAmt) < 0.009);
        const ra = document.getElementById('roundingAmount');
        if (ra) ra.textContent = 'LKR ' + roundingAmt.toFixed(2);
    }

    const totalAmountEl = document.getElementById('totalAmount');
    if (totalAmountEl) totalAmountEl.textContent = 'LKR ' + total.toFixed(2);
    const mobileTotalAmountEl = document.getElementById('mobileTotalAmount');
    if (mobileTotalAmountEl) mobileTotalAmountEl.textContent = 'LKR ' + total.toFixed(2);

    const mobileCartTotalEl = document.getElementById('mobileCartTotal');
    if (mobileCartTotalEl) mobileCartTotalEl.textContent = 'LKR ' + total.toFixed(2);

    const paymentTotal = document.getElementById('paymentTotal');
    if (paymentTotal) paymentTotal.textContent = 'LKR ' + total.toFixed(2);
}

function filterCategory(catId, btn) {
    currentCategoryFilter = String(catId);
    currentSubcategoryFilter = 'all';
    document.querySelectorAll('.category-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.catId === String(catId) || (catId === 'all' && b.dataset.catId === 'all'));
    });
    if (btn) btn.classList.add('active');
    renderSubcategoryChips(catId);
    applyProductFilters();
}

let currentCategoryFilter = 'all';
let currentSubcategoryFilter = 'all';
const subcategoryMap = @json($subcategoryMap ?? new \stdClass);

function renderSubcategoryChips(catId) {
    const wrap = document.getElementById('subcategoryChips');
    if (!wrap) return;
    const subs = (catId && catId !== 'all' && subcategoryMap[String(catId)]) ? subcategoryMap[String(catId)] : [];
    if (!subs.length) {
        wrap.classList.add('d-none');
        wrap.innerHTML = '';
        return;
    }
    wrap.classList.remove('d-none');
    let html = `<button type="button" class="subcategory-chip active" data-sub-id="all" onclick="filterSubcategory('all', this)">All</button>`;
    subs.forEach(s => {
        html += `<button type="button" class="subcategory-chip" data-sub-id="${s.id}" onclick="filterSubcategory('${s.id}', this)">${s.name}</button>`;
    });
    wrap.innerHTML = html;
}

function filterSubcategory(subId, btn) {
    currentSubcategoryFilter = String(subId);
    document.querySelectorAll('#subcategoryChips .subcategory-chip').forEach(b => {
        b.classList.toggle('active', b.dataset.subId === String(subId));
    });
    if (btn) btn.classList.add('active');
    applyProductFilters();
}

function applyProductFilters() {
    const input = document.getElementById('productSearch');
    const q = (input?.value || '').trim().toLowerCase();
    document.querySelectorAll('.product-item').forEach(el => {
        // Always keep Custom Item button visible
        if (el.id === 'customItemGridBtn') {
            el.classList.remove('is-filtered-out');
            el.style.setProperty('display', 'block', 'important');
            return;
        }
        const name = el.dataset.name || '';
        const code = el.dataset.code || '';
        const barcode = el.dataset.barcode || '';
        const catOk = (currentCategoryFilter === 'all' || el.dataset.category === currentCategoryFilter);
        const subOk = (currentSubcategoryFilter === 'all' || el.dataset.subcategory === currentSubcategoryFilter);
        const matchesQuery = !q || name.includes(q) || code.includes(q) || barcode.includes(q);
        const show = catOk && subOk && matchesQuery;
        el.classList.toggle('is-filtered-out', !show);
        if (show) el.style.removeProperty('display');
        else el.style.setProperty('display', 'none', 'important');
    });
}

function togglePosPrintFlag(flag) {
    if (flag === 'ask') {
        printAskBefore = !printAskBefore;
    } else if (flag === 'receipt') {
        autoPrintReceipt = !autoPrintReceipt;
    } else if (flag === 'kot') {
        autoPrintKot = !autoPrintKot;
    }
    syncBakeryPrintToggles();
    showToast('info', 'Print preference updated for this session');
}

function syncBakeryPrintToggles() {
    document.querySelectorAll('[data-print-toggle="ask"]').forEach(el => el.classList.toggle('is-on', !!printAskBefore));
    document.querySelectorAll('[data-print-toggle="receipt"]').forEach(el => el.classList.toggle('is-on', !!autoPrintReceipt));
    document.querySelectorAll('[data-print-toggle="kot"]').forEach(el => el.classList.toggle('is-on', !!autoPrintKot));
}

function bakeryQuickPay(method) {
    if (!openBillId && !settleOrderId && cart.length === 0) {
        showToast('warning', 'Cart is empty');
        return;
    }
    const run = () => {
        const input = document.getElementById('cashReceived');
        if (input) input.value = '';
        if (method === 'cash_exact') {
            quickPay('exact');
            selectPaymentMethod('cash');
        } else {
            selectPaymentMethod(method);
        }
    };
    const modal = document.getElementById('paymentModal');
    if (modal?.classList.contains('show')) {
        run();
        return;
    }
    if (modal) {
        modal.addEventListener('shown.bs.modal', function once() {
            modal.removeEventListener('shown.bs.modal', once);
            run();
        });
    }
    openPaymentModal();
}

function searchProducts() {
    const input = document.getElementById('productSearch');
    const q = (input?.value || '').trim().toLowerCase();
    const activeCat = currentCategoryFilter || document.querySelector('.category-btn.active')?.dataset?.catId || 'all';
    const activeSub = currentSubcategoryFilter || 'all';
    document.querySelectorAll('.product-item').forEach(el => {
        // Always keep Custom Item button visible
        if (el.id === 'customItemGridBtn') {
            el.classList.remove('is-filtered-out');
            el.style.setProperty('display', 'block', 'important');
            return;
        }
        const name = el.dataset.name || '';
        const code = el.dataset.code || '';
        const barcode = el.dataset.barcode || '';
        const inCategory = (activeCat === 'all' || el.dataset.category === String(activeCat));
        const inSubcategory = (activeSub === 'all' || el.dataset.subcategory === String(activeSub));
        const matchesQuery = !q || name.includes(q) || code.includes(q) || barcode.includes(q);
        const show = inCategory && inSubcategory && matchesQuery;
        el.classList.toggle('is-filtered-out', !show);
        if (show) el.style.removeProperty('display');
        else el.style.setProperty('display', 'none', 'important');
    });
}

function tryBarcodeAddToCart() {
    const input = document.getElementById('productSearch');
    if (!input) return false;

    const q = input.value.trim();
    if (!q) return false;

    const needle = q.toLowerCase();
    // Exclude Custom Item grid card from barcode matching
    const items = Array.from(document.querySelectorAll('.product-item')).filter(el => el.id !== 'customItemGridBtn');

    // Exact barcode or product code match (scanner / typed barcode)
    let match = items.find(el => el.dataset.barcode && el.dataset.barcode === needle);
    if (!match) {
        match = items.find(el => el.dataset.code && el.dataset.code === needle);
    }

    if (!match) {
        // If only one visible filtered product, Enter adds that one
        const visible = items.filter(el => el.style.display !== 'none');
        if (visible.length === 1 && needle.length >= 3) {
            match = visible[0];
        }
    }

    if (!match) return false;

    const productId = parseInt(match.dataset.id, 10);
    const name = match.dataset.displayName || match.dataset.name;
    const price = parseFloat(match.dataset.price || 0);
    const hasVariants = match.dataset.hasVariants === '1';
    const hasAddons = match.dataset.hasAddons === '1';

    handleProductClick(productId, name, price, hasVariants, hasAddons, { keepSearchFocus: true });
    input.value = '';
    searchProducts();
    focusProductSearch(true);
    return true;
}

let barcodeScanTimer = null;
function onProductSearchInput() {
    searchProducts();
    const input = document.getElementById('productSearch');
    const q = (input?.value || '').trim();
    clearTimeout(barcodeScanTimer);
    // USB scanners dump digits quickly then pause (often without Enter)
    if (q.length < 4) return;
    barcodeScanTimer = setTimeout(() => {
        const items = Array.from(document.querySelectorAll('.product-item')).filter(el => el.id !== 'customItemGridBtn');
        const needle = q.toLowerCase();
        const exact = items.find(el =>
            (el.dataset.barcode && el.dataset.barcode === needle)
            || (el.dataset.code && el.dataset.code === needle)
        );
        if (exact) {
            tryBarcodeAddToCart();
        }
    }, 120);
}

function onProductSearchKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        clearTimeout(barcodeScanTimer);
        if (!tryBarcodeAddToCart()) {
            searchProducts();
            showToast('warning', 'No product found for that barcode');
            focusProductSearch(true);
        }
    }
}

let deliveryPayMode = 'prepaid'; // prepaid | cod | partner

function isMarketplaceDeliveryPartner() {
    const meta = getSelectedDeliveryPartnerMeta();
    return !!(meta && meta.collection === 'partner');
}

function getSelectedDeliveryPartnerMeta() {
    const sel = document.getElementById('deliveryPartnerSelect');
    if (!sel?.value) return null;
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return null;
    return {
        id: Number(sel.value),
        name: opt.textContent.trim(),
        collection: opt.dataset.collection || 'partner',
        cycle: opt.dataset.cycle || 'weekly',
        tracks: opt.dataset.tracks === '1',
    };
}

function selectDeliveryPartnerCard(btn) {
    if (!btn) return;
    const id = btn.dataset.partnerId || '';
    const sel = document.getElementById('deliveryPartnerSelect');
    if (sel) {
        sel.value = id;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    document.querySelectorAll('#deliveryPartnerLogoGrid .dpl-card').forEach(el => {
        el.classList.toggle('active', el === btn);
    });
    syncDeliveryCustomerVisibility();
    syncDeliveryPayModeForPartner();
    updateDeliverySummary();
    applyDeliveryPartnerPricing();
}

function syncDeliveryPartnerCardsFromSelect() {
    const sel = document.getElementById('deliveryPartnerSelect');
    const id = sel?.value || '';
    document.querySelectorAll('#deliveryPartnerLogoGrid .dpl-card').forEach(el => {
        el.classList.toggle('active', String(el.dataset.partnerId || '') === String(id));
    });
    // If nothing selected, highlight Own Delivery
    if (!id) {
        const own = document.querySelector('#deliveryPartnerLogoGrid .dpl-card[data-partner-id=""]');
        if (own) own.classList.add('active');
    }
}

function syncDeliveryCustomerVisibility() {
    const marketplace = isMarketplaceDeliveryPartner();
    const cust = document.getElementById('deliveryCustomerBlock');
    const addr = document.getElementById('deliveryAddressWrapper');
    if (cust) cust.style.display = marketplace ? 'none' : '';
    if (addr) addr.style.display = marketplace ? 'none' : '';
}

function setDeliveryPayMode(mode, { silent = false } = {}) {
    if (!['prepaid', 'cod', 'partner'].includes(mode)) mode = 'prepaid';
    deliveryPayMode = mode;
    document.querySelectorAll('.delivery-pay-mode-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.mode === deliveryPayMode);
    });
    if (!silent) {
        updateOpenBillUI();
        updateDeliverySummary();
    }
}

function syncDeliveryPayModeForPartner() {
    const meta = getSelectedDeliveryPartnerMeta();
    const hint = document.getElementById('deliveryPartnerPayHint');
    const prepaidBtn = document.querySelector('.delivery-pay-mode-btn[data-mode="prepaid"]');
    const codBtn = document.querySelector('.delivery-pay-mode-btn[data-mode="cod"]');
    const partnerBtn = document.querySelector('.delivery-pay-mode-btn[data-mode="partner"]');

    if (!meta) {
        if (hint) {
            hint.classList.add('d-none');
            hint.textContent = '';
        }
        [prepaidBtn, codBtn, partnerBtn].forEach(b => { if (b) b.style.display = ''; });
        if (partnerBtn) partnerBtn.style.display = 'none';
        syncDeliveryCustomerVisibility();
        return;
    }

    if (meta.collection === 'partner') {
        setDeliveryPayMode('partner', { silent: true });
        if (prepaidBtn) prepaidBtn.style.display = 'none';
        if (codBtn) codBtn.style.display = 'none';
        if (partnerBtn) partnerBtn.style.display = '';
        if (hint) {
            const cycle = meta.cycle === 'weekly' ? 'once a week' : String(meta.cycle).replace(/_/g, ' ');
            hint.className = 'alert alert-warning py-2 px-3 small mb-2';
            hint.style.borderRadius = '12px';
            hint.classList.remove('d-none');
            hint.innerHTML = `<i class="fas fa-info-circle me-1"></i><b>${meta.name}</b> — no customer details. Settles <b>${cycle}</b> via Partner Ledger.`;
        }
    } else {
        if (deliveryPayMode === 'partner') setDeliveryPayMode('cod', { silent: true });
        if (prepaidBtn) prepaidBtn.style.display = '';
        if (codBtn) codBtn.style.display = '';
        if (partnerBtn) partnerBtn.style.display = 'none';
        if (hint) {
            hint.className = 'alert alert-info py-2 px-3 small mb-2';
            hint.style.borderRadius = '12px';
            hint.classList.remove('d-none');
            hint.innerHTML = `<i class="fas fa-motorcycle me-1"></i><b>${meta.name}</b> is our delivery — customer + address required.`;
        }
    }
    syncDeliveryCustomerVisibility();
    updateOpenBillUI();
    updateDeliverySummary();
}

function openDeliveryDetailsModal() {
    const type = getActiveOrderType();
    const modalEl = document.getElementById('deliveryDetailsModal');
    if (!modalEl) return;

    const title = document.getElementById('deliveryDetailsModalTitle');
    const deliveryBlock = document.getElementById('deliveryDetailsBlock');
    const partnerPicker = document.getElementById('deliveryPartnerPickerBlock');
    if (type === 'delivery') {
        if (title) title.innerHTML = '<i class="fas fa-motorcycle me-2"></i>Delivery details';
        if (deliveryBlock) deliveryBlock.style.display = '';
        if (partnerPicker) partnerPicker.style.display = '';
    } else {
        if (title) title.innerHTML = '<i class="fas fa-user me-2"></i>' + (type === 'express' ? 'Express customer' : (isBakeryUi ? 'Counter customer' : 'Takeaway customer'));
        if (deliveryBlock) deliveryBlock.style.display = 'none';
        if (partnerPicker) partnerPicker.style.display = 'none';
        const cust = document.getElementById('deliveryCustomerBlock');
        if (cust) cust.style.display = '';
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(() => {
        initCustomerSelect2();
        syncDeliveryPartnerCardsFromSelect();
        syncDeliveryCustomerVisibility();
        if (type === 'delivery') syncDeliveryPayModeForPartner();
    }, 50);
}

function applyDeliveryDetails() {
    const type = getActiveOrderType();
    if (type === 'delivery') {
        if (isMarketplaceDeliveryPartner()) {
            const meta = getSelectedDeliveryPartnerMeta();
            const addr = document.getElementById('deliveryAddress');
            if (addr && !(addr.value || '').trim()) {
                addr.value = 'Via ' + (meta?.name || 'partner');
            }
        } else {
            const customerId = document.getElementById('customerSelect')?.value;
            const address = (document.getElementById('deliveryAddress')?.value || '').trim();
            if (!customerId) {
                showToast('warning', 'Select a customer for own delivery');
                return;
            }
            if (!address) {
                showToast('warning', 'Enter delivery address');
                return;
            }
            saveDeliveryAddressToCustomer();
        }
    }

    updateDeliverySummary();
    applyDeliveryPartnerPricing();
    updateOpenBillUI();
    bootstrap.Modal.getInstance(document.getElementById('deliveryDetailsModal'))?.hide();
    showToast('success', 'Delivery details saved');
}

function getSelectedDeliveryPartnerId() {
    if (getActiveOrderType() !== 'delivery') return null;
    const id = document.getElementById('deliveryPartnerSelect')?.value;
    return id ? Number(id) : null;
}

function productPriceForPartner(el, partnerId) {
    const base = parseFloat(el.dataset.price || 0) || 0;
    if (!partnerId) return base;
    let map = {};
    try { map = JSON.parse(el.dataset.partnerPrices || '{}'); } catch (e) { map = {}; }
    if (map[partnerId] != null && map[String(partnerId)] == null) {
        return Number(map[partnerId]);
    }
    if (map[String(partnerId)] != null) return Number(map[String(partnerId)]);
    return base;
}

function applyDeliveryPartnerPricing() {
    const partnerId = getSelectedDeliveryPartnerId();
    document.querySelectorAll('.product-item').forEach(el => {
        if (el.id === 'customItemGridBtn') return; // skip custom item card
        const price = productPriceForPartner(el, partnerId);
        const label = el.querySelector('.product-price');
        if (label) label.textContent = 'LKR ' + Number(price).toFixed(0);
        const card = el.querySelector('.product-card');
        if (card) {
            const name = el.dataset.displayName || el.dataset.name;
            const hasV = el.dataset.hasVariants === '1';
            const hasA = el.dataset.hasAddons === '1';
            card.setAttribute('onclick', `handleProductClick(${el.dataset.id}, ${JSON.stringify(name)}, ${price}, ${hasV}, ${hasA})`);
        }
    });
            // Refresh unlocked cart lines to partner menu price
    cart.forEach(item => {
        if (item.order_item_id || item.loyalty_free) return;
        const el = document.querySelector(`.product-item[data-id="${item.product_id}"]`);
        if (!el) return;
        const unit = productPriceForPartner(el, partnerId);
        const variantAdj = Number(item.variant_adj || 0);
        item.base_price = unit;
        item.price = unit + variantAdj;
    });
    if (typeof updateCart === 'function') updateCart();
}

function updateDeliverySummary() {
    const emptyEl = document.getElementById('deliverySummaryEmpty');
    const filledEl = document.getElementById('deliverySummaryFilled');
    if (!emptyEl || !filledEl) return;

    const type = getActiveOrderType();
    const select = document.getElementById('customerSelect');
    const customerText = select?.selectedIndex > 0 ? select.options[select.selectedIndex].text : '';
    const address = (document.getElementById('deliveryAddress')?.value || '').trim();
    const partnerSelect = document.getElementById('deliveryPartnerSelect');
    const partnerText = partnerSelect?.value
        ? (partnerSelect.options[partnerSelect.selectedIndex]?.text || '')
        : 'Own Delivery';
    const marketplace = type === 'delivery' && isMarketplaceDeliveryPartner();

    const hasCustomer = !!select?.value;
    const isReady = type === 'delivery'
        ? (marketplace ? !!partnerSelect?.value : (hasCustomer && !!address))
        : hasCustomer;

    if (!isReady) {
        emptyEl.classList.remove('d-none');
        filledEl.classList.add('d-none');
        return;
    }

    emptyEl.classList.add('d-none');
    filledEl.classList.remove('d-none');

    const nameEl = document.getElementById('deliverySummaryCustomer');
    const metaEl = document.getElementById('deliverySummaryMeta');
    const payEl = document.getElementById('deliverySummaryPay');
    const iconEl = document.getElementById('deliverySummaryIcon');

    if (nameEl) nameEl.textContent = marketplace ? partnerText : (customerText || 'Customer');
    if (type === 'delivery') {
        if (iconEl) iconEl.className = 'fas fa-motorcycle';
        if (metaEl) {
            metaEl.textContent = marketplace
                ? ('Partner order · no customer details')
                : ([address, partnerText].filter(Boolean).join(' · ') || 'No address');
        }
        if (payEl) {
            payEl.style.display = '';
            payEl.textContent = deliveryPayMode === 'partner'
                ? 'Partner later'
                : (deliveryPayMode === 'cod' ? 'COD' : 'Pay now');
            payEl.classList.toggle('is-cod', deliveryPayMode === 'cod' || deliveryPayMode === 'partner');
        }
    } else {
        if (iconEl) iconEl.className = type === 'express' ? 'fas fa-bolt' : (isBakeryUi ? 'fas fa-store' : 'fas fa-shopping-bag');
        if (metaEl) metaEl.textContent = type === 'express' ? 'Express order' : (isBakeryUi ? 'Counter order' : 'Takeaway order');
        if (payEl) payEl.style.display = 'none';
    }
}

function changeOrderType(type) {
    if (isBakeryUi && bakeryDirectBilling && type === 'dine_in') {
        type = 'takeaway';
    }
    document.querySelectorAll('.order-type-btn').forEach(btn => btn.classList.remove('active'));
    const activeBtn = document.querySelector(`.order-type-btn[data-type="${type}"]`);
    if (activeBtn) activeBtn.classList.add('active');

    const tableContainer = document.getElementById('tableSelectContainer');
    const waiterContainer = document.getElementById('waiterSelectContainer');
    const takeawayCustomerContainer = document.getElementById('takeawayCustomerContainer');
    const metaRow = document.querySelector('#desktopMetaSlot .cart-meta-row');
    const metaSlot = document.getElementById('desktopMetaSlot');
    const customerContainer = document.getElementById('customerSelectContainer');
    const deliveryDetailsBlock = document.getElementById('deliveryDetailsBlock');
    const titleEl = document.getElementById('customerSectionTitle');
    const hintEl = document.getElementById('customerSectionHint');
    updateOpenBillUI();

    if (isBakeryUi && bakeryDirectBilling) {
        if (tableContainer) tableContainer.classList.add('d-none');
        if (waiterContainer) waiterContainer.classList.add('d-none');
        if (takeawayCustomerContainer) takeawayCustomerContainer.classList.add('d-none');
        if (metaRow) metaRow.classList.add('d-none');
        if (metaSlot && type !== 'dine_in') metaSlot.classList.add('d-none');
        document.querySelectorAll('.open-bills-btn, #transferTableBtn').forEach(el => el.classList.add('d-none'));
    } else if (metaSlot) {
        metaSlot.classList.remove('d-none');
        document.querySelectorAll('.open-bills-btn').forEach(el => el.classList.remove('d-none'));
    }

    if (type === 'dine_in') {
        if (!(isBakeryUi && bakeryDirectBilling)) {
            if (tableContainer) tableContainer.classList.remove('d-none');
            if (waiterContainer) waiterContainer.classList.remove('d-none');
            if (metaRow) metaRow.classList.remove('d-none');
        }
        if (takeawayCustomerContainer) takeawayCustomerContainer.classList.add('d-none');
        if (customerContainer) customerContainer.classList.add('d-none');
        if (deliveryDetailsBlock) deliveryDetailsBlock.style.display = 'none';
    } else if (type === 'delivery') {
        if (tableContainer) tableContainer.classList.add('d-none');
        if (typeof clearTable === 'function') clearTable();
        if (waiterContainer) waiterContainer.classList.remove('d-none');
        if (metaRow) metaRow.classList.remove('d-none');
        if (takeawayCustomerContainer) takeawayCustomerContainer.classList.add('d-none');
        if (customerContainer) customerContainer.classList.remove('d-none');
        if (deliveryDetailsBlock) deliveryDetailsBlock.style.display = '';
        if (titleEl) titleEl.textContent = 'Delivery details';
        if (hintEl) hintEl.textContent = 'Customer · address · partner · pay';
        customerContainer?.classList.add('is-delivery');
    } else if (type === 'takeaway') {
        // Takeaway: no table, no waiter — cart name/phone instead
        if (tableContainer) tableContainer.classList.add('d-none');
        if (typeof clearTable === 'function') clearTable();
        if (waiterContainer) waiterContainer.classList.add('d-none');
        if (metaRow) metaRow.classList.add('d-none');
        if (takeawayCustomerContainer) takeawayCustomerContainer.classList.remove('d-none');
        if (customerContainer) customerContainer.classList.add('d-none');
        if (deliveryDetailsBlock) deliveryDetailsBlock.style.display = 'none';
        customerContainer?.classList.remove('is-delivery');
    } else {
        // express (and bakery counter) — keep existing customer Add + waiter behavior
        if (tableContainer) tableContainer.classList.add('d-none');
        if (typeof clearTable === 'function') clearTable();
        if (waiterContainer) waiterContainer.classList.remove('d-none');
        if (metaRow) metaRow.classList.remove('d-none');
        if (takeawayCustomerContainer) takeawayCustomerContainer.classList.add('d-none');
        if (customerContainer) customerContainer.classList.remove('d-none');
        if (deliveryDetailsBlock) deliveryDetailsBlock.style.display = 'none';
        if (titleEl) titleEl.textContent = type === 'express' ? 'Express customer' : (isBakeryUi ? 'Counter customer' : 'Takeaway customer');
        if (hintEl) hintEl.textContent = 'Optional — tap Add';
        customerContainer?.classList.remove('is-delivery');
    }

    if (type !== 'delivery') {
        customerContainer?.classList.toggle('is-delivery', false);
        applyDeliveryPartnerPricing(); // reset to base prices
    }

    updateDeliverySummary();
}

function initCustomerSelect2() {
    const $el = $('#customerSelect');
    if (!$el.length) return;
    const $modal = $('#deliveryDetailsModal');
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.off('select2:select select2:clear change').select2('destroy');
    }
    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search customer...',
        allowClear: true,
        dropdownParent: $modal.length ? $modal : $(document.body)
    }).on('select2:select select2:clear change', function() {
        fillDeliveryAddressFromCustomer();
        updateDeliverySummary();
        syncLoyaltyFromMainCustomer();
    });
}

function initDeliveryPartnerSelect2() {
    // Logo cards replace Select2 — keep hidden <select> in sync only
    const $el = $('#deliveryPartnerSelect');
    if ($el.length && $el.hasClass('select2-hidden-accessible')) {
        $el.off('change').select2('destroy');
    }
    syncDeliveryPartnerCardsFromSelect();
    syncDeliveryCustomerVisibility();
    const sel = document.getElementById('deliveryPartnerSelect');
    if (sel && !sel.dataset.boundPartnerChange) {
        sel.dataset.boundPartnerChange = '1';
        sel.addEventListener('change', () => {
            syncDeliveryPartnerCardsFromSelect();
            syncDeliveryCustomerVisibility();
            updateDeliverySummary();
            applyDeliveryPartnerPricing();
            syncDeliveryPayModeForPartner();
        });
    }
}

function getSelectedCustomerOption() {
    const select = document.getElementById('customerSelect');
    if (!select || !select.value) return null;
    return select.options[select.selectedIndex] || null;
}

function syncCustomerOptionAddress(customerId, address) {
    const select = document.getElementById('customerSelect');
    if (!select || !customerId) return;
    const option = Array.from(select.options).find(o => String(o.value) === String(customerId));
    if (option) option.dataset.address = address || '';
}

function fillDeliveryAddressFromCustomer() {
    const addressField = document.getElementById('deliveryAddress');
    if (!addressField) return;

    const selected = getSelectedCustomerOption();
    if (!selected || !selected.value) {
        addressField.value = '';
        return;
    }

    const address = (selected.dataset.address || '').trim();
    const city = (selected.dataset.city || '').trim();
    addressField.value = [address, city].filter(Boolean).join(', ');
}

function onCustomerChange() {
    fillDeliveryAddressFromCustomer();
    updateDeliverySummary();
    syncLoyaltyFromMainCustomer();
}

function handleLoyaltyResult(loyalty) {
    if (!loyaltyEnabled || !loyalty) return;
    currentLoyalty = loyalty;
    renderLoyaltyPanel(loyalty);
    if (loyalty.earned) {
        let msg = '+' + loyalty.earned + ' stamp(s)';
        if (loyalty.free_gained) msg += ' · ' + loyalty.free_gained + '× ' + (loyalty.reward_label || loyaltyRewardLabel) + ' unlocked!';
        showToast('success', msg);
        if (loyalty.just_completed || loyalty.free_drinks > 0) {
            Swal.fire({
                icon: 'success',
                title: loyalty.reward_label || loyaltyRewardLabel,
                html: '<b>' + (loyalty.name || 'Customer') + '</b> now has <b>' + loyalty.free_drinks + '</b> free drink credit(s).<br>Stamps: ' + loyalty.stamps + '/' + loyalty.stamps_required,
                confirmButtonColor: '#f59e0b',
            });
        }
    }
}

function renderLoyaltyPanel(loyalty) {
    if (!loyaltyEnabled) return;
    const box = document.getElementById('loyaltyPosPanel');
    const modalCard = document.getElementById('loyaltyModalCard');
    const stampsHtml = (filled, need, size) => {
        let dots = '';
        for (let i = 1; i <= need; i++) {
            dots += `<span class="loy-stamp ${size}${i <= filled ? ' on' : ''}">${i <= filled ? '✓' : i}</span>`;
        }
        return `<div class="loy-stamps ${size === 'lg' ? 'loy-stamps-lg' : ''}">${dots}</div>`;
    };

    if (!loyalty || !loyalty.joined) {
        if (box) {
            box.innerHTML = loyalty && loyalty.id && !loyalty.joined
                ? `<div class="loy-empty"><strong>${loyalty.name || 'Customer'}</strong> not joined — open to enroll</div>`
                : '<div class="loy-empty">Pick customer in Delivery details, or tap to search / scan QR</div>';
        }
        if (modalCard) {
            modalCard.innerHTML = loyalty && loyalty.id && !loyalty.joined
                ? `<div class="text-center py-3"><div class="fw-bold mb-2">${loyalty.name || 'Customer'}</div><div class="text-muted mb-3">Not on stamp card yet</div></div>`
                : '<div class="loy-empty text-center py-4">Search or scan a customer to see their stamps</div>';
        }
        return;
    }

    const filled = Number(loyalty.stamps || 0);
    const need = Number(loyalty.stamps_required || loyaltyStampsRequired || 10);
    const free = Number(loyalty.free_drinks || 0);
    const reward = loyalty.reward_label || loyaltyRewardLabel;

    if (box) {
        box.innerHTML = `
            <div class="loy-top">
                <strong>${loyalty.name || 'Member'}</strong>
                <span class="loy-count">${filled}/${need}</span>
            </div>
            ${stampsHtml(filled, need, 'sm')}
            <div class="loy-actions">
                ${free > 0 || loyalty.can_redeem ? `<button type="button" class="btn btn-sm btn-success" onclick="event.stopPropagation();redeemLoyaltyFree()">Redeem ${reward} (${free})</button>` : `<span class="text-muted small">${reward} at ${need}</span>`}
                ${pendingLoyaltyRedeem ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation();undoLoyaltyRedeem()">Undo free</button>` : ''}
            </div>`;
    }

    if (modalCard) {
        modalCard.innerHTML = `
            <div class="loyalty-pass">
                <div class="loyalty-pass-head">
                    <div>
                        <div class="loyalty-pass-biz">Stamp card</div>
                        <div class="loyalty-pass-name">${loyalty.name || 'Member'}</div>
                        <div class="loyalty-pass-phone">${loyalty.phone || ''}</div>
                    </div>
                    <div class="loyalty-pass-count">${filled}<span>/${need}</span></div>
                </div>
                ${loyalty.expired ? '<div class="loyalty-pass-alert danger">Card expired</div>' : ''}
                ${!loyalty.expired && free > 0 ? `<div class="loyalty-pass-alert ok">${free} × ${reward} ready</div>` : ''}
                <div class="stamps-label">Stamps</div>
                ${stampsHtml(filled, need, 'lg')}
                <div class="loyalty-pass-foot">
                    ${!loyalty.expired && (free > 0 || loyalty.can_redeem) ? `<button type="button" class="btn btn-success" onclick="redeemLoyaltyFree()">Redeem ${reward}</button>` : `<span class="text-muted">${reward} when card is full</span>`}
                    ${pendingLoyaltyRedeem ? `<button type="button" class="btn btn-outline-danger" onclick="undoLoyaltyRedeem()">Undo free</button>` : ''}
                    ${loyalty.card_url ? `<a class="btn btn-outline-light" href="${loyalty.card_url}" target="_blank">Open card</a>` : ''}
                </div>
                ${loyalty.expires_at ? `<div class="loyalty-pass-exp">Expires ${loyalty.expires_at}</div>` : ''}
            </div>`;
    }
}

function undoLoyaltyRedeem() {
    if (!pendingLoyaltyRedeem) return;
    const freeIdx = cart.findIndex(i => i.loyalty_free && !i.order_item_id);
    if (freeIdx >= 0) {
        const item = cart[freeIdx];
        item.price = Number(item.original_price || item.base_price || 0);
        item.loyalty_free = false;
        item.name = item.base_name || String(item.name || '').replace(/\s*\(FREE\)\s*$/, '');
        item.special_instructions = String(item.special_instructions || '').replace(/LOYALTY FREE[^·]*·?\s*/gi, '').trim();
    }
    if (loyaltyRedeemValue > 0 && billDiscountType === 'fixed') {
        billDiscount = Math.max(0, Number(billDiscount || 0) - Number(loyaltyRedeemValue || 0));
    }
    cart.forEach(i => { delete i.loyalty_free_pending; });
    resetLoyaltyRedeemState();
    if (currentLoyalty) {
        currentLoyalty.free_drinks = Number(currentLoyalty.free_drinks || 0) + 1;
        currentLoyalty.can_redeem = true;
        renderLoyaltyPanel(currentLoyalty);
    } else {
        updateCart();
    }
    updateCart();
    showToast('info', 'Free drink removed from bill');
}

let loyaltyCustomerSyncLock = false;

function refreshLoyaltyForSelectedCustomer() {
    if (!loyaltyEnabled) return;
    const id = document.getElementById('loyaltyCustomerSelect')?.value;
    if (!id) {
        currentLoyalty = null;
        renderLoyaltyPanel(null);
        return;
    }
    fetch('/pos/loyalty/customer/' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentLoyalty = data.loyalty;
                // Keep id/name even if not joined so enroll UI works
                if (currentLoyalty && !currentLoyalty.id) currentLoyalty.id = Number(id);
                renderLoyaltyPanel(currentLoyalty);
            }
        }).catch(() => {});
}

/** When delivery/takeaway customer is picked, auto-attach stamp card if they are a member. */
function syncLoyaltyFromMainCustomer() {
    if (!loyaltyEnabled || loyaltyCustomerSyncLock) return;
    const main = document.getElementById('customerSelect');
    const id = main?.value;
    if (!id) {
        clearLoyaltySelection(false, true);
        return;
    }

    const opt = getSelectedCustomerOption();
    const alreadyJoinedHint = opt?.dataset?.loyaltyJoined === '1';

    fetch('/pos/loyalty/customer/' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.loyalty) return;
            const loyalty = data.loyalty;
            if (!loyalty.joined) {
                // Not on stamp card — leave loyalty empty (no separate add needed only when member)
                if (String(getLoyaltyCustomerId() || '') === String(id)) {
                    clearLoyaltySelection(false, true);
                }
                return;
            }

            loyaltyCustomerSyncLock = true;
            try {
                ensureCustomerOption(document.getElementById('loyaltyCustomerSelect'), {
                    id: Number(id),
                    name: loyalty.name || (opt?.textContent || '').split(' - ')[0],
                    phone: loyalty.phone || opt?.dataset?.phone || '',
                    address: opt?.dataset?.address || '',
                });
                const loyEl = document.getElementById('loyaltyCustomerSelect');
                if (loyEl) {
                    const $loy = $('#loyaltyCustomerSelect');
                    if ($loy.length && !$loy.hasClass('select2-hidden-accessible')) initLoyaltySelect2();
                    if ($loy.length && $loy.hasClass('select2-hidden-accessible')) {
                        $loy.val(String(id)).trigger('change.select2');
                    } else {
                        loyEl.value = String(id);
                    }
                }
                if (opt) opt.dataset.loyaltyJoined = '1';
                currentLoyalty = loyalty;
                if (!currentLoyalty.id) currentLoyalty.id = Number(id);
                renderLoyaltyPanel(currentLoyalty);
                if (alreadyJoinedHint || loyalty.can_redeem) {
                    // Soft confirm on panel only — no extra toast every time
                }
            } finally {
                loyaltyCustomerSyncLock = false;
            }
        }).catch(() => {});
}

function initLoyaltySelect2() {
    const $el = $('#loyaltyCustomerSelect');
    if (!$el.length) return;
    const $modal = $('#loyaltyPosModal');
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.off('select2:select select2:clear change').select2('destroy');
    }
    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search customer by name or phone…',
        allowClear: true,
        dropdownParent: $modal.length ? $modal : $(document.body),
    }).on('select2:select select2:clear change', function () {
        if (loyaltyCustomerSyncLock) return;
        refreshLoyaltyForSelectedCustomer();
        // Keep delivery customer in sync when loyalty picks someone
        const id = this.value;
        const main = document.getElementById('customerSelect');
        if (main && id && String(main.value) !== String(id)) {
            ensureCustomerOption(main, {
                id: Number(id),
                name: currentLoyalty?.name,
                phone: currentLoyalty?.phone,
            });
            const opt = [...main.options].find(o => o.value == id);
            if (opt) {
                loyaltyCustomerSyncLock = true;
                try {
                    main.value = String(id);
                    fillDeliveryAddressFromCustomer();
                    updateDeliverySummary();
                    $('#customerSelect').trigger('change.select2');
                } finally {
                    loyaltyCustomerSyncLock = false;
                }
            }
        }
    });
}

function openLoyaltyModal() {
    if (!loyaltyEnabled) return;
    const modalEl = document.getElementById('loyaltyPosModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    initLoyaltySelect2();
    renderLoyaltyPanel(currentLoyalty);
    setTimeout(() => {
        const scan = document.getElementById('loyaltyScanInput');
        if (scan && !document.getElementById('loyaltyCustomerSelect')?.value) scan.focus();
        else $('#loyaltyCustomerSelect').select2('open');
    }, 250);
}

function loyaltyScanOrLookup() {
    const token = document.getElementById('loyaltyScanInput')?.value?.trim();
    if (!token) return;
    fetch('/pos/loyalty/lookup?token=' + encodeURIComponent(token), { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                showToast('error', data.message || 'Not found');
                return;
            }
            applyLoyaltyCustomer(data.customer);
            showToast('success', 'Loyalty member loaded');
        });
}

function ensureCustomerOption(selectEl, customer) {
    if (!selectEl || !customer?.id) return;
    if (![...selectEl.options].some(o => o.value == customer.id)) {
        const opt = document.createElement('option');
        opt.value = customer.id;
        opt.textContent = customer.name + (customer.phone ? ' - ' + customer.phone : '');
        if (customer.phone) opt.dataset.phone = customer.phone;
        if (customer.address) opt.dataset.address = customer.address;
        selectEl.appendChild(opt);
    }
}

function applyLoyaltyCustomer(customer) {
    if (!customer?.id) return;
    loyaltyCustomerSyncLock = true;
    try {
        ensureCustomerOption(document.getElementById('loyaltyCustomerSelect'), customer);
        ensureCustomerOption(document.getElementById('customerSelect'), customer);

        const $loy = $('#loyaltyCustomerSelect');
        if ($loy.length) {
            if (!$loy.hasClass('select2-hidden-accessible')) initLoyaltySelect2();
            $loy.val(String(customer.id)).trigger('change.select2');
        }

        const main = document.getElementById('customerSelect');
        if (main) {
            main.value = String(customer.id);
            fillDeliveryAddressFromCustomer();
            updateDeliverySummary();
            $('#customerSelect').trigger('change.select2');
        }

        currentLoyalty = customer.loyalty || currentLoyalty;
        if (currentLoyalty) {
            currentLoyalty.id = customer.id;
            currentLoyalty.name = customer.name || currentLoyalty.name;
            currentLoyalty.phone = customer.phone || currentLoyalty.phone;
        }
        renderLoyaltyPanel(currentLoyalty);
    } finally {
        loyaltyCustomerSyncLock = false;
    }
    if (!customer.loyalty) refreshLoyaltyForSelectedCustomer();
}

function enrollSelectedLoyaltyCustomer() {
    const id = document.getElementById('loyaltyCustomerSelect')?.value;
    if (!id) {
        showToast('warning', 'Search and select a customer first');
        return;
    }
    fetch('/pos/loyalty/enroll/' + id, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    }).then(r => r.json()).then(data => {
        if (!data.success) {
            showToast('error', data.message || 'Could not join');
            return;
        }
        currentLoyalty = data.loyalty;
        renderLoyaltyPanel(data.loyalty);
        showToast('success', 'Joined stamp card');
        if (data.card_url) {
            Swal.fire({
                icon: 'success',
                title: 'Digital stamp card ready',
                html: 'Share this link with the customer for Apple/Google home screen / wallet tips:<br><a href="' + data.card_url + '" target="_blank">' + data.card_url + '</a>',
                confirmButtonColor: '#f59e0b',
            });
        }
    });
}

function redeemLoyaltyFree() {
    const id = getLoyaltyCustomerId();
    if (!id) {
        showToast('warning', 'Select / scan loyalty customer first');
        return;
    }
    if (!currentLoyalty?.can_redeem && !(currentLoyalty?.free_drinks > 0)) {
        showToast('warning', 'No free drink credit yet');
        return;
    }
    if (pendingLoyaltyRedeem || cart.some(i => i.loyalty_free)) {
        showToast('info', 'Free drink already applied on this bill');
        return;
    }
    if (!cart.length) {
        showToast('warning', 'Add the drink to cart first, then redeem');
        return;
    }

    const cats = loyaltyCategoryIds || [];
    const idx = cart.findIndex(item => {
        if (item.loyalty_free) return false;
        const cat = Number(item.category_id || 0);
        if (cats.length && cat && !cats.includes(cat)) return false;
        const unit = Number(item.price || 0) + (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);
        return unit > 0;
    });
    if (idx < 0) {
        showToast('warning', 'Add a stamp-category drink to the cart to redeem');
        return;
    }

    Swal.fire({
        title: 'Redeem ' + (loyaltyRewardLabel || 'free drink') + '?',
        text: 'Makes 1× ' + (cart[idx].name || 'item') + ' FREE and deducts it from the bill total.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        confirmButtonText: 'Yes, make it free',
    }).then(res => {
        if (!res.isConfirmed) return;
        applyLoyaltyFreeToCartIndex(idx);
    });
}

function applyLoyaltyFreeToCartIndex(idx) {
    const item = cart[idx];
    if (!item) return;
    const unit = Number(item.price || 0) + (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);

    if (item.order_item_id) {
        // Open bill line — apply as bill discount (server adjusts on settle)
        if (billDiscountType === 'percentage') {
            billDiscountType = 'fixed';
            billDiscount = unit;
        } else {
            billDiscount = Number(billDiscount || 0) + unit;
        }
        pendingLoyaltyRedeem = true;
        loyaltyRedeemValue = unit;
        item.loyalty_free_pending = true;
        item.special_instructions = trimJoin(item.special_instructions, 'LOYALTY FREE · ' + (loyaltyRewardLabel || 'Free drink'));
    } else if (Number(item.quantity) > 1) {
        item.quantity = Number(item.quantity) - 1;
        cart.splice(idx + 1, 0, {
            ...item,
            quantity: 1,
            order_item_id: null,
            original_price: unit,
            price: 0,
            addons: (item.addons || []).map(a => ({ ...a, price: 0 })),
            loyalty_free: true,
            special_instructions: trimJoin(item.special_instructions, 'LOYALTY FREE · ' + (loyaltyRewardLabel || 'Free drink')),
            name: (item.base_name || item.name) + ' (FREE)',
        });
        pendingLoyaltyRedeem = true;
        loyaltyRedeemValue = unit;
    } else {
        item.original_price = unit;
        item.price = 0;
        item.addons = (item.addons || []).map(a => ({ ...a, price: 0 }));
        item.loyalty_free = true;
        item.special_instructions = trimJoin(item.special_instructions, 'LOYALTY FREE · ' + (loyaltyRewardLabel || 'Free drink'));
        if (!String(item.name || '').includes('(FREE)')) {
            item.name = (item.base_name || item.name) + ' (FREE)';
        }
        pendingLoyaltyRedeem = true;
        loyaltyRedeemValue = unit;
    }

    if (currentLoyalty) {
        currentLoyalty.free_drinks = Math.max(0, Number(currentLoyalty.free_drinks || 0) - 1);
        currentLoyalty.can_redeem = currentLoyalty.free_drinks > 0;
        renderLoyaltyPanel(currentLoyalty);
    }
    updateCart();
    showToast('success', (loyaltyRewardLabel || 'Free drink') + ' applied — deducted from bill');
}

function trimJoin(a, b) {
    return [a, b].filter(Boolean).join(' · ');
}

function loyaltyCheckoutFlags() {
    return {
        loyalty_redeem: !!pendingLoyaltyRedeem,
        loyalty_redeem_value: pendingLoyaltyRedeem ? Number(loyaltyRedeemValue || 0) : 0,
    };
}

function resetLoyaltyRedeemState() {
    pendingLoyaltyRedeem = false;
    loyaltyRedeemValue = 0;
}

function getLoyaltyCustomerId() {
    return document.getElementById('loyaltyCustomerSelect')?.value
        || (currentLoyalty?.joined ? String(currentLoyalty.id || '') : null)
        || null;
}

function clearLoyaltySelection(keepPanel = false, silent = false) {
    if (!loyaltyEnabled) return;
    const $sel = $('#loyaltyCustomerSelect');
    if ($sel.length) {
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.val(null);
            if (!keepPanel && !silent) $sel.trigger('change');
        } else if (document.getElementById('loyaltyCustomerSelect')) {
            document.getElementById('loyaltyCustomerSelect').value = '';
        }
    }
    const scan = document.getElementById('loyaltyScanInput');
    if (scan) scan.value = '';
    resetLoyaltyRedeemState();
    if (!keepPanel) {
        currentLoyalty = null;
        renderLoyaltyPanel(null);
    }
}

function saveDeliveryAddressToCustomer() {
    const select = document.getElementById('customerSelect');
    const addressField = document.getElementById('deliveryAddress');
    if (!select?.value || !addressField) return;

    const address = addressField.value.trim();
    if (!address) return;

    const option = getSelectedCustomerOption();
    if (option && (option.dataset.address || '').trim() === address) return;

    syncCustomerOptionAddress(select.value, address);

    fetch(`/pos/customer/${select.value}/address`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ address }),
    }).catch(() => {});
}

let currentCustomerId = null;

function showAddCustomerModal() {
    document.getElementById('addCustomerForm').reset();
    const existingAddress = (document.getElementById('deliveryAddress')?.value || '').trim();
    if (existingAddress) {
        document.getElementById('newCustomerAddress').value = existingAddress;
    }
    const joinLoyalty = document.getElementById('newCustomerJoinLoyalty');
    if (joinLoyalty) joinLoyalty.checked = true;
    const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
    modal.show();
    setTimeout(() => document.getElementById('newCustomerName').focus(), 200);
}

function submitAddCustomer(e) {
    e.preventDefault();

    const name = document.getElementById('newCustomerName').value.trim();
    const phone = document.getElementById('newCustomerPhone').value.trim();
    const email = document.getElementById('newCustomerEmail').value.trim();
    const address = document.getElementById('newCustomerAddress').value.trim();
    const joinLoyalty = !!document.getElementById('newCustomerJoinLoyalty')?.checked;

    if (!name || !phone) {
        if (typeof showToast === 'function') showToast('error', 'Name and phone are required!');
        return;
    }

    fetch('/pos/customer/quick-add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ name, phone, email, address, join_loyalty: joinLoyalty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();

            const select = document.getElementById('customerSelect');
            const savedAddress = (data.customer.address || address || '').trim();
            const joined = !!(data.loyalty?.joined || joinLoyalty);
            const newOption = new Option(`${name} - ${phone}${joined ? ' ★' : ''}`, data.customer.id, true, true);
            newOption.dataset.address = savedAddress;
            newOption.dataset.phone = phone;
            newOption.dataset.email = email || '';
            newOption.dataset.loyaltyJoined = joined ? '1' : '0';
            select.add(newOption);

            $('#customerSelect').trigger('change');
            fillDeliveryAddressFromCustomer();
            updateDeliverySummary();

            const customerPayload = {
                id: data.customer.id,
                name: data.customer.name || name,
                phone: data.customer.phone || phone,
                address: savedAddress,
                loyalty: data.loyalty || null,
            };
            ensureCustomerOption(document.getElementById('loyaltyCustomerSelect'), customerPayload);
            if (data.loyalty?.joined) {
                applyLoyaltyCustomer(customerPayload);
            } else if (document.getElementById('loyaltyPosModal')?.classList.contains('show')) {
                initLoyaltySelect2();
                $('#loyaltyCustomerSelect').val(String(data.customer.id)).trigger('change');
            }

            if (typeof showToast === 'function') {
                showToast('success', data.loyalty ? 'Customer added & joined stamp card!' : 'Customer added successfully!');
            }
            if (data.card_url && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Digital stamp card ready',
                    html: 'Share this link with the customer:<br><a href="' + data.card_url + '" target="_blank">' + data.card_url + '</a>',
                    confirmButtonColor: '#f59e0b',
                });
            }
        } else {
            if (typeof showToast === 'function') showToast('error', data.message || 'Failed to add customer');
        }
    })
    .catch(err => {
        console.error('Add customer error:', err);
        if (typeof showToast === 'function') showToast('error', 'Error adding customer');
    });
}

function keypadInput(key) {
    const searchInput = document.getElementById('productSearch');
    if (!searchInput) return;
    if (key === 'C') {
        searchInput.value = '';
    } else if (key === 'DEL') {
        searchInput.value = searchInput.value.slice(0, -1);
    } else {
        searchInput.value += key;
    }
    searchProducts();
}

const POS_NUMPAD_PREF_KEY = 'pos_numpad_visible';

function getPosNumpadVisible() {
    try {
        const stored = sessionStorage.getItem(POS_NUMPAD_PREF_KEY);
        if (stored === '0') return false;
        if (stored === '1') return true;
    } catch (e) {}
    return !!showScreenNumbersKeyboard;
}

function applyPosNumpadVisibility(visible) {
    document.body.classList.toggle('pos-numpad-hidden', !visible);
    document.querySelectorAll('.pos-cart-keypad-host, .pos-cart-keypad').forEach(el => {
        el.classList.toggle('d-none', !visible);
        el.hidden = !visible;
    });
    document.querySelectorAll('.pos-numpad-toggle').forEach(btn => {
        const label = btn.querySelector('.pos-numpad-toggle-label');
        const icon = btn.querySelector('.pos-numpad-toggle-icon');
        if (label) label.textContent = visible ? 'Hide Numpad' : 'Show Numpad';
        if (icon) {
            icon.classList.toggle('fa-keyboard', visible);
            icon.classList.toggle('fa-th', !visible);
        }
        btn.setAttribute('title', visible ? 'Hide the on-screen numpad' : 'Show the on-screen numpad');
        btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
    });
}

function togglePosNumpad() {
    const next = !getPosNumpadVisible();
    try { sessionStorage.setItem(POS_NUMPAD_PREF_KEY, next ? '1' : '0'); } catch (e) {}
    applyPosNumpadVisibility(next);
}

function printBill() {
    handleReceiptPrint('/pos/last-receipt', lastSaleOrderId || null, true);
}

let printPreviewQueue = [];
let printPreviewBusy = false;

function openPrintPreview(url) {
    if (!url) return;
    printPreviewQueue.push(url);
    processPrintPreviewQueue();
}

function extractOrderIdFromPrintUrl(url) {
    if (!url) return null;
    const m = String(url).match(/print-receipt\/(\d+)/i) || String(url).match(/\/orders\/(\d+)/i);
    return m ? m[1] : null;
}

function receiptBridgeBase() {
    return String(receiptPrintBridgeUrl || 'http://127.0.0.1:18181').replace(/\/$/, '');
}

async function localPrintBridgeHealthy() {
    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), 3000);
    try {
        const r = await fetch(receiptBridgeBase() + '/health', {
            method: 'GET',
            signal: ctrl.signal,
            cache: 'no-store',
            mode: 'cors',
        });
        clearTimeout(t);
        if (!r.ok) return false;
        const data = await r.json().catch(() => ({}));
        return !!(data.success || data.service);
    } catch (e) {
        clearTimeout(t);
        return false;
    }
}

async function printViaLocalBridgeKitchen(kitchenOrderId, attempt = 1) {
    const escRes = await fetch('/pos/kot-escpos/' + kitchenOrderId, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        cache: 'no-store',
    });
    const esc = await escRes.json().catch(() => ({}));
    if (!escRes.ok || !esc.success || !esc.payload_base64) {
        if (esc.has_printer === false) {
            throw new Error(esc.message || 'NO_PRINTER');
        }
        throw new Error(esc.message || 'Could not build kitchen ticket data');
    }

    const ip = (esc.printer_ip || '').trim();
    const port = Number(esc.printer_port || 9100) || 9100;
    const isBot = String(esc.type || esc.ticket_kind || '').toLowerCase() === 'bar'
        || String(esc.ticket_kind || '').toLowerCase() === 'bot';
    let printerName = (esc.printer_name || '').trim();
    // Only default KOT Windows name for kitchen tickets — never for BOT
    if (!printerName) {
        if (isBot) {
            throw new Error('No bar/BOT printer configured — not sending to kitchen KOT');
        }
        printerName = 'XP-80Kitchen KOT';
    }
    if (/^kitchen printer$/i.test(printerName) && isBot) {
        throw new Error('No bar/BOT printer configured — not sending to kitchen KOT');
    }
    if (!printerName || /^kitchen printer$/i.test(printerName)) {
        printerName = 'XP-80Kitchen KOT';
    }
    // LAN: tcp first; bridge falls back to Windows queue if TCP fails (kitchen only)
    const mode = ip ? 'tcp' : 'windows';

    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), 40000);
    try {
        const r = await fetch(receiptBridgeBase() + '/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                ip: ip || null,
                port,
                printer_name: printerName,
                mode,
                job_type: isBot ? 'bot' : 'kitchen',
                allow_windows_fallback: !isBot,
                data: esc.payload_base64,
            }),
            signal: ctrl.signal,
            cache: 'no-store',
            mode: 'cors',
        });
        clearTimeout(t);
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.success) {
            const errMsg = data.message || 'Kitchen print failed';
            // Bridge reached; printer TCP failed
            if (ip && /cannot be reached|timeout|refused|unreachable/i.test(errMsg)) {
                throw new Error((isBot ? 'Bar' : 'Kitchen') + ' printer cannot be reached at ' + ip + ':' + port + '.');
            }
            throw new Error(errMsg);
        }
        markKotPrintedOnServer(kitchenOrderId);
        return { ...data, printer_name: printerName, printer_ip: ip, printer_port: port, print_mode: esc.print_mode };
    } catch (e) {
        clearTimeout(t);
        if (e && e.message && /no bar\/bot printer|not sending to kitchen kot|NO_PRINTER/i.test(String(e.message))) {
            throw e;
        }
        // First TCP after printer idle often fails; retry the SAME KOT (do not place a new order).
        if (e && e.message && /cannot be reached at/i.test(e.message)) {
            if (attempt < 3) {
                await new Promise(resolve => setTimeout(resolve, 800 * attempt));
                return printViaLocalBridgeKitchen(kitchenOrderId, attempt + 1);
            }
            throw e;
        }
        const isAbort = e && e.name === 'AbortError';
        const isFetchFail = !e || !e.message || /failed to fetch|networkerror|load failed|bridge connection/i.test(String(e.message));
        if (isAbort || isFetchFail) {
            if (attempt < 2) {
                await new Promise(resolve => setTimeout(resolve, 400 * attempt));
                return printViaLocalBridgeKitchen(kitchenOrderId, attempt + 1);
            }
            throw new Error('BRIDGE_DOWN');
        }
        if (attempt < 2) {
            await new Promise(resolve => setTimeout(resolve, 400 * attempt));
            return printViaLocalBridgeKitchen(kitchenOrderId, attempt + 1);
        }
        throw e;
    }
}

function markKotPrintedOnServer(kitchenOrderId) {
    if (!kitchenOrderId) return;
    fetch('/pos/kot-printed/' + kitchenOrderId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: '{}',
        cache: 'no-store',
    }).catch(() => {});
}

let pendingKotPrintBusy = false;
const pendingKotInFlight = new Set();

function updatePendingKotBadge(count) {
    const n = Number(count) || 0;
    const badge = document.getElementById('pendingKotBadge');
    if (badge) {
        badge.textContent = String(n);
        badge.classList.toggle('is-visible', n > 0);
    }
    const btn = document.getElementById('pendingKotPrintBtn');
    if (btn) {
        btn.title = n > 0
            ? (n + ' pending KOT/BOT — click to print via Print Bridge')
            : 'No pending KOTs — click to refresh / print';
    }
}

async function fetchPendingKotJobs() {
    const r = await fetch('/pos/pending-kot-prints?hours=12', {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store',
    });
    const data = await r.json().catch(() => ({}));
    if (!r.ok || !data.success) {
        return { count: 0, jobs: [] };
    }
    return { count: data.count || 0, jobs: data.jobs || [] };
}

async function refreshPendingKotBadge() {
    try {
        const { count } = await fetchPendingKotJobs();
        updatePendingKotBadge(count);
    } catch (_) {}
}

/**
 * Print KOTs that never made it to the kitchen printer (waiter phone has no Print Bridge).
 * @param {boolean} manual — true when cashier clicks the KOT button
 */
async function printPendingKotsNow(manual = false) {
    if (pendingKotPrintBusy) {
        if (manual) showToast('info', 'Already printing pending KOTs…');
        return;
    }
    pendingKotPrintBusy = true;
    try {
        const healthy = await localPrintBridgeHealthy();
        if (!healthy) {
            if (manual) showToast('error', 'Print Bridge is not running. Start Start-Print-Bridge.bat on this POS PC.');
            await refreshPendingKotBadge();
            return;
        }

        const { jobs } = await fetchPendingKotJobs();
        updatePendingKotBadge(jobs.length);
        if (!jobs.length) {
            if (manual) showToast('success', 'No pending KOTs to print');
            return;
        }

        let ok = 0;
        let fail = 0;
        for (const job of jobs) {
            const id = job.kitchen_order_id;
            if (!id || pendingKotInFlight.has(id)) continue;
            pendingKotInFlight.add(id);
            try {
                await printViaLocalBridgeKitchen(id);
                ok++;
                showKotPrintedLog('KOT printed');
                setWaiterAlertStatus(id, 'printed', 'KOT printed');
            } catch (e) {
                fail++;
                console.warn('Pending KOT print failed', id, e);
                setWaiterAlertStatus(id, 'error', (e && e.message) || 'Print failed');
                if (manual && String(e && e.message) === 'BRIDGE_DOWN') {
                    showToast('error', 'Print Bridge stopped — start Start-Print-Bridge.bat');
                    break;
                }
            } finally {
                pendingKotInFlight.delete(id);
            }
        }

        await refreshPendingKotBadge();
        if (manual || ok > 0) {
            if (ok && !fail) showToast('success', ok + ' pending KOT' + (ok === 1 ? '' : 's') + ' printed');
            else if (ok && fail) showToast('warning', ok + ' printed, ' + fail + ' failed');
            else if (fail && manual) showToast('error', 'Could not print pending KOTs');
        }
    } catch (e) {
        if (manual) showToast('error', (e && e.message) || 'Pending KOT check failed');
    } finally {
        pendingKotPrintBusy = false;
    }
}

async function pollPendingKotPrints() {
    try {
        const { count } = await fetchPendingKotJobs();
        updatePendingKotBadge(count);
        if (posBridgePrintPendingKot && count > 0) {
            await printPendingKotsNow(false);
        }
    } catch (_) {}
}

const WAITER_ALERT_SEEN_KEY = 'pos_waiter_alert_seen_v3';
const WAITER_ALERT_CLEARED_KEY = 'pos_waiter_alert_cleared_v2';
const WAITER_ALERT_HIDDEN_KEY = 'pos_waiter_alert_hidden_middle_v2';
let waiterAlertCards = new Map(); // kitchen_order_id -> el (middle tray)
let waiterAlertCache = new Map(); // kitchen_order_id -> alert payload
let waiterAlertBaselineDone = false;
let waiterAlertPollStarted = false;
let waiterAlertPanelOpen = false;

function loadWaiterAlertSeen() {
    try {
        return new Set(JSON.parse(sessionStorage.getItem(WAITER_ALERT_SEEN_KEY) || '[]'));
    } catch (_) {
        return new Set();
    }
}
function saveWaiterAlertSeen(set) {
    try {
        sessionStorage.setItem(WAITER_ALERT_SEEN_KEY, JSON.stringify([...set].slice(-120)));
    } catch (_) {}
}
function loadWaiterAlertCleared() {
    try {
        return new Set(JSON.parse(sessionStorage.getItem(WAITER_ALERT_CLEARED_KEY) || '[]'));
    } catch (_) {
        return new Set();
    }
}
function saveWaiterAlertCleared(set) {
    try {
        sessionStorage.setItem(WAITER_ALERT_CLEARED_KEY, JSON.stringify([...set].slice(-120)));
    } catch (_) {}
}
function loadWaiterAlertHiddenMiddle() {
    try {
        return new Set(JSON.parse(sessionStorage.getItem(WAITER_ALERT_HIDDEN_KEY) || '[]'));
    } catch (_) {
        return new Set();
    }
}
function saveWaiterAlertHiddenMiddle(set) {
    try {
        sessionStorage.setItem(WAITER_ALERT_HIDDEN_KEY, JSON.stringify([...set].slice(-120)));
    } catch (_) {}
}
let waiterAlertSeen = loadWaiterAlertSeen();
let waiterAlertCleared = loadWaiterAlertCleared();
let waiterAlertHiddenMiddle = loadWaiterAlertHiddenMiddle();

function woaEscape(str) {
    if (typeof escapeHtml === 'function') return escapeHtml(str);
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function ensureWaiterAlertDom() {
    let tray = document.getElementById('waiterAlertTray');
    const grid = document.getElementById('productsGrid');
    if (!tray) {
        tray = document.createElement('div');
        tray.id = 'waiterAlertTray';
        tray.setAttribute('aria-live', 'assertive');
        if (grid && grid.parentNode) {
            if (grid.nextSibling) grid.parentNode.insertBefore(tray, grid.nextSibling);
            else grid.parentNode.appendChild(tray);
        } else {
            document.body.appendChild(tray);
        }
    } else if (grid && tray.parentNode === grid.parentNode && (tray.compareDocumentPosition(grid) & Node.DOCUMENT_POSITION_FOLLOWING)) {
        // Tray is above grid — move below products
        if (grid.nextSibling) grid.parentNode.insertBefore(tray, grid.nextSibling);
        else grid.parentNode.appendChild(tray);
    }
    let stack = document.getElementById('waiterOrderAlertStack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'waiterOrderAlertStack';
        tray.appendChild(stack);
    } else if (stack.parentElement !== tray) {
        tray.appendChild(stack);
    }
    if (!document.getElementById('waiterOrderAlertStyles')) {
        const style = document.createElement('style');
        style.id = 'waiterOrderAlertStyles';
        style.textContent = `
#waiterAlertTray{width:100%;margin:8px 0;flex-shrink:0;z-index:30}
#waiterOrderAlertStack{display:none;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;width:100%;max-height:168px;overflow-y:auto;padding:2px}
#waiterOrderAlertStack.has-items{display:grid}
#waiterOrderAlertStack .woa-card{background:#1c1917;color:#fafaf9;border-radius:10px;border:1px solid #44403c;box-shadow:0 4px 12px rgba(0,0,0,.2);padding:8px;min-height:76px;display:flex;flex-direction:column;gap:4px}
#waiterOrderAlertStack .woa-card.is-new{border-color:#f59e0b}
#waiterOrderAlertStack .woa-card.is-modified{border-color:#38bdf8}
#waiterOrderAlertStack .woa-title{font-weight:700;font-size:.68rem;color:#fbbf24;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#waiterOrderAlertStack .woa-meta{font-size:.62rem;color:#d6d3d1;line-height:1.25;flex:1}
#waiterOrderAlertStack .woa-items{color:#a8a29e;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
#waiterOrderAlertStack .woa-actions{display:flex;gap:4px;margin-top:auto}
#waiterOrderAlertStack .woa-actions .btn{font-size:.58rem;padding:2px 5px;border-radius:5px;line-height:1.2;flex:1}
#waiterOrderAlertStack .woa-status{font-size:.58rem;font-weight:700;padding:1px 5px;border-radius:5px;display:inline-flex;width:fit-content}
#waiterOrderAlertStack .woa-status.pending{background:#422006;color:#fcd34d}
#waiterOrderAlertStack .woa-status.printed{background:#14532d;color:#86efac}
#waiterOrderAlertStack .woa-status.error{background:#7f1d1d;color:#fecaca}
@media (max-width:1100px){#waiterOrderAlertStack{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media (max-width:768px){#waiterOrderAlertStack{grid-template-columns:repeat(2,minmax(0,1fr));max-height:200px}}
`;
        document.head.appendChild(style);
    }
    let log = document.getElementById('kotPrintLogStack');
    if (!log) {
        log = document.createElement('div');
        log.id = 'kotPrintLogStack';
        log.setAttribute('aria-live', 'polite');
        document.body.appendChild(log);
    }
    return stack;
}

function updateWaiterAlertBadge() {
    const badge = document.getElementById('waiterAlertBadge');
    if (!badge) return;
    let pending = 0;
    waiterAlertCache.forEach((alert, id) => {
        if (waiterAlertCleared.has(Number(id))) return;
        pending++;
    });
    if (pending > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = String(pending);
    } else {
        badge.style.display = 'none';
        badge.textContent = '0';
    }
}

function positionWaiterAlertPanel() {
    const btn = document.getElementById('waiterAlertBtn');
    const panel = document.getElementById('waiterAlertPanel');
    if (!btn || !panel) return;

    const rect = btn.getBoundingClientRect();
    const width = Math.min(268, window.innerWidth - 16);
    let left = rect.right - width;
    left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
    const top = Math.min(rect.bottom + 8, Math.max(8, window.innerHeight - 120));

    panel.style.position = 'fixed';
    panel.style.top = top + 'px';
    panel.style.left = left + 'px';
    panel.style.right = 'auto';
    panel.style.width = width + 'px';
    panel.style.maxWidth = width + 'px';
    panel.style.zIndex = '30000';
}

let waiterAlertIgnoreOutsideUntil = 0;

function toggleWaiterAlertPanel(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const panel = document.getElementById('waiterAlertPanel');
    if (!panel) return;
    if (waiterAlertPanelOpen) {
        closeWaiterAlertPanel();
        return;
    }
    waiterAlertPanelOpen = true;
    waiterAlertIgnoreOutsideUntil = Date.now() + 400;
    positionWaiterAlertPanel();
    panel.classList.add('is-open');
    panel.style.display = 'block';
    panel.setAttribute('aria-hidden', 'false');
    renderWaiterAlertPanel();
}

function closeWaiterAlertPanel() {
    waiterAlertPanelOpen = false;
    const panel = document.getElementById('waiterAlertPanel');
    if (!panel) return;
    panel.classList.remove('is-open');
    panel.style.display = 'none';
    panel.setAttribute('aria-hidden', 'true');
}

function bindWaiterAlertUi() {
    const btn = document.getElementById('waiterAlertBtn');
    if (btn && !btn.dataset.woaBound) {
        btn.dataset.woaBound = '1';
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleWaiterAlertPanel(e);
        });
    }
    const closeBtn = document.getElementById('waiterAlertPanelCloseBtn');
    if (closeBtn && !closeBtn.dataset.woaBound) {
        closeBtn.dataset.woaBound = '1';
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeWaiterAlertPanel();
        });
    }
}

document.addEventListener('click', function (e) {
    if (!waiterAlertPanelOpen) return;
    if (Date.now() < waiterAlertIgnoreOutsideUntil) return;
    if (e.target.closest('#waiterAlertBtn') || e.target.closest('#waiterAlertPanel')) return;
    closeWaiterAlertPanel();
});
window.addEventListener('resize', function () {
    if (waiterAlertPanelOpen) positionWaiterAlertPanel();
});

// Ensure handlers exist even if DOMContentLoaded already fired
try { bindWaiterAlertUi(); } catch (_) {}
document.addEventListener('DOMContentLoaded', bindWaiterAlertUi);

window.toggleWaiterAlertPanel = toggleWaiterAlertPanel;
window.closeWaiterAlertPanel = closeWaiterAlertPanel;

function renderWaiterAlertPanel() {
    const list = document.getElementById('waiterAlertPanelList');
    if (!list) return;
    const items = [];
    waiterAlertCache.forEach((alert, id) => {
        if (waiterAlertCleared.has(Number(id))) return;
        items.push(alert);
    });
    items.sort((a, b) => Number(b.kitchen_order_id) - Number(a.kitchen_order_id));
    if (!items.length) {
        list.innerHTML = '<p class="waiter-alert-empty">No waiter alerts</p>';
        return;
    }
    list.innerHTML = items.map(alert => {
        const id = Number(alert.kitchen_order_id);
        const isModified = !!alert.is_reorder;
        const printed = !!alert.printed;
        const canPrint = id > 0 && (alert.type === 'kot' || alert.type === 'bot');
        const itemsTxt = (alert.item_names || []).slice(0, 2).join(', ');
        const more = (alert.items_count || 0) > 2 ? '…' : '';
        return `
        <div class="woa-panel-item${isModified ? ' is-modified' : ''}" data-kitchen-order-id="${id}">
            <div class="woa-panel-title">${isModified ? 'Modified' : 'New'} · ${woaEscape(alert.table_name || 'Table')}</div>
            <div class="woa-panel-meta">
                ${woaEscape(alert.waiter_name || 'Waiter')} · ${woaEscape(alert.order_number || '')}
                <div class="woa-panel-items" title="${woaEscape(itemsTxt)}${more}">${woaEscape(itemsTxt)}${more}</div>
                <div style="margin-top:3px;font-weight:700;font-size:.65rem;color:${printed ? '#86efac' : '#fcd34d'}">${printed ? 'Printed' : 'Pending'}</div>
            </div>
            <div class="woa-panel-actions">
                ${canPrint ? `<button type="button" class="btn btn-sm btn-success" onclick="printWaiterAlertKot(${id}, this)"><i class="fas fa-print me-1"></i>${printed ? 'Reprint' : 'Print'}</button>` : ''}
                <button type="button" class="btn btn-sm btn-outline-light" onclick="completeWaiterAlert(${id})"><i class="fas fa-times me-1"></i>Close</button>
            </div>
        </div>`;
    }).join('');
}

function announceWaiterOrderAlert(alert) {
    const table = alert.table_name || 'Table';
    const who = alert.waiter_name || 'Waiter';
    const isModified = !!alert.is_reorder;
    const title = isModified ? 'Order modified by waiter' : 'New order from waiter';

    if (typeof playKitchenBell === 'function') playKitchenBell();
    if (typeof showToast === 'function') {
        showToast('warning', `${title} — ${table} · ${who}`);
    }

    // Immediately push KOT to Print Bridge from this alert
    const id = Number(alert.kitchen_order_id || 0);
    if (id > 0 && !alert.printed && (alert.type === 'kot' || alert.type === 'bot')) {
        setTimeout(() => {
            try { printWaiterAlertKot(id, null); } catch (e) { console.warn('auto waiter KOT print failed', e); }
        }, 250);
    }
}

async function printOpenBillKots(orderId, kotIds, btn) {
    const ids = Array.isArray(kotIds) ? kotIds.map(Number).filter(Boolean) : [];
    if (!ids.length) {
        showToast('warning', 'No KOT tickets on this bill');
        return;
    }
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Printing…';
    }
    let ok = 0;
    let fail = 0;
    try {
        const healthy = await localPrintBridgeHealthy();
        if (!healthy) throw new Error('Print Bridge is not running');
        for (const id of ids) {
            try {
                await printViaLocalBridgeKitchen(id);
                ok++;
                setWaiterAlertStatus(id, 'printed', 'KOT printed');
            } catch (e) {
                fail++;
                console.warn('Open bill KOT print failed', id, e);
            }
        }
        if (ok) {
            showKotPrintedLog(ok === 1 ? 'KOT printed' : (ok + ' KOTs printed'));
            showToast('success', ok + ' KOT printed');
        }
        if (fail && !ok) showToast('error', 'KOT print failed — check Print Bridge');
        else if (fail) showToast('warning', ok + ' printed, ' + fail + ' failed');
        if (typeof openBillTableModal === 'function' && document.getElementById('billTableModal')?.classList.contains('show')) {
            openBillTableModal();
        }
        refreshPendingKotBadge();
        renderWaiterAlertPanel();
    } catch (e) {
        showToast('error', (e && e.message) || 'KOT print failed');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-print me-1"></i>KOT print';
        }
    }
}

function showKotPrintedLog(message) {
    ensureWaiterAlertDom();
    const stack = document.getElementById('kotPrintLogStack');
    if (!stack) return;
    const el = document.createElement('div');
    el.className = 'kot-log';
    el.innerHTML = '<i class="fas fa-check-circle me-1"></i>' + (message || 'KOT printed');
    stack.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity .4s';
        setTimeout(() => el.remove(), 400);
    }, 4500);
}

function setWaiterAlertStatus(kitchenOrderId, state, text) {
    const id = Number(kitchenOrderId);
    const cached = waiterAlertCache.get(id);
    if (cached && state === 'printed') {
        cached.printed = true;
        waiterAlertCache.set(id, cached);
    }
    const card = waiterAlertCards.get(id);
    if (card) {
        const status = card.querySelector('.woa-status');
        if (status) {
            status.className = 'woa-status ' + state;
            status.innerHTML = state === 'printed'
                ? '<i class="fas fa-check"></i> ' + (text || 'KOT printed')
                : state === 'error'
                    ? '<i class="fas fa-exclamation-triangle"></i> ' + (text || 'Print failed')
                    : '<i class="fas fa-clock"></i> ' + (text || 'KOT pending');
        }
        const printBtn = card.querySelector('.woa-actions .btn-success');
        if (printBtn && state === 'printed') {
            printBtn.innerHTML = '<i class="fas fa-print me-1"></i>Reprint';
        }
    }
    renderWaiterAlertPanel();
    updateWaiterAlertBadge();
}

async function printWaiterAlertKot(kitchenOrderId, btn) {
    const id = Number(kitchenOrderId);
    if (!id || id < 0) return;
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Printing…';
    }
    setWaiterAlertStatus(id, 'pending', 'Printing…');
    try {
        const healthy = await localPrintBridgeHealthy();
        if (!healthy) {
            throw new Error('Print Bridge is not running');
        }
        await printViaLocalBridgeKitchen(id);
        setWaiterAlertStatus(id, 'printed', 'KOT printed');
        showKotPrintedLog('KOT printed');
        showToast('success', 'KOT printed');
        refreshPendingKotBadge();
        // Keep alert visible (Printed) until cashier clicks Close
        syncWaiterMiddleGrid();
        renderWaiterAlertPanel();
        updateWaiterAlertBadge();
    } catch (e) {
        const msg = (e && e.message) ? String(e.message) : 'Print failed';
        setWaiterAlertStatus(id, 'error', msg === 'BRIDGE_DOWN' ? 'Print Bridge off' : msg);
        showToast('error', msg === 'BRIDGE_DOWN' ? 'Start Print Bridge on this PC' : msg);
    } finally {
        if (btn) {
            btn.disabled = false;
            const printed = !!(waiterAlertCache.get(id)?.printed);
            btn.innerHTML = '<i class="fas fa-print me-1"></i>' + (printed ? 'Reprint' : 'Print KOT');
        }
    }
}

function openBillsFromWaiterAlert(orderId) {
    if (typeof openBillTableModal === 'function') {
        openBillTableModal();
    }
    if (orderId && typeof editOrder === 'function') {
        setTimeout(() => editOrder(Number(orderId)), 700);
    }
}

function dismissWaiterAlert(kitchenOrderId, fromPanel = false) {
    const id = Number(kitchenOrderId);
    waiterAlertSeen.add(id);
    saveWaiterAlertSeen(waiterAlertSeen);

    // Middle Close: hide banner only; keep in Waiter topbar until panel Close / completed
    waiterAlertHiddenMiddle.add(id);
    saveWaiterAlertHiddenMiddle(waiterAlertHiddenMiddle);

    const card = waiterAlertCards.get(id);
    if (card) {
        card.remove();
        waiterAlertCards.delete(id);
    }

    if (fromPanel) {
        waiterAlertCleared.add(id);
        saveWaiterAlertCleared(waiterAlertCleared);
        waiterAlertCache.delete(id);
    }

    if (typeof syncWaiterMiddleGrid === 'function') syncWaiterMiddleGrid();
    renderWaiterAlertPanel();
    updateWaiterAlertBadge();
}

function hideWaiterAlertMiddle(kitchenOrderId) {
    dismissWaiterAlert(kitchenOrderId, false);
}

function completeWaiterAlert(kitchenOrderId) {
    dismissWaiterAlert(kitchenOrderId, true);
}

window.hideWaiterAlertMiddle = hideWaiterAlertMiddle;
window.completeWaiterAlert = completeWaiterAlert;
window.printWaiterAlertKot = printWaiterAlertKot;

function buildWaiterAlertCardHtml(alert, isNew) {
    const id = Number(alert.kitchen_order_id);
    const items = (alert.item_names || []).slice(0, 2).join(', ');
    const more = (alert.items_count || 0) > 2 ? '…' : '';
    const printed = !!alert.printed;
    const isModified = !!alert.is_reorder;
    const canPrint = id > 0 && (alert.type === 'kot' || alert.type === 'bot');
    const titleText = isModified ? 'Modified' : 'New order';

    return {
        className: 'woa-card' + (isNew && !printed ? ' is-new' : '') + (isModified ? ' is-modified' : ''),
        html: `
        <div class="woa-title" title="${woaEscape(titleText)} · ${woaEscape(alert.table_name || '')}">
            ${titleText} · ${woaEscape(alert.table_name || 'Table')}
        </div>
        <div class="woa-meta">
            <div>${woaEscape(alert.waiter_name || 'Waiter')}</div>
            <div class="woa-items" title="${woaEscape(items)}${more}">${woaEscape(items)}${more}</div>
        </div>
        <div class="woa-status ${printed ? 'printed' : 'pending'}">${printed ? 'Printed' : 'Pending'}</div>
        <div class="woa-actions">
            ${canPrint ? `<button type="button" class="btn btn-sm btn-success" onclick="printWaiterAlertKot(${id}, this)">${printed ? 'Reprint' : 'Print'}</button>` : ''}
            <button type="button" class="btn btn-sm btn-outline-light" onclick="hideWaiterAlertMiddle(${id})">Close</button>
        </div>`
    };
}

function syncWaiterMiddleGrid(highlightIds = []) {
    const stack = ensureWaiterAlertDom();
    if (!stack) return;
    const highlight = new Set((highlightIds || []).map(Number));
    const items = [];
    waiterAlertCache.forEach((alert, id) => {
        const nid = Number(id);
        if (waiterAlertCleared.has(nid)) return;
        if (waiterAlertHiddenMiddle.has(nid)) return;
        items.push(alert);
    });
    items.sort((a, b) => Number(b.kitchen_order_id) - Number(a.kitchen_order_id));
    const shown = items.slice(0, 8);
    stack.innerHTML = '';
    waiterAlertCards.clear();
    shown.forEach(alert => {
        const id = Number(alert.kitchen_order_id);
        const built = buildWaiterAlertCardHtml(alert, highlight.has(id) || !alert.printed);
        const card = document.createElement('div');
        card.className = built.className;
        card.dataset.kitchenOrderId = String(id);
        card.innerHTML = built.html;
        stack.appendChild(card);
        waiterAlertCards.set(id, card);
    });
    stack.classList.toggle('has-items', shown.length > 0);
}

function renderWaiterOrderAlert(alert, isNew, opts = {}) {
    const showMiddle = opts.showMiddle !== false;
    const id = Number(alert.kitchen_order_id);
    waiterAlertCache.set(id, alert);

    if (waiterAlertCleared.has(id)) {
        updateWaiterAlertBadge();
        if (waiterAlertPanelOpen) renderWaiterAlertPanel();
        return;
    }

    if (!showMiddle) {
        waiterAlertHiddenMiddle.add(id);
        saveWaiterAlertHiddenMiddle(waiterAlertHiddenMiddle);
    } else if (isNew) {
        waiterAlertHiddenMiddle.delete(id);
        saveWaiterAlertHiddenMiddle(waiterAlertHiddenMiddle);
    }

    syncWaiterMiddleGrid(isNew ? [id] : []);
    updateWaiterAlertBadge();
    if (waiterAlertPanelOpen) renderWaiterAlertPanel();
}

async function pollWaiterOrderAlerts() {
    try {
        ensureWaiterAlertDom();
        const r = await fetch('/pos/waiter-order-alerts?minutes=90', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
            credentials: 'same-origin',
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.success) {
            console.warn('waiter alerts HTTP', r.status, data);
            return;
        }
        const alerts = data.alerts || [];
        const activeIds = new Set(alerts.map(a => Number(a.kitchen_order_id)));

        // Drop cache entries no longer returned by API (completed / paid / old)
        [...waiterAlertCache.keys()].forEach(id => {
            if (!activeIds.has(Number(id))) {
                waiterAlertCache.delete(Number(id));
                const card = waiterAlertCards.get(Number(id));
                if (card) {
                    card.remove();
                    waiterAlertCards.delete(Number(id));
                }
            }
        });

        if (!waiterAlertBaselineDone) {
            // Seed seen; keep alerts (printed or not) until cashier closes them
            alerts.forEach(alert => {
                const id = Number(alert.kitchen_order_id);
                waiterAlertSeen.add(id);
                waiterAlertCache.set(id, alert);
                if (waiterAlertCleared.has(id)) {
                    waiterAlertHiddenMiddle.add(id);
                }
            });
            saveWaiterAlertSeen(waiterAlertSeen);
            saveWaiterAlertHiddenMiddle(waiterAlertHiddenMiddle);
            waiterAlertBaselineDone = true;
            syncWaiterMiddleGrid();
            updateWaiterAlertBadge();
            renderWaiterAlertPanel();
            return;
        }

        let announced = false;
        const freshIds = [];
        alerts.forEach(alert => {
            const id = Number(alert.kitchen_order_id);
            if (waiterAlertCleared.has(id)) {
                return;
            }
            const isNew = !waiterAlertSeen.has(id);
            if (isNew) {
                waiterAlertHiddenMiddle.delete(id);
                freshIds.push(id);
            }
            const showMiddle = isNew || !waiterAlertHiddenMiddle.has(id);
            renderWaiterOrderAlert(alert, isNew, { showMiddle });
            if (isNew) {
                waiterAlertSeen.add(id);
                if (!announced) {
                    announced = true;
                    announceWaiterOrderAlert(alert);
                }
            }
        });
        saveWaiterAlertHiddenMiddle(waiterAlertHiddenMiddle);
        saveWaiterAlertSeen(waiterAlertSeen);
        syncWaiterMiddleGrid(freshIds);
        updateWaiterAlertBadge();
        if (waiterAlertPanelOpen) renderWaiterAlertPanel();
    } catch (e) {
        console.warn('waiter alerts poll failed', e);
    }
}

function startWaiterOrderAlertPolling() {
    if (waiterAlertPollStarted) return;
    waiterAlertPollStarted = true;
    const tick = () => {
        try { pollWaiterOrderAlerts(); } catch (e) { console.warn(e); }
    };
    tick();
    smartPosInterval(tick, 3000);
}

function printKitchenTicketSmart(job, options = {}) {
    const forceAsk = !!options.forceAsk;
    const id = job.kitchen_order_id;
    const label = (job.type || 'Ticket').toUpperCase();
    const mode = String(job.print_mode || 'preview');
    const printerIp = String(job.printer_ip || '').trim();
    const printerName = String(job.printer_name || '').trim();
    const hasPrinter = !!(printerIp || (printerName && !/^kitchen printer$/i.test(printerName)));
    const isKot = String(job.type || '').toLowerCase() === 'kot';
    const isBot = String(job.type || '').toLowerCase() === 'bot';

    if (!id) {
        if (isKot) {
            showToast('error', 'Kitchen KOT printer job missing — cannot print');
            return;
        }
        if (job.url) openPrintPreview(job.url);
        return;
    }

    // BOT with no bar printer: never send to kitchen KOT
    if (isBot && !hasPrinter) {
        markKotPrintedOnServer(id);
        if (forceAsk && job.url) {
            openPrintPreview(job.url);
            return;
        }
        showToast('info', 'BOT skipped — no bar printer configured (not sent to KOT)');
        return;
    }

    // Kitchen KOT: Cloud POS -> Local Print Bridge -> TCP kitchen IP:port (ESC/POS). Never window.print().
    if (isKot) {
        (async () => {
            let printerPort = Number(job.printer_port || 9100) || 9100;

            const healthy = await localPrintBridgeHealthy();
            if (!healthy) {
                showToast('error', 'Print Bridge is not running. Please start Start-Print-Bridge.bat.');
                return;
            }

            try {
                const data = await printViaLocalBridgeKitchen(id);
                const dest = (data.printer_ip || printerIp)
                    ? ((data.printer_ip || printerIp) + ':' + (data.printer_port || printerPort))
                    : (data.printer_name || 'kitchen printer');
                showToast('success', data.message || ('KOT sent to ' + dest));
            } catch (e) {
                const msg = (e && e.message) ? String(e.message) : '';
                if (msg === 'BRIDGE_DOWN') {
                    showToast('error', 'Print Bridge is not running. Please start Start-Print-Bridge.bat.');
                    return;
                }
                if (/cannot be reached at/i.test(msg)) {
                    showToast('error', msg);
                    return;
                }
                // Fallback message with configured IP when known
                if (printerIp) {
                    showToast('error', 'Kitchen printer cannot be reached at ' + printerIp + ':' + printerPort + '.');
                } else {
                    showToast('error', msg || 'Kitchen printer is offline or unreachable.');
                }
            }
        })();
        return;
    }

    // BOT / other: Direct = Print Bridge path (only when bar printer exists)
    if (!forceAsk && mode === 'direct' && hasPrinter) {
        (async () => {
            try {
                const healthy = await localPrintBridgeHealthy();
                if (!healthy) {
                    try {
                        const data = await printViaLocalBridgeKitchen(id);
                        showToast('success', data.message || (label + ' printed on ' + (data.printer_name || job.printer_name || 'printer')));
                        return;
                    } catch (_) {
                        showToast('warning', 'Start Print Bridge — trying network fallback…');
                    }
                } else {
                    const data = await printViaLocalBridgeKitchen(id);
                    showToast('success', data.message || (label + ' printed on ' + (data.printer_name || job.printer_name || 'printer')));
                    return;
                }
            } catch (e) {
                const msg = (e && e.message) ? String(e.message) : '';
                if (/no bar\/bot printer|not sending to kitchen kot/i.test(msg)) {
                    markKotPrintedOnServer(id);
                    showToast('info', 'BOT skipped — no bar printer configured');
                    return;
                }
                console.warn('Kitchen print bridge failed', e);
                showToast('warning', msg || 'Local bridge failed');
            }

            if (job.printer_ip) {
                try {
                    const r = await fetch('/pos/network-print/' + id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({}),
                    });
                    const data = await r.json();
                    if (data.success) {
                        showToast('success', data.message || (label + ' sent to ' + (job.printer_name || job.printer_ip)));
                        return;
                    }
                    showToast('warning', (data.message || 'Network print failed') + ' — opening preview');
                } catch (_) {
                    showToast('warning', 'Network print failed — opening preview');
                }
            }
            if (job.url) openPrintPreview(job.url);
        })();
        return;
    }

    // Preview mode (BOT): silent TCP when Ask Before Printing is OFF
    const canSilent = !printAskBefore && !forceAsk && job.printer_ip && id;
    if (canSilent) {
        fetch('/pos/network-print/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', label + ' sent to ' + (job.printer_name || job.printer_ip || 'printer'));
            } else {
                showToast('warning', (data.message || 'Network print failed') + ' — opening preview');
                openPrintPreview(job.url);
            }
        })
        .catch(() => {
            showToast('warning', 'Network print failed — opening preview');
            openPrintPreview(job.url);
        });
        return;
    }

    if (job.url) openPrintPreview(job.url);
}

async function printViaLocalBridge(orderId = null, attempt = 1) {
    const escUrl = orderId
        ? ('/pos/receipt-escpos/' + orderId)
        : '/pos/last-receipt-escpos';
    const escRes = await fetch(escUrl, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        cache: 'no-store',
    });
    const esc = await escRes.json().catch(() => ({}));
    if (!escRes.ok || !esc.success || !esc.payload_base64) {
        throw new Error(esc.message || 'Could not build receipt data');
    }
    if (esc.order_id) lastSaleOrderId = esc.order_id;

    const ip = (esc.printer_ip || receiptPrinterIp || '').trim();
    const port = Number(esc.printer_port || receiptPrinterPort || 9100) || 9100;
    const printerName = (esc.printer_name || receiptPrinterName || 'XP-80C').trim() || 'XP-80C';
    // Bill printer only — never fall back to KOT Windows queue
    const mode = ip ? 'tcp' : 'windows';

    const ctrl = new AbortController();
    const t = setTimeout(() => ctrl.abort(), 12000);
    try {
        const r = await fetch(receiptBridgeBase() + '/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                ip: ip || null,
                port,
                printer_name: printerName,
                mode,
                job_type: 'receipt',
                allow_windows_fallback: false,
                data: esc.payload_base64,
            }),
            signal: ctrl.signal,
            cache: 'no-store',
        });
        clearTimeout(t);
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'Local Print Bridge failed');
        }
        return data;
    } catch (e) {
        clearTimeout(t);
        const msg = (e && e.name === 'AbortError')
            ? 'Print Bridge timed out — is it running in the background?'
            : ((e && e.message) ? e.message : 'Print Bridge connection failed');
        if (attempt < 3) {
            await new Promise(resolve => setTimeout(resolve, 450 * attempt));
            return printViaLocalBridge(orderId, attempt + 1);
        }
        throw new Error(msg + ' — run Start-Print-Bridge.bat (hidden background)');
    }
}

function printViaServerNetwork(url, orderId, isLast) {
    const endpoint = orderId
        ? ('/pos/network-print-receipt/' + orderId)
        : '/pos/network-print-last-receipt';
    return fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
    }).then(r => r.json());
}

/** Direct print: Local Bridge on POS PC → server TCP → preview.
 *  options.forceDirect: print via XP-80C bridge/network only (never window.print / never re-open preview).
 */
function printReceiptSmart(url, orderId = null, options = {}) {
    if (!url && !orderId) return;
    const forceDirect = !!(options && options.forceDirect);
    const id = orderId || extractOrderIdFromPrintUrl(url);
    const isLast = !id && url && String(url).indexOf('last-receipt') !== -1;

    if (!forceDirect && (String(receiptPrintMode || 'preview') !== 'direct' || (!id && !isLast))) {
        if (url) openPrintPreview(url);
        return;
    }

    if (forceDirect && !id && !isLast) {
        showToast('warning', 'Receipt print needs an order — cannot send to XP-80C');
        return;
    }

    (async () => {
        try {
            const healthy = await localPrintBridgeHealthy();
            if (!healthy) {
                try {
                    const data = await printViaLocalBridge(id || null);
                    showToast('success', data.message || ('Printed on ' + (receiptPrinterName || 'XP-80C')));
                    if (openDrawerAfterPrint) setTimeout(() => kickCashDrawerSilent(), 400);
                    return;
                } catch (_) {
                    showToast('warning', 'Start Start-Print-Bridge.bat on this PC — trying fallback…');
                }
            } else {
                const data = await printViaLocalBridge(id || null);
                showToast('success', data.message || ('Printed on ' + (receiptPrinterName || 'XP-80C')));
                if (openDrawerAfterPrint) setTimeout(() => kickCashDrawerSilent(), 400);
                return;
            }
        } catch (e) {
            console.warn('Local print bridge failed', e);
            showToast('warning', (e && e.message) ? e.message : 'Local bridge failed');
        }

        try {
            const data = await printViaServerNetwork(url, id, isLast);
            if (data.success) {
                if (data.order_id) lastSaleOrderId = data.order_id;
                showToast('success', data.message || ('Printed on ' + (receiptPrinterName || 'XP-80C')));
                if (openDrawerAfterPrint) setTimeout(() => kickCashDrawerSilent(), 400);
                return;
            }
            if (forceDirect) {
                showToast('warning', (data.message || 'Direct print failed') + ' — start Start-Print-Bridge.bat (XP-80C)');
                return;
            }
            showToast('warning', (data.message || 'Direct print failed') + ' — opening preview');
            if (url) openPrintPreview(url);
        } catch (e) {
            if (forceDirect) {
                showToast('warning', 'Direct print failed — open Start-Print-Bridge.bat on POS PC');
                return;
            }
            showToast('warning', 'Direct print failed — open Start-Print-Bridge.bat on POS PC');
            if (url) openPrintPreview(url);
        }
    })();
}

async function kickCashDrawerSilent() {
    const bridgeUrl = receiptBridgeBase();
    const printerName = (receiptPrinterName || 'XP-80C');
    try {
        if (!(await localPrintBridgeHealthy())) return;
        let payloadB64 = null;
        try {
            const r = await fetch('/pos/register/open-drawer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
            });
            const data = await r.json().catch(() => ({}));
            if (data.success && data.needs_local_bridge === false) return;
            payloadB64 = data.payload_base64 || null;
        } catch (e) { /* use bridge default */ }

        let r = await fetch(bridgeUrl + '/drawer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                printer_name: printerName,
                mode: 'windows',
                job_type: 'drawer',
                allow_windows_fallback: false,
                data: payloadB64 || null,
            }),
        });
        if (r.status === 404) {
            await fetch(bridgeUrl + '/print', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    printer_name: printerName,
                    mode: 'windows',
                    job_type: 'drawer',
                    allow_windows_fallback: false,
                    data: payloadB64,
                }),
            });
        }
    } catch (e) {
        console.warn('Drawer kick after print failed', e);
    }
}

/** Handle KOT/BOT jobs: Direct Print Bridge → server TCP → preview */
function handlePrintJobs(jobs, options = {}) {
    const force = !!options.force;
    const forceAsk = !!options.forceAsk;
    const jobsList = Array.isArray(jobs) ? jobs : [];
    if (!jobsList.length) return;

    jobsList.forEach(job => {
        const shouldPrint = force || forceAsk || job.auto_print || autoPrintKot;
        if (!shouldPrint) return;
        printKitchenTicketSmart(job, { forceAsk });
    });
}

function handleReceiptPrint(url, orderId = null, force = false) {
    if (!url && !orderId) return;
    // Always ask before printing the invoice — Yes prints, No stays on POS
    Swal.fire({
        title: 'Print invoice?',
        text: 'Send this bill to the receipt printer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, print',
        cancelButtonText: 'No',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
    }).then(result => {
        if (!result.isConfirmed) {
            showToast('info', 'Invoice not printed');
            return;
        }
        printReceiptSmart(url, orderId, force ? { forceDirect: true } : {});
    });
}

function processPrintPreviewQueue() {
    if (printPreviewBusy || printPreviewQueue.length === 0) return;

    printPreviewBusy = true;
    const url = printPreviewQueue.shift();
    const modalEl = document.getElementById('printPreviewModal');
    const frame = document.getElementById('printPreviewFrame');
    const separator = url.includes('?') ? '&' : '?';
    frame.src = url + separator + 'format=html';

    // Always stack print preview above any open modal (Recent Orders, order detail, etc.)
    const openModals = document.querySelectorAll('.modal.show').length;
    const baseZ = 1060 + (openModals * 20);
    modalEl.style.zIndex = String(baseZ);

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });
    const onShown = () => {
        modalEl.removeEventListener('shown.bs.modal', onShown);
        const backdrops = document.querySelectorAll('.modal-backdrop');
        const lastBackdrop = backdrops[backdrops.length - 1];
        if (lastBackdrop) {
            lastBackdrop.style.zIndex = String(baseZ - 5);
            lastBackdrop.classList.add('print-preview-backdrop');
        }
        modalEl.focus();
    };
    const onHidden = () => {
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
        printPreviewBusy = false;
        frame.src = 'about:blank';
        modalEl.style.zIndex = '';
        processPrintPreviewQueue();
    };
    modalEl.addEventListener('shown.bs.modal', onShown);
    modalEl.addEventListener('hidden.bs.modal', onHidden);
    modal.show();
}

function isCashierReceiptPrintUrl(url) {
    const s = String(url || '');
    return /print-receipt|last-receipt/i.test(s);
}

function hidePrintPreviewModal() {
    const modalEl = document.getElementById('printPreviewModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function printPreviewFrame() {
    const frame = document.getElementById('printPreviewFrame');
    if (!frame) return;
    const src = frame.src || '';

    if (typeof isKotPrintUrl === 'function' && isKotPrintUrl(src)) {
        const kid = extractKitchenOrderIdFromUrl(src);
        if (kid) {
            printKitchenTicketSmart({
                type: 'kot',
                kitchen_order_id: kid,
                printer_name: 'Kitchen Printer',
                print_mode: 'direct',
                auto_print: true,
            });
            hidePrintPreviewModal();
            return;
        }
        fetch('/pos/last-kot?format=json', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            cache: 'no-store',
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.kitchen_order_id) {
                showToast('error', data.message || 'No KOT found');
                return;
            }
            printKitchenTicketSmart({
                type: 'kot',
                kitchen_order_id: data.kitchen_order_id,
                printer_ip: data.printer_ip,
                printer_port: data.printer_port,
                printer_name: data.printer_name,
                print_mode: 'direct',
                auto_print: true,
                url: data.url,
            });
            hidePrintPreviewModal();
        })
        .catch(() => showToast('error', 'Could not load KOT for direct print'));
        return;
    }

    // Cashier receipt (XP-80C): Print Bridge / network — NEVER browser/Windows print dialog
    if (isCashierReceiptPrintUrl(src)) {
        const cleanUrl = String(src)
            .replace(/([?&])format=html(&)?/i, (_, p1, p2) => (p2 ? p1 : ''))
            .replace(/[?&]$/, '');
        const orderId = extractOrderIdFromPrintUrl(cleanUrl) || lastSaleOrderId || null;
        printReceiptSmart(cleanUrl, orderId, { forceDirect: true });
        hidePrintPreviewModal();
        return;
    }

    // Non-receipt docs only (shift/day-end reports, etc.)
    if (frame.contentWindow) {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    }
}

// Payment Modal Functions
function getPaymentBillTotal() {
    if (settleOrderId) return settleTotal;
    return parseFloat(document.getElementById('totalAmount').textContent.replace(/[^\d.]/g, '')) || 0;
}

function paymentMethodLabel(method) {
    return ({
        cash: 'Cash',
        card: 'Card',
        bank_transfer: 'Bank',
        online: 'Online',
        credit: 'Credit',
    })[method] || method;
}

function paymentLinesSum() {
    return paymentLines.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
}

function estimateCardSurcharge(baseCardAmount) {
    if (!cardSurchargeEnabled || !(cardSurchargePercent > 0)) return 0;
    const base = baseCardAmount != null
        ? Number(baseCardAmount)
        : paymentLines.filter(p => p.method === 'card').reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
    return Math.round(base * (cardSurchargePercent / 100) * 100) / 100;
}

function getPayableWithSurcharge() {
    return Math.round((getPaymentBillTotal() + estimateCardSurcharge()) * 100) / 100;
}

function paymentRemaining() {
    return Math.max(0, Math.round((getPaymentBillTotal() - paymentLinesSum()) * 100) / 100);
}

function openPaymentModal() {
    if (!openBillId && !settleOrderId && cart.length === 0) {
        showToast('warning', 'Cart is empty');
        return;
    }
    if (!openBillId && !settleOrderId && getActiveOrderType() === 'dine_in' && tableSelectionRequired && !document.getElementById('tableSelect').value) {
        if (typeof showToast === 'function') showToast('warning', 'Please select a table for dine-in');
        openTableModal();
        return;
    }
    if (!openBillId && !settleOrderId && getActiveOrderType() === 'delivery') {
        if (deliveryPayMode === 'cod' || deliveryPayMode === 'partner') {
            placeDeliveryCodOrder();
            return;
        }
        if (!validateDeliveryFields()) return;
    }

    // When editing an open bill, pay the live cart total (includes new retail)
    if (openBillId) {
        settleOrderId = openBillId;
        settleTotal = parseFloat(document.getElementById('totalAmount').textContent.replace(/[^\d.]/g, '')) || 0;
    }

    paymentLines = [];
    paymentLineSeq = 1;
    selectedMethod = getActiveOrderType() === 'delivery' ? 'bank_transfer' : 'cash';

    const modal = document.getElementById('paymentModal');
    modal.addEventListener('hidden.bs.modal', () => {
        if (!openBillId) {
            settleOrderId = null;
            settleTotal = 0;
        }
        paymentLines = [];
        document.getElementById('paymentTotal').textContent = currencySymbol + ' ' + document.getElementById('totalAmount').textContent.replace(/^[^\d]+/, '');
    }, { once: true });
    modal.addEventListener('shown.bs.modal', () => {
        const body = modal.querySelector('.payment-modal-body');
        if (body) body.scrollTop = 0;
    }, { once: true });
    new bootstrap.Modal(modal).show();

    const total = getPaymentBillTotal();
    document.getElementById('paymentTotal').textContent = currencySymbol + ' ' + total.toFixed(2);
    document.getElementById('cashReceived').value = '';
    document.getElementById('paymentNotes').value = '';
    document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
    const hint = document.getElementById('deliveryPayHint');
    if (hint) {
        hint.style.display = (!openBillId && !settleOrderId && getActiveOrderType() === 'delivery') ? 'block' : 'none';
    }
    renderPaymentLines();
    updatePaymentBalanceUI();
}

function selectPaymentMethod(method) {
    addPaymentLine(method);

    // Full pay: auto-close & print — no Complete Payment needed
    const autoMethods = ['cash', 'card', 'credit', 'bank_transfer'];
    if (autoMethods.includes(method) && paymentRemaining() <= 0.009) {
        clearTimeout(window.__payAutoTimer);
        window.__payAutoTimer = setTimeout(() => processPayment(), 120);
    }
}

function addPaymentLine(method) {
    const remaining = paymentRemaining();
    if (remaining <= 0) {
        showToast('info', 'Bill already covered');
        return;
    }

    let amount = parseFloat(document.getElementById('cashReceived').value);
    if (!amount || amount <= 0) {
        amount = remaining;
    }

    if (method !== 'cash' && amount > remaining + 0.009) {
        amount = remaining;
    }

    paymentLines.push({
        id: paymentLineSeq++,
        method,
        amount: Math.round(amount * 100) / 100,
    });
    selectedMethod = method;
    document.getElementById('cashReceived').value = '';
    document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
    const btn = document.querySelector(`[data-method="${method}"]`);
    if (btn) btn.classList.add('active');
    renderPaymentLines();
    updatePaymentBalanceUI();
}

function removePaymentLine(id) {
    paymentLines = paymentLines.filter(p => p.id !== id);
    renderPaymentLines();
    updatePaymentBalanceUI();
}

function renderPaymentLines() {
    const box = document.getElementById('splitPayments');
    const wrap = document.getElementById('splitPaymentContainer');
    if (!box) return;
    if (!paymentLines.length) {
        if (isIceCreamUi) {
            box.innerHTML = '<div class="text-muted small py-2">Enter amount, then tap Cash / Card / … to add</div>';
            if (wrap) wrap.classList.remove('d-none');
        } else {
            box.innerHTML = '';
            if (wrap) wrap.classList.add('d-none');
        }
        return;
    }
    if (wrap) wrap.classList.remove('d-none');
    const surcharge = estimateCardSurcharge();
    box.innerHTML = paymentLines.map(p => {
        const lineSurcharge = (p.method === 'card' && cardSurchargeEnabled)
            ? Math.round((Number(p.amount) * (cardSurchargePercent / 100)) * 100) / 100
            : 0;
        return `
        <div class="d-flex align-items-center justify-content-between py-2 px-3 mb-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
            <div>
                <span class="fw-semibold">${paymentMethodLabel(p.method)}</span>
                <span class="text-muted ms-2">${currencySymbol} ${Number(p.amount).toFixed(2)}</span>
                ${lineSurcharge > 0 ? `<div class="small text-muted">+ Card ${cardSurchargePercent}% → ${currencySymbol} ${(Number(p.amount) + lineSurcharge).toFixed(2)}</div>` : ''}
            </div>
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removePaymentLine(${p.id})" title="Remove">&times;</button>
        </div>`;
    }).join('') + (surcharge > 0.009 ? `
        <div class="alert alert-warning py-2 px-3 small mb-0 mt-1">
            Card surcharge (${cardSurchargePercent}%): <strong>${currencySymbol} ${surcharge.toFixed(2)}</strong>
            · Charged total: <strong>${currencySymbol} ${getPayableWithSurcharge().toFixed(2)}</strong>
        </div>` : '');
}

function updatePaymentBalanceUI() {
    const total = getPaymentBillTotal();
    const surcharge = estimateCardSurcharge();
    const payable = Math.round((total + surcharge) * 100) / 100;
    const paid = paymentLinesSum();
    const remaining = paymentRemaining();
    const entered = parseFloat(document.getElementById('cashReceived')?.value) || 0;
    const changeFromLines = Math.max(0, Math.round((paid + surcharge - payable) * 100) / 100);

    const payTotalEl = document.getElementById('paymentTotal');
    if (payTotalEl) payTotalEl.textContent = currencySymbol + ' ' + payable.toFixed(2);

    const remEl = document.getElementById('paymentRemaining');
    const labelEl = document.getElementById('paymentBalanceLabel');
    const paidEl = document.getElementById('paymentPaidSum');

    // Live Balance while typing (overpay = change, still labeled Balance)
    if (remEl) {
        if (labelEl) labelEl.textContent = surcharge > 0.009 && remaining <= 0.009 ? 'To charge' : 'Balance';
        if (entered > 0.009 && remaining > 0.009) {
            if (entered + 0.009 >= remaining) {
                const change = Math.round((entered - remaining) * 100) / 100;
                remEl.textContent = currencySymbol + ' ' + change.toFixed(2);
                remEl.style.color = '#059669';
                remEl.closest('.payment-balance-chip')?.classList.add('is-change');
            } else {
                const stillDue = Math.round((remaining - entered) * 100) / 100;
                remEl.textContent = currencySymbol + ' ' + stillDue.toFixed(2);
                remEl.style.color = '#dc2626';
                remEl.closest('.payment-balance-chip')?.classList.remove('is-change');
            }
        } else if (remaining <= 0.009 && changeFromLines > 0.009) {
            remEl.textContent = currencySymbol + ' ' + changeFromLines.toFixed(2);
            remEl.style.color = '#059669';
            remEl.closest('.payment-balance-chip')?.classList.add('is-change');
        } else if (remaining <= 0.009 && surcharge > 0.009) {
            remEl.textContent = currencySymbol + ' ' + payable.toFixed(2);
            remEl.style.color = '#059669';
            remEl.closest('.payment-balance-chip')?.classList.remove('is-change');
        } else {
            remEl.textContent = currencySymbol + ' ' + remaining.toFixed(2);
            remEl.style.color = remaining > 0.009 ? '#dc2626' : '#059669';
            remEl.closest('.payment-balance-chip')?.classList.toggle('is-change', remaining <= 0.009);
        }
    }

    if (paidEl) paidEl.textContent = currencySymbol + ' ' + paid.toFixed(2);

    const changeEl = document.getElementById('changeAmount');
    if (changeEl) {
        let previewChange = changeFromLines;
        if (entered > 0.009 && remaining > 0.009 && entered + 0.009 >= remaining) {
            previewChange = Math.round((entered - remaining) * 100) / 100;
        }
        changeEl.textContent = currencySymbol + ' ' + previewChange.toFixed(2);
    }

    const completeBtn = document.getElementById('completePaymentBtn');
    if (completeBtn) {
        completeBtn.disabled = remaining > 0.009;
        completeBtn.style.opacity = remaining > 0.009 ? '0.55' : '1';
    }

    if (typeof broadcastCart === 'function' && cart.length) {
        broadcastCart();
    }
}

function payKeypad(key) {
    const input = document.getElementById('cashReceived');
    let current = input.value || '';
    if (key === 'backspace') {
        input.value = current.slice(0, -1);
    } else if (key === '.') {
        if (!current.includes('.')) input.value = current + '.';
    } else {
        // Limit absurd length while typing
        if (current.replace('.', '').length >= 10) return;
        input.value = current + key;
    }
    updatePaymentBalanceUI();
}

function calculateChange() {
    updatePaymentBalanceUI();
}

function quickPay(action) {
    const remaining = paymentRemaining();
    const input = document.getElementById('cashReceived');
    let current = parseFloat(input.value) || 0;
    switch(action) {
        case 'exact': input.value = remaining.toFixed(2); break;
        case '100': input.value = (current + 100).toFixed(2); break;
        case '500':
            input.value = isIceCreamUi ? '500.00' : (current + 500).toFixed(2);
            break;
        case '1000':
            input.value = isIceCreamUi ? '1000.00' : (current + 1000).toFixed(2);
            break;
        case '2000':
            input.value = '2000.00';
            break;
        case '5000':
            input.value = '5000.00';
            break;
        case 'round': input.value = (Math.ceil(remaining / 100) * 100).toFixed(2); break;
        case 'clear': input.value = ''; break;
    }
    if (typeof updatePaymentBalanceUI === 'function') updatePaymentBalanceUI();
}

// ===== Table Selection (button + popup) =====
function openTableModal() {
    refreshTablePickerStatus().finally(() => {
        new bootstrap.Modal(document.getElementById('selectTableModal')).show();
    });
}

function markTablePickerBtn(btn, { orderId = '', orderNumber = '', status = 'available' } = {}) {
    if (!btn) return;
    const busy = !!orderId;
    btn.dataset.orderId = orderId ? String(orderId) : '';
    btn.classList.remove(
        'has-open-bill',
        'table-status-available',
        'table-status-occupied',
        'table-status-reserved',
        'table-status-cleaning',
        'selected'
    );
    if (busy) {
        btn.classList.add('has-open-bill', 'table-status-occupied');
    } else {
        btn.classList.add('table-status-' + (status || 'available'));
    }
    const st = btn.querySelector('.tp-status');
    if (st) {
        st.textContent = busy
            ? ((orderNumber || 'Open') + ' · Open bill')
            : (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Available');
    }
    btn.querySelectorAll('.seat-chair').forEach(c => {
        c.classList.toggle('is-filled', busy);
    });
}

function refreshTablePickerStatus() {
    return fetch('/pos/dine-in/orders')
        .then(r => r.json())
        .then(data => {
            const openByTable = {};
            (data.orders || []).forEach(o => {
                if (!o.table_id) return;
                if (!openByTable[o.table_id]) openByTable[o.table_id] = o;
            });

            document.querySelectorAll('#selectTableModal .table-pick-btn').forEach(btn => {
                const id = btn.dataset.id;
                const meta = (data.tables || []).find(t => String(t.id) === String(id));
                const open = openByTable[id] || openByTable[Number(id)];
                const isBusy = meta ? meta.status === 'occupied' : !!open;

                if (isBusy) {
                    markTablePickerBtn(btn, {
                        orderId: open?.id || '',
                        orderNumber: open?.order_number || 'Open',
                        status: 'occupied',
                    });
                } else {
                    markTablePickerBtn(btn, { status: 'available' });
                }
            });
            return data;
        })
        .catch(() => null);
}

function updateTransferTableBtn() {
    const btn = document.getElementById('transferTableBtn');
    if (!btn) return;
    const hasTable = !!document.getElementById('tableSelect')?.value;
    const show = !!openBillId && hasTable && getActiveOrderType() === 'dine_in';
    btn.classList.toggle('d-none', !show);
}

function seatDiagramHtml(capacity, opts = {}) {
    const n = Math.max(1, Math.min(12, Number(capacity) || 4));
    const occupied = !!opts.occupied;
    const guestCount = opts.guests != null && opts.guests !== ''
        ? Math.max(0, Math.min(n, Number(opts.guests)))
        : null;
    const radius = n <= 2 ? 34 : (n <= 4 ? 36 : (n <= 6 ? 38 : 40));
    let chairs = '';
    for (let i = 0; i < n; i++) {
        const angle = (360 / n) * i - 90;
        const rad = angle * Math.PI / 180;
        const x = 50 + radius * Math.cos(rad);
        const y = 50 + radius * Math.sin(rad);
        const filled = guestCount !== null ? (i < guestCount) : occupied;
        chairs += `<span class="seat-chair${filled ? ' is-filled' : ''}" style="left:${x.toFixed(2)}%;top:${y.toFixed(2)}%;--seat-rot:${(angle + 90).toFixed(1)}deg;"></span>`;
    }
    return `<div class="seat-diagram seats-n-${n}${occupied ? ' is-occupied' : ''}" data-seats="${n}" aria-label="${n} seats"><div class="seat-table-top"></div>${chairs}</div>`;
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function openTransferTableModal() {
    if (!openBillId) {
        showToast('warning', 'Open a bill first to transfer table');
        return;
    }
    const currentId = document.getElementById('tableSelect')?.value;
    const currentName = document.getElementById('selectedTableLabel')?.textContent || 'current table';
    const hint = document.getElementById('transferTableHint');
    if (hint) hint.textContent = 'Moving ' + (openBillNumber || 'bill') + ' from ' + currentName + '. Pick a free table:';
    const list = document.getElementById('transferTableList');
    if (list) list.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    new bootstrap.Modal(document.getElementById('transferTableModal')).show();

    fetch('/pos/dine-in/orders')
        .then(r => r.json())
        .then(data => {
            if (!list) return;
            const availableIds = new Set(
                (data.tables || [])
                    .filter(t => t.status === 'available' && String(t.id) !== String(currentId))
                    .map(t => String(t.id))
            );
            if (!availableIds.size) {
                list.innerHTML = '<p class="text-muted text-center py-3 mb-0">No available tables right now</p>';
                return;
            }

            // Reuse Select Table cards so seat diagrams match exactly
            const sourceBody = document.querySelector('#selectTableModal .modal-body');
            if (sourceBody) {
                const clone = sourceBody.cloneNode(true);
                clone.querySelectorAll('.table-pick-btn').forEach(btn => {
                    const id = String(btn.dataset.id || '');
                    if (!availableIds.has(id)) {
                        btn.remove();
                        return;
                    }
                    btn.classList.remove('selected', 'has-open-bill', 'table-status-occupied', 'table-status-reserved', 'table-status-cleaning');
                    btn.classList.add('table-status-available');
                    btn.dataset.orderId = '';
                    const st = btn.querySelector('.tp-status');
                    if (st) st.textContent = 'Available';
                    btn.querySelectorAll('.seat-chair').forEach(c => c.classList.remove('is-filled'));
                    btn.removeAttribute('onclick');
                    btn.addEventListener('click', () => transferBillToTable(parseInt(id, 10), btn.dataset.name));
                });
                clone.querySelectorAll('.mb-4').forEach(section => {
                    if (!section.querySelector('.table-pick-btn')) section.remove();
                });
                if (!clone.querySelector('.table-pick-btn')) {
                    list.innerHTML = '<p class="text-muted text-center py-3 mb-0">No available tables right now</p>';
                    return;
                }
                list.innerHTML = '';
                list.appendChild(clone);
                return;
            }

            // Fallback if Select Table modal markup is missing
            const tables = (data.tables || []).filter(t => availableIds.has(String(t.id)));
            const byFloor = {};
            tables.forEach(t => {
                const floor = t.floor_name || 'Main';
                if (!byFloor[floor]) byFloor[floor] = [];
                byFloor[floor].push(t);
            });
            list.innerHTML = Object.keys(byFloor).map(floor => `
                <div class="mb-4">
                    <div class="table-floor-title"><i class="fas fa-layer-group me-2"></i>${escapeHtml(floor)}</div>
                    <div class="table-pick-grid">
                        ${byFloor[floor].map(t => {
                            const seats = Number(t.capacity) || 4;
                            return `
                            <button type="button" class="table-pick-btn table-status-available"
                                    onclick="transferBillToTable(${t.id}, ${JSON.stringify(t.name)})">
                                <div class="tp-name">${escapeHtml(t.name)}</div>
                                ${seatDiagramHtml(seats, { occupied: false })}
                                <div class="tp-cap">${seats} seats</div>
                                <div class="tp-status">Available</div>
                            </button>`;
                        }).join('')}
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            if (list) list.innerHTML = '<p class="text-danger text-center py-3 mb-0">Could not load tables</p>';
        });
}

function transferBillToTable(newTableId, newTableName) {
    if (!openBillId) return;
    const btn = document.getElementById('transferTableBtn');
    if (btn) btn.disabled = true;

    fetch('/pos/dine-in/change-table', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ order_id: openBillId, table_id: newTableId }),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (btn) btn.disabled = false;
        if (!ok || !data.success) {
            showToast('error', data.message || 'Could not transfer table');
            return;
        }

        const oldId = document.getElementById('tableSelect')?.value;
        const sel = document.getElementById('tableSelect');
        if (sel) sel.value = String(newTableId);
        const label = document.getElementById('selectedTableLabel');
        const name = data.new_table || newTableName || ('Table ' + newTableId);
        if (label) label.textContent = name;
        document.querySelector('.select-table-btn')?.classList.add('has-table');

        document.querySelectorAll('#selectTableModal .table-pick-btn').forEach(el => {
            const id = String(el.dataset.id);
            if (oldId && id === String(oldId)) {
                markTablePickerBtn(el, { status: 'available' });
            }
            if (id === String(newTableId)) {
                markTablePickerBtn(el, {
                    orderId: openBillId,
                    orderNumber: openBillNumber || 'Open',
                    status: 'occupied',
                });
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });

        const inst = bootstrap.Modal.getInstance(document.getElementById('transferTableModal'));
        if (inst) inst.hide();
        updateTransferTableBtn();
        refreshTablePickerStatus();
        showToast('success', data.message || ('Transferred to ' + name));
    })
    .catch(() => {
        if (btn) btn.disabled = false;
        showToast('error', 'Network error');
    });
}

function selectTable(el) {
    const id = el.dataset.id;
    const name = el.dataset.name;
    const openOrderId = el.dataset.orderId ? parseInt(el.dataset.orderId, 10) : null;
    const sel = document.getElementById('tableSelect');
    if (sel) sel.value = id;
    const label = document.getElementById('selectedTableLabel');
    if (label) label.textContent = name;
    const btn = document.querySelector('.select-table-btn');
    if (btn) btn.classList.add('has-table');
    document.querySelectorAll('.table-pick-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    updateTransferTableBtn();
    const inst = bootstrap.Modal.getInstance(document.getElementById('selectTableModal'));
    if (inst) inst.hide();

    // Occupied / unpaid table → load invoice into cart (not just table label)
    const loadBill = (orderId) => {
        if (!orderId) return;
        openOrderInCart(orderId, true);
    };

    if (openOrderId) {
        loadBill(openOrderId);
        return;
    }

    fetch('/pos/table-open-order/' + id)
        .then(r => r.json())
        .then(data => {
            if (data.order?.id) loadBill(data.order.id);
        })
        .catch(() => {});
}

function closePosModals() {
    ['recentOrdersModal', 'orderDetailModal', 'billTableModal', 'payBillsModal', 'selectTableModal', 'transferTableModal', 'kotModifyModal'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const inst = bootstrap.Modal.getInstance(el);
        if (inst) inst.hide();
    });
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
}

function openOrderInCart(orderId, showToastMsg = true) {
    closePosModals();
    // Close ready-orders notify panel
    if (typeof closeNotifyPanel === 'function') closeNotifyPanel();
    else {
        const panel = document.getElementById('notifyPanel');
        if (panel) panel.style.display = 'none';
        if (typeof notifyPanelOpen !== 'undefined') notifyPanelOpen = false;
    }

    return loadOpenBill(orderId, showToastMsg, false).then(ok => {
        if (!ok) return false;
        currentEditOrderId = orderId;
        // Ensure desktop cart is visible / highlight
        document.querySelector('.cart-card')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (window.innerWidth < 992 && typeof openMobileCart === 'function') {
            openMobileCart();
        }
        return true;
    });
}

function clearTable() {
    const sel = document.getElementById('tableSelect');
    if (sel) sel.value = '';
    const label = document.getElementById('selectedTableLabel');
    if (label) label.textContent = 'Select Table';
    const btn = document.querySelector('.select-table-btn');
    if (btn) btn.classList.remove('has-table');
    document.querySelectorAll('.table-pick-btn').forEach(b => b.classList.remove('selected'));
    updateTransferTableBtn();
    const inst = bootstrap.Modal.getInstance(document.getElementById('selectTableModal'));
    if (inst) inst.hide();
}

function clearTakeawayCustomerFields() {
    const nameEl = document.getElementById('takeawayCustomerName');
    const phoneEl = document.getElementById('takeawayCustomerPhone');
    if (nameEl) nameEl.value = '';
    if (phoneEl) phoneEl.value = '';
}

function getTakeawayCustomerFields() {
    return {
        customer_name: (document.getElementById('takeawayCustomerName')?.value || '').trim(),
        customer_phone: (document.getElementById('takeawayCustomerPhone')?.value || '').trim(),
    };
}

function appendTakeawayCustomerToPayload(payload, orderType) {
    if (orderType !== 'takeaway') return payload;
    const fields = getTakeawayCustomerFields();
    payload.customer_name = fields.customer_name || null;
    payload.customer_phone = fields.customer_phone || null;
    // Takeaway cart fields replace waiter; never send a waiter for takeaway
    payload.waiter_id = null;
    return payload;
}

function validateDeliveryFields() {
    if (isMarketplaceDeliveryPartner()) {
        if (!document.getElementById('deliveryPartnerSelect')?.value) {
            showToast('warning', 'Select Uber Eats, PickMe or Buyit');
            return false;
        }
        return true;
    }
    const customerId = document.getElementById('customerSelect')?.value;
    const address = (document.getElementById('deliveryAddress')?.value || '').trim();
    if (!customerId) {
        showToast('warning', 'Select a customer for own delivery');
        return false;
    }
    if (!address) {
        showToast('warning', 'Enter delivery address');
        return false;
    }
    return true;
}

function placeDeliveryCodOrder() {
    if (cart.length === 0) {
        showToast('warning', 'Cart is empty');
        return;
    }
    if (getActiveOrderType() !== 'delivery') {
        showToast('warning', 'Switch to Delivery first');
        return;
    }
    if (!validateDeliveryFields()) return;

    const marketplace = isMarketplaceDeliveryPartner();
    const meta = getSelectedDeliveryPartnerMeta();
    const payload = {
        order_type: 'delivery',
        customer_id: marketplace ? null : (getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null),
        waiter_id: document.getElementById('waiterSelect')?.value || null,
        items: cart,
        discount_amount: billDiscount,
        discount_type: billDiscountType,
        tax_rate: taxRate,
        service_charge: serviceChargeEnabled ? serviceChargeRate : 0,
        order_notes: orderNotes,
        delivery_address: marketplace
            ? ('Via ' + (meta?.name || 'partner'))
            : (document.getElementById('deliveryAddress')?.value || ''),
        delivery_partner_id: document.getElementById('deliveryPartnerSelect')?.value || null,
        payment_on_delivery: true,
        payment_method: 'cash',
    };

    fetch('/pos/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || (
                deliveryPayMode === 'partner'
                    ? ('Partner order ' + (data.order_number || '') + ' — due on ledger')
                    : ('COD order ' + (data.order_number || '') + ' placed')
            ));
            if (!marketplace) syncCustomerOptionAddress(payload.customer_id, payload.delivery_address);
            updateLastSale(data);
            cart = [];
            billDiscount = 0;
            orderNotes = '';
            updateCart();
            clearCustomerDisplay();
            // Delivery place: always direct-print KOT
            if (data.print_jobs && data.print_jobs.length) {
                handlePrintJobs(data.print_jobs, { force: true });
            }
            if (data.print_url || data.order_id) handleReceiptPrint(data.print_url, data.order_id);
        } else {
            showToast('error', data.message || 'Could not place COD order');
        }
    })
    .catch(() => showToast('error', 'Network error'));
}

function placeDineInOrder() {
    if (openBillId) {
        saveOpenBillUpdates();
        return;
    }

    if (getActiveOrderType() === 'delivery' && (deliveryPayMode === 'cod' || deliveryPayMode === 'partner')) {
        placeDeliveryCodOrder();
        return;
    }

    if (cart.length === 0) {
        showToast('warning', 'Cart is empty');
        return;
    }

    const orderType = getActiveOrderType();
    const tableId = document.getElementById('tableSelect').value;
    if (orderType === 'dine_in' && !tableId) {
        if (typeof showToast === 'function') showToast('warning', 'Please select a table');
        openTableModal();
        return;
    }

    const payload = {
        order_type: orderType,
        table_id: orderType === 'dine_in' ? (tableId || null) : null,
        waiter_id: document.getElementById('waiterSelect')?.value || null,
        customer_id: getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null,
        items: cart,
        discount_amount: billDiscount,
        discount_type: billDiscountType,
        tax_rate: taxRate,
        service_charge: serviceChargeEnabled ? serviceChargeRate : 0,
        order_notes: orderNotes,
    };
    appendTakeawayCustomerToPayload(payload, orderType);

    fetch('/pos/place-order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Order placed! Prep tickets sent (retail stays on bill only)');
            cart = [];
            billDiscount = 0;
            orderNotes = '';
            if (typeof paymentLines !== 'undefined') paymentLines = [];
            openBillId = null;
            openBillNumber = null;
            settleOrderId = null;
            settleTotal = 0;
            updateOpenBillUI();
            updateCart();
            clearCustomerDisplay();
            clearTable();
            clearTakeawayCustomerFields();
            refreshTablePickerStatus();
            if (typeof refreshOpenBillsBadge === 'function') refreshOpenBillsBadge();
            // Place Order (dine-in / takeaway): always direct-print KOT/BOT now
            if (data.print_jobs && data.print_jobs.length) {
                handlePrintJobs(data.print_jobs, { force: true });
            } else if (data.print_kot_url) {
                showToast('warning', 'KOT created but no print job — check kitchen printer settings');
            }
            if (typeof refreshPendingKotBadge === 'function') refreshPendingKotBadge();
        } else {
            showToast('error', data.message || 'Error');
        }
    })
    .catch(e => {
        showToast('error', 'Network error');
    });
}

function saveOpenBillUpdates() {
    if (!openBillId) return Promise.resolve(true);

    // Emptying the cart is not an edit — cancelling a bill is a deliberate void
    if (!cart.length) {
        showToast('warning', 'An order must keep at least one item — use Cancel on Open Bills to cancel this bill');
        return Promise.resolve(false);
    }

    // The whole cart is posted so the server can apply removals and quantity
    // changes to the existing order instead of only appending new lines.
    const payload = {
        items: cart.map(item => ({ ...item, order_item_id: item.order_item_id || null })),
        sync_items: true,
        discount_amount: billDiscount,
        discount_type: billDiscountType,
        tax_rate: taxRate,
        service_charge: serviceChargeEnabled ? serviceChargeRate : 0,
        order_notes: orderNotes,
        waiter_id: document.getElementById('waiterSelect')?.value || null,
        customer_id: getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null,
    };
    appendTakeawayCustomerToPayload(payload, getActiveOrderType());

    return fetch('/pos/update-order/' + openBillId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(async data => {
        if (!data.success) {
            showToast('error', data.message || 'Could not update bill');
            return false;
        }

        settleTotal = data.new_total ?? settleTotal;
        showToast('success', (data.order_number || 'Bill') + ' updated' + (data.was_paid ? ' — reopened for payment' : ''));

        if (data.items_changed) {
            await askReprintAfterBillUpdate(data);
        }
        return loadOpenBill(openBillId, false).then(() => true);
    })
    .catch(() => {
        showToast('error', 'Network error');
        return false;
    });
}

function onWaiterSelectChange() {
    if (!openBillId) return;
    const waiterId = document.getElementById('waiterSelect')?.value || null;
    fetch('/pos/assign-waiter/' + openBillId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ waiter_id: waiterId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) showToast('success', data.message || 'Waiter saved');
        else showToast('error', data.message || 'Could not save waiter');
    })
    .catch(() => showToast('error', 'Network error'));
}

function askReprintAfterBillUpdate(data) {
    const jobs = data.print_jobs || [];
    const kotJobs = jobs.filter(j => j.type === 'kot');
    const botJobs = jobs.filter(j => j.type === 'bot');
    const hasKot = kotJobs.length > 0;
    const hasBot = botJobs.length > 0;
    const receiptUrl = data.print_receipt_url || (data.order_id ? `/pos/print-receipt/${data.order_id}` : null);

    if (!hasKot && !hasBot && !receiptUrl) {
        return Promise.resolve();
    }

    let html = '<div class="text-start">';
    html += `<p class="mb-2">Bill <strong>${data.order_number || ''}</strong> updated to <strong>${currencySymbol} ${(data.new_total || 0).toFixed(2)}</strong>.</p>`;
    html += (hasKot || hasBot)
        ? `<p class="text-muted small mb-3">Tickets print as <strong>REORDER</strong> against ${data.order_number || 'this order'}:</p>`
        : '<p class="text-muted small mb-3">Choose what to reprint:</p>';
    if (hasKot) {
        html += `<div class="form-check mb-2 text-start">
            <input class="form-check-input" type="checkbox" id="reprintKotCheck" checked>
            <label class="form-check-label" for="reprintKotCheck">Print REORDER KOT (kitchen)</label>
        </div>`;
    }
    if (hasBot) {
        html += `<div class="form-check mb-2 text-start">
            <input class="form-check-input" type="checkbox" id="reprintBotCheck" checked>
            <label class="form-check-label" for="reprintBotCheck">Print REORDER BOT (juice / bar)</label>
        </div>`;
    }
    html += `<div class="form-check mb-2 text-start">
        <input class="form-check-input" type="checkbox" id="reprintInvoiceCheck">
        <label class="form-check-label" for="reprintInvoiceCheck">Reprint Invoice</label>
    </div>`;
    html += '</div>';

    return Swal.fire({
        title: 'Update KOT / BOT?',
        html,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Print selected',
        cancelButtonText: 'Skip',
        confirmButtonColor: '#f59e0b',
        reverseButtons: true,
    }).then(result => {
        if (!result.isConfirmed) return;

        if (hasKot && document.getElementById('reprintKotCheck')?.checked) {
            handlePrintJobs(kotJobs, { force: true });
        }
        if (hasBot && document.getElementById('reprintBotCheck')?.checked) {
            handlePrintJobs(botJobs, { force: true });
        }
        if (receiptUrl && document.getElementById('reprintInvoiceCheck')?.checked) {
            openPrintPreview(receiptUrl);
        }
    });
}

function modifyBill(orderId) {
    closePosModals();
    // Paid bills need confirm; unpaid open straight into cart
    fetch('/pos/order-details/' + orderId)
        .then(r => r.json())
        .then(async data => {
            const order = data.order;
            if (!order) {
                showToast('error', 'Bill not found');
                return;
            }
            const allowPaid = order.payment_status === 'paid';
            const ok = await loadOpenBill(orderId, false, allowPaid);
            if (!ok) return;
            currentEditOrderId = orderId;
            showToast('success', 'Editing ' + order.order_number + (allowPaid
                ? ' — a paid bill reopens for the new total'
                : ' — add, remove or change quantities, then Update Bill'));
            document.querySelector('.cart-card')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (window.innerWidth < 992 && typeof openMobileCart === 'function') openMobileCart();
        })
        .catch(() => showToast('error', 'Could not open bill'));
}

function editOrderAddItems(orderId) {
    modifyBill(orderId);
}

/** Edit an already-placed order in the cart. The original order number is kept. */
function editOrder(orderId) {
    if (hasUnsavedBillEdits() && orderId !== openBillId) {
        Swal.fire({
            title: 'Discard unsaved changes?',
            text: `Changes to ${openBillNumber} have not been saved yet.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Discard & switch',
            cancelButtonText: 'Stay',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
        }).then(result => {
            if (result.isConfirmed) modifyBill(orderId);
        });
        return;
    }
    modifyBill(orderId);
}

function openBillTableModal() {
    selectedOpenBillId = null;
    const body = document.getElementById('billTableList');
    if (body) body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';
    new bootstrap.Modal(document.getElementById('billTableModal')).show();
    fetch('/pos/billing-orders')
    .then(r => r.json())
    .then(data => {
        if (typeof updateOpenBillsBadge === 'function') {
            updateOpenBillsBadge((data.orders || []).length);
        }
        if (!data.orders || data.orders.length === 0) {
            if (body) body.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-receipt fa-3x mb-3 opacity-25"></i><p>No unpaid bills.</p></div>';
            updateOpenBillActionBar();
            return;
        }
        let html = '';
        data.orders.forEach(o => {
            const isDelivery = (o.order_type || '') === 'delivery';
            const typeLabel = isDelivery ? 'Delivery' : (o.order_type || 'dine_in').replace('_', ' ');
            let kitchenBadge = '';
            if (o.kitchen_ready) {
                kitchenBadge = `<span class="badge bg-success ms-1"><i class="fas fa-bell me-1"></i>${o.kitchen_label}</span>`;
            } else if (o.kitchen_served) {
                kitchenBadge = `<span class="badge bg-secondary ms-1"><i class="fas fa-check me-1"></i>Served</span>`;
            } else if (o.kitchen_label && o.kitchen_label !== 'No KOT') {
                kitchenBadge = `<span class="badge bg-warning text-dark ms-1">${o.kitchen_label}</span>`;
            }
            let kotLogHtml = '';
            if ((o.kot_count || 0) > 0) {
                if (o.kot_all_printed) {
                    kotLogHtml = `<div class="bt-kot-log printed"><i class="fas fa-check-circle me-1"></i>KOT printed</div>`;
                } else {
                    kotLogHtml = `<div class="bt-kot-log pending"><i class="fas fa-exclamation-circle me-1"></i>KOT not printed (${o.kot_unprinted_count || 0})</div>`;
                }
            }
            const kotIds = JSON.stringify(o.unprinted_kot_ids && o.unprinted_kot_ids.length ? o.unprinted_kot_ids : (o.kot_ids || []));
            const showKotBtn = (o.kot_count || 0) > 0;
            const titleIcon = isDelivery ? 'fa-motorcycle' : 'fa-receipt';
            const floorOrAddr = isDelivery
                ? (o.delivery_partner_name || o.floor_name || 'Delivery')
                : (o.floor_name || typeLabel);
            html += `
            <div class="bill-table-card ${o.kitchen_ready ? 'bill-ready' : ''} ${isDelivery ? 'bill-delivery' : ''}" data-order-id="${o.id}" role="button" tabindex="0" title="Tap to open bill" onclick="editOrder(${o.id})" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editOrder(${o.id});}">
                <div class="bt-left">
                    <div class="bt-name"><i class="fas ${titleIcon} me-2 text-warning"></i>${o.table_name} <span class="bt-floor">${floorOrAddr}</span>${kitchenBadge}</div>
                    <div class="bt-meta">${o.order_number} · ${o.customer}${o.waiter_name ? ' · ' + o.waiter_name : ''} · ${o.items_count} item(s) · ${o.elapsed}</div>
                    ${kotLogHtml}
                    <div class="bt-hint text-muted small mt-1"><i class="fas fa-hand-pointer me-1"></i>Tap row to open bill</div>
                </div>
                <div class="bt-right text-end">
                    <div class="bt-total">${currencySymbol} ${o.total.toFixed(2)}</div>
                    <div class="bt-cap text-capitalize">${typeLabel}</div>
                    <div class="bt-actions mt-2" onclick="event.stopPropagation()">
                        ${showKotBtn ? `<button type="button" class="btn btn-sm btn-success" onclick='printOpenBillKots(${o.id}, ${kotIds}, this)'><i class="fas fa-print me-1"></i>${o.kot_all_printed ? 'Reprint KOT' : 'KOT print'}</button>` : ''}
                        ${isDelivery ? `<button type="button" class="btn btn-sm btn-primary" onclick="markOpenBillDelivered(${o.id}, '${String(o.order_number || '').replace(/'/g, '')}')"><i class="fas fa-check-double me-1"></i>Delivered</button>` : ''}
                        <button type="button" class="btn btn-sm btn-warning text-white" onclick="editOrder(${o.id})"><i class="fas fa-edit me-1"></i>Edit</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="voidOpenBill(${o.id}, '${String(o.order_number || '').replace(/'/g, '')}')"><i class="fas fa-times me-1"></i>Cancel</button>
                    </div>
                </div>
            </div>`;
        });
        if (body) body.innerHTML = html;
        updateOpenBillActionBar();
    })
    .catch(e => {
        if (body) body.innerHTML = '<div class="text-center text-danger py-4">Could not load bills.</div>';
        updateOpenBillActionBar();
    });
}

function markOpenBillDelivered(orderId, orderNumber) {
    Swal.fire({
        title: 'Mark delivered?',
        html: `<p class="mb-1">Order <strong>${orderNumber || ('#' + orderId)}</strong> will be completed and removed from Open Bills.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Delivered',
        confirmButtonColor: '#2563eb',
        cancelButtonText: 'Cancel',
    }).then(res => {
        if (!res.isConfirmed) return;
        Swal.fire({ title: 'Updating...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('/pos/delivery-order/' + orderId + '/delivered', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(data => {
            Swal.close();
            if (!data.success) {
                showToast('error', data.message || 'Could not mark delivered');
                return;
            }
            showToast('success', data.message || 'Delivered — order completed');
            openBillTableModal();
            if (typeof refreshOpenBillsBadge === 'function') refreshOpenBillsBadge();
        })
        .catch(() => {
            Swal.close();
            showToast('error', 'Network error');
        });
    });
}

let selectedOpenBillId = null;

function selectOpenBill(orderId, el) {
    selectedOpenBillId = orderId;
    document.querySelectorAll('#billTableList .bill-table-card').forEach(card => {
        card.classList.toggle('selected', Number(card.dataset.orderId) === Number(orderId));
    });
    if (el) el.classList.add('selected');
    updateOpenBillActionBar();
}

function updateOpenBillActionBar() {
    const bar = document.getElementById('openBillActionBar');
    const label = document.getElementById('openBillSelectedLabel');
    if (!bar) return;
    const has = !!selectedOpenBillId;
    bar.classList.toggle('d-none', !has);
    if (label) label.textContent = has ? ('Selected bill #' + selectedOpenBillId) : '';
}

function openSelectedOpenBill() {
    if (!selectedOpenBillId) {
        showToast('warning', 'Select a bill first');
        return;
    }
    billOrder(selectedOpenBillId);
}

function voidSelectedOpenBill() {
    if (!selectedOpenBillId) {
        showToast('warning', 'Select a bill first');
        return;
    }
    voidOpenBill(selectedOpenBillId);
}

function voidOpenBill(orderId, orderNumber = '') {
    Swal.fire({
        title: 'Cancel bill',
        html: `
            <p class="text-muted small mb-2">${orderNumber ? ('Bill <strong>' + orderNumber + '</strong>') : 'This unpaid bill'} will be closed and removed from Open Bills.</p>
            <label class="form-label fw-semibold text-start w-100">Reason</label>
            <textarea id="voidReasonInput" class="form-control" rows="3" placeholder="Reason required (shown in Cancelled Bills report)"></textarea>
            <div class="d-flex flex-wrap gap-1 mt-2 justify-content-start">
                ${['Customer left', 'Wrong order', 'Kitchen issue', 'Duplicate bill'].map(r =>
                    `<button type="button" class="btn btn-sm btn-outline-secondary void-reason-chip">${r}</button>`
                ).join('')}
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Cancel bill',
        cancelButtonText: 'Back',
        confirmButtonColor: '#dc2626',
        focusConfirm: false,
        didOpen: () => {
            document.querySelectorAll('.void-reason-chip').forEach(btn => {
                btn.addEventListener('click', () => {
                    const input = document.getElementById('voidReasonInput');
                    if (input) input.value = btn.textContent;
                });
            });
            document.getElementById('voidReasonInput')?.focus();
        },
        preConfirm: () => {
            const reason = (document.getElementById('voidReasonInput')?.value || '').trim();
            if (reason.length < 3) {
                Swal.showValidationMessage('Enter a reason (at least 3 characters)');
                return false;
            }
            return reason;
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch('/pos/void', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                order_id: orderId,
                reason: result.value,
                action: 'cancel',
            }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.success) {
                showToast('error', (d && d.message) || 'Could not cancel bill');
                return;
            }
            showToast('success', d.message || 'Bill cancelled — refreshing…');

            // Always clear local sale state + stuck modal chrome, then hard-refresh POS
            try {
                openBillId = null;
                openBillNumber = null;
                openBillSignature = '';
                settleOrderId = null;
                settleTotal = 0;
                selectedOpenBillId = null;
                cart = [];
                billDiscount = 0;
                orderNotes = '';
                if (typeof paymentLines !== 'undefined') paymentLines = [];
                if (typeof exitOpenBill === 'function') exitOpenBill(true);
                const billModal = document.getElementById('billTableModal');
                if (billModal && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(billModal)?.hide();
                }
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            } catch (_) {}

            setTimeout(() => {
                window.location.reload();
            }, 450);
        })
        .catch(() => showToast('error', 'Network error'));
    });
}

function billOrder(orderId) {
    openOrderInCart(orderId, true);
}

function loadOpenBill(orderId, showToastMsg = true, allowPaid = false) {
    return fetch('/pos/order-details/' + orderId)
        .then(r => r.json())
        .then(async data => {
            const order = data.order;
            if (!order) {
                showToast('error', 'Bill not found');
                return false;
            }
            if (order.is_void) {
                showToast('error', 'This bill is voided');
                return false;
            }
            if (order.payment_status === 'paid' && !allowPaid) {
                showToast('error', 'This bill is already paid — use Modify Bill from Recent Transactions');
                return false;
            }
            if (order.payment_status === 'paid' && allowPaid) {
                const confirm = await Swal.fire({
                    title: 'Modify paid bill?',
                    text: 'Adding items will reopen this bill as unpaid so you can collect the new total.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, modify',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#f59e0b',
                });
                if (!confirm.isConfirmed) return false;
            }

            openBillId = order.id;
            openBillNumber = order.order_number;
            settleOrderId = order.id;
            settleTotal = order.total_amount;
            orderNotes = order.order_notes || '';
            billDiscount = order.discount_amount || 0;
            billDiscountType = 'fixed';

            cart = (order.items || []).map(item => ({
                order_item_id: item.id,
                product_id: item.product_id || null,
                is_custom_item: !!item.is_custom_item,
                name: item.product_name || item.name,
                price: item.unit_price ?? item.price,
                quantity: item.quantity,
                variant_id: item.variant_id || null,
                variant_name: item.variant_name || null,
                addons: (item.addons || []).map(a => ({
                    id: a.id,
                    name: a.name || a.addon_name,
                    price: a.price,
                    shared: a.shared !== false,
                })),
                discount: Number(item.discount_amount || item.discount || 0),
                has_options: !!(item.variant_id || (item.addons || []).length),
                special_instructions: item.special_instructions || '',
                loyalty_free: false,
                is_comp: !!item.is_comp,
                comp_reason: item.comp_reason || '',
                routed_to: item.routed_to,
            }));

            changeOrderType(order.order_type || 'dine_in');
            // Only dine-in may restore a table; takeaway/delivery/express stay unassigned
            if (order.table_id && (order.order_type || 'dine_in') === 'dine_in') {
                const sel = document.getElementById('tableSelect');
                if (sel) sel.value = order.table_id;
                const label = document.getElementById('selectedTableLabel');
                if (label) label.textContent = order.table_name || ('Table ' + order.table_id);
                document.querySelector('.select-table-btn')?.classList.add('has-table');
            }
            if ((order.order_type || '') === 'takeaway') {
                const nameEl = document.getElementById('takeawayCustomerName');
                const phoneEl = document.getElementById('takeawayCustomerPhone');
                if (nameEl) nameEl.value = order.customer_name || '';
                if (phoneEl) phoneEl.value = order.customer_phone || '';
            }
            if (order.customer_id) {
                const cust = document.getElementById('customerSelect');
                if (cust) cust.value = order.customer_id;
            }
            const waiterSel = document.getElementById('waiterSelect');
            if (waiterSel) waiterSel.value = order.waiter_id || '';

            updateOpenBillUI();
            updateCart();
            openBillSignature = cartSignature();
            // Re-push after DOM/table labels settle so customer display shows this open bill
            setTimeout(() => broadcastCart(), 50);
            if (showToastMsg) {
                showToast('success', 'Editing ' + order.order_number + ' — add, remove or change quantities, then Update Bill');
            }
            return true;
        })
        .catch(() => {
            showToast('error', 'Could not open bill');
            return false;
        });
}

function getActiveOrderType() {
    const activeBtn = document.querySelector('.order-type-btn.active');
    if (activeBtn) return activeBtn.dataset.type;
    return isBakeryUi ? 'takeaway' : 'dine_in';
}

function buildPaymentsPayload() {
    if (!paymentLines.length) {
        showToast('warning', 'Add at least one payment (enter amount, tap method)');
        return null;
    }
    if (paymentRemaining() > 0.009) {
        showToast('warning', 'Balance remaining: ' + currencySymbol + ' ' + paymentRemaining().toFixed(2));
        return null;
    }
    return paymentLines.map(p => ({
        method: p.method,
        amount: Number(p.amount),
    }));
}

function processPayment() {
    if (openBillId || settleOrderId) {
        processSettlePayment();
        return;
    }

    const payments = buildPaymentsPayload();
    if (!payments) return;

    const orderType = getActiveOrderType();

    const payload = {
        order_type: orderType,
        table_id: orderType === 'dine_in' ? (document.getElementById('tableSelect').value || null) : null,
        waiter_id: document.getElementById('waiterSelect')?.value || null,
        customer_id: getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null,
        items: cart,
        discount_amount: billDiscount,
        discount_type: billDiscountType,
        tax_rate: taxRate,
        service_charge: serviceChargeEnabled ? serviceChargeRate : 0,
        payment_method: payments.length === 1 ? payments[0].method : 'split',
        payments,
        payment_notes: document.getElementById('paymentNotes').value,
        cash_received: payments.filter(p => p.method === 'cash').reduce((s, p) => s + p.amount, 0),
        order_notes: orderNotes,
        ...loyaltyCheckoutFlags(),
    };
    appendTakeawayCustomerToPayload(payload, orderType);

    // Add delivery details if delivery order
    if (orderType === 'delivery') {
        if (!validateDeliveryFields()) return;
        const marketplace = isMarketplaceDeliveryPartner();
        const meta = getSelectedDeliveryPartnerMeta();
        payload.delivery_partner_id = document.getElementById('deliveryPartnerSelect')?.value || null;
        if (marketplace) {
            payload.customer_id = null;
            payload.delivery_address = 'Via ' + (meta?.name || 'partner');
        } else {
            payload.delivery_address = document.getElementById('deliveryAddress')?.value || '';
        }
    }

    fetch('/pos/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Order saved!');
            if (orderType === 'delivery' && payload.customer_id) {
                syncCustomerOptionAddress(payload.customer_id, payload.delivery_address || '');
            }
            updateLastSale(data);
            handleLoyaltyResult(data.loyalty);
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            cart = [];
            billDiscount = 0;
            orderNotes = '';
            paymentLines = [];
            updateCart();
            clearCustomerDisplay();
            clearLoyaltySelection(true);
            clearTable();
            clearTakeawayCustomerFields();
            refreshTablePickerStatus();
            if (data.print_url || data.order_id) handleReceiptPrint(data.print_url, data.order_id, true);
            // Delivery Pay Now: still direct-print KOT (kitchen needs it).
            // Dine-in/takeaway Pay Now: no KOT — use Reprint when needed.
            if (orderType === 'delivery' && data.print_jobs && data.print_jobs.length) {
                handlePrintJobs(data.print_jobs, { force: true });
            } else if (typeof refreshPendingKotBadge === 'function') {
                refreshPendingKotBadge();
            }
        } else {
            showToast('error', data.message || 'Error');
        }
    })
    .catch(e => {
        showToast('error', 'Network error');
    });
}

function processSettlePayment() {
    if (!settleOrderId && !openBillId) return;
    const payments = buildPaymentsPayload();
    if (!payments) return;

    const orderId = openBillId || settleOrderId;
    const payload = {
        payment_method: payments.length === 1 ? payments[0].method : 'split',
        payments,
        payment_notes: document.getElementById('paymentNotes').value,
        cash_received: payments.filter(p => p.method === 'cash').reduce((s, p) => s + p.amount, 0),
        waiter_id: document.getElementById('waiterSelect')?.value || null,
        customer_id: getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null,
        ...loyaltyCheckoutFlags(),
    };
    appendTakeawayCustomerToPayload(payload, getActiveOrderType());

    const finishSettle = () => fetch('/pos/settle-order/' + orderId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Bill settled!');
            updateLastSale(data);
            handleLoyaltyResult(data.loyalty);
            bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
            paymentLines = [];
            clearLoyaltySelection(true);
            exitOpenBill(true);
            clearTable();
            refreshTablePickerStatus();
            refreshPayBillsBadge();
            refreshOpenBillsBadge();
            if (data.needs_rating) {
                showToast('info', 'Ask waiter to collect guest rating on tablet');
            }
            if (data.print_url || data.order_id) handleReceiptPrint(data.print_url, data.order_id, true);
            // Settling a bill: receipt only — KOT already printed on Place Order / Update
        } else {
            showToast('error', data.message || 'Error');
        }
    })
    .catch(e => {
        showToast('error', 'Network error');
    });

    // Commit pending cart edits (added, removed or re-quantified lines) before taking payment
    if (hasUnsavedBillEdits()) {
        saveOpenBillUpdates().then(ok => {
            if (ok) finishSettle();
        });
        return;
    }

    finishSettle();
}

function holdOrder(opts = {}) {
    const startNew = !!opts.startNew;
    if (cart.length === 0) {
        showToast('warning', 'Cart is empty');
        return;
    }
    if (openBillId) {
        showToast('warning', 'Close the open bill first, then hold if needed');
        return;
    }
    const holdOrderType = getActiveOrderType();
    const payload = {
        items: cart,
        order_type: holdOrderType,
        table_id: holdOrderType === 'dine_in' ? (document.getElementById('tableSelect').value || null) : null,
        customer_id: getLoyaltyCustomerId() || document.getElementById('customerSelect')?.value || null,
        notes: orderNotes,
    };
    appendTakeawayCustomerToPayload(payload, holdOrderType);
    fetch('/pos/hold', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (startNew) {
                prepareNewSaleAfterHold();
                showToast('success', 'Order held — ready for new order');
            } else {
                cart = [];
                updateCart();
                broadcastCart();
                showToast('success', 'Order held!');
            }
        } else {
            showToast('error', data.message || 'Could not hold order');
        }
    })
    .catch(() => showToast('error', 'Network error'));
}

function holdAndNew() {
    holdOrder({ startNew: true });
}

function prepareNewSaleAfterHold() {
    openBillId = null;
    openBillNumber = null;
    settleOrderId = null;
    settleTotal = 0;
    cart = [];
    billDiscount = 0;
    orderNotes = '';
    if (typeof paymentLines !== 'undefined') paymentLines = [];

    const tableSelect = document.getElementById('tableSelect');
    if (tableSelect) {
        tableSelect.value = '';
        tableSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect) {
        if (window.jQuery && $(customerSelect).hasClass('select2-hidden-accessible')) {
            $(customerSelect).val(null).trigger('change');
        } else {
            customerSelect.value = '';
            customerSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    if (typeof clearLoyaltySelection === 'function') {
        clearLoyaltySelection(false, true);
    }

    clearTakeawayCustomerFields();

    const deliveryAddress = document.getElementById('deliveryAddress');
    if (deliveryAddress) deliveryAddress.value = '';

    if (typeof updateOpenBillUI === 'function') updateOpenBillUI();
    updateCart();
    if (typeof clearCustomerDisplay === 'function') clearCustomerDisplay();
    broadcastCart();
}

function showHeldOrders() {
    fetch('/pos/held')
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('heldOrdersList');
        if (!data.orders.length) { 
            list.innerHTML = '<p class="text-muted text-center">No held orders</p>'; 
        } else {
            list.innerHTML = data.orders.map(o => `
                <div class="card mb-2" onclick="recallOrder(${o.id})" style="cursor:pointer">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                            <strong>${o.order_number || 'Order #' + o.id}</strong>
                            <small class="text-muted">${window.BusinessClock ? BusinessClock.formatInstant(o.created_at, {hour:"2-digit", minute:"2-digit"}) : new Date(o.created_at).toLocaleTimeString()}</small>
                        </div>
                        <small>${o.items?.length || 0} items - ${o.order_type}</small>
                    </div>
                </div>
            `).join('');
        }
        new bootstrap.Modal(document.getElementById('heldOrdersModal')).show();
    });
}

function recallOrder(id) {
    fetch('/pos/recall', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cart = data.items || [];
            if (data.order_type && typeof changeOrderType === 'function') {
                changeOrderType(data.order_type);
            }
            if ((data.order_type || '') === 'takeaway') {
                const nameEl = document.getElementById('takeawayCustomerName');
                const phoneEl = document.getElementById('takeawayCustomerPhone');
                if (nameEl) nameEl.value = data.customer_name || '';
                if (phoneEl) phoneEl.value = data.customer_phone || '';
            }
            if (data.customer_id) {
                const cust = document.getElementById('customerSelect');
                if (cust) cust.value = data.customer_id;
            }
            updateCart();
            bootstrap.Modal.getInstance(document.getElementById('heldOrdersModal')).hide();
            showToast('success', 'Order recalled!');
        }
    });
}

// ===== RECENT ORDERS =====
function showRecentOrders() {
    const el = document.getElementById('recentOrdersModal');
    if (!el || typeof bootstrap === 'undefined') {
        console.error('Recent orders modal unavailable');
        if (typeof showToast === 'function') showToast('error', 'Could not open orders');
        return;
    }
    const existing = bootstrap.Modal.getInstance(el);
    const modal = existing || new bootstrap.Modal(el);
    modal.show();
    loadRecentOrders();
}

function showWaiterReport(range = 'month') {
    const modal = new bootstrap.Modal(document.getElementById('waiterReportModal'));
    modal.show();
    loadWaiterReport(range);
}

function showShiftReport() {
    const el = document.getElementById('shiftReportModal');
    if (!el || typeof bootstrap === 'undefined') {
        showToast('error', 'Could not open shift report');
        return;
    }
    const existing = bootstrap.Modal.getInstance(el);
    (existing || new bootstrap.Modal(el)).show();
    loadShiftReport();
}

function loadShiftReport() {
    const body = document.getElementById('shiftReportBody');
    const footerMeta = document.getElementById('shiftReportMeta');
    const printBtn = document.getElementById('shiftReportPrintBtn');
    if (body) {
        body.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading shift report...</p></div>';
    }
    if (printBtn) {
        printBtn.classList.add('d-none');
        printBtn.onclick = null;
    }
    if (footerMeta) footerMeta.textContent = '';

    fetch('/pos/register/report')
        .then(r => {
            if (!r.ok) {
                if (r.status === 401) throw new Error('Please log in first');
                if (r.status === 404) throw new Error('No open shift. Open the drawer / start a shift first.');
                throw new Error('Failed to load shift report');
            }
            return r.json();
        })
        .then(data => {
            if (!data.success || !data.register) {
                throw new Error(data.message || 'Could not load shift report');
            }
            applyShiftLabels(data.labels, data.shift_method);
            const r = data.register;
            const cur = currencySymbol || 'LKR';
            const money = (n) => cur + ' ' + Number(n || 0).toFixed(2);
            const bankOnline = Number(r.bank_transfer_sales || 0) + Number(r.online_sales || 0);

            if (footerMeta) {
                footerMeta.textContent = (r.cashier_name ? r.cashier_name + ' · ' : '') +
                    'Opened ' + (r.opened_at || '—') +
                    ' · ' + (r.orders_count || 0) + ' orders';
            }

            if (printBtn && r.id) {
                printBtn.classList.remove('d-none');
                printBtn.onclick = () => openPrintPreview('/pos/register/' + r.id + '/print?format=html');
            }

            body.innerHTML = `
                <div class="row g-3">
                    <div class="col-12"><h6 class="fw-bold text-muted mb-0">Cash</h6></div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#f8fafc;">
                            <small class="text-muted">Opening</small>
                            <div class="fw-bold fs-5 mt-1">${money(r.opening_balance)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#ecfdf5;">
                            <small class="text-muted">Cash Sales</small>
                            <div class="fw-bold mt-1">${money(r.cash_sales)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#fef2f2;">
                            <small class="text-muted">Cash Refunds</small>
                            <div class="fw-bold mt-1">${money(r.cash_refunds || 0)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#f0fdf4;">
                            <small class="text-muted">Cash In</small>
                            <div class="fw-bold mt-1 text-success">+ ${money(r.cash_in)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#fef2f2;">
                            <small class="text-muted">Cash Out</small>
                            <div class="fw-bold mt-1 text-danger">− ${money(r.cash_out)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#fff7ed;border:2px solid #f59e0b;">
                            <small class="text-muted">Expected</small>
                            <div class="fw-bold fs-5 mt-1" style="color:#b45309;">${money(r.expected_cash)}</div>
                        </div>
                    </div>
                    <div class="col-12"><h6 class="fw-bold text-muted mb-0 mt-1">Other</h6></div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#eff6ff;">
                            <small class="text-muted">Card (incl. surcharge)</small>
                            <div class="fw-bold mt-1">${money(r.card_sales)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#ecfeff;">
                            <small class="text-muted">Other methods</small>
                            <div class="fw-bold mt-1">${money(bankOnline + Number(r.credit_sales || 0))}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#f0fdf4;">
                            <small class="text-muted">Shift Total</small>
                            <div class="fw-bold fs-5 mt-1 text-success">${money(r.total_sales)}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 h-100" style="background:#fff7ed;">
                            <small class="text-muted">Orders</small>
                            <div class="fw-bold fs-5 mt-1" style="color:#ea580c;">${r.orders_count || 0}</div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            if (body) {
                body.innerHTML = `<div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <p class="mb-0">${escHtml(err.message || 'Could not load shift report')}</p>
                </div>`;
            }
        });
}

function showPayBills() {
    const modal = new bootstrap.Modal(document.getElementById('payBillsModal'));
    modal.show();
    loadPayBills();
}

function loadPayBills() {
    const body = document.getElementById('payBillsList');
    if (body) body.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading bills…</p></div>';

    fetch('/pos/pay-bills')
        .then(r => r.json())
        .then(data => {
            updatePayBillsBadge(data.count || 0);
            const orders = data.orders || [];
            if (!orders.length) {
                body.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i><p>No bills waiting for payment</p></div>';
                return;
            }
            body.innerHTML = orders.map(o => `
                <div class="bill-table-card" onclick="payBillFromAlert(${o.id})" style="cursor:pointer;">
                    <div class="bt-left">
                        <div class="bt-name">
                            <span class="badge bg-danger me-2">Bill ready</span>
                            ${o.table_name}
                            ${o.floor_name ? `<span class="bt-floor">${o.floor_name}</span>` : ''}
                        </div>
                        <div class="bt-meta">${o.order_number} · ${o.waiter ? 'Waiter: ' + o.waiter + ' · ' : ''}${o.items_count} item(s) · asked ${o.bill_requested_at || o.elapsed}</div>
                    </div>
                    <div class="bt-right text-end">
                        <div class="bt-total">${currencySymbol} ${Number(o.total).toFixed(2)}</div>
                        <button type="button" class="btn btn-sm btn-warning text-white mt-1" onclick="event.stopPropagation(); payBillFromAlert(${o.id})">
                            <i class="fas fa-cash-register me-1"></i>Pay
                        </button>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            if (body) body.innerHTML = '<div class="text-center text-danger py-4">Could not load pay bills</div>';
        });
}

function payBillFromAlert(orderId) {
    openOrderInCart(orderId, true).then(ok => {
        if (ok) {
            setTimeout(() => {
                if (typeof openPaymentModal === 'function') openPaymentModal();
                else document.getElementById('payBtn')?.click();
            }, 250);
        }
    });
}

function updatePayBillsBadge(count) {
    const n = Number(count) || 0;
    const badge = document.getElementById('payBillsBadge');
    const mobile = document.getElementById('payBillsBadgeMobile');
    if (badge) {
        badge.textContent = n;
        badge.classList.toggle('is-visible', n > 0);
    }
    if (mobile) mobile.textContent = n;
}

function updateOpenBillsBadge(count) {
    const n = Number(count) || 0;
    const badge = document.getElementById('openBillsBadge');
    const mobile = document.getElementById('openBillsBadgeMobile');
    if (badge) {
        badge.textContent = String(n);
        badge.classList.toggle('is-visible', n > 0);
    }
    if (mobile) mobile.textContent = String(n);
    document.querySelectorAll('.open-bills-btn').forEach(btn => {
        const shortcut = (typeof shortcutSuffix === 'function') ? shortcutSuffix('open_bills') : '';
        const badge = n > 0
            ? ` <span class="badge rounded-pill bg-dark ms-1">${n}</span>`
            : '';
        btn.innerHTML = '<i class="fas fa-receipt me-2"></i>Open Bills' + badge + shortcut;
    });
}

function setPayBillsVisible(visible) {
    document.querySelectorAll('[data-pay-bills-btn]').forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
}

function refreshPayBillsBadge() {
    fetch('/pos/pay-bills/count')
        .then(r => r.json())
        .then(data => {
            const on = data.bring_bill_enabled !== false;
            setPayBillsVisible(on);
            updatePayBillsBadge(on ? (data.count || 0) : 0);
        })
        .catch(() => {});
}

function refreshOpenBillsBadge() {
    fetch('/pos/open-bills/count')
        .then(r => r.json())
        .then(data => updateOpenBillsBadge(data.count || 0))
        .catch(() => {});
}

function loadWaiterReport(range) {
    const container = document.getElementById('waiterReportBody');
    const from = document.getElementById('waiterReportFrom')?.value || '';
    const to = document.getElementById('waiterReportTo')?.value || '';
    container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading waiter report...</p></div>';

    document.querySelectorAll('.waiter-range-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === range);
    });

    let url = '/pos/waiter-report?range=' + encodeURIComponent(range);
    if (range === 'custom' && from && to) {
        url += '&from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div class="text-center text-danger py-4">Could not load report</div>';
                return;
            }
            const cur = data.currency || 'LKR';
            const ranking = data.ranking || [];
            const invoices = data.invoices || [];
            const summary = data.summary || {};
            const top = data.top_waiter;

            const recentRatings = data.recent_ratings || [];

            if (data.from) document.getElementById('waiterReportFrom').value = data.from;
            if (data.to) document.getElementById('waiterReportTo').value = data.to;

            container.innerHTML = `
                <div class="row g-2 mb-3">
                    <div class="col-md-3"><div class="p-3 rounded-3" style="background:#fff7ed;"><div class="small text-muted">Total sales</div><div class="fw-bold fs-5" style="color:#ea580c;">${cur} ${Number(summary.total_sales||0).toFixed(2)}</div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-3" style="background:#f0fdf4;"><div class="small text-muted">Orders</div><div class="fw-bold fs-5">${summary.total_orders||0}</div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-3" style="background:#eff6ff;"><div class="small text-muted">Top waiter</div><div class="fw-bold">${top ? top.waiter_name : '—'}</div><div class="small text-muted">${top ? cur + ' ' + Number(top.total_sales).toFixed(2) : ''}</div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-3" style="background:#fefce8;"><div class="small text-muted">Guest rating</div><div class="fw-bold fs-5">${summary.avg_rating != null ? summary.avg_rating + ' ★' : '—'}</div><div class="small text-muted">${summary.ratings_count||0} ratings</div></div></div>
                </div>
                <h6 class="fw-bold mb-2"><i class="fas fa-trophy me-1 text-warning"></i>Who did good sales</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-striped align-middle">
                        <thead><tr><th>#</th><th>Waiter</th><th class="text-end">Orders</th><th class="text-end">Sales</th><th class="text-end">Rating</th><th class="text-end">Share</th></tr></thead>
                        <tbody>
                            ${ranking.length ? ranking.map(r => `
                                <tr>
                                    <td>${r.rank === 1 ? '<span class="badge bg-warning text-dark">1</span>' : r.rank}</td>
                                    <td class="fw-semibold">${r.waiter_name}</td>
                                    <td class="text-end">${r.orders_count}</td>
                                    <td class="text-end fw-bold text-success">${cur} ${Number(r.total_sales).toFixed(2)}</td>
                                    <td class="text-end">${r.avg_rating != null ? ((r.rating_emoji||'') + ' ' + r.avg_rating + ' <small class="text-muted">(' + r.ratings_count + ')</small>') : '—'}</td>
                                    <td class="text-end">${r.share_pct}%</td>
                                </tr>
                            `).join('') : '<tr><td colspan="6" class="text-center text-muted py-3">No waiter sales in this range</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <h6 class="fw-bold mb-2"><i class="fas fa-face-smile me-1"></i>Recent guest ratings</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover align-middle" style="font-size:0.85rem;">
                        <thead><tr><th>When</th><th>Invoice</th><th>Waiter</th><th>Rating</th></tr></thead>
                        <tbody>
                            ${recentRatings.length ? recentRatings.map(r => `
                                <tr>
                                    <td>${r.rated_at || ''}</td>
                                    <td><span class="badge bg-dark">${r.order_number || ''}</span> ${r.table ? '<small class="text-muted">' + r.table + '</small>' : ''}</td>
                                    <td>${r.waiter_name || '—'}</td>
                                    <td style="font-size:1.2rem;">${r.emoji || ''} <small class="text-muted">${r.rating}/5</small></td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" class="text-center text-muted py-3">No ratings yet</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <h6 class="fw-bold mb-2"><i class="fas fa-receipt me-1"></i>Invoices &amp; KOT waiter</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" style="font-size:0.85rem;">
                        <thead><tr><th>Invoice</th><th>Time</th><th>Table</th><th>Waiter</th><th>Rating</th><th>KOT/BOT</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                        <tbody>
                            ${invoices.length ? invoices.map(inv => `
                                <tr>
                                    <td><span class="badge bg-dark">${inv.order_number}</span></td>
                                    <td>${inv.created_at || ''}</td>
                                    <td>${inv.table || '—'}</td>
                                    <td class="fw-semibold">${inv.waiter_name || '—'}</td>
                                    <td>${inv.rating ? ((inv.rating_emoji||'') + ' <small class="text-muted">' + inv.rating + '/5</small>') : '—'}</td>
                                    <td>${(inv.kots||[]).map(k => `<span class="badge ${k.type==='bar'?'bg-info':'bg-warning text-dark'} me-1">${k.number}</span>`).join('') || '—'}</td>
                                    <td class="text-end">${cur} ${Number(inv.total).toFixed(2)}</td>
                                    <td><span class="badge ${inv.payment_status==='paid'?'bg-success':'bg-secondary'}">${inv.payment_status}</span></td>
                                </tr>
                            `).join('') : '<tr><td colspan="8" class="text-center text-muted py-3">No invoices</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center text-danger py-4">Network error</div>';
        });
}

function loadRecentOrders() {
    const container = document.getElementById('recentOrdersList');
    container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading orders...</p></div>';

    fetch('/pos/recent-orders')
        .then(r => r.json())
        .then(data => {
            const orders = data.orders || [];
            if (!orders.length) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-inbox fa-3x mb-3"></i><p>No orders today yet</p></div>';
                return;
            }

            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table recent-orders-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Waiter</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orders.map(o => {
                                const typeCls = o.order_type === 'dine_in' ? 'type-dine' : o.order_type === 'takeaway' ? 'type-take' : o.order_type === 'delivery' ? 'type-del' : 'type-exp';
                                const payCls = o.payment_status === 'paid' ? 'pay-paid' : 'pay-unpaid';
                                return `
                                <tr>
                                    <td><span class="ro-pill ro-order">${escHtml(o.order_number)}</span></td>
                                    <td><span class="ro-pill ${typeCls}">${escHtml((o.order_type || '').replace('_', ' '))}</span></td>
                                    <td>
                                        <div class="ro-customer">${escHtml(o.customer || 'Walk-in')}</div>
                                        ${o.table ? `<div class="ro-table">T: ${escHtml(o.table)}</div>` : ''}
                                    </td>
                                    <td class="text-muted">${o.waiter ? escHtml(o.waiter) : '—'}</td>
                                    <td>${o.items_count}</td>
                                    <td class="ro-total">${currencySymbol} ${Number(o.total).toFixed(2)}</td>
                                    <td class="text-muted text-nowrap">${escHtml(o.created_at)}</td>
                                    <td>
                                        <div class="ro-actions">
                                            <button type="button" class="ro-btn ro-btn-view" onclick="viewOrderDetail(${o.id})" title="View"><i class="fas fa-eye"></i></button>
                                            <button type="button" class="ro-btn ro-btn-open" onclick="editOrder(${o.id})" title="Edit Order — same order number"><i class="fas fa-edit"></i> Edit Order</button>
                                            <span class="ro-pill ${payCls}">${escHtml(o.payment_status || 'unpaid')}</span>
                                            <button type="button" class="ro-btn ro-btn-receipt" onclick="reprintReceipt(${o.id})" title="Reprint Invoice"><i class="fas fa-receipt"></i></button>
                                            ${o.has_kot ? `<button type="button" class="ro-btn ro-btn-kot" onclick="reprintKOT(${o.id})" title="Reprint KOT"><i class="fas fa-print"></i> KOT</button>` : ''}
                                            ${o.has_bot ? `<button type="button" class="ro-btn ro-btn-bot" onclick="reprintBOT(${o.id})" title="Reprint BOT"><i class="fas fa-cocktail"></i> BOT</button>` : ''}
                                            ${(o.kitchen_orders || []).length ? `<button type="button" class="ro-btn ro-btn-kitchen" onclick="openKotModifyModal(${o.id})" title="Modify KOT/BOT"><i class="fas fa-utensils"></i></button>` : ''}
                                        </div>
                                    </td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        })
        .catch(err => {
            console.error('Load recent orders error:', err);
            container.innerHTML = '<div class="text-center text-danger py-5"><i class="fas fa-exclamation-triangle fa-2x mb-3"></i><p>Failed to load orders</p></div>';
        });
}

function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function viewOrderDetail(orderId) {
    fetch(`/pos/order-details/${orderId}`)
        .then(r => r.json())
        .then(data => {
            const order = data.order;
            document.getElementById('orderDetailTitle').innerHTML = `<i class="fas fa-receipt me-2"></i>${order.order_number}`;

            let itemsHtml = order.items.map(item => `
                <div class="d-flex justify-content-between align-items-center p-2 mb-1" style="background: #f8fafc; border-radius: 8px;">
                    <div>
                        <div class="fw-semibold">${item.product_name}${item.is_comp ? ' <span class="badge bg-dark">COMP</span>' : ''}</div>
                        <div class="text-muted small">Qty: ${item.quantity} x LKR ${item.unit_price.toFixed(2)}</div>
                        ${item.addons.length ? `<div class="small text-muted">+ ${item.addons.map(a => a.addon_name).join(', ')}</div>` : ''}
                        ${(item.discount_amount || item.discount || 0) > 0 && !item.is_comp ? `<div class="small text-danger fw-semibold">Discount −LKR ${Number(item.discount_amount || item.discount).toFixed(2)}</div>` : ''}
                        ${item.is_comp && item.comp_reason ? `<div class="small text-muted">Comp: ${escHtml(item.comp_reason)}</div>` : ''}
                    </div>
                    <div class="fw-bold" style="color: #f59e0b;">${item.is_comp ? 'COMP' : ('LKR ' + item.total_price.toFixed(2))}</div>
                </div>
            `).join('');

            document.getElementById('orderDetailBody').innerHTML = `
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-${order.order_type === 'dine_in' ? 'success' : order.order_type === 'takeaway' ? 'warning text-dark' : 'info'}">${order.order_type.replace('_', ' ')}</span>
                    <span class="badge bg-secondary">${order.status}</span>
                </div>
                ${order.is_comp ? `<div class="alert alert-dark py-2 small">COMP bill${order.comp_reason ? ': ' + escHtml(order.comp_reason) : ''}</div>` : ''}
                <div class="mb-3"><strong>Customer:</strong> ${order.customer_name || 'Walk-in'}</div>
                ${order.table_name ? `<div class="mb-3"><strong>Table:</strong> ${order.table_name}</div>` : ''}
                <div class="mb-3"><strong>Waiter:</strong> ${order.waiter_name || '—'}</div>
                ${order.cashier_name ? `<div class="mb-3"><strong>Cashier:</strong> ${order.cashier_name}</div>` : ''}
                ${order.order_notes ? `<div class="mb-3 alert alert-light"><strong>Notes:</strong> ${order.order_notes}</div>` : ''}
                <h6 class="fw-bold mb-2" style="color: #475569;">Items:</h6>
                ${itemsHtml}
                <hr>
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>LKR ${order.subtotal.toFixed(2)}</span></div>
                ${taxEnabled ? `<div class="d-flex justify-content-between"><span>${taxName || 'Tax'}</span><span>LKR ${order.tax_amount.toFixed(2)}</span></div>` : ''}
                ${order.discount_amount > 0 ? `<div class="d-flex justify-content-between"><span>Discount</span><span>-LKR ${order.discount_amount.toFixed(2)}</span></div>` : ''}
                ${(order.rounding_amount || 0) != 0 ? `<div class="d-flex justify-content-between"><span>Rounding</span><span>LKR ${Number(order.rounding_amount).toFixed(2)}</span></div>` : ''}
                ${(order.card_surcharge_amount || 0) > 0 ? `<div class="d-flex justify-content-between"><span>Card surcharge</span><span>LKR ${Number(order.card_surcharge_amount).toFixed(2)}</span></div>` : ''}
                <div class="d-flex justify-content-between fw-bold fs-5 mt-2" style="color: #f59e0b;">
                    <span>TOTAL</span><span>LKR ${(Number(order.total_amount) + Number(order.card_surcharge_amount || 0)).toFixed(2)}</span>
                </div>
            `;

            document.getElementById('orderDetailFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                ${!order.is_void ? `<button type="button" class="btn btn-warning text-white" onclick="editOrder(${order.id})"><i class="fas fa-edit me-2"></i>Edit Order</button>` : ''}
                ${canPosRefund && order.payment_status === 'paid' && order.status !== 'refunded' ? `<button type="button" class="btn btn-outline-danger" onclick="refundOrderPrompt(${order.id}, ${Number(order.total_amount) + Number(order.card_surcharge_amount || 0)})"><i class="fas fa-undo me-2"></i>Refund</button>` : ''}
                <button type="button" class="btn btn-success" onclick="reprintReceipt(${order.id})"><i class="fas fa-receipt me-2"></i>Reprint Invoice</button>
                ${order.kitchen_orders.some(k => k.type === 'kitchen') ? `<button type="button" class="btn btn-outline-warning" onclick="reprintKOT(${order.id})"><i class="fas fa-print me-2"></i>KOT</button>` : ''}
                ${order.kitchen_orders.some(k => k.type === 'bar') ? `<button type="button" class="btn btn-outline-info" onclick="reprintBOT(${order.id})"><i class="fas fa-cocktail me-2"></i>BOT</button>` : ''}
            `;

            const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
            modal.show();
        });
}

function refundOrderPrompt(orderId, maxAmount) {
    if (!canPosRefund) {
        showToast('error', 'Not allowed to refund');
        return;
    }
    Swal.fire({
        title: 'Refund order',
        html: `
            <div class="text-start">
                <label class="form-label small">Amount (max ${Number(maxAmount).toFixed(2)})</label>
                <input id="refundAmountInput" type="number" class="form-control mb-2" min="0.01" step="0.01" value="${Number(maxAmount).toFixed(2)}">
                <label class="form-label small">Method</label>
                <select id="refundMethodSelect" class="form-select mb-2">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                </select>
                <label class="form-label small">Reason</label>
                <input id="refundReasonInput" type="text" class="form-control" value="Refund">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Refund',
        confirmButtonColor: '#dc2626',
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('refundAmountInput')?.value || '0');
            const method = document.getElementById('refundMethodSelect')?.value || 'cash';
            const reason = (document.getElementById('refundReasonInput')?.value || '').trim();
            if (!(amount > 0)) {
                Swal.showValidationMessage('Enter a valid amount');
                return false;
            }
            return { amount, method, reason };
        },
    }).then(result => {
        if (!result.isConfirmed || !result.value) return;
        fetch('/pos/refund', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                order_id: orderId,
                amount: result.value.amount,
                method: result.value.method,
                reason: result.value.reason,
                full: Math.abs(result.value.amount - Number(maxAmount)) < 0.02,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Refund recorded');
                bootstrap.Modal.getInstance(document.getElementById('orderDetailModal'))?.hide();
                if (typeof loadRecentOrders === 'function') loadRecentOrders();
            } else {
                showToast('error', data.message || 'Refund failed');
            }
        })
        .catch(() => showToast('error', 'Network error'));
    });
}

function reprintReceipt(orderId) {
    handleReceiptPrint(`/pos/print-receipt/${orderId}`, orderId, true);
}

function reprintKOT(orderId) {
    reprintKitchenTickets(orderId, 'kitchen');
}

function reprintBOT(orderId) {
    reprintKitchenTickets(orderId, 'bar');
}

function reprintKitchenTickets(orderId, type) {
    const label = type === 'bar' ? 'BOT' : 'KOT';
    fetch('/pos/order-details/' + orderId, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const jobs = (data.order?.kitchen_orders || [])
                .filter(k => k.type === type)
                .map(k => ({
                    type: type === 'bar' ? 'bot' : 'kot',
                    kitchen_order_id: k.id,
                    url: type === 'bar'
                        ? (`/pos/print-bot/${orderId}?kitchen_order_id=${k.id}`)
                        : (`/pos/print-kot/${orderId}?kitchen_order_id=${k.id}`),
                    auto_print: true,
                    printer_ip: k.printer_ip,
                    printer_port: k.printer_port,
                    printer_name: k.printer_name,
                    print_mode: type === 'kitchen' ? 'direct' : (k.print_mode || 'preview'),
                    kitchen_name: k.kitchen_name,
                }));
            if (!jobs.length) {
                if (type === 'bar') {
                    openPrintPreview(`/pos/print-bot/${orderId}`);
                    if (typeof showToast === 'function') showToast('info', label + ' opening...');
                } else {
                    showToast('error', 'No KOT found to print');
                }
                return;
            }
            handlePrintJobs(jobs, { force: true });
        })
        .catch(() => {
            if (type === 'bar') {
                openPrintPreview(`/pos/print-bot/${orderId}`);
                if (typeof showToast === 'function') showToast('info', label + ' opening...');
            } else {
                showToast('error', 'Could not load KOT for printing');
            }
        });
}

function reprintLastInvoice() {
    if (lastSaleOrderId) {
        handleReceiptPrint(`/pos/print-receipt/${lastSaleOrderId}`, lastSaleOrderId, true);
    } else {
        handleReceiptPrint('/pos/last-receipt', null, true);
    }
}

function reprintLastKOT() {
    fetch('/pos/last-kot?format=json', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        cache: 'no-store',
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.success || !d.kitchen_order_id) {
            throw new Error((d && d.message) || 'No KOT found');
        }
        printKitchenTicketSmart({
            type: 'kot',
            kitchen_order_id: d.kitchen_order_id,
            printer_ip: d.printer_ip,
            printer_port: d.printer_port,
            printer_name: d.printer_name,
            print_mode: 'direct',
            auto_print: true,
            url: d.url,
        });
        if (typeof showToast === 'function') showToast('info', 'Sending last KOT to kitchen printer...');
    })
    .catch(e => {
        showToast('warning', e.message || 'No KOT found');
    });
}

function reprintLastBOT() {
    fetch('/pos/last-bot')
    .then(r => {
        if (!r.ok) return r.json().then(d => { throw new Error(d.message || 'No BOT found'); });
        openPrintPreview('/pos/last-bot');
        if (typeof showToast === 'function') showToast('info', 'Last BOT opening...');
    })
    .catch(e => {
        showToast('warning', e.message);
    });
}

let currentKotId = null;

function openKotModifyModal(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('kotModifyModal'));
    const body = document.getElementById('kotModifyList');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading KOTs...</p></div>';
    modal.show();

    fetch(`/pos/order-details/${orderId}`)
    .then(r => r.json())
    .then(data => {
        const order = data.order;
        if (!order.kitchen_orders.length) {
            body.innerHTML = '<div class="text-center text-muted py-4"><p>No KOT/BOT available</p></div>';
            return;
        }
        let html = `<div class="mb-3"><strong>Order:</strong> ${order.order_number} ${order.table_name ? '· Table: ' + order.table_name : ''}</div>`;
        order.kitchen_orders.forEach(kot => {
            html += `
            <div class="kot-select-card" onclick="editKot(${kot.id})">
                <div class="d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-${kot.type === 'bar' ? 'cocktail' : 'utensils'} me-2 text-warning"></i><strong>${kot.kot_number}</strong></div>
                    <span class="badge bg-secondary">${kot.status}</span>
                </div>
                <div class="text-muted small mt-1">${kot.type.toUpperCase()} · Click to modify</div>
            </div>`;
        });
        body.innerHTML = html;
    })
    .catch(err => {
        body.innerHTML = '<div class="text-center text-danger py-4"><p>Failed to load KOTs</p></div>';
    });
}

function editKot(kotId) {
    currentKotId = kotId;
    const body = document.getElementById('kotModifyList');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading KOT items...</p></div>';

    fetch(`/pos/kitchen-order/${kotId}`)
    .then(r => r.json())
    .then(data => {
        const kot = data.kot;
        let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><strong>${kot.kot_number}</strong> <span class="badge bg-secondary">${kot.type.toUpperCase()}</span></div>
            <button class="btn btn-sm btn-secondary" onclick="openKotModifyModal(${kot.order_id})"><i class="fas fa-arrow-left me-1"></i>Back</button>
        </div>
        <div class="alert alert-light mb-3"><i class="fas fa-info-circle me-2"></i>Update quantities below. Set to 0 to remove an item.</div>
        <div id="kotEditItems" class="d-flex flex-column gap-2">`;
        kot.items.forEach(item => {
            html += `
            <div class="kot-edit-item d-flex justify-content-between align-items-center p-3" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
                <div>
                    <div class="fw-semibold">${item.product_name}</div>
                    ${item.addons.length ? `<div class="small text-muted">+ ${item.addons.map(a => a.addon_name).join(', ')}</div>` : ''}
                    <div class="small text-muted">LKR ${item.unit_price.toFixed(2)} each</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="adjustKotQty(this, -1)"><i class="fas fa-minus"></i></button>
                    <input type="number" class="form-control form-control-sm text-center kot-qty-input" data-id="${item.id}" value="${item.quantity}" min="0" step="0.1" style="width:70px;">
                    <button class="btn btn-sm btn-outline-secondary" onclick="adjustKotQty(this, 1)"><i class="fas fa-plus"></i></button>
                </div>
            </div>`;
        });
        html += `</div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-secondary flex-fill" onclick="bootstrap.Modal.getInstance(document.getElementById('kotModifyModal')).hide();">Cancel</button>
            <button class="btn btn-warning flex-fill" onclick="saveKotChanges()"><i class="fas fa-save me-2"></i>Save Changes</button>
        </div>`;
        body.innerHTML = html;
    })
    .catch(err => {
        body.innerHTML = '<div class="text-center text-danger py-4"><p>Failed to load KOT items</p></div>';
    });
}

function adjustKotQty(btn, delta) {
    const input = btn.parentElement.querySelector('.kot-qty-input');
    let val = parseFloat(input.value) || 0;
    val = Math.max(0, val + delta);
    input.value = val % 1 === 0 ? val.toFixed(0) : val.toFixed(1);
}

function saveKotChanges() {
    if (!currentKotId) return;
    const items = [];
    document.querySelectorAll('#kotEditItems .kot-qty-input').forEach(input => {
        items.push({ id: parseInt(input.dataset.id), quantity: parseFloat(input.value) || 0 });
    });

    fetch(`/pos/kitchen-order/${currentKotId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ items: items })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'KOT updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('kotModifyModal')).hide();
            loadRecentOrders();
        } else {
            showToast('error', data.message || 'Error');
        }
    })
    .catch(e => {
        showToast('error', 'Network error');
    });
}

function applyBillDiscount() {
    Swal.fire({
        title: 'Apply Discount',
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-semibold">Discount Type</label>
                <div class="btn-group w-100" role="group" id="discountTypeGroup">
                    <button type="button" class="btn ${billDiscountType === 'fixed' ? 'btn-warning' : 'btn-outline-secondary'}" onclick="setDiscountType('fixed')" id="discountTypeFixed">Fixed Amount</button>
                    <button type="button" class="btn ${billDiscountType === 'percentage' ? 'btn-warning' : 'btn-outline-secondary'}" onclick="setDiscountType('percentage')" id="discountTypePercentage">Percentage (%)</button>
                </div>
            </div>
            <div class="text-start mb-2">
                <label class="form-label fw-semibold" id="discountInputLabel">${billDiscountType === 'percentage' ? 'Discount %' : 'Discount Amount'}</label>
                <input type="number" id="discountValueInput" class="form-control form-control-lg" placeholder="0" value="${billDiscount > 0 ? billDiscount : ''}" min="0" step="0.01">
            </div>
            <div class="text-start text-muted small"><i class="fas fa-info-circle me-1"></i>Enter 0 to remove discount</div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-2"></i>Apply',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        allowOutsideClick: false,
        didOpen: () => {
            document.getElementById('discountValueInput').focus();
        },
        preConfirm: () => {
            const value = parseFloat(document.getElementById('discountValueInput').value) || 0;
            const type = document.getElementById('discountTypeFixed').classList.contains('btn-warning') ? 'fixed' : 'percentage';
            return { value: value, type: type };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            billDiscount = result.value.value;
            billDiscountType = result.value.type;
            updateCart();
            if (typeof showToast === 'function') showToast('success', 'Discount updated');
        }
    });
}

function applyItemDiscount(index) {
    const item = cart[index];
    if (!item || item.loyalty_free) return;
    const addonTotal = (item.addons || []).reduce((s, a) => s + Number(a.price || 0), 0);
    const lineGross = (Number(item.price) + addonTotal) * Number(item.quantity);
    const current = Number(item.discount || 0);

    Swal.fire({
        title: 'Item discount',
        html: `
            <p class="text-muted small mb-2 text-start"><strong>${escHtml(item.name)}</strong><br>Line total ${currencySymbol} ${lineGross.toFixed(2)}</p>
            <div class="text-start mb-2">
                <label class="form-label fw-semibold">Discount type</label>
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-warning" id="itemDiscFixed" onclick="setItemDiscType('fixed')">Fixed</button>
                    <button type="button" class="btn btn-outline-secondary" id="itemDiscPct" onclick="setItemDiscType('percentage')">%</button>
                </div>
            </div>
            <div class="text-start">
                <label class="form-label fw-semibold" id="itemDiscLabel">Discount amount</label>
                <input type="number" id="itemDiscValue" class="form-control form-control-lg" min="0" step="0.01" value="${current > 0 ? current : ''}" placeholder="0">
            </div>
            <div class="text-muted small text-start mt-2">Enter 0 to clear this item discount</div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Apply',
        confirmButtonColor: '#f59e0b',
        didOpen: () => document.getElementById('itemDiscValue')?.focus(),
        preConfirm: () => {
            const raw = parseFloat(document.getElementById('itemDiscValue')?.value) || 0;
            const isPct = document.getElementById('itemDiscPct')?.classList.contains('btn-warning');
            let amount = isPct ? (lineGross * raw / 100) : raw;
            if (amount < 0) amount = 0;
            if (amount > lineGross) amount = lineGross;
            return Math.round(amount * 100) / 100;
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        cart[index].discount = result.value;
        updateCart();
        if (typeof showToast === 'function') {
            showToast('success', result.value > 0
                ? ('Item discount ' + currencySymbol + ' ' + result.value.toFixed(2))
                : 'Item discount cleared');
        }
    });
}

function setItemDiscType(type) {
    const fixedBtn = document.getElementById('itemDiscFixed');
    const pctBtn = document.getElementById('itemDiscPct');
    const label = document.getElementById('itemDiscLabel');
    if (!fixedBtn || !pctBtn) return;
    if (type === 'fixed') {
        fixedBtn.className = 'btn btn-warning';
        pctBtn.className = 'btn btn-outline-secondary';
        if (label) label.textContent = 'Discount amount';
    } else {
        fixedBtn.className = 'btn btn-outline-secondary';
        pctBtn.className = 'btn btn-warning';
        if (label) label.textContent = 'Discount %';
    }
}

function setDiscountType(type) {
    const fixedBtn = document.getElementById('discountTypeFixed');
    const percentBtn = document.getElementById('discountTypePercentage');
    const label = document.getElementById('discountInputLabel');
    const input = document.getElementById('discountValueInput');
    if (type === 'fixed') {
        fixedBtn.className = 'btn btn-warning';
        percentBtn.className = 'btn btn-outline-secondary';
        label.textContent = 'Discount Amount';
        input.step = '0.01';
    } else {
        fixedBtn.className = 'btn btn-outline-secondary';
        percentBtn.className = 'btn btn-warning';
        label.textContent = 'Discount %';
        input.step = '0.01';
    }
}

function showNotes() {
    Swal.fire({
        title: 'Order Notes',
        input: 'textarea',
        inputLabel: 'Add notes for the order',
        inputValue: orderNotes,
        inputPlaceholder: 'E.g. less spicy, no onions, extra sauce...',
        inputAttributes: { 'aria-label': 'Order notes' },
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save me-2"></i>Save Notes',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#64748b',
        allowOutsideClick: false,
    }).then((result) => {
        if (result.isConfirmed) {
            orderNotes = result.value || '';
            if (typeof showToast === 'function') showToast('success', 'Notes saved');
        }
    });
}

// Register Functions
let currentRegister = null;
let shiftMethod = @json(\App\Models\CashRegister::configuredMethod());
let shiftLabels = @json(\App\Models\CashRegister::labels());

document.addEventListener('DOMContentLoaded', () => {
    if (typeof applyShiftLabels === 'function') applyShiftLabels(shiftLabels, shiftMethod);
    if (typeof applyPosNumpadVisibility === 'function') applyPosNumpadVisibility(getPosNumpadVisible());
});

function applyShiftLabels(labels, method) {
    if (labels) shiftLabels = { ...shiftLabels, ...labels };
    if (method) shiftMethod = method;
    document.querySelectorAll('[data-shift-label="open-title"]').forEach(el => { el.innerHTML = `<i class="fas fa-cash-register me-2"></i>${shiftLabels.open}`; });
    document.querySelectorAll('[data-shift-label="open-btn"]').forEach(el => { el.innerHTML = `<i class="fas fa-check me-2"></i>${shiftLabels.open}`; });
    document.querySelectorAll('[data-shift-label="close-title"]').forEach(el => { el.innerHTML = `<i class="fas fa-clock me-2"></i>${shiftLabels.close}`; });
    document.querySelectorAll('[data-shift-label="close-btn"]').forEach(el => { el.innerHTML = `<i class="fas fa-lock me-2"></i>${shiftLabels.close}`; });
    document.querySelectorAll('[data-shift-label="report"]').forEach(el => {
        const icon = el.querySelector('i')?.outerHTML || '<i class="fas fa-chart-pie"></i>';
        const span = el.querySelector('span');
        if (span) span.textContent = shiftLabels.report;
        else el.innerHTML = `${icon} ${shiftLabels.report}`;
    });
    document.querySelectorAll('[data-shift-label="report-title"]').forEach(el => {
        el.innerHTML = `<i class="fas fa-chart-pie me-2" style="color:#fbbf24;"></i>${shiftLabels.report}`;
    });
    document.querySelectorAll('[data-shift-label="close-from-report"]').forEach(el => {
        el.innerHTML = `<i class="fas fa-clock me-1"></i>${shiftLabels.close}`;
    });
    document.querySelectorAll('[data-shift-label="header-close"]').forEach(el => {
        const text = el.querySelector('[data-shift-label-text]') || el.querySelector('span');
        if (text) text.textContent = shiftLabels.close;
        el.setAttribute('title', shiftLabels.close);
    });
    const openHint = document.getElementById('openingBalanceHint');
    if (openHint) openHint.textContent = `Enter the cash amount in the drawer at start of ${shiftLabels.session.toLowerCase()}`;
    const dayEndBtn = document.getElementById('dayEndAggregateBtn');
    if (dayEndBtn) dayEndBtn.style.display = shiftMethod === 'shift' ? '' : 'none';
}

async function showDayEndAggregate() {
    bootstrap.Modal.getInstance(document.getElementById('cashDrawerModal'))?.hide();
    const { value: formValues } = await Swal.fire({
        title: 'Day End Report',
        html: `
            <p class="text-muted small mb-3">Totals <strong>all closed shifts</strong> for the business day. Close every open shift and <strong>complete all open bills</strong> first. After {{ \App\Models\CashRegister::dayEndCutoff() }}, closing the last shift asks whether to end the day.</p>
            <label class="form-label text-start w-100">Opening cash (optional)</label>
            <input id="swal-opening" type="number" step="0.01" min="0" class="swal2-input" placeholder="Uses first shift opening if blank" style="width:90%">
            <label class="form-label text-start w-100 mt-2">Closing cash *</label>
            <input id="swal-closing" type="number" step="0.01" min="0" class="swal2-input" placeholder="0.00" style="width:90%">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'End Day & Print',
        confirmButtonColor: '#f59e0b',
        preConfirm: () => {
            const closing = parseFloat(document.getElementById('swal-closing').value);
            if (isNaN(closing) || closing < 0) {
                Swal.showValidationMessage('Enter closing cash');
                return false;
            }
            const openingRaw = document.getElementById('swal-opening').value;
            return {
                closing_cash: closing,
                opening_cash: openingRaw === '' ? null : parseFloat(openingRaw),
            };
        }
    });
    if (!formValues) return;

    try {
        const check = await fetch('/pos/open-bills/count', { headers: { 'Accept': 'application/json' } });
        const checkData = await check.json();
        const openBills = Number(checkData.count || 0);
        if (openBills > 0) {
            showToast('error', `Complete all open bills before day end (${openBills} still open)`);
            return;
        }
    } catch (e) {}

    const body = { closing_cash: formValues.closing_cash };
    if (formValues.opening_cash !== null && !isNaN(formValues.opening_cash)) {
        body.opening_cash = formValues.opening_cash;
    }

    try {
        const r = await fetch('/pos/register/day-end', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(body),
        });
        const data = await r.json();
        if (!data.success) {
            showToast('error', data.message || 'Day end failed');
            return;
        }
        const printUrl = data.print_url;
        Swal.fire({
            title: 'Day Ended',
            html: `<div class="text-start">
                <div class="d-flex justify-content-between"><span>Shifts</span><strong>${data.shifts_count}</strong></div>
                <div class="d-flex justify-content-between"><span>Total sales</span><strong>LKR ${data.summary.total_sales.toFixed(2)}</strong></div>
                <div class="d-flex justify-content-between"><span>Difference</span><strong>LKR ${data.summary.difference.toFixed(2)}</strong></div>
                ${(data.shifts || []).map(s => `<div class="d-flex justify-content-between small mt-1"><span>${s.cashier || '#'+s.id} ${s.opened_at||''}-${s.closed_at||''}</span><strong>LKR ${Number(s.total_sales||0).toFixed(2)}</strong></div>`).join('')}
            </div>`,
            icon: 'success',
            confirmButtonText: 'Print 80mm',
            confirmButtonColor: '#f59e0b',
        }).then((res) => {
            if (res.isConfirmed && printUrl) openPrintPreview(printUrl);
        });
    } catch (e) {
        showToast('error', 'Day end failed');
    }
}

function checkRegisterStatus() {
    fetch('/pos/register/status')
    .then(r => r.json())
    .then(data => {
        applyShiftLabels(data.labels, data.shift_method);
        if (!data.has_open_register) {
            new bootstrap.Modal(document.getElementById('registerOpenModal')).show();
        } else {
            currentRegister = data.register;
            updateRegisterDisplay();
        }
    });
}

function openRegister() {
    const balance = parseFloat(document.getElementById('openingBalance').value) || 0;
    const notes = document.getElementById('registerNotes').value;

    fetch('/pos/register/open', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ opening_balance: balance, notes: notes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            applyShiftLabels(data.labels, data.shift_method);
            currentRegister = data.register;
            bootstrap.Modal.getInstance(document.getElementById('registerOpenModal')).hide();
            showToast('success', shiftLabels.session + ' started');
            updateRegisterDisplay();
        } else {
            showToast('error', data.message || 'Error opening register');
        }
    });
}

function showShiftCloseModal() {
    fetch('/pos/register/report')
    .then(r => {
        if (!r.ok) {
            if (r.status === 401) {
                throw new Error('Please log in first');
            }
            if (r.status === 404) {
                throw new Error('No open register found. Please open a register first (Click "Drawer" button).');
            }
            throw new Error(`Failed to load register report (Status: ${r.status})`);
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            const r = data.register;
            document.getElementById('shiftReport').innerHTML = `
                <div class="row g-3">
                    <div class="col-12"><h6 class="fw-bold text-muted mb-0">Cash</h6></div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #f8fafc;">
                            <small class="text-muted">Opening</small>
                            <h5 class="mb-0">LKR ${parseFloat(r.opening_balance).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #ecfdf5;">
                            <small class="text-muted">Cash Sales</small>
                            <h5 class="mb-0">LKR ${parseFloat(r.cash_sales).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #fef2f2;">
                            <small class="text-muted">Cash Refunds</small>
                            <h5 class="mb-0">LKR ${parseFloat(r.cash_refunds || 0).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #f0fdf4;">
                            <small class="text-muted">Cash In</small>
                            <h5 class="mb-0 text-success">+ LKR ${parseFloat(r.cash_in).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #fef2f2;">
                            <small class="text-muted">Cash Out</small>
                            <h5 class="mb-0 text-danger">− LKR ${parseFloat(r.cash_out).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #fff7ed;">
                            <small class="text-muted">Expected</small>
                            <h5 class="mb-0 fw-bold text-primary">LKR ${parseFloat(r.expected_cash).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-12"><h6 class="fw-bold text-muted mb-0 mt-2">Other</h6></div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #eff6ff;">
                            <small class="text-muted">Card (incl. surcharge)</small>
                            <h5 class="mb-0">LKR ${parseFloat(r.card_sales).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background: #ecfeff;">
                            <small class="text-muted">Bank / Online / Credit</small>
                            <h5 class="mb-0">LKR ${(parseFloat(r.bank_transfer_sales) + parseFloat(r.online_sales) + parseFloat(r.credit_sales)).toFixed(2)}</h5>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background: #f0fdf4;">
                            <small class="text-muted">Shift Total</small>
                            <h4 class="mb-0 fw-bold text-success">LKR ${parseFloat(r.total_sales).toFixed(2)}</h4>
                        </div>
                    </div>
                </div>
            `;
            resetDenominationInputs();
            new bootstrap.Modal(document.getElementById('shiftCloseModal')).show();
        }
    })
    .catch(err => {
        console.error('Shift close error:', err);
        showToast('error', err.message || 'Error loading register report');
    });
}

// Calculate denomination total
function calculateDenominationTotal() {
    let total = 0;
    document.querySelectorAll('#shiftCloseModal .denom-input').forEach(input => {
        const count = parseInt(input.value, 10) || 0;
        const value = parseInt(input.dataset.value, 10) || 0;
        total += count * value;
    });
    const coins = parseFloat(document.getElementById('coinAmount')?.value) || 0;
    total += coins;

    const balanceEl = document.getElementById('closingBalance');
    const totalEl = document.getElementById('denominationTotal');
    if (balanceEl) balanceEl.value = total.toFixed(2);
    if (totalEl) totalEl.textContent = `Total: LKR ${total.toFixed(2)}`;
}

// Event delegation — works even though the modal HTML is below this script
document.addEventListener('input', function (e) {
    const t = e.target;
    if (!t) return;
    if (t.classList?.contains('denom-input') || t.id === 'coinAmount') {
        calculateDenominationTotal();
    }
});

function resetDenominationInputs() {
    document.querySelectorAll('#shiftCloseModal .denom-input').forEach(input => {
        input.value = '';
    });
    const coinInput = document.getElementById('coinAmount');
    if (coinInput) coinInput.value = '';
    calculateDenominationTotal();
}

function closeRegister() {
    calculateDenominationTotal();
    const balance = parseFloat(document.getElementById('closingBalance').value) || 0;
    const notes = document.getElementById('closingNotes').value;

    // Build denomination breakdown
    const denominations = {};
    document.querySelectorAll('.denom-input').forEach(input => {
        const count = parseInt(input.value) || 0;
        const value = parseInt(input.dataset.value);
        if (count > 0) {
            denominations[value] = count;
        }
    });
    const coins = parseFloat(document.getElementById('coinAmount')?.value) || 0;
    if (coins > 0) {
        denominations['coins'] = coins;
    }

    fetch('/pos/register/close', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ closing_balance: balance, notes: notes, denominations: denominations })
    })
    .then(r => r.json())
    .then(async (data) => {
        if (!data.success) {
            showToast('error', data.message || 'Error closing shift');
            return;
        }

        bootstrap.Modal.getInstance(document.getElementById('shiftCloseModal'))?.hide();

        const printUrl = data.print_url || (data.register_id ? `/pos/register/${data.register_id}/print` : null);
        const dayEnd = data.day_end;

        // Past cutoff + last shift: ask before ending the day
        if (dayEnd && dayEnd.ask) {
            const ask = await Swal.fire({
                title: 'End the day?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">${dayEnd.message || 'Past the day-end cutoff. End the day now?'}</p>
                        <div class="d-flex justify-content-between small text-muted"><span>Cutoff</span><strong>${dayEnd.cutoff || ''}</strong></div>
                        <div class="d-flex justify-content-between small text-muted"><span>Business date</span><strong>${dayEnd.business_date || ''}</strong></div>
                        <div class="d-flex justify-content-between small text-muted"><span>Closed shifts</span><strong>${dayEnd.shifts_count || 0}</strong></div>
                        <div class="d-flex justify-content-between small text-muted"><span>Day sales so far</span><strong>LKR ${Number(dayEnd.preview_sales || 0).toFixed(2)}</strong></div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, end day',
                cancelButtonText: 'No, shift only',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#94a3b8',
                allowOutsideClick: false,
            });

            if (ask.isConfirmed) {
                try {
                    const r2 = await fetch('/pos/register/day-end', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            date: dayEnd.business_date,
                            closing_cash: dayEnd.closing_cash != null ? dayEnd.closing_cash : balance,
                        }),
                    });
                    const dayData = await r2.json();
                    if (dayData.success) {
                        showShiftClosedWithDayEnd(data, {
                            triggered: true,
                            cutoff: dayEnd.cutoff,
                            print_url: dayData.print_url,
                            shifts_count: dayData.shifts_count,
                            shifts: dayData.shifts || [],
                            summary: dayData.summary || {},
                        }, printUrl);
                        return;
                    }
                    showToast('error', dayData.message || 'Day end failed');
                } catch (e) {
                    showToast('error', 'Day end failed');
                }
            }

            showShiftClosedWithDayEnd(data, null, printUrl);
            return;
        }

        showShiftClosedWithDayEnd(data, dayEnd, printUrl);
    })
    .catch(err => {
        console.error('Close register error:', err);
        showToast('error', 'Error closing shift. Please try again.');
    });
}

function showShiftClosedWithDayEnd(data, dayEnd, printUrl) {
    const diff = data.summary.difference;
    const diffText = diff >= 0 ? `+LKR ${diff.toFixed(2)}` : `-LKR ${Math.abs(diff).toFixed(2)}`;
    let dayHtml = '';
    if (dayEnd && dayEnd.triggered) {
        const shiftsRows = (dayEnd.shifts || []).map(s =>
            `<div class="d-flex justify-content-between small"><span>${s.cashier || 'Shift #'+s.id} (${s.opened_at||''}-${s.closed_at||''})</span><strong>LKR ${Number(s.total_sales||0).toFixed(2)}</strong></div>`
        ).join('');
        dayHtml = `
            <hr class="my-2">
            <div class="fw-bold mb-1">Day End (after ${dayEnd.cutoff || '22:00'})</div>
            <div class="d-flex justify-content-between mb-1"><span>Shifts</span><strong>${dayEnd.shifts_count}</strong></div>
            <div class="d-flex justify-content-between mb-1"><span>Day sales</span><strong>LKR ${Number(dayEnd.summary?.total_sales||0).toFixed(2)}</strong></div>
            ${shiftsRows}
        `;
    } else if (dayEnd && dayEnd.pending) {
        dayHtml = `<hr class="my-2"><div class="text-muted small">${dayEnd.message || ''}</div>`;
    }

    Swal.fire({
        title: shiftLabels.session + ' Closed!',
        html: `
            <div class="text-start">
                <div class="d-flex justify-content-between mb-2"><span>Total Sales</span><strong>LKR ${data.summary.total_sales.toFixed(2)}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Orders</span><strong>${data.summary.orders_count}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Opening cash</span><strong>LKR ${data.summary.opening_balance.toFixed(2)}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Closing cash</span><strong>LKR ${data.summary.closing_balance.toFixed(2)}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Difference</span><strong class="${diff >= 0 ? 'text-success' : 'text-danger'}">${diffText}</strong></div>
                ${dayHtml}
            </div>
        `,
        icon: 'success',
        showCancelButton: !!(printUrl || dayEnd?.print_url),
        confirmButtonText: dayEnd?.triggered ? 'Print Day End 80mm' : (printUrl ? 'Print Shift 80mm' : 'OK'),
        cancelButtonText: dayEnd?.triggered && printUrl ? 'Print Shift Only' : 'Skip',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#94a3b8',
        showDenyButton: !!(dayEnd?.triggered && printUrl && dayEnd.print_url),
        denyButtonText: 'Print Both',
        denyButtonColor: '#0f172a',
        allowOutsideClick: false,
    }).then((result) => {
        const dayUrl = dayEnd?.print_url;
        const previewEl = document.getElementById('printPreviewModal');
        const logoutAfterPrint = () => {
            previewEl?.removeEventListener('hidden.bs.modal', logoutAfterPrint);
            logoutAfterShiftClose();
        };
        if (result.isDenied && printUrl && dayUrl) {
            openPrintPreview(printUrl);
            setTimeout(() => openPrintPreview(dayUrl), 600);
            previewEl?.addEventListener('hidden.bs.modal', logoutAfterPrint);
        } else if (result.isConfirmed) {
            const url = (dayEnd?.triggered && dayUrl) ? dayUrl : printUrl;
            if (url) {
                previewEl?.addEventListener('hidden.bs.modal', logoutAfterPrint);
                openPrintPreview(url);
            } else {
                logoutAfterShiftClose();
            }
        } else if (result.dismiss === Swal.DismissReason.cancel && dayEnd?.triggered && printUrl) {
            previewEl?.addEventListener('hidden.bs.modal', logoutAfterPrint);
            openPrintPreview(printUrl);
        } else {
            logoutAfterShiftClose();
        }
    });
}

function logoutAfterShiftClose() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route('logout') }}';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token || '';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

function showCashDrawer() {
    new bootstrap.Modal(document.getElementById('cashDrawerModal')).show();
}

function openCashDrawer() {
    fetch('/pos/register/open-drawer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(async ({ ok, data }) => {
        if (ok && data.success && data.needs_local_bridge === false) {
            showToast('success', data.message || 'Cash drawer opened!');
            return;
        }
        // USB / Print Bridge path
        try {
            if (typeof localPrintBridgeHealthy === 'function' && await localPrintBridgeHealthy()) {
                const bridgeUrl = receiptBridgeBase();
                const printerName = data.printer_name || receiptPrinterName || 'XP-80C';
                let r = await fetch(bridgeUrl + '/drawer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        printer_name: printerName,
                        mode: 'windows',
                        job_type: 'drawer',
                        allow_windows_fallback: false,
                        data: data.payload_base64 || null,
                    }),
                });
                if (r.status === 404) {
                    r = await fetch(bridgeUrl + '/print', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            printer_name: printerName,
                            mode: 'windows',
                            job_type: 'drawer',
                            allow_windows_fallback: false,
                            data: data.payload_base64 || null,
                        }),
                    });
                }
                const bridge = await r.json().catch(() => ({}));
                if (r.ok && bridge.success) {
                    showToast('success', bridge.message || 'Cash drawer opened!');
                    return;
                }
            }
        } catch (e) { /* fall through */ }

        if (ok && data.success) {
            showToast('success', data.message || 'Cash drawer opened!');
        } else {
            showToast('error', data.message || 'Could not open cash drawer — start Print Bridge on this PC');
        }
    })
    .catch(() => showToast('error', 'Network error opening cash drawer'));
}

function showCashMovementModal(type) {
    const title = type === 'in' ? 'Cash IN' : 'Cash OUT';
    const confirmColor = type === 'in' ? '#10b981' : '#dc2626';
    Swal.fire({
        title: title,
        html: `
            <div class="text-start mb-3">
                <label class="form-label fw-semibold">Amount</label>
                <input type="number" id="cashMovementAmount" class="form-control form-control-lg" placeholder="0.00" step="0.01" min="0" autofocus>
            </div>
            <div class="text-start">
                <label class="form-label fw-semibold">Reason</label>
                <input type="text" id="cashMovementReason" class="form-control" placeholder="Enter reason...">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        allowOutsideClick: false,
        didOpen: () => {
            document.getElementById('cashMovementAmount').focus();
        },
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('cashMovementAmount').value) || 0;
            const reason = document.getElementById('cashMovementReason').value.trim();
            if (amount <= 0) {
                Swal.showValidationMessage('Please enter a valid amount');
                return false;
            }
            if (!reason) {
                Swal.showValidationMessage('Please enter a reason');
                return false;
            }
            return { amount: amount, reason: reason };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/pos/register/cash-movement', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ type: type, amount: result.value.amount, reason: result.value.reason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('success', type === 'in' ? 'Cash IN recorded' : 'Cash OUT recorded');
                } else {
                    showToast('error', data.message || 'Error');
                }
            })
            .catch(() => showToast('error', 'Network error'));
        }
    });
}

function showCashInModal() {
    showCashMovementModal('in');
}

function showCashOutModal() {
    showCashMovementModal('out');
}

function updateRegisterDisplay() {
    if (currentRegister) {
        // Could add a register status indicator to the UI
        console.log('Register #' + currentRegister.id + ' is open');
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    changeOrderType(isBakeryUi ? 'takeaway' : 'dine_in');
    syncBakeryPrintToggles();
    checkRegisterStatus();
    refreshPayBillsBadge();
    if (isIceCreamUi) {
        const firstCat = document.querySelector('.ice-cat-list .category-btn.active, .ice-cat-list .category-btn');
        if (firstCat) {
            filterCategory(firstCat.getAttribute('data-cat-id'), firstCat);
        }
    } else if (!isBakeryUi) {
        const firstCat = document.querySelector('.restaurant-cat-list .category-btn.active, .restaurant-cat-list .category-btn');
        if (firstCat) {
            filterCategory(firstCat.getAttribute('data-cat-id'), firstCat);
        }
    }
    refreshOpenBillsBadge();
    smartPosInterval(refreshPayBillsBadge, 15000);
    smartPosInterval(refreshOpenBillsBadge, 15000);
    pollPendingKotPrints();
    smartPosInterval(pollPendingKotPrints, 8000);
    startWaiterOrderAlertPolling();
    if (isBakeryUi) {
        focusProductSearch(true);
    }
});

// Fail-safe: start waiter alerts even if the main DOMContentLoaded init throws earlier
try { startWaiterOrderAlertPolling(); } catch (_) {}
</script>

@php
    $posUiMode = $settings['pos_ui_mode'] ?? 'restaurant';
    $isIceCreamUi = $posUiMode === 'ice_cream';
    $isBakeryUi = $posUiMode === 'bakery' || $isIceCreamUi;
@endphp
@if($isBakeryUi)
<div class="bakery-workspace">
<header class="bakery-header" aria-label="Bakery controls">
    <div class="bakery-header-row bakery-header-tools">
        <div class="bakery-print-actions">
            <button type="button" class="bakery-tool-btn" onclick="reprintLastInvoice()" title="Reprint last receipt">
                <i class="fas fa-receipt"></i><span>Last bill</span>
            </button>
            <button type="button" class="bakery-tool-btn" onclick="printBill()" title="Print current bill">
                <i class="fas fa-print"></i><span>Print</span>
            </button>
            @unless(!empty($settings['bakery_disable_kot']) || !empty($settings['bakery_direct_billing']))
            <button type="button" class="bakery-tool-btn" onclick="reprintLastKOT()" title="Reprint last KOT">
                <i class="fas fa-utensils"></i><span>KOT</span>
            </button>
            @endunless
            @if(!empty($settings['bakery_show_orders_display']))
            <button type="button" class="bakery-tool-btn" onclick="showRecentOrders()" title="Recent sales">
                <i class="fas fa-history"></i><span>Recent</span>
            </button>
            @endif
            <button type="button" class="bakery-tool-btn" data-shift-label="report" onclick="showShiftReport()" title="Shift report">
                <i class="fas fa-chart-pie"></i><span>Shift</span>
            </button>
            @unless(!empty($settings['bakery_direct_billing']))
            <button type="button" class="bakery-tool-btn" onclick="openBillTableModal()" title="Open bills">
                <i class="fas fa-file-invoice"></i><span>Bills</span>
            </button>
            @endunless
        </div>
        <div class="bakery-print-toggles" title="Session print preferences">
            <button type="button" class="bakery-toggle" data-print-toggle="ask" onclick="togglePosPrintFlag('ask')">
                <i class="fas fa-eye"></i> Ask
            </button>
            <button type="button" class="bakery-toggle" data-print-toggle="receipt" onclick="togglePosPrintFlag('receipt')">
                <i class="fas fa-receipt"></i> Auto bill
            </button>
            @unless(!empty($settings['bakery_disable_kot']) || !empty($settings['bakery_direct_billing']))
            <button type="button" class="bakery-toggle" data-print-toggle="kot" onclick="togglePosPrintFlag('kot')">
                <i class="fas fa-fire"></i> Auto KOT
            </button>
            @endunless
        </div>
        <div class="bakery-last-sale" id="lastSalePanel" onclick="reprintLastInvoice()" role="button" title="Reprint last invoice">
            <span class="bls-label">Last</span>
            <strong id="lastSaleInvoice">{{ $lastSale->order_number ?? '—' }}</strong>
            <span id="lastSaleAmount">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->total_amount ?? 0), 2) }}</span>
            <span class="bls-change">Chg <strong id="lastSaleChange">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->change_amount ?? 0), 2) }}</strong></span>
            <span class="d-none" id="lastSalePaid">{{ $settings['currency_symbol'] }} {{ number_format((float) ($lastSale->paid_amount ?? 0), 2) }}</span>
        </div>
    </div>
    <div class="bakery-header-row bakery-header-billing">
        <div class="d-flex gap-2 bakery-order-types" id="orderTypeGroup">
            @if(!empty($settings['bakery_show_dine_in']) && empty($settings['bakery_direct_billing']))
            <button type="button" class="order-type-btn dine-in" data-type="dine_in" onclick="changeOrderType('dine_in')">
                <i class="fas fa-utensils"></i><span>Dine-in</span>
            </button>
            @endif
            <button type="button" class="order-type-btn takeaway active" data-type="takeaway" onclick="changeOrderType('takeaway')">
                <i class="fas fa-store"></i><span>Counter</span>
            </button>
            @if(!empty($settings['bakery_show_delivery']))
            <button type="button" class="order-type-btn delivery" data-type="delivery" onclick="changeOrderType('delivery')">
                <i class="fas fa-motorcycle"></i><span>Delivery</span>
            </button>
            @endif
            @if(!empty($settings['bakery_show_express']))
            <button type="button" class="order-type-btn express" data-type="express" onclick="changeOrderType('express')">
                <i class="fas fa-bolt"></i><span>Express</span>
            </button>
            @endif
        </div>
        <div id="customerSelectContainer" class="customer-section bakery-customer-slot d-none">
            <div id="deliverySummaryCard" class="delivery-summary bakery-delivery-summary">
                <div class="ds-empty" id="deliverySummaryEmpty">
                    <div class="ds-empty-copy">
                        <strong id="customerSectionTitle">Counter customer</strong>
                        <span id="customerSectionHint">Optional</span>
                    </div>
                    <button type="button" class="btn ds-add-btn" onclick="openDeliveryDetailsModal()">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
                <div class="ds-filled d-none" id="deliverySummaryFilled">
                    <div class="ds-main">
                        <div class="ds-icon"><i class="fas fa-store" id="deliverySummaryIcon"></i></div>
                        <div class="ds-text min-w-0">
                            <div class="ds-name text-truncate" id="deliverySummaryCustomer">—</div>
                            <div class="ds-meta text-truncate" id="deliverySummaryMeta">—</div>
                        </div>
                        <span class="ds-pay-badge" id="deliverySummaryPay">Pay now</span>
                    </div>
                    <button type="button" class="btn ds-edit-btn" onclick="openDeliveryDetailsModal()" title="Edit details">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
            </div>
        </div>
        @if(\App\Services\LoyaltyService::enabled())
        <button type="button" class="bakery-loyalty-chip" onclick="openLoyaltyModal()" title="Loyalty stamps">
            <i class="fas fa-stamp"></i><span>Loyalty</span>
        </button>
        <div id="loyaltyPosPanel" class="d-none" aria-hidden="true"></div>
        @endif
        <div class="bakery-search-wrap pos-search-wrap">
            <input type="text" id="productSearch" class="search-input" placeholder="Search name or scan barcode…" oninput="onProductSearchInput()" onkeydown="onProductSearchKeydown(event)" autocomplete="off" autofocus>
        </div>
    </div>
</header>
@endif
<div class="pos-shell {{ $isBakeryUi ? ($isIceCreamUi ? 'pos-shell--bakery pos-shell--ice-cream' : 'pos-shell--bakery') : 'pos-shell--restaurant' }}">
    {{-- Category rail (restaurant + bakery + ice cream) --}}
    @if($isBakeryUi)
    <aside class="bakery-cat-rail {{ $isIceCreamUi ? 'ice-cat-rail' : '' }}" aria-label="Categories">
        <div class="bakery-cat-title">{{ $isIceCreamUi ? 'Flavours' : 'Categories' }}</div>
        <div class="category-tabs bakery-cat-list {{ $isIceCreamUi ? 'ice-cat-list' : '' }}" id="categoryTabs"
             @if($isIceCreamUi) data-count="{{ $categories->count() }}" style="--cat-count: {{ max(1, $categories->count()) }}" @endif>
            @unless($isIceCreamUi)
            <button type="button" class="category-btn active" data-cat-id="all" onclick="filterCategory('all', this)">
                <span>All</span>
                <small>{{ $categories->sum(fn ($c) => $c->products->count()) }}</small>
            </button>
            @endunless
            @foreach($categories as $category)
            <button type="button"
                    class="category-btn {{ $isIceCreamUi && $loop->first ? 'active' : '' }}"
                    data-cat-id="{{ $category->id }}"
                    onclick="filterCategory('{{ $category->id }}', this)">
                @if($category->hasImageFile())
                    <img src="{{ $category->imageUrl() }}" alt="" class="bakery-cat-img" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('d-none');">
                    @if($isIceCreamUi)
                        <span class="bakery-cat-placeholder d-none" aria-hidden="true"><i class="fas fa-ice-cream"></i></span>
                    @endif
                @elseif($isIceCreamUi)
                    <span class="bakery-cat-placeholder" aria-hidden="true"><i class="fas fa-ice-cream"></i></span>
                @endif
                <span class="bakery-cat-text">
                    <span class="{{ $isIceCreamUi ? 'bakery-cat-name' : '' }}">{{ $category->name }}</span>
                    <small class="{{ $isIceCreamUi ? 'bakery-cat-count' : '' }}">{{ $category->products->count() }}</small>
                </span>
            </button>
            @endforeach
        </div>
    </aside>
    @else
    <aside class="restaurant-cat-rail" aria-label="Categories">
        <div class="restaurant-cat-title">Menu</div>
        <div class="category-tabs restaurant-cat-list restaurant-cat-grid" id="categoryTabs" data-count="{{ $categories->count() }}">
            @foreach($categories as $category)
            <button type="button"
                    class="category-btn {{ $loop->first ? 'active' : '' }}"
                    data-cat-id="{{ $category->id }}"
                    onclick="filterCategory('{{ $category->id }}', this)">
                @if($category->hasImageFile())
                    <img src="{{ $category->imageUrl() }}" alt="" class="restaurant-cat-img" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('d-none');">
                    <span class="restaurant-cat-placeholder d-none" aria-hidden="true"><i class="fas fa-utensils"></i></span>
                @else
                    <span class="restaurant-cat-placeholder" aria-hidden="true"><i class="fas fa-utensils"></i></span>
                @endif
                <span class="restaurant-cat-text">
                    <span class="restaurant-cat-name">{{ $category->name }}</span>
                    <small>{{ $category->products->count() }}</small>
                </span>
            </button>
            @endforeach
        </div>
    </aside>
    @endif

    <!-- Catalog / products -->
    <div class="pos-catalog">
        <div class="pos-catalog-inner h-100">
            <div class="pos-catalog-main">
                @unless($isBakeryUi)
                <!-- Order Type Selection -->
                <div class="d-flex gap-2 mb-3" id="orderTypeGroup">
                    <button type="button" class="order-type-btn active dine-in" data-type="dine_in" onclick="changeOrderType('dine_in')">
                        <i class="fas fa-utensils"></i><span>Dine-in</span>
                    </button>
                    <button type="button" class="order-type-btn takeaway" data-type="takeaway" onclick="changeOrderType('takeaway')">
                        <i class="fas fa-shopping-bag"></i><span>Takeaway</span>
                    </button>
                    <button type="button" class="order-type-btn delivery" data-type="delivery" onclick="changeOrderType('delivery')">
                        <i class="fas fa-motorcycle"></i><span>Delivery</span>
                    </button>
                    <button type="button" class="order-type-btn express" data-type="express" onclick="changeOrderType('express')">
                        <i class="fas fa-bolt"></i><span>Express</span>
                    </button>
                </div>

                <!-- Guest / delivery details (compact; edit in popup) -->
                <div id="customerSelectContainer" class="mb-3 d-none customer-section">
                    <div id="deliverySummaryCard" class="delivery-summary">
                        <div class="ds-empty" id="deliverySummaryEmpty">
                            <div class="ds-empty-copy">
                                <strong id="customerSectionTitle">Delivery details</strong>
                                <span id="customerSectionHint">Customer · address · partner · pay</span>
                            </div>
                            <button type="button" class="btn ds-add-btn" onclick="openDeliveryDetailsModal()">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                        <div class="ds-filled d-none" id="deliverySummaryFilled">
                            <div class="ds-main">
                                <div class="ds-icon"><i class="fas fa-motorcycle" id="deliverySummaryIcon"></i></div>
                                <div class="ds-text min-w-0">
                                    <div class="ds-name text-truncate" id="deliverySummaryCustomer">—</div>
                                    <div class="ds-meta text-truncate" id="deliverySummaryMeta">—</div>
                                </div>
                                <span class="ds-pay-badge" id="deliverySummaryPay">Pay now</span>
                            </div>
                            <button type="button" class="btn ds-edit-btn" onclick="openDeliveryDetailsModal()" title="Edit details">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-3 pos-search-wrap">
                    <input type="text" id="productSearch" class="search-input" placeholder="Search name or scan barcode…" oninput="onProductSearchInput()" onkeydown="onProductSearchKeydown(event)" autocomplete="off" autofocus>
                </div>

                @if(\App\Services\LoyaltyService::enabled())
                <div class="loyalty-pos-bar mb-3" role="button" tabindex="0" onclick="openLoyaltyModal()" onkeydown="if(event.key==='Enter')openLoyaltyModal()">
                    <div class="loy-head">
                        <span><i class="fas fa-stamp me-1"></i>Loyalty stamps</span>
                        <button type="button" class="btn btn-sm btn-warning" onclick="event.stopPropagation();openLoyaltyModal()"><i class="fas fa-search me-1"></i>Search / Scan</button>
                    </div>
                    <div id="loyaltyPosPanel" class="loy-body">
                        <div class="loy-empty">Pick customer in Delivery details, or tap to search / scan QR</div>
                    </div>
                </div>
                @endif

                @endunless

                <!-- Products Grid -->
                <div id="subcategoryChips" class="subcategory-chips mb-2 d-none" role="group" aria-label="Subcategories"></div>
                <div class="product-grid {{ !empty($isIceCreamUi) ? 'ice-products-4x4' : '' }}"
                     id="productsGrid"
                     @if(!empty($isIceCreamUi))
                     style="display:grid !important;grid-template-columns:repeat(4,minmax(0,1fr)) !important;grid-template-rows:none !important;grid-auto-rows:calc((100% - 24px) / 4) !important;gap:8px !important;height:100% !important;align-content:start !important;"
                     @endif>
                    {{-- CUSTOM ITEM button — always first in grid --}}
                    <div class="product-item custom-item-grid-btn" id="customItemGridBtn" data-category="custom" data-subcategory="" data-id="0" data-name="" data-display-name="" data-code="" data-barcode="" data-price="0" data-partner-prices='{}' data-has-variants="0" data-has-addons="0" style="display:block !important;">
                        <div class="product-card custom-item-card" onclick="openCustomItemModal()" title="Add a custom item not in the product list">
                            <div class="custom-item-icon"><i class="fas fa-keyboard"></i></div>
                            <div class="product-info">
                                <div class="product-name" style="text-align:center;font-weight:700;letter-spacing:0.5px;">CUSTOM ITEM</div>
                                <span class="product-price" style="color:#f59e0b;">LKR 0</span>
                            </div>
                        </div>
                    </div>
                    <script>window.__POS_PRODUCT_EXTRAS = window.__POS_PRODUCT_EXTRAS || {};</script>

                    @foreach($categories as $category)
                        @foreach($category->products as $product)
                        @php
                            $hasVariants = $product->variants->isNotEmpty() || $product->has_variants;
                            // Flags only — full modifiers/options load on click via /pos/products?id=
                            $hasAddons = (int) ($product->pos_addons_count ?? 0) > 0
                                || (int) ($product->pos_shared_addons_count ?? 0) > 0
                                || (int) ($product->pos_addon_groups_count ?? 0) > 0
                                || (int) ($product->pos_option_sets_count ?? 0) > 0
                                || (bool) $product->has_addons;
                        @endphp
                        <div class="product-item"
                             data-category="{{ $category->id }}"
                             data-subcategory="{{ $product->subcategory_id ?? '' }}"
                             data-id="{{ $product->id }}"
                             data-name="{{ strtolower($product->name) }}"
                             data-display-name="{{ $product->name }}"
                             data-code="{{ strtolower($product->code ?? '') }}"
                             data-barcode="{{ strtolower($product->barcode ?? '') }}"
                             data-price="{{ $product->final_price }}"
                             data-partner-prices='@json($product->partnerPriceMap())'
                             data-has-variants="{{ $hasVariants ? '1' : '0' }}"
                             data-has-addons="{{ $hasAddons ? '1' : '0' }}">
                            <div class="product-card" onclick='handleProductClick({{ $product->id }}, @json($product->name), {{ $product->final_price }}, {{ $hasVariants ? "true" : "false" }}, {{ $hasAddons ? "true" : "false" }})'>
                                <img src="{{ $product->imageUrl() }}{{ !empty($isIceCreamUi) ? '?v=2' : '' }}" class="product-img" alt="{{ $product->name }}"
                                     onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                                <div class="product-info">
                                    <div class="product-name">{{ $product->name }}</div>
                                    <span class="product-price" data-base-label="1">LKR {{ number_format($product->final_price, 0) }}</span>
                                    @if($hasVariants || $hasAddons)
                                    <div class="product-meta">
                                        @if($hasVariants)<i class="fas fa-layer-group"></i>@endif
                                        @if($hasAddons)<i class="fas fa-plus-circle ms-1"></i>@endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                </div>

                <!-- Waiter alerts BELOW product grid -->
                <div id="waiterAlertTray" aria-live="assertive">
                    <div id="waiterOrderAlertStack"></div>
                </div>

                <!-- Mobile Cart Toggle -->
                <button class="mobile-cart-toggle" onclick="openMobileCart()">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="fw-bold" id="mobileCartCount">0 items</span>
                        <span class="ms-auto fw-bold" id="mobileCartTotal">LKR 0.00</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Right Panel - Cart (desktop only) -->
    <div class="pos-cart-col d-none d-lg-block">
        <div class="cart-card">
            <div class="cart-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Cart <span id="cartCount" class="badge bg-white text-primary ms-2">0</span></h5>
                <div class="d-flex gap-1 cart-actions">
                    @unless($isBakeryUi)
                    <button class="btn cart-action-btn cart-action-reprint" onclick="reprintLastInvoice()" title="Reprint last invoice"><i class="fas fa-receipt"></i></button>
                    @endunless
                    <button class="btn cart-action-btn cart-action-hold" onclick="holdOrder()" title="Hold"><i class="fas fa-pause"></i></button>
                    <button class="btn cart-action-btn cart-action-hold-new" onclick="holdAndNew()" title="Hold & New"><i class="fas fa-pause"></i><i class="fas fa-plus cart-action-plus"></i></button>
                    <button class="btn cart-action-btn cart-action-recall" onclick="showHeldOrders()" title="Recall"><i class="fas fa-play"></i></button>
                    <button class="btn cart-action-btn cart-action-clear" onclick="clearCart()" title="Clear"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="open-bill-banner d-none px-3 py-2" style="background:#fff7ed;border-bottom:1px solid #fed7aa;color:#9a3412;font-size:0.85rem;font-weight:600;">
                <i class="fas fa-file-invoice me-1"></i><span class="open-bill-label">Editing bill</span>
                <button type="button" class="btn btn-sm btn-link p-0 ms-2" style="color:#c2410c;font-size:0.8rem;" onclick="exitOpenBill(true)">Close</button>
            </div>
                            <div id="desktopMetaSlot" class="cart-meta-slot px-3 pt-3 pb-2 {{ !empty($settings['bakery_direct_billing']) ? 'd-none' : '' }}" style="border-bottom:1px solid #f1f5f9;">
                <div class="cart-meta-row">
                    <div id="tableSelectContainer" class="cart-meta-item cart-meta-table mb-0">
                        <select id="tableSelect" class="d-none" tabindex="-1">
                            <option value=""></option>
                            @foreach($floors as $floor)
                                <optgroup label="{{ $floor->name }}">
                                    @foreach($floor->tables as $table)
                                    <option value="{{ $table->id }}">{{ $table->name }} ({{ $table->capacity }}p)</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button type="button" class="select-table-btn" onclick="openTableModal()">
                            <span class="stb-left"><i class="fas fa-chair"></i> <span id="selectedTableLabel">Select Table</span></span>
                            <i class="fas fa-chevron-right stb-arrow"></i>
                        </button>
                    </div>
                    <button type="button" id="transferTableBtn" class="transfer-table-btn cart-meta-item cart-meta-transfer d-none" onclick="openTransferTableModal()">
                        <i class="fas fa-exchange-alt me-1"></i><span class="transfer-table-label">Transfer Table</span>
                    </button>
                    <div id="waiterSelectContainer" class="cart-meta-item cart-meta-waiter mb-0">
                        <label for="waiterSelect" class="form-label small fw-semibold text-muted mb-1">
                            <i class="fas fa-user-tie me-1"></i>Waiter <span class="fw-normal">(optional)</span>
                        </label>
                        <select id="waiterSelect" class="form-select form-select-modern" onchange="onWaiterSelectChange()">
                            <option value="">— Assign later —</option>
                            @foreach($waiters as $waiter)
                            <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="takeawayCustomerContainer" class="cart-meta-takeaway d-none">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fas fa-user me-1"></i>Customer <span class="fw-normal">(optional)</span>
                    </label>
                    <div class="cart-takeaway-fields">
                        <input type="text" id="takeawayCustomerName" class="form-control form-select-modern" placeholder="Customer name" autocomplete="off" maxlength="255">
                        <input type="tel" id="takeawayCustomerPhone" class="form-control form-select-modern" placeholder="Phone number" autocomplete="off" maxlength="30" inputmode="tel">
                    </div>
                </div>
            </div>
            <div class="cart-body" id="cartBodyContainer">
                <div id="emptyCart" class="text-center py-3 text-muted">
                    <i class="fas {{ $isIceCreamUi ? 'fa-ice-cream' : ($isBakeryUi ? 'fa-bread-slice' : 'fa-utensils') }} fa-2x mb-2 opacity-25"></i>
                    <p class="mb-0 small">Tap a product to add</p>
                </div>
            </div>
            <div class="cart-footer">
                <div class="cart-summary-block">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal:</span><strong id="subtotal">LKR 0.00</strong></div>
                <div class="d-flex justify-content-between mb-2 d-none" id="discountRow"><span class="text-muted" id="discountLabel">Discount:</span><strong class="text-danger" id="discountAmount">-LKR 0.00</strong></div>
                <div class="d-flex justify-content-between mb-2 {{ $settings['tax_enabled'] ? '' : 'd-none' }}" id="taxRow"><span class="text-muted">{{ $settings['tax_name'] ?? 'Tax' }} ({{ $settings['tax_rate'] }}%):</span><strong id="taxAmount">LKR 0.00</strong></div>
                @if($settings['service_charge_enabled'])
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Service ({{ $settings['service_charge_rate'] }}%):</span><strong id="serviceCharge">LKR 0.00</strong></div>
                @endif
                <div class="d-flex justify-content-between cart-total-row pt-2 border-top"><span class="fs-5 fw-bold">Total:</span><strong class="total-display" id="totalAmount">LKR 0.00</strong></div>
                </div>

                @if($isBakeryUi && empty($isIceCreamUi))
                <div class="bakery-pay-methods mb-2">
                    <div class="bakery-pay-label">Quick pay</div>
                    <div class="bakery-pay-grid bakery-pay-grid--4">
                        <button type="button" class="bakery-pay-btn cash" onclick="bakeryQuickPay('cash_exact')"><i class="fas fa-money-bill-wave"></i>Cash</button>
                        <button type="button" class="bakery-pay-btn card" onclick="bakeryQuickPay('card')"><i class="fas fa-credit-card"></i>Card</button>
                        <button type="button" class="bakery-pay-btn credit" onclick="bakeryQuickPay('credit')"><i class="fas fa-hand-holding-usd"></i>Credit</button>
                        <button type="button" class="bakery-pay-btn bank" onclick="bakeryQuickPay('bank_transfer')"><i class="fas fa-university"></i>Bank</button>
                    </div>
                </div>
                @endif

                @unless(!empty($isIceCreamUi))
                <div class="d-flex gap-2 mb-2 cart-utility-row">
                    <button class="btn btn-secondary flex-fill" onclick="applyBillDiscount()">Discount</button>
                    <button class="btn btn-secondary flex-fill" onclick="showNotes()">Notes</button>
                </div>

                <button type="button" class="btn btn-secondary w-100 mb-2 pos-numpad-toggle" onclick="togglePosNumpad()" title="Show or hide the on-screen numpad">
                    <i class="fas fa-keyboard me-1 pos-numpad-toggle-icon"></i><span class="pos-numpad-toggle-label">Hide Numpad</span>
                </button>

                <div class="pos-cart-keypad-host {{ empty($settings['show_screen_numbers_keyboard']) ? 'd-none' : '' }}">
                <div class="keypad mb-2 pos-cart-keypad {{ $isBakeryUi ? 'bakery-keypad-compact' : 'rest-cart-keypad' }}">
                    <button type="button" class="touch-btn" onclick="keypadInput('1')">1</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('2')">2</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('3')">3</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('4')">4</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('5')">5</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('6')">6</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('7')">7</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('8')">8</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('9')">9</button>
                    <button type="button" class="touch-btn warning" onclick="keypadInput('C')">C</button>
                    <button type="button" class="touch-btn" onclick="keypadInput('0')">0</button>
                    <button type="button" class="touch-btn warning" onclick="keypadInput('DEL')" title="Backspace"><i class="fas fa-backspace"></i></button>
                </div>
                </div>
                @endunless

                @if(!empty($isIceCreamUi))
                <div class="d-flex flex-column gap-2 ice-checkout-actions">
                    @unless(!empty($settings['bakery_direct_billing']))
                    <button id="placeOrderBtn" class="btn btn-success w-100 pos-btn place-order-btn d-none" onclick="placeDineInOrder()">
                        <i class="fas fa-utensils me-2"></i>Place Order
                    </button>
                    @endunless
                    <button class="btn pay-btn w-100 pos-btn ice-pay-now-btn" onclick="openPaymentModal()">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </button>
                </div>
                @else
                <div class="d-flex gap-2 mb-2 cart-pay-row">
                    @unless(!empty($settings['bakery_direct_billing']))
                    <button id="placeOrderBtn" class="btn btn-success flex-fill pos-btn place-order-btn d-none" onclick="placeDineInOrder()">
                        <i class="fas fa-utensils me-2"></i>Place Order
                    </button>
                    @endunless
                    <button class="btn pay-btn flex-fill pos-btn" onclick="openPaymentModal()">
                        <i class="fas fa-credit-card me-2"></i>Pay Now
                    </button>
                </div>
                @if($isBakeryUi)
                <div class="d-flex gap-2 bakery-secondary-actions">
                    @unless(!empty($settings['bakery_direct_billing']))
                    <button class="btn btn-warning flex-fill pos-btn open-bills-btn" onclick="openBillTableModal()">
                        <i class="fas fa-receipt me-2"></i>Open Bills
                    </button>
                    @endunless
                    <button class="btn btn-secondary flex-fill pos-btn" onclick="printBill()">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
                @elseif(empty($settings['bakery_direct_billing']))
                <div class="d-flex gap-2">
                    <button class="btn btn-warning w-100 pos-btn open-bills-btn" onclick="openBillTableModal()">
                        <i class="fas fa-receipt me-2"></i>Open Bills
                    </button>
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
@if($isBakeryUi)
</div>{{-- bakery-workspace --}}
@endif

    <!-- Print Preview Modal (above other POS modals) -->
    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1080;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 360px;">
            <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i>Print Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="background: #f8fafc;">
                    <iframe id="printPreviewFrame" title="Receipt print preview" src="" style="width: 100%; height: 520px; border: none; display: block;"></iframe>
                </div>
                <div class="modal-footer" style="background: #fff; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPreviewFrame()" style="background: #f59e0b; border: none;">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Mobile Cart Drawer -->
<div class="mobile-cart-backdrop" id="mobileCartBackdrop" onclick="closeMobileCart()"></div>
<div class="mobile-cart-drawer" id="mobileCartDrawer">
    <div class="drawer-handle" onclick="closeMobileCart()"></div>
    <div class="drawer-header">
        <h5 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Cart</h5>
        <div class="d-flex gap-1 cart-actions">
            @unless($isBakeryUi)
            <button class="btn cart-action-btn cart-action-reprint" onclick="reprintLastInvoice()" title="Reprint last invoice"><i class="fas fa-receipt"></i></button>
            @endunless
            <button class="btn cart-action-btn cart-action-hold" onclick="holdOrder()" title="Hold"><i class="fas fa-pause"></i></button>
            <button class="btn cart-action-btn cart-action-hold-new" onclick="holdAndNew()" title="Hold & New"><i class="fas fa-pause"></i><i class="fas fa-plus cart-action-plus"></i></button>
            <button class="btn cart-action-btn cart-action-recall" onclick="showHeldOrders()" title="Recall"><i class="fas fa-play"></i></button>
            <button class="btn cart-action-btn cart-action-clear" onclick="clearCart()" title="Clear"><i class="fas fa-trash"></i></button>
            <button class="btn cart-action-btn cart-action-close" onclick="closeMobileCart()" title="Close"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="open-bill-banner d-none px-3 py-2" style="background:#fff7ed;border-bottom:1px solid #fed7aa;color:#9a3412;font-size:0.85rem;font-weight:600;">
        <i class="fas fa-file-invoice me-1"></i><span class="open-bill-label">Editing bill</span>
        <button type="button" class="btn btn-sm btn-link p-0 ms-2" style="color:#c2410c;font-size:0.8rem;" onclick="exitOpenBill(true)">Close</button>
    </div>
    <div id="mobileMetaSlot" class="cart-meta-slot px-3 pt-3 pb-2" style="border-bottom:1px solid #f1f5f9;"></div>
    <div class="drawer-body" id="mobileCartBody">
        <div class="text-center py-5 text-muted">
            <i class="fas fa-utensils fa-3x mb-3 opacity-25"></i>
            <p>Tap a product to add</p>
        </div>
    </div>
    <div class="drawer-footer">
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal:</span><strong id="mobileSubtotal">LKR 0.00</strong></div>
        <div class="d-flex justify-content-between mb-2 d-none" id="mobileDiscountRow"><span class="text-muted" id="mobileDiscountLabel">Discount:</span><strong class="text-danger" id="mobileDiscountAmount">-LKR 0.00</strong></div>
        <div class="d-flex justify-content-between mb-2 {{ $settings['tax_enabled'] ? '' : 'd-none' }}" id="mobileTaxRow"><span class="text-muted">{{ $settings['tax_name'] ?? 'Tax' }} ({{ $settings['tax_rate'] }}%):</span><strong id="mobileTaxAmount">LKR 0.00</strong></div>
        @if($settings['service_charge_enabled'])
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Service ({{ $settings['service_charge_rate'] }}%):</span><strong id="mobileServiceCharge">LKR 0.00</strong></div>
        @endif
        <div class="d-flex justify-content-between mb-3 pt-2 border-top"><span class="fs-5 fw-bold">Total:</span><strong class="total-display" id="mobileTotalAmount">LKR 0.00</strong></div>

        @unless(!empty($isIceCreamUi))
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-secondary flex-fill" onclick="applyBillDiscount()">Discount</button>
            <button class="btn btn-secondary flex-fill" onclick="showNotes()">Notes</button>
        </div>

        <button type="button" class="btn btn-secondary w-100 mb-3 pos-numpad-toggle" onclick="togglePosNumpad()" title="Show or hide the on-screen numpad">
            <i class="fas fa-keyboard me-1 pos-numpad-toggle-icon"></i><span class="pos-numpad-toggle-label">Hide Numpad</span>
        </button>

        <div class="pos-cart-keypad-host {{ empty($settings['show_screen_numbers_keyboard']) ? 'd-none' : '' }}">
        <div class="keypad mb-3 pos-cart-keypad">
            <button class="touch-btn" onclick="keypadInput('1')">1</button>
            <button class="touch-btn" onclick="keypadInput('2')">2</button>
            <button class="touch-btn" onclick="keypadInput('3')">3</button>
            <button class="touch-btn" onclick="keypadInput('4')">4</button>
            <button class="touch-btn" onclick="keypadInput('5')">5</button>
            <button class="touch-btn" onclick="keypadInput('6')">6</button>
            <button class="touch-btn" onclick="keypadInput('7')">7</button>
            <button class="touch-btn" onclick="keypadInput('8')">8</button>
            <button class="touch-btn" onclick="keypadInput('9')">9</button>
            <button class="touch-btn warning" onclick="keypadInput('C')">C</button>
            <button class="touch-btn" onclick="keypadInput('0')">0</button>
            <button class="touch-btn warning" onclick="keypadInput('DEL')"><i class="fas fa-backspace"></i></button>
        </div>
        </div>
        @endunless

        @if(!empty($isIceCreamUi))
        <div class="d-flex flex-column gap-2 ice-checkout-actions">
            @unless(!empty($settings['bakery_direct_billing']))
            <button class="btn btn-success w-100 pos-btn mobile-place-order-btn d-none" onclick="placeDineInOrder()">
                <i class="fas fa-utensils me-2"></i>Place Order
            </button>
            @endunless
            <button class="btn pay-btn w-100 pos-btn ice-pay-now-btn" onclick="openPaymentModal()">
                <i class="fas fa-credit-card me-2"></i>Pay Now
            </button>
        </div>
        @else
        <div class="d-flex gap-2 mb-2">
            @unless(!empty($settings['bakery_direct_billing']))
            <button class="btn btn-success flex-fill pos-btn mobile-place-order-btn d-none" onclick="placeDineInOrder()">
                <i class="fas fa-utensils me-2"></i>Place Order
            </button>
            @endunless
            <button class="btn pay-btn flex-fill pos-btn" onclick="openPaymentModal()">
                <i class="fas fa-credit-card me-2"></i>Pay Now
            </button>
        </div>
        @if($isBakeryUi)
        <div class="d-flex gap-2">
            @unless(!empty($settings['bakery_direct_billing']))
            <button class="btn btn-warning flex-fill pos-btn open-bills-btn" onclick="openBillTableModal()">
                <i class="fas fa-receipt me-2"></i>Open Bills
            </button>
            @endunless
            <button class="btn btn-secondary flex-fill pos-btn" onclick="printBill()">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
        @elseif(empty($settings['bakery_direct_billing']))
        <div class="d-flex gap-2">
            <button class="btn btn-warning w-100 pos-btn open-bills-btn" onclick="openBillTableModal()">
                <i class="fas fa-receipt me-2"></i>Open Bills
            </button>
        </div>
        @endif
        @endif
    </div>
</div>

<!-- Register Open Modal -->
<div class="modal fade" id="registerOpenModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 20px 20px 0 0; padding: 20px 24px;">
                <h5 class="modal-title text-white fw-bold" data-shift-label="open-title"><i class="fas fa-cash-register me-2"></i>Open Shift</h5>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-block p-3 rounded-3" style="background: #f0fdf4;">
                        <i class="fas fa-coins fa-3x" style="color: #10b981;"></i>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Opening cash (LKR)</label>
                    <div class="input-group input-group-lg" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text fw-bold" style="background: #f59e0b; color: #fff; border: none;">LKR</span>
                        <input type="number" id="openingBalance" class="form-control fw-bold" placeholder="0.00" step="0.01" min="0" style="border: 1px solid #e2e8f0; font-size: 1.5rem;">
                    </div>
                    <small class="text-muted" id="openingBalanceHint">Enter the cash amount in the drawer at start of shift</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Notes (Optional)</label>
                    <textarea id="registerNotes" class="form-control" rows="2" placeholder="Any notes..." style="border-radius: 12px; border: 1px solid #e2e8f0;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 20px 24px;">
                <a href="{{ route('dashboard') }}" class="btn btn-lg" style="background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 600;">Back to Dashboard</a>
                <button type="button" class="btn btn-lg text-white" data-shift-label="open-btn" onclick="openRegister()" style="background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                    <i class="fas fa-check me-2"></i>Open Shift
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Shift Close Modal -->
<div class="modal fade" id="shiftCloseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 20px 20px 0 0; padding: 20px 24px;">
                <h5 class="modal-title text-white fw-bold" data-shift-label="close-title"><i class="fas fa-clock me-2"></i>Close Shift</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="shiftReport" class="mb-4">
                    <!-- Shift report will be loaded here -->
                </div>
                <div class="mb-3" style="background: #fef2f2; border-radius: 16px; padding: 20px;">
                    <label class="form-label fw-semibold text-muted d-flex justify-content-between">
                        <span>Cash Denominations</span>
                        <span class="text-primary fw-bold" id="denominationTotal">Total: LKR 0.00</span>
                    </label>
                    <div class="row g-2" id="denominationInputs">
                        <!-- 5000 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #ef4444; color: #fff; border: none; min-width: 50px;">5000</span>
                                <input type="number" class="form-control denom-input" data-value="5000" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 2000 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #ef4444; color: #fff; border: none; min-width: 50px;">2000</span>
                                <input type="number" class="form-control denom-input" data-value="2000" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 1000 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #ef4444; color: #fff; border: none; min-width: 50px;">1000</span>
                                <input type="number" class="form-control denom-input" data-value="1000" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 500 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #f97316; color: #fff; border: none; min-width: 50px;">500</span>
                                <input type="number" class="form-control denom-input" data-value="500" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 100 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #f97316; color: #fff; border: none; min-width: 50px;">100</span>
                                <input type="number" class="form-control denom-input" data-value="100" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 50 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #22c55e; color: #fff; border: none; min-width: 50px;">50</span>
                                <input type="number" class="form-control denom-input" data-value="50" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 20 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #22c55e; color: #fff; border: none; min-width: 50px;">20</span>
                                <input type="number" class="form-control denom-input" data-value="20" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- 10 -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #22c55e; color: #fff; border: none; min-width: 50px;">10</span>
                                <input type="number" class="form-control denom-input" data-value="10" placeholder="0" min="0" inputmode="numeric" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                        <!-- Coins -->
                        <div class="col-4 col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold" style="background: #64748b; color: #fff; border: none; min-width: 50px;">Coins</span>
                                <input type="number" id="coinAmount" class="form-control" placeholder="0.00" step="0.01" min="0" inputmode="decimal" oninput="calculateDenominationTotal()" style="text-align: center;">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="closingBalance" value="0">
                    <small class="text-muted">Enter number of notes for each denomination</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Closing Notes</label>
                    <textarea id="closingNotes" class="form-control" rows="2" placeholder="Any notes about discrepancies or issues..." style="border-radius: 12px; border: 1px solid #e2e8f0;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 20px 24px;">
                <button type="button" class="btn btn-lg" data-bs-dismiss="modal" style="background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 600;">Cancel</button>
                <button type="button" class="btn btn-lg text-white" data-shift-label="close-btn" onclick="closeRegister()" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(239,68,68,0.3);">
                    <i class="fas fa-lock me-2"></i>Close Shift
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cash Drawer Button Modal -->
<div class="modal fade" id="cashDrawerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #ea580c); border-radius: 16px 16px 0 0;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-box me-2"></i>Cash Drawer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-grid gap-2">
                    <button class="btn btn-lg py-3" onclick="openCashDrawer()" style="background: #f0fdf4; color: #059669; border: 2px solid #10b981; border-radius: 12px; font-weight: 600;">
                        <i class="fas fa-drawer me-2"></i>Open Drawer
                    </button>
                    <button class="btn btn-lg py-3" onclick="showCashInModal()" style="background: #ecfdf5; color: #047857; border: 2px solid #059669; border-radius: 12px; font-weight: 600;">
                        <i class="fas fa-arrow-down me-2"></i>Cash In
                    </button>
                    <button class="btn btn-lg py-3" onclick="showCashOutModal()" style="background: #fef2f2; color: #b91c1c; border: 2px solid #ef4444; border-radius: 12px; font-weight: 600;">
                        <i class="fas fa-arrow-up me-2"></i>Cash Out
                    </button>
                    <button class="btn btn-lg py-3" id="dayEndAggregateBtn" onclick="showDayEndAggregate()" style="background: #fff7ed; color: #c2410c; border: 2px solid #f59e0b; border-radius: 12px; font-weight: 600; display:none;">
                        <i class="fas fa-calendar-check me-2"></i>Day End (all shifts)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog payment-modal-dialog {{ !empty($isIceCreamUi) ? 'payment-modal-dialog--dock-right modal-dialog-centered' : 'payment-modal-dialog--wide' }}">
        <div class="modal-content payment-modal-content">
            <div class="modal-header payment-modal-header">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-credit-card me-2"></i>Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body payment-modal-body">
                @if(!empty($isIceCreamUi))
                <div class="payment-modal-summary payment-top-inline">
                    <div class="payment-total-box payment-top-chip">
                        <span class="text-muted small">Total to Pay</span>
                        <strong id="paymentTotal" class="payment-total-amount">LKR 0.00</strong>
                    </div>
                    <div class="payment-balance-chip payment-top-chip">
                        <span class="text-muted small" id="paymentBalanceLabel">Balance</span>
                        <strong id="paymentRemaining" class="payment-balance-amount">LKR 0.00</strong>
                    </div>
                </div>
                <div id="deliveryPayHint" class="alert alert-info py-1 px-2 small mb-2" style="display:none;border-radius:10px;">
                    Delivery prepaid — use <strong>Bank</strong>, Cash, Card, or Credit. For COD, close this and choose <strong>Cash on delivery</strong>.
                </div>
                <div class="payment-modal-section payment-all-in-one mb-0" id="cashInputContainer">
                    <label class="form-label fw-semibold text-muted mb-1 small">Enter amount</label>
                    <div class="input-group mb-2 payment-amount-group">
                        <span class="input-group-text fw-bold payment-amount-prefix">LKR</span>
                        <input type="text" id="cashReceived" class="form-control fw-bold payment-amount-input" readonly placeholder="0.00">
                    </div>

                    <div class="keypad bakery-keypad-compact bakery-pay-keypad ice-pay-keypad mb-2"
                         style="display:grid !important;grid-template-columns:repeat(4,minmax(0,1fr)) !important;gap:10px !important;"
                         onpointerdown="event.preventDefault()">
                        <button type="button" class="touch-btn" onclick="payKeypad('1')">1</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('2')">2</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('3')">3</button>
                        <button type="button" class="touch-btn warning bakery-key-backspace" onclick="payKeypad('backspace')" aria-label="Delete"><i class="fas fa-backspace"></i></button>
                        <button type="button" class="touch-btn" onclick="payKeypad('4')">4</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('5')">5</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('6')">6</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('.')">.</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('7')">7</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('8')">8</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('9')">9</button>
                        <button type="button" class="touch-btn" onclick="payKeypad('00')">00</button>
                        <button type="button" class="touch-btn bakery-key-zero" style="grid-column:1 / -1;aspect-ratio:auto;height:52px;min-height:52px;max-height:52px;" onclick="payKeypad('0')">0</button>
                    </div>

                    <div class="row g-2 mb-2 payment-quick-grid" id="quickPayButtons">
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('exact')">Exact</button></div>
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('round')" title="Round cash up to nearest 100">Round</button></div>
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('500')">500</button></div>
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('1000')">1000</button></div>
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('2000')">2000</button></div>
                        <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('5000')">5000</button></div>
                    </div>

                    <label class="form-label fw-semibold text-muted mb-2 small">
                        Tap method — full payment closes &amp; prints automatically
                    </label>
                    <div class="payment-solid-grid mb-0">
                        <button type="button" class="payment-method-btn payment-solid-btn cash" data-method="cash" onclick="selectPaymentMethod('cash')">Cash</button>
                        <button type="button" class="payment-method-btn payment-solid-btn card" data-method="card" onclick="selectPaymentMethod('card')">Card</button>
                        <button type="button" class="payment-method-btn payment-solid-btn credit" data-method="credit" onclick="selectPaymentMethod('credit')">Credit</button>
                        <button type="button" class="payment-method-btn payment-solid-btn bank" data-method="bank_transfer" onclick="selectPaymentMethod('bank_transfer')">Bank</button>
                    </div>
                </div>

                <div class="payment-modal-section mt-2" id="splitPaymentContainer">
                    <label class="form-label fw-semibold text-muted mb-1 small">Payments added</label>
                    <div id="splitPayments"></div>
                </div>
                <div id="paymentPaidSum" class="d-none">LKR 0.00</div>
                <input type="hidden" id="paymentNotes" value="">
                <div class="text-center py-1 rounded-3 payment-change-box d-none">
                    <span class="text-muted small">Change</span>
                    <h4 class="mb-0 fw-bold" id="changeAmount" style="color: #059669; font-size: 1.15rem;">LKR 0.00</h4>
                </div>
                @else
                {{-- Restaurant: one-screen — methods LEFT, keypad RIGHT, live Balance only --}}
                <div class="payment-modal-summary payment-top-inline payment-top-inline--rest">
                    <div class="payment-total-box payment-top-chip">
                        <span class="text-muted small">Total to Pay</span>
                        <strong id="paymentTotal" class="payment-total-amount">LKR 0.00</strong>
                    </div>
                    <div class="payment-balance-chip payment-top-chip">
                        <span class="text-muted small" id="paymentBalanceLabel">Balance</span>
                        <strong id="paymentRemaining" class="payment-balance-amount">LKR 0.00</strong>
                    </div>
                </div>
                <div id="deliveryPayHint" class="alert alert-info py-1 px-2 small mb-2" style="display:none;border-radius:10px;">
                    Delivery prepaid — use <strong>Bank</strong>, Cash, Card, or Credit. For COD, close this and choose <strong>Cash on delivery</strong>.
                </div>

                <div class="payment-modal-section payment-all-in-one mb-0" id="cashInputContainer">
                    <label class="form-label fw-semibold text-muted mb-1 small">Enter amount</label>
                    <div class="input-group mb-2 payment-amount-group">
                        <span class="input-group-text fw-bold payment-amount-prefix">LKR</span>
                        <input type="text" id="cashReceived" class="form-control fw-bold payment-amount-input" readonly placeholder="0.00">
                    </div>

                    <div class="payment-wide-split">
                        <div class="payment-wide-methods">
                            <div class="payment-solid-grid payment-solid-grid--stack">
                                <button type="button" class="payment-method-btn payment-solid-btn cash" data-method="cash" onclick="selectPaymentMethod('cash')">Cash</button>
                                <button type="button" class="payment-method-btn payment-solid-btn card" data-method="card" onclick="selectPaymentMethod('card')">Card</button>
                                <button type="button" class="payment-method-btn payment-solid-btn credit" data-method="credit" onclick="selectPaymentMethod('credit')">Credit</button>
                                <button type="button" class="payment-method-btn payment-solid-btn bank" data-method="bank_transfer" onclick="selectPaymentMethod('bank_transfer')">Bank</button>
                            </div>
                        </div>

                        <div class="payment-wide-keypad">
                            <div class="keypad bakery-keypad-compact bakery-pay-keypad rest-pay-keypad mb-2"
                                 onpointerdown="event.preventDefault()">
                                <button type="button" class="touch-btn" onclick="payKeypad('1')">1</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('2')">2</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('3')">3</button>
                                <button type="button" class="touch-btn warning bakery-key-backspace" onclick="payKeypad('backspace')" aria-label="Delete"><i class="fas fa-backspace"></i></button>
                                <button type="button" class="touch-btn" onclick="payKeypad('4')">4</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('5')">5</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('6')">6</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('.')">.</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('7')">7</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('8')">8</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('9')">9</button>
                                <button type="button" class="touch-btn" onclick="payKeypad('00')">00</button>
                                <button type="button" class="touch-btn bakery-key-zero" onclick="payKeypad('0')">0</button>
                            </div>

                            <div class="row g-2 mb-0 payment-quick-grid" id="quickPayButtons">
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('exact')">Exact</button></div>
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('round')" title="Round cash up to nearest 100">Round</button></div>
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('500')">500</button></div>
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('1000')">1000</button></div>
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('2000')">2000</button></div>
                                <div class="col"><button type="button" class="btn w-100 payment-quick" onclick="quickPay('5000')">5000</button></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-modal-section mb-0 mt-2 d-none" id="splitPaymentContainer">
                    <label class="form-label fw-semibold text-muted mb-1 small">Payments added</label>
                    <div id="splitPayments"></div>
                    <div id="paymentPaidSum" class="d-none">LKR 0.00</div>
                </div>

                <input type="hidden" id="paymentNotes" value="">
                @endif
            </div>
            <div class="modal-footer payment-modal-footer justify-content-between">
                <button type="button" class="btn btn-sm payment-cancel-btn" data-bs-dismiss="modal">Cancel</button>
                <span class="text-muted small payment-footer-hint">Tap Cash / Card / Credit / Bank — pays &amp; prints automatically</span>
                <button type="button" id="completePaymentBtn" class="btn btn-sm text-white d-none" onclick="processPayment()" disabled aria-hidden="true">
                    <i class="fas fa-check me-1"></i>Complete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Held Orders Modal -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Held Orders</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="heldOrdersList"></div>
            </div>
        </div>
    </div>
</div>

<!-- Pay Bills Modal -->
<div class="modal fade" id="payBillsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc2626, #9a3412); border-radius: 20px 20px 0 0; padding: 18px 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-bell me-2"></i>Pay Bills — waiter requested</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="payBillsList" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Waiter Report Modal -->
<div class="modal fade" id="waiterReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #c45f08, #9a3412); border-radius: 20px 20px 0 0; padding: 18px 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-user-tie me-2"></i>Waiter Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm waiter-range-btn" data-range="today" onclick="loadWaiterReport('today')">Today</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm waiter-range-btn" data-range="yesterday" onclick="loadWaiterReport('yesterday')">Yesterday</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm waiter-range-btn" data-range="week" onclick="loadWaiterReport('week')">Week</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm waiter-range-btn active" data-range="month" onclick="loadWaiterReport('month')">Month</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm waiter-range-btn" data-range="year" onclick="loadWaiterReport('year')">Year</button>
                    </div>
                    <div class="d-flex gap-2 align-items-end ms-auto">
                        <div>
                            <label class="form-label small mb-0">From</label>
                            <input type="date" id="waiterReportFrom" class="form-control form-control-sm">
                        </div>
                        <div>
                            <label class="form-label small mb-0">To</label>
                            <input type="date" id="waiterReportTo" class="form-control form-control-sm">
                        </div>
                        <button type="button" class="btn btn-warning btn-sm text-white" onclick="loadWaiterReport('custom')">Custom</button>
                    </div>
                </div>
                <div id="waiterReportBody" style="max-height: 65vh; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Shift Report Modal (view current open shift — not close) -->
<div class="modal fade" id="shiftReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #1c1917, #44403c); border-radius: 20px 20px 0 0; padding: 18px 24px; border-bottom: 2px solid #f59e0b;">
                <h5 class="modal-title text-white fw-bold" data-shift-label="report-title"><i class="fas fa-chart-pie me-2" style="color:#fbbf24;"></i>Shift Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="shiftReportBody">
                    <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading shift report...</p></div>
                </div>
            </div>
            <div class="modal-footer d-flex flex-wrap gap-2 justify-content-between align-items-center" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <small class="text-muted" id="shiftReportMeta"></small>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="loadShiftReport()"><i class="fas fa-sync-alt me-1"></i>Refresh</button>
                    <button type="button" class="btn btn-warning text-white d-none" id="shiftReportPrintBtn"><i class="fas fa-print me-1"></i>Print</button>
                    <button type="button" class="btn btn-outline-danger" data-shift-label="close-from-report" onclick="(bootstrap.Modal.getInstance(document.getElementById('shiftReportModal'))||{}).hide?.(); setTimeout(() => showShiftCloseModal(), 250);"><i class="fas fa-clock me-1"></i>Close Shift</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Modal -->
<div class="modal fade" id="recentOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content recent-orders-modal">
            <div class="modal-header recent-orders-header">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-history me-2" style="color:#fbbf24;"></i>Recent Orders — Today</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
                <div id="recentOrdersList" class="p-0">
                    <div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading orders...</p></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #ea580c); border-radius: 20px 20px 0 0; padding: 20px 24px;">
                <h5 class="modal-title text-white fw-bold" id="orderDetailTitle"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="orderDetailBody">
                <!-- Loaded dynamically -->
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;" id="orderDetailFooter">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- KOT/BOT Modify Modal -->
<div class="modal fade" id="kotModifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1c1917; border-radius: 20px 20px 0 0; padding: 18px 24px; border-bottom: 2px solid #f59e0b;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-edit me-2" style="color:#f59e0b;"></i>Modify KOT / BOT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="kotModifyList" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Loading...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Delivery / Guest Details Modal -->
<div class="modal fade" id="deliveryDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border-radius: 20px 20px 0 0; padding: 18px 24px;">
                <h5 class="modal-title text-white fw-bold" id="deliveryDetailsModalTitle"><i class="fas fa-motorcycle me-2"></i>Delivery details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 customer-section is-delivery" style="box-shadow:none;border:0;padding-top:1.25rem!important;">
                <div id="deliveryPartnerPickerBlock" class="mb-3" style="display:none;">
                    <div class="cs-label mb-2"><i class="fas fa-motorcycle"></i>Who delivers?</div>
                    <div class="delivery-partner-logos" id="deliveryPartnerLogoGrid" role="listbox" aria-label="Delivery partner">
                        <button type="button" class="dpl-card active" data-partner-id="" data-collection="own" onclick="selectDeliveryPartnerCard(this)" title="Own delivery">
                            <span class="dpl-logo dpl-own"><i class="fas fa-store"></i></span>
                            <span class="dpl-name">Own Delivery</span>
                            <small class="dpl-hint">Customer details required</small>
                        </button>
                        @foreach(\App\Models\DeliveryPartner::active()->orderBy('name')->get(['id','name','code','collection_type','settlement_cycle','tracks_settlement']) as $partner)
                            @php
                                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', ($partner->code ?? '').' '.($partner->name ?? '')));
                                $logoKey = null;
                                $label = $partner->name;
                                if (str_contains($slug, 'uber')) { $logoKey = 'ubereats'; $label = 'Uber Eats'; }
                                elseif (str_contains($slug, 'pickme')) { $logoKey = 'pickme'; $label = 'PickMe'; }
                                elseif (str_contains($slug, 'buyit')) { $logoKey = 'buyit'; $label = 'Buyit'; }
                                $logoData = $logoKey ? (config('partner_logos.'.$logoKey) ?: null) : null;
                                $logoFile = $logoKey ? '/images/partners/'.$logoKey.'.png' : null;
                            @endphp
                            <button type="button" class="dpl-card" data-partner-id="{{ $partner->id }}"
                                data-collection="{{ $partner->collection_type ?? 'partner' }}"
                                data-cycle="{{ $partner->settlement_cycle ?? 'weekly' }}"
                                data-tracks="{{ ($partner->tracks_settlement ?? true) ? '1' : '0' }}"
                                data-name="{{ $partner->name }}"
                                onclick="selectDeliveryPartnerCard(this)" title="{{ $partner->name }}">
                                @if($logoData)
                                    <span class="dpl-logo"><img src="{{ $logoData }}" alt="{{ $label }}"></span>
                                @elseif($logoFile)
                                    <span class="dpl-logo"><img src="{{ $logoFile }}" alt="{{ $label }}" onerror="this.parentElement.classList.add('dpl-fallback');this.parentElement.textContent='{{ strtoupper(substr($label,0,1)) }}';"></span>
                                @else
                                    <span class="dpl-logo dpl-fallback">{{ strtoupper(substr($partner->name, 0, 1)) }}</span>
                                @endif
                                <span class="dpl-name">{{ $label }}</span>
                                <small class="dpl-hint">{{ ($partner->collection_type ?? 'partner') === 'own' ? 'Our rider' : 'No customer needed' }}</small>
                            </button>
                        @endforeach
                    </div>
                    <select id="deliveryPartnerSelect" class="d-none" aria-hidden="true">
                        <option value=""></option>
                        @foreach(\App\Models\DeliveryPartner::active()->orderBy('name')->get(['id','name','code','collection_type','settlement_cycle','tracks_settlement']) as $partner)
                        <option value="{{ $partner->id }}"
                            data-collection="{{ $partner->collection_type ?? 'partner' }}"
                            data-cycle="{{ $partner->settlement_cycle ?? 'weekly' }}"
                            data-tracks="{{ ($partner->tracks_settlement ?? true) ? '1' : '0' }}">
                            {{ $partner->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="cs-row cs-customer-row mb-3" id="deliveryCustomerBlock">
                    <div class="cs-field flex-grow-1">
                        <label for="customerSelect" class="cs-label"><i class="fas fa-user"></i>Customer</label>
                        <select id="customerSelect" class="form-select-modern select2" data-placeholder="Search customer..." onchange="onCustomerChange(this)">
                            <option value=""></option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                data-address="{{ $customer->address }}"
                                data-phone="{{ $customer->phone }}"
                                data-loyalty-joined="{{ $customer->loyalty_joined ? '1' : '0' }}">
                                {{ $customer->name }} - {{ $customer->phone }}{{ $customer->loyalty_joined ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn add-customer-btn" onclick="showAddCustomerModal()" title="Add New Customer" aria-label="Add customer">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div id="deliveryDetailsBlock" class="delivery-details">
                    <div class="cs-grid">
                        <div class="cs-field" id="deliveryAddressWrapper">
                            <label for="deliveryAddress" class="cs-label"><i class="fas fa-map-marker-alt"></i>Delivery address</label>
                            <textarea id="deliveryAddress" class="form-control cs-input" placeholder="Street, landmark, city…" rows="2" onblur="saveDeliveryAddressToCustomer()"></textarea>
                        </div>
                    </div>

                    <div id="deliveryPayModeWrapper" class="cs-pay">
                        <div class="cs-label mb-2"><i class="fas fa-wallet"></i>How will they pay?</div>
                        <div id="deliveryPartnerPayHint" class="d-none alert alert-warning py-2 px-3 small mb-2" style="border-radius:12px;"></div>
                        <div class="delivery-pay-seg" role="group" aria-label="Delivery payment mode">
                            <button type="button" class="delivery-pay-mode-btn active" data-mode="prepaid" onclick="setDeliveryPayMode('prepaid')">
                                <span class="dpm-icon"><i class="fas fa-university"></i></span>
                                <span class="dpm-copy">
                                    <strong>Pay now</strong>
                                    <small>Bank · Cash · Card</small>
                                </span>
                            </button>
                            <button type="button" class="delivery-pay-mode-btn" data-mode="cod" onclick="setDeliveryPayMode('cod')">
                                <span class="dpm-icon"><i class="fas fa-hand-holding-usd"></i></span>
                                <span class="dpm-copy">
                                    <strong>Cash on delivery</strong>
                                    <small>We collect when delivered</small>
                                </span>
                            </button>
                            <button type="button" class="delivery-pay-mode-btn" data-mode="partner" onclick="setDeliveryPayMode('partner')">
                                <span class="dpm-icon"><i class="fas fa-calendar-week"></i></span>
                                <span class="dpm-copy">
                                    <strong>Partner settles later</strong>
                                    <small>Weekly / no payment now</small>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <button type="button" class="btn btn-lg" data-bs-dismiss="modal" style="background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 600;">Cancel</button>
                <button type="button" class="btn btn-lg text-white" onclick="applyDeliveryDetails()" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none; border-radius: 12px; font-weight: 600;">
                    <i class="fas fa-check me-2"></i>Save details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loyalty Scan / Select Modal -->
<div class="modal fade" id="loyaltyPosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content loyalty-modal-content">
            <div class="modal-header loyalty-modal-header">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-stamp me-2"></i>Loyalty stamp card</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Selecting a loyalty member in Delivery details also loads their stamp card here. You can still scan QR or search by name / phone.</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="fas fa-qrcode me-1"></i>Scan QR / paste code</label>
                    <div class="input-group">
                        <input type="text" id="loyaltyScanInput" class="form-control form-control-lg" placeholder="Scan card QR or paste LOYALTY:… / card link" style="border-radius: 12px 0 0 12px;" onkeydown="if(event.key==='Enter'){event.preventDefault();loyaltyScanOrLookup();}">
                        <button type="button" class="btn btn-warning" onclick="loyaltyScanOrLookup()" style="border-radius: 0 12px 12px 0; font-weight: 700;">Load</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="loyaltyCustomerSelect"><i class="fas fa-user me-1"></i>Search customer</label>
                    <div class="d-flex gap-2 align-items-stretch">
                        <select id="loyaltyCustomerSelect" class="form-select-modern" data-placeholder="Search customer by name or phone…" style="width:100%">
                            <option value=""></option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" data-phone="{{ $customer->phone }}" data-loyalty-joined="{{ $customer->loyalty_joined ? '1' : '0' }}">
                                {{ $customer->name }}{{ $customer->phone ? ' - '.$customer->phone : '' }}{{ $customer->loyalty_joined ? ' ★' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn add-customer-btn flex-shrink-0" onclick="showAddCustomerModal()" title="Add customer" aria-label="Add customer" style="min-width:48px;border-radius:12px;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div id="loyaltyModalCard" class="loyalty-modal-card">
                    <div class="loy-empty text-center py-4">Search or scan a customer to see their stamps</div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary" id="loyaltyJoinBtn" onclick="enrollSelectedLoyaltyCustomer()">
                        <i class="fas fa-user-plus me-1"></i>Join selected customer to stamp card
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearLoyaltySelection(false)">
                        Clear loyalty customer
                    </button>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <button type="button" class="btn btn-lg text-white" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 12px; font-weight: 600; width: 100%;">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #ea580c); border-radius: 20px 20px 0 0; padding: 20px 24px;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-user-plus me-2"></i>Add New Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCustomerForm" onsubmit="submitAddCustomer(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #475569;">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" id="newCustomerName" class="form-control form-control-lg" placeholder="Enter customer name" required style="border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #475569;">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" id="newCustomerPhone" class="form-control form-control-lg" placeholder="07X XXX XXXX" required style="border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #475569;">Email</label>
                        <input type="email" id="newCustomerEmail" class="form-control form-control-lg" placeholder="customer@email.com (optional)" style="border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1rem;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="color: #475569;">Address</label>
                        <textarea id="newCustomerAddress" class="form-control" placeholder="Delivery address (optional)" rows="2" style="border-radius: 12px; border: 2px solid #e2e8f0; font-size: 1rem;"></textarea>
                    </div>
                    @if(\App\Services\LoyaltyService::enabled())
                    <div class="mb-1 p-3" style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px;">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="newCustomerJoinLoyalty" checked style="width: 2.5em; height: 1.3em; cursor: pointer;">
                            <label class="form-check-label fw-semibold" for="newCustomerJoinLoyalty" style="color: #9a3412; cursor: pointer;">
                                <i class="fas fa-stamp me-1"></i>Join loyalty stamp card
                            </label>
                        </div>
                        <div class="form-text mt-1 mb-0" style="color: #a16207;">Creates digital stamp card linked to this phone (scan QR next visits).</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 20px 24px;">
                    <button type="button" class="btn btn-lg" data-bs-dismiss="modal" style="background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 600;">Cancel</button>
                    <button type="submit" class="btn btn-lg text-white" style="background: linear-gradient(135deg, #f59e0b, #ea580c); border: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                        <i class="fas fa-save me-2"></i>Save Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Product Options Modal (Variants & Addons) -->
<div class="modal fade" id="productOptionsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 20px 20px 0 0; padding: 20px 24px;">
                <h5 class="modal-title text-white fw-bold" id="optionsModalTitle"><i class="fas fa-utensils me-2"></i>Customize Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="optionsModalBody">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 20px 24px;">
                <button type="button" class="btn btn-lg" data-bs-dismiss="modal" style="background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 600;">Cancel</button>
                <button type="button" class="btn btn-lg text-white" id="optionsConfirmBtn" onclick="confirmAddToCart()" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                    <i class="fas fa-plus me-2"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productOptionsModal')?.addEventListener('hidden.bs.modal', () => {
    editingCartIndex = null;
    pendingProduct = null;
    selectedAddons = [];
    selectedVariant = null;
    const confirmBtn = document.getElementById('optionsConfirmBtn');
    if (confirmBtn) confirmBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add to Cart';
});
</script>

<!-- Select Table Modal -->
<div class="modal fade" id="selectTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1c1917; border-radius: 20px 20px 0 0; padding: 18px 24px; border-bottom: 2px solid #f59e0b;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-chair me-2" style="color:#f59e0b;"></i>Select Table</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @forelse($floors as $floor)
                <div class="mb-4">
                    <div class="table-floor-title"><i class="fas fa-layer-group me-2"></i>{{ $floor->name }}</div>
                    <div class="table-pick-grid">
                        @forelse($floor->tables as $table)
                        @php
                            $isBusy = (bool) $table->open_order_id;
                            $tableStatus = $isBusy ? 'occupied' : 'available';
                        @endphp
                        <button type="button" class="table-pick-btn table-status-{{ $tableStatus }}{{ $table->open_order_id ? ' has-open-bill' : '' }}"
                                data-id="{{ $table->id }}"
                                data-name="{{ $table->name }}"
                                data-order-id="{{ $table->open_order_id ?? '' }}"
                                onclick="selectTable(this)">
                            <div class="tp-name">{{ $table->name }}</div>
                            <x-seat-diagram
                                :capacity="$table->capacity"
                                :occupied="$isBusy"
                                theme="light"
                            />
                            <div class="tp-cap">{{ $table->capacity }} seats</div>
                            <div class="tp-status">
                                @if($table->open_order_id)
                                    {{ $table->open_order_number }} · Open bill
                                @else
                                    Available
                                @endif
                            </div>
                        </button>
                        @empty
                        <div class="text-muted small">No tables on this floor.</div>
                        @endforelse
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chair fa-3x mb-3 opacity-25"></i>
                    <p>No floors/tables configured. Add them in Floors &amp; Tables.</p>
                </div>
                @endforelse
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <button type="button" class="btn btn-secondary" onclick="clearTable()"><i class="fas fa-times me-1"></i>Clear Table</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     CUSTOM ITEM MODAL
     ===================================================================== -->
<div class="modal fade" id="customItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:20px;border:none;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);overflow:hidden;">

            {{-- Header --}}
            <div class="modal-header" style="background:linear-gradient(135deg,#1c1917,#292524);padding:18px 22px;border-bottom:2px solid #f59e0b;">
                <div class="d-flex align-items-center gap-2">
                    <span style="background:#f59e0b;border-radius:8px;padding:6px 9px;line-height:1;"><i class="fas fa-keyboard" style="color:#1c1917;font-size:1rem;"></i></span>
                    <h5 class="modal-title text-white fw-bold mb-0">CUSTOM ITEM</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="customItemCloseBtn"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-0">
                <div style="background:#1c1917;padding:18px 20px 14px;">

                    {{-- Instruction line --}}
                    <div style="color:#a8a29e;font-size:0.78rem;margin-bottom:12px;letter-spacing:.3px;">
                        <span style="color:#f59e0b;">Name</span> · Enter &nbsp;|&nbsp;
                        <span style="color:#f59e0b;">Price</span> keys / numpad · Enter add &nbsp;|&nbsp;
                        <span style="color:#f59e0b;">Esc</span> close
                    </div>

                    {{-- Item Name --}}
                    <div class="mb-3">
                        <label class="form-label text-white fw-semibold mb-1" style="font-size:.8rem;letter-spacing:.5px;">ITEM NAME</label>
                        <input type="text" id="customItemName"
                               class="form-control"
                               placeholder="Type any item name…"
                               autocomplete="off"
                               style="border-radius:10px;background:#292524;border:1.5px solid #44403c;color:#fff;font-size:1rem;padding:10px 14px;"
                               oninput="customItemClearNameError()"
                               onkeydown="customItemNameKeydown(event)">
                        <div id="customItemNameError" class="text-danger mt-1" style="font-size:.8rem;display:none;">Item name is required.</div>
                    </div>

                    {{-- Price --}}
                    <div class="mb-3">
                        <label class="form-label text-white fw-semibold mb-1" style="font-size:.8rem;letter-spacing:.5px;">PRICE</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold"
                                  style="background:#292524;border:1.5px solid #44403c;border-right:none;color:#f59e0b;border-radius:10px 0 0 10px;">LKR</span>
                            <input type="text" id="customItemPrice"
                                   class="form-control text-end fw-bold"
                                   value="0.00"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   style="border-radius:0 10px 10px 0;background:#292524;border:1.5px solid #44403c;border-left:none;color:#fff;font-size:1.1rem;padding:10px 14px;"
                                   onfocus="customItemPriceFocus()"
                                   onkeydown="customItemPriceKeydown(event)"
                                   oninput="customItemClearPriceError()">
                        </div>
                        <div id="customItemPriceError" class="text-danger mt-1" style="font-size:.8rem;display:none;">Please enter a valid price greater than 0.</div>
                    </div>

                    {{-- Numeric Keypad --}}
                    <div class="custom-item-keypad">
                        <button type="button" class="cik-btn" onclick="customItemKeypad('7')">7</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('8')">8</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('9')">9</button>
                        <button type="button" class="cik-btn cik-del" onclick="customItemKeypad('DEL')"><i class="fas fa-backspace"></i></button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('4')">4</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('5')">5</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('6')">6</button>
                        <button type="button" class="cik-btn cik-clr" onclick="customItemKeypad('C')">C</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('1')">1</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('2')">2</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('3')">3</button>
                        <button type="button" class="cik-btn cik-add-row" onclick="customItemAddToCart()">
                            <i class="fas fa-cart-plus me-1"></i>Add
                        </button>
                        <button type="button" class="cik-btn cik-zero" onclick="customItemKeypad('0')">0</button>
                        <button type="button" class="cik-btn" onclick="customItemKeypad('.')">.</button>
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer" style="background:#1c1917;border-top:1px solid #44403c;padding:14px 20px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="border-radius:10px;background:#292524;border:1px solid #44403c;color:#a8a29e;font-weight:600;">
                    Cancel
                </button>
                <button type="button" class="btn btn-warning fw-bold"
                        onclick="customItemAddToCart()"
                        style="border-radius:10px;min-width:120px;font-size:.95rem;">
                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Subcategory filter chips */
.subcategory-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.subcategory-chip {
    border: 1px solid #d6d3d1;
    background: #fff;
    color: #44403c;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    line-height: 1.2;
}
.subcategory-chip:hover { border-color: #f59e0b; color: #c2410c; }
.subcategory-chip.active {
    background: #fff7ed;
    border-color: #f59e0b;
    color: #c2410c;
}

/* Custom Item grid card */
.custom-item-card {
    background: linear-gradient(160deg, #292524, #1c1917) !important;
    border: 2px dashed #f59e0b !important;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.custom-item-card:hover {
    border-color: #fbbf24 !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(245,158,11,.25);
}
.custom-item-icon {
    font-size: 1.6rem;
    color: #f59e0b;
    margin-bottom: 4px;
    margin-top: 10px;
}
/* Numeric keypad in modal */
.custom-item-keypad {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.cik-btn {
    background: #292524;
    border: 1.5px solid #44403c;
    border-radius: 10px;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    padding: 10px 4px;
    cursor: pointer;
    transition: background .12s, transform .08s;
    text-align: center;
}
.cik-btn:hover  { background: #3c3530; }
.cik-btn:active { background: #57534e; transform: scale(.96); }
.cik-del  { color: #fca5a5; }
.cik-clr  { color: #fcd34d; }
.cik-zero { grid-column: span 2; }
.cik-add-row {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #1c1917 !important;
    border-color: #d97706 !important;
    grid-row: span 2;
    font-size: .9rem;
}
.cik-add-row:hover  { background: linear-gradient(135deg, #fbbf24, #f59e0b) !important; }
</style>

<script>
// ===== Custom Item Modal Logic =====
let _customItemPriceRaw = '';
let _customItemPriceFocused = false;

function openCustomItemModal() {
    _customItemPriceRaw = '';
    _customItemPriceFocused = false;
    const nameEl  = document.getElementById('customItemName');
    const priceEl = document.getElementById('customItemPrice');
    if (nameEl)  { nameEl.value = ''; }
    if (priceEl) { priceEl.value = '0.00'; }
    document.getElementById('customItemNameError')?.style.setProperty('display', 'none');
    document.getElementById('customItemPriceError')?.style.setProperty('display', 'none');
    const modal = new bootstrap.Modal(document.getElementById('customItemModal'));
    modal.show();
    setTimeout(() => document.getElementById('customItemName')?.focus(), 300);
}

function customItemPriceFocus() {
    if (!_customItemPriceFocused) {
        _customItemPriceRaw = '';
        _customItemPriceFocused = true;
        document.getElementById('customItemPrice').value = '';
    }
}

function customItemKeypad(key) {
    const priceEl = document.getElementById('customItemPrice');
    if (!priceEl) return;

    _customItemPriceFocused = true;

    if (key === 'C') {
        _customItemPriceRaw = '';
        priceEl.value = '0.00';
        return;
    }
    if (key === 'DEL') {
        _customItemPriceRaw = _customItemPriceRaw.slice(0, -1);
    } else if (key === '.') {
        if (!_customItemPriceRaw.includes('.')) {
            _customItemPriceRaw += '.';
        }
        priceEl.value = _customItemPriceRaw || '0.';
        return;
    } else {
        _customItemPriceRaw += key;
    }

    const num = parseFloat(_customItemPriceRaw);
    priceEl.value = isNaN(num) ? '0.00' : _customItemPriceRaw;
    customItemClearPriceError();
}

function customItemClearNameError() {
    document.getElementById('customItemNameError')?.style.setProperty('display', 'none');
}
function customItemClearPriceError() {
    document.getElementById('customItemPriceError')?.style.setProperty('display', 'none');
}

function customItemNameKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('customItemPrice')?.focus();
        document.getElementById('customItemPrice')?.select();
        _customItemPriceFocused = false;
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        bootstrap.Modal.getInstance(document.getElementById('customItemModal'))?.hide();
    }
}

function customItemPriceKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        customItemAddToCart();
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        bootstrap.Modal.getInstance(document.getElementById('customItemModal'))?.hide();
    }
    // Allow digits, dot, backspace, delete, arrows, tab
    const allowed = /^[0-9.]$/.test(e.key) || ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key);
    if (!allowed) { e.preventDefault(); }
}

function customItemAddToCart() {
    const nameEl  = document.getElementById('customItemName');
    const priceEl = document.getElementById('customItemPrice');

    const name  = (nameEl?.value || '').trim();
    const price = parseFloat(priceEl?.value || '0');

    let valid = true;
    if (!name) {
        document.getElementById('customItemNameError').style.display = 'block';
        nameEl?.focus();
        valid = false;
    }
    if (isNaN(price) || price <= 0) {
        document.getElementById('customItemPriceError').style.display = 'block';
        if (valid) { priceEl?.focus(); }
        valid = false;
    }
    if (!valid) return;

    // Check if there's already an unlocked custom item with exact same name & price — merge qty
    const existing = cart.find(item =>
        !item.order_item_id &&
        !!item.is_custom_item &&
        item.name === name &&
        item.price === price
    );
    if (existing) {
        existing.quantity += 1;
        updateCart();
        if (typeof showToast === 'function') showToast('success', name + ' (Qty: ' + existing.quantity + ')');
    } else {
        cart.push({
            product_id: null,
            category_id: null,
            is_custom_item: true,
            base_name: name,
            base_price: price,
            name: name,
            price: price,
            quantity: 1,
            variant_id: null,
            variant_name: null,
            variant_adj: 0,
            addons: [],
            discount: 0,
            has_options: false,
            special_instructions: '',
            loyalty_free: false,
        });
        updateCart();
        if (typeof showToast === 'function') showToast('success', name + ' added to cart');
    }

    bootstrap.Modal.getInstance(document.getElementById('customItemModal'))?.hide();
    setTimeout(() => focusProductSearch(true), 60);
}

// Keep focus returning after custom-item modal closes
document.getElementById('customItemModal')?.addEventListener('hidden.bs.modal', () => {
    if (pendingQtyFocusIndex == null) focusProductSearch(true);
});

// Prevent ESC on customItemModal inputs from closing the modal via POS shortcut handler
document.getElementById('customItemModal')?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { e.stopPropagation(); }
    // Block POS F-key shortcuts while modal is open
    const key = (e.key || '').toUpperCase();
    if (/^F([1-9]|1[0-2])$/.test(key)) { e.stopPropagation(); }
}, true);
</script>

<!-- Transfer Table Modal -->
<div class="modal fade" id="transferTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1c1917; border-radius: 20px 20px 0 0; padding: 18px 24px; border-bottom: 2px solid #f59e0b;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-exchange-alt me-2" style="color:#f59e0b;"></i>Transfer Table</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3" id="transferTableHint">Select a free table for this bill.</p>
                <div id="transferTableList"></div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Bill Table Modal -->
<div class="modal fade" id="billTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1c1917; border-radius: 20px 20px 0 0; padding: 18px 24px; border-bottom: 2px solid #f59e0b;">
                <h5 class="modal-title text-white fw-bold"><i class="fas fa-receipt me-2" style="color:#f59e0b;"></i>Open Bills — Unpaid</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="billTableList">
                    <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                <div id="openBillActionBar" class="d-none me-auto d-flex flex-wrap gap-2 align-items-center">
                    <span id="openBillSelectedLabel" class="text-muted small"></span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="openSelectedOpenBill()"><i class="fas fa-folder-open me-1"></i>Open</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="voidSelectedOpenBill()"><i class="fas fa-times me-1"></i>Cancel</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .select-table-btn {
        width: 100%; display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 14px 18px; border-radius: 12px; border: 2px solid #e2e8f0;
        background: #fff; color: #57534e; font-size: 1rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s; text-align: left;
    }
    .cart-meta-slot .select-table-btn { margin-bottom: 0; padding: 12px 14px; font-size: 0.95rem; }
    .cart-meta-slot .form-select-modern { font-size: 0.9rem; }
    .select-table-btn:hover { border-color: #f59e0b; background: #fff7ed; }
    .select-table-btn .stb-left i { color: #f59e0b; margin-right: 6px; }
    .select-table-btn .stb-arrow { color: #cbd5e1; font-size: 0.85rem; }
    .select-table-btn.has-table { border-color: #f59e0b; background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; }
    .select-table-btn.has-table .stb-left i, .select-table-btn.has-table .stb-arrow { color: #fff; }
    .transfer-table-btn {
        width: 100%; padding: 10px 14px; border-radius: 12px; border: 2px dashed #fdba74;
        background: #fff7ed; color: #c2410c; font-size: 0.9rem; font-weight: 700; cursor: pointer;
        transition: all 0.15s ease;
    }
    .transfer-table-btn:hover { border-color: #f59e0b; background: #ffedd5; color: #9a3412; }
    .transfer-table-btn:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Cart meta: Table | Transfer | Waiter on one row */
    .cart-meta-row {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        min-width: 0;
        width: 100%;
    }
    .cart-meta-row .cart-meta-item {
        min-width: 0;
    }
    .cart-meta-row .cart-meta-table {
        flex: 1.1 1 0;
    }
    .cart-meta-row .cart-meta-transfer {
        flex: 1 1 0;
        width: auto;
        margin-top: 0;
        padding: 8px 6px;
        font-size: 0.72rem;
        line-height: 1.15;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-meta-row .cart-meta-transfer .transfer-table-label {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-meta-row .cart-meta-waiter {
        flex: 1.15 1 0;
    }
    .cart-meta-row .select-table-btn {
        width: 100%;
        min-width: 0;
        padding: 8px 8px;
        font-size: 0.78rem;
        gap: 4px;
    }
    .cart-meta-row .select-table-btn .stb-left {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cart-meta-row .select-table-btn .stb-arrow {
        flex-shrink: 0;
    }
    .cart-meta-row .cart-meta-waiter .form-label {
        margin-bottom: 0.15rem !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.7rem;
    }
    .cart-meta-row .cart-meta-waiter .form-select-modern {
        width: 100%;
        min-width: 0;
        padding: 6px 8px;
        font-size: 0.78rem;
    }
    .cart-meta-takeaway {
        margin-top: 0;
        min-width: 0;
        width: 100%;
    }
    .cart-meta-takeaway .form-label {
        margin-bottom: 0.15rem !important;
        font-size: 0.7rem;
    }
    .cart-takeaway-fields {
        display: flex;
        gap: 6px;
        min-width: 0;
        width: 100%;
    }
    .cart-takeaway-fields .form-control {
        flex: 1 1 0;
        min-width: 0;
        padding: 6px 8px;
        font-size: 0.78rem;
        border-radius: 10px;
    }

    .loyalty-pos-bar {
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        border: 1px solid #fed7aa;
        border-radius: 14px;
        padding: 12px 14px;
        cursor: pointer;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .loyalty-pos-bar:hover { box-shadow: 0 6px 18px rgba(245,158,11,.18); }
    .loyalty-pos-bar .loy-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        font-weight: 700; color: #9a3412; font-size: 0.9rem; margin-bottom: 8px;
    }
    .loyalty-pos-bar .loy-body { min-height: 36px; }
    .loyalty-pos-bar .loy-empty { color: #a8a29e; font-size: 0.82rem; }
    .loyalty-pos-bar .loy-top {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 0.88rem; margin-bottom: 8px; color: #44403c;
    }
    .loyalty-pos-bar .loy-count {
        font-weight: 800; color: #c2410c; background: #fff; border-radius: 999px;
        padding: 2px 10px; font-size: .8rem; border: 1px solid #fed7aa;
    }
    .loy-stamps { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
    .loy-stamps-lg { gap: 10px; justify-content: center; }
    .loy-stamp {
        display: inline-grid; place-items: center; font-weight: 800;
        border-radius: 50%; border: 2px dashed #d6d3d1; background: #fafaf9; color: #a8a29e;
    }
    .loy-stamp.sm { width: 22px; height: 22px; font-size: .65rem; }
    .loy-stamp.lg { width: 48px; height: 48px; font-size: .95rem; border-width: 3px; }
    .loy-stamp.on {
        border-style: solid; border-color: #f59e0b;
        background: linear-gradient(145deg, #fbbf24, #f59e0b); color: #fff;
        box-shadow: 0 4px 10px rgba(245,158,11,.35);
    }
    .loyalty-pos-bar .loy-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }

    .loyalty-modal-content { border-radius: 20px; border: none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .loyalty-modal-header { background: linear-gradient(135deg, #4A0E1A, #7c1d2e); border-radius: 20px 20px 0 0; padding: 20px 24px; }
    .loyalty-modal-card { margin-top: 4px; }
    .loyalty-pass {
        background: linear-gradient(160deg, #4A0E1A 0%, #2d0810 100%);
        color: #fff; border-radius: 18px; padding: 18px 16px 16px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
    }
    .loyalty-pass-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 12px; }
    .loyalty-pass-biz { font-size: .68rem; letter-spacing: .14em; text-transform: uppercase; opacity: .55; }
    .loyalty-pass-name { font-size: 1.2rem; font-weight: 800; }
    .loyalty-pass-phone { opacity: .65; font-size: .88rem; }
    .loyalty-pass-count { font-size: 1.8rem; font-weight: 800; color: #e8c28a; line-height: 1; }
    .loyalty-pass-count span { font-size: 1rem; opacity: .7; }
    .loyalty-pass .stamps-label { font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; opacity: .5; margin: 8px 0 10px; text-align: center; }
    .loyalty-pass-alert { text-align: center; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
    .loyalty-pass-alert.ok { background: rgba(16,185,129,.18); color: #6ee7b7; border: 1px solid rgba(110,231,183,.3); }
    .loyalty-pass-alert.danger { background: rgba(239,68,68,.18); color: #fca5a5; border: 1px solid rgba(252,165,165,.3); }
    .loyalty-pass-foot { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 14px; }
    .loyalty-pass-exp { text-align: center; margin-top: 10px; font-size: .75rem; opacity: .5; }
    #loyaltyPosModal .select2-container { width: 100% !important; z-index: 1065; }
    #loyaltyPosModal .select2-dropdown { z-index: 1070; }

    .table-floor-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #f59e0b; margin-bottom: 12px; }
    .table-pick-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: 14px; }
    .table-pick-btn {
        border: 2px solid #e2e8f0; border-radius: 16px; background: #fff; padding: 12px 10px 14px;
        text-align: center; cursor: pointer; transition: all 0.15s ease; position: relative;
        display: flex; flex-direction: column; align-items: center;
    }
    .table-pick-btn:hover { transform: translateY(-2px); border-color: #f59e0b; box-shadow: 0 6px 18px rgba(245,158,11,0.18); }
    .table-pick-btn .tp-name { font-size: 0.98rem; font-weight: 700; color: #292524; }
    .table-pick-btn .tp-cap { font-size: 0.72rem; color: #78716c; margin-top: 2px; }
    .table-pick-btn .tp-status { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 8px; padding: 3px 8px; border-radius: 20px; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .table-pick-btn.table-status-available { border-color: #bbf7d0; }
    .table-pick-btn.table-status-available .tp-status { background: #dcfce7; color: #15803d; }
    .table-pick-btn.table-status-occupied { border-color: #fecaca; }
    .table-pick-btn.table-status-occupied .tp-status { background: #fee2e2; color: #b91c1c; }
    .table-pick-btn.has-open-bill { border-color: #fcd34d; box-shadow: 0 0 0 1px rgba(245,158,11,0.2); }
    .table-pick-btn.has-open-bill .tp-status { background: #fff7ed; color: #c2410c; font-weight: 700; }
    .table-pick-btn.table-status-reserved { border-color: #fde68a; }
    .table-pick-btn.table-status-reserved .tp-status { background: #fef3c7; color: #b45309; }
    .table-pick-btn.table-status-cleaning { border-color: #e2e8f0; }
    .table-pick-btn.table-status-cleaning .tp-status { background: #f1f5f9; color: #475569; }
    .table-pick-btn.selected { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.3); background: #fff7ed; }

    /* Seat diagram (POS light theme) */
    .seat-diagram {
        position: relative;
        width: 100px;
        height: 82px;
        margin: 8px auto 2px;
        flex: 0 0 auto;
    }
    .seat-diagram .seat-table-top {
        position: absolute;
        left: 50%; top: 50%;
        transform: translate(-50%, -50%);
        width: 44%; height: 40%;
        border-radius: 10px;
        background: linear-gradient(160deg, #e7e5e4, #d6d3d1);
        border: 2px solid #a8a29e;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }
    .seat-diagram.seats-n-2 .seat-table-top { width: 30%; height: 46%; border-radius: 40%; }
    .seat-diagram.seats-n-3 .seat-table-top,
    .seat-diagram.seats-n-4 .seat-table-top { width: 40%; height: 40%; border-radius: 12px; }
    .seat-diagram.seats-n-5 .seat-table-top,
    .seat-diagram.seats-n-6 .seat-table-top { width: 52%; height: 34%; border-radius: 10px; }
    .seat-diagram.seats-n-7 .seat-table-top,
    .seat-diagram.seats-n-8 .seat-table-top,
    .seat-diagram.seats-n-9 .seat-table-top,
    .seat-diagram.seats-n-10 .seat-table-top,
    .seat-diagram.seats-n-11 .seat-table-top,
    .seat-diagram.seats-n-12 .seat-table-top { width: 58%; height: 32%; border-radius: 10px; }
    .seat-chair {
        position: absolute;
        width: 15px; height: 10px;
        border-radius: 4px 4px 3px 3px;
        transform: translate(-50%, -50%) rotate(var(--seat-rot, 0deg));
        background: #f5f5f4;
        border: 1.5px solid #a8a29e;
        box-sizing: border-box;
    }
    .table-pick-btn.table-status-available .seat-chair {
        background: #ecfdf5;
        border-color: #34d399;
    }
    .table-pick-btn.has-open-bill .seat-chair,
    .table-pick-btn.table-status-occupied .seat-chair,
    .seat-chair.is-filled {
        background: #f59e0b;
        border-color: #d97706;
    }

    .pos-left-actions-wrapper {
        min-width: 170px; max-width: 200px;
        align-self: stretch;
    }
    .pos-left-actions {
        display: flex; flex-direction: column; gap: 10px;
        position: sticky; top: 20px;
        max-height: calc(100vh - 100px);
    }
    .pos-left-action-btn {
        text-align: left; padding: 14px 14px; border-radius: 12px;
        border: 2px solid #e2e8f0; background: #fff; color: #44403c;
        font-weight: 600; font-size: 0.85rem; transition: all 0.15s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        white-space: nowrap;
    }
    .pos-left-action-btn:hover { border-color: #f59e0b; background: #fff7ed; color: #92400e; transform: translateX(4px); }
    .pos-left-action-btn i { color: #f59e0b; width: 18px; text-align: center; }
    .pos-mobile-actions { padding-bottom: 4px; }
    .pos-mobile-actions .pos-left-action-btn { font-size: 0.8rem; padding: 10px 12px; }

    .last-sale-panel {
        background: #1c1917;
        color: #fafaf9;
        border-radius: 14px;
        padding: 12px 12px 14px;
        cursor: pointer;
        border: 1px solid #292524;
        box-shadow: 0 8px 18px rgba(28, 25, 23, 0.18);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .last-sale-panel:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(28, 25, 23, 0.24);
    }
    .last-sale-panel .ls-label {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #a8a29e;
        margin-bottom: 4px;
    }
    .last-sale-panel .ls-invoice {
        font-size: 0.82rem;
        font-weight: 700;
        color: #fbbf24;
        margin-bottom: 2px;
    }
    .last-sale-panel .ls-amount {
        font-size: 1.05rem;
        font-weight: 800;
        color: #fff;
        margin-bottom: 8px;
        line-height: 1.15;
    }
    .last-sale-panel .ls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        font-size: 0.72rem;
        color: #a8a29e;
        margin-top: 4px;
    }
    .last-sale-panel .ls-row strong {
        color: #e7e5e4;
        font-weight: 700;
    }
    .last-sale-panel .ls-change strong {
        color: #34d399;
    }
    .last-sale-panel-mobile {
        padding: 10px 12px;
    }
    .last-sale-panel-mobile .ls-mobile-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .last-sale-panel-mobile .ls-amount {
        margin-bottom: 0;
        font-size: 1rem;
    }
    .last-sale-panel-mobile .ls-label span {
        color: #fbbf24;
        text-transform: none;
        letter-spacing: 0;
        font-weight: 700;
    }
    .last-sale-panel-mobile .ls-mobile-change {
        text-align: right;
        font-size: 0.7rem;
        color: #a8a29e;
    }
    .last-sale-panel-mobile .ls-mobile-change strong {
        display: block;
        color: #34d399;
        font-size: 0.9rem;
        font-weight: 800;
    }

    .customer-section {
        background: #fff;
        border-radius: 18px;
        padding: 0;
        border: 1px solid #e7e5e4;
        box-shadow: 0 8px 24px rgba(28, 25, 23, 0.05);
        overflow: hidden;
    }
    .delivery-summary { padding: 0; }
    .delivery-summary .ds-empty {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
    }
    .delivery-summary .ds-empty-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .delivery-summary .ds-empty-copy strong {
        font-size: 0.92rem;
        font-weight: 750;
        color: #1c1917;
    }
    .delivery-summary .ds-empty-copy span {
        font-size: 0.72rem;
        font-weight: 600;
        color: #a8a29e;
    }
    .delivery-summary .ds-add-btn {
        flex: 0 0 auto;
        border-radius: 11px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: none;
        color: #fff;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 8px 14px;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.28);
    }
    .delivery-summary .ds-add-btn:hover { filter: brightness(1.05); color: #fff; }
    .delivery-summary .ds-filled {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
    }
    .delivery-summary .ds-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .delivery-summary .ds-icon {
        width: 36px; height: 36px; border-radius: 11px;
        display: grid; place-items: center;
        background: #eff6ff; color: #2563eb;
        flex: 0 0 auto;
    }
    .customer-section:not(.is-delivery) .delivery-summary .ds-icon {
        background: #fff7ed; color: #ea580c;
    }
    .delivery-summary .ds-name {
        font-weight: 750;
        font-size: 0.9rem;
        color: #1c1917;
        line-height: 1.2;
    }
    .delivery-summary .ds-meta {
        font-size: 0.72rem;
        font-weight: 600;
        color: #78716c;
        margin-top: 2px;
    }
    .delivery-summary .ds-pay-badge {
        flex: 0 0 auto;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        padding: 5px 8px;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .delivery-summary .ds-pay-badge.is-cod {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .delivery-summary .ds-edit-btn {
        width: 34px; height: 34px;
        border-radius: 10px;
        border: 1px solid #e7e5e4;
        background: #fafaf9;
        color: #57534e;
        display: grid; place-items: center;
        padding: 0;
        flex: 0 0 auto;
    }
    .delivery-summary .ds-edit-btn:hover {
        background: #fff;
        border-color: #f59e0b;
        color: #c2410c;
    }

    .customer-section .cs-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f5f5f4;
    }
    .customer-section .cs-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 750;
        font-size: 0.95rem;
        color: #1c1917;
    }
    .customer-section .cs-title i {
        width: 28px; height: 28px; border-radius: 9px;
        display: grid; place-items: center;
        background: #fff7ed; color: #ea580c; font-size: 0.8rem;
    }
    .customer-section .cs-hint {
        font-size: 0.72rem;
        font-weight: 600;
        color: #a8a29e;
        letter-spacing: 0.02em;
    }
    .customer-section .cs-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #78716c;
        margin-bottom: 6px;
    }
    .customer-section .cs-label i { color: #f59e0b; width: 14px; text-align: center; }
    .customer-section .cs-row {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }
    .customer-section .cs-field { min-width: 0; }
    .customer-section .cs-input,
    .customer-section #deliveryAddress {
        border-radius: 12px !important;
        border: 1.5px solid #e7e5e4 !important;
        background: #fafaf9;
        resize: vertical;
        min-height: 72px;
        font-size: 0.92rem;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .customer-section .cs-input:focus,
    .customer-section #deliveryAddress:focus {
        background: #fff;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245,158,11,.15);
    }
    .customer-section .add-customer-btn {
        flex: 0 0 auto;
        width: 46px; height: 46px;
        border-radius: 12px;
        padding: 0;
        display: grid; place-items: center;
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        border: none;
        color: #fff;
        box-shadow: 0 6px 14px rgba(234,88,12,.28);
        margin: 0 0 2px;
    }
    .customer-section .add-customer-btn:hover { filter: brightness(1.05); color: #fff; }
    .customer-section .select2-container { width: 100% !important; }
    .customer-section.is-delivery .cs-title i { background: #eff6ff; color: #2563eb; }

    #deliveryDetailsModal .customer-section {
        background: transparent;
        border: 0;
        box-shadow: none;
        border-radius: 0;
        overflow: visible;
    }
    #deliveryDetailsModal .select2-container { z-index: 1060; }

    .delivery-details { margin-top: 4px; }
    .delivery-details .cs-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .delivery-partner-logos {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    @media (min-width: 576px) {
        .delivery-partner-logos {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    .dpl-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        text-align: center;
        border: 2px solid #e7e5e4;
        background: #fff;
        border-radius: 16px;
        padding: 14px 10px 12px;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s, transform .12s;
        min-height: 128px;
    }
    .dpl-card:hover {
        border-color: #fdba74;
        transform: translateY(-1px);
    }
    .dpl-card.active {
        border-color: #ea580c;
        box-shadow: 0 8px 20px rgba(234, 88, 12, 0.18);
        background: #fff7ed;
    }
    .dpl-logo {
        width: 72px;
        height: 72px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: #fafaf9;
        border: 1px solid #f5f5f4;
        flex: 0 0 auto;
    }
    .dpl-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .dpl-logo.dpl-own {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        font-size: 1.6rem;
        border: 0;
    }
    .dpl-logo.dpl-fallback {
        background: #1c1917;
        color: #fff;
        font-weight: 800;
        font-size: 1.5rem;
        border: 0;
    }
    .dpl-name {
        font-weight: 800;
        font-size: 0.92rem;
        color: #1c1917;
        line-height: 1.2;
    }
    .dpl-hint {
        font-size: 0.68rem;
        color: #78716c;
        font-weight: 600;
        line-height: 1.2;
    }

    .delivery-pay-seg {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 6px;
        border-radius: 14px;
        background: #f5f5f4;
        border: 1px solid #e7e5e4;
    }
    .delivery-pay-mode-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
        border: 0;
        background: transparent;
        border-radius: 11px;
        padding: 10px 12px;
        color: #57534e;
        transition: background .15s, color .15s, box-shadow .15s, transform .15s;
    }
    .delivery-pay-mode-btn .dpm-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: grid; place-items: center;
        background: #fff; color: #a8a29e;
        flex: 0 0 auto;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .delivery-pay-mode-btn .dpm-copy {
        display: flex; flex-direction: column; gap: 1px; min-width: 0;
    }
    .delivery-pay-mode-btn .dpm-copy strong {
        font-size: 0.86rem; font-weight: 750; line-height: 1.2;
    }
    .delivery-pay-mode-btn .dpm-copy small {
        font-size: 0.7rem; color: #a8a29e; font-weight: 600;
    }
    .delivery-pay-mode-btn:hover { background: rgba(255,255,255,.7); }
    .delivery-pay-mode-btn.active {
        background: #fff;
        color: #1c1917;
        box-shadow: 0 4px 14px rgba(28,25,23,.08);
    }
    .delivery-pay-mode-btn.active .dpm-icon {
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        color: #ea580c;
    }
    .delivery-pay-mode-btn[data-mode="cod"].active .dpm-icon {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #059669;
    }
    .delivery-pay-mode-btn[data-mode="partner"].active .dpm-icon {
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        color: #c2410c;
    }
    .delivery-pay-mode-btn.active .dpm-copy small { color: #78716c; }

    .cart-actions { align-items: center; }
    .cart-action-btn {
        width: 34px; height: 34px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        border: none; padding: 0;
        font-size: 0.85rem;
        transition: all 0.15s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    .cart-action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.12); }
    .cart-action-hold { background: #f59e0b; color: #fff; }
    .cart-action-hold-new {
        background: #ea580c; color: #fff;
        position: relative;
        width: 40px;
    }
    .cart-action-hold-new .cart-action-plus {
        position: absolute;
        right: 4px;
        top: 4px;
        font-size: 0.55rem;
        line-height: 1;
        background: #fff;
        color: #ea580c;
        border-radius: 50%;
        width: 11px;
        height: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cart-action-recall { background: #06b6d4; color: #fff; }
    .cart-action-reprint { background: #7c3aed; color: #fff; }
    .cart-action-clear { background: #ef4444; color: #fff; }
    .cart-action-close { background: #64748b; color: #fff; }

    .pos-numpad-toggle {
        font-weight: 700;
        white-space: nowrap;
    }
    body.pos-numpad-hidden .pos-cart-keypad-host,
    body.pos-numpad-hidden .pos-cart-keypad,
    body.pos-numpad-hidden .pos-shell--restaurant .cart-footer .keypad.rest-cart-keypad,
    body.pos-numpad-hidden .pos-shell--bakery .cart-footer .bakery-keypad-compact,
    body.pos-mode-restaurant.pos-numpad-hidden .pos-shell--restaurant .cart-footer .rest-cart-keypad,
    body.pos-mode-bakery.pos-numpad-hidden .pos-shell--bakery .cart-footer .bakery-keypad-compact {
        display: none !important;
    }

    .kot-select-card {
        padding: 16px 18px; border-radius: 14px; border: 2px solid #e2e8f0;
        background: #fff; cursor: pointer; margin-bottom: 12px;
        transition: all 0.15s ease;
    }
    .kot-select-card:hover { border-color: #f59e0b; background: #fff7ed; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(245,158,11,0.12); }
    .kot-edit-item { transition: all 0.15s ease; }
    .kot-edit-item:hover { border-color: #f59e0b !important; }
    .kot-qty-input { font-weight: 700; font-size: 1rem; }

    .bill-table-card.bill-ready { border-color: #16a34a; background: #f0fdf4; }
    .bill-table-card.selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 2px rgba(37,99,235,.2); }
    .bill-table-card {
        display: flex; align-items: center; justify-content: space-between;
        gap: 16px; padding: 16px 18px; border-radius: 14px;
        border: 2px solid #e2e8f0; background: #fff; cursor: pointer;
        user-select: none;
        transition: all 0.15s ease; margin-bottom: 12px;
    }
    .bill-table-card:hover { border-color: #f59e0b; background: #fff7ed; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(245,158,11,0.12); }
    .bill-table-card:active { transform: scale(0.99); }
    .bill-table-card .bt-name { font-size: 1.15rem; font-weight: 700; color: #292524; }
    .bill-table-card .bt-floor { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #f59e0b; margin-left: 6px; }
    .bill-table-card .bt-meta { font-size: 0.82rem; color: #78716c; margin-top: 4px; }
    .bill-table-card .bt-hint { font-size: 0.72rem; opacity: 0.85; }
    .bill-table-card .bt-kot-log {
        margin-top: 6px; font-size: 0.78rem; font-weight: 700;
        display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 8px;
    }
    .bill-table-card .bt-kot-log.printed { background: #dcfce7; color: #166534; }
    .bill-table-card .bt-kot-log.pending { background: #ffedd5; color: #9a3412; }
    .bill-table-card .bt-total { font-size: 1.2rem; font-weight: 700; color: #16a34a; }
    .bill-table-card .bt-cap { font-size: 0.75rem; color: #fff; background: #f59e0b; padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px; }
    .bill-table-card .bt-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
    .qty-btn-discount { color: #b45309; border-color: #fcd34d !important; background: #fffbeb !important; }
    .qty-btn-discount.has-discount { background: #f59e0b !important; color: #fff !important; border-color: #f59e0b !important; }

    /* Recent Orders — amber/stone theme */
    .recent-orders-modal {
        border: none; border-radius: 18px; overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(28, 25, 23, 0.35);
    }
    .recent-orders-header {
        background: #1c1917; border-bottom: 2px solid #f59e0b;
        padding: 18px 22px;
    }
    .recent-orders-table { font-size: 0.9rem; --bs-table-bg: transparent; }
    .recent-orders-table thead th {
        background: #fafaf9; color: #78716c; font-weight: 700; font-size: 0.72rem;
        text-transform: uppercase; letter-spacing: 0.04em;
        border-bottom: 1px solid #e7e5e4; padding: 12px 14px; white-space: nowrap;
    }
    .recent-orders-table tbody td {
        border-color: #f5f5f4; padding: 14px; vertical-align: middle;
    }
    .recent-orders-table tbody tr:hover { background: #fffbeb; }
    .ro-pill {
        display: inline-block; padding: 0.28rem 0.7rem; border-radius: 999px;
        font-size: 0.75rem; font-weight: 700; color: #fff; line-height: 1.2;
        text-transform: lowercase; white-space: nowrap;
    }
    .ro-order { background: #1c1917; text-transform: none; letter-spacing: 0.02em; }
    .ro-pill.type-dine { background: #16a34a; }
    .ro-pill.type-take { background: #d97706; }
    .ro-pill.type-del { background: #0891b2; }
    .ro-pill.type-exp { background: #78716c; }
    .ro-pill.pay-paid { background: #16a34a; }
    .ro-pill.pay-unpaid { background: #78716c; }
    .ro-customer { font-weight: 600; color: #292524; }
    .ro-table { font-size: 0.78rem; color: #a8a29e; margin-top: 2px; }
    .ro-total { font-weight: 800; color: #d97706; font-size: 0.98rem; white-space: nowrap; }
    .ro-actions {
        display: flex; flex-wrap: wrap; align-items: center; gap: 6px; max-width: 340px;
    }
    .ro-btn {
        border: none; border-radius: 8px; font-weight: 700; font-size: 0.78rem;
        padding: 0.35rem 0.65rem; line-height: 1.2; cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }
    .ro-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.12); }
    .ro-btn-view { background: #2563eb; color: #fff; padding: 0.4rem 0.55rem; }
    .ro-btn-open { background: #f59e0b; color: #fff; }
    .ro-btn-receipt { background: #16a34a; color: #fff; padding: 0.4rem 0.55rem; }
    .ro-btn-kot { background: #eab308; color: #1c1917; }
    .ro-btn-bot { background: #38bdf8; color: #0c4a6e; }
    .ro-btn-kitchen { background: #f5f5f4; color: #1c1917; border: 1px solid #d6d3d1; padding: 0.4rem 0.55rem; }

    .pos-fkey {
        font-size: 0.72em;
        font-weight: 700;
        opacity: 0.85;
        letter-spacing: 0.02em;
    }
    .cart-qty-input {
        width: 52px;
        min-width: 44px;
        text-align: center;
        border: 1.5px solid #e7e5e4;
        border-radius: 8px;
        padding: 4px 2px;
        font-size: 0.95rem;
        background: #fff;
        -moz-appearance: textfield;
    }
    .cart-qty-input:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
    }
    .cart-qty-input::-webkit-outer-spin-button,
    .cart-qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* ===== POS shell: restaurant vs bakery ===== */
    .bakery-workspace {
        display: flex;
        flex-direction: column;
        gap: 10px;
        height: calc(100vh - 64px);
        min-height: 0;
        max-height: calc(100vh - 64px);
        overflow: hidden;
    }
    .bakery-header {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: #fffdf9;
        border: 1px solid #e8dfd2;
        border-radius: 16px;
        padding: 8px 10px;
        box-shadow: 0 6px 18px rgba(59, 42, 34, 0.06);
    }
    .bakery-header-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }
    .bakery-header-tools {
        padding-bottom: 8px;
        border-bottom: 1px solid #efe6da;
    }
    .bakery-header-billing {
        align-items: stretch;
    }
    .bakery-order-types {
        flex: 0 0 auto;
    }
    .bakery-order-types .order-type-btn {
        flex: 0 0 auto;
        flex-direction: row;
        gap: 6px;
        padding: 8px 12px;
        min-height: 40px;
        font-size: 0.78rem;
        border-radius: 10px;
    }
    .bakery-order-types .order-type-btn i { font-size: 0.95rem; }
    .bakery-customer-slot {
        flex: 0 1 260px;
        min-width: 180px;
        margin: 0 !important;
    }
    .bakery-delivery-summary {
        margin: 0;
        height: 100%;
    }
    .bakery-delivery-summary .ds-empty,
    .bakery-delivery-summary .ds-filled {
        min-height: 40px;
        padding: 6px 10px;
    }
    .bakery-loyalty-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e8dfd2;
        background: #fff;
        color: #5c3d2e;
        border-radius: 10px;
        padding: 0 12px;
        font-size: 0.78rem;
        font-weight: 700;
        min-height: 40px;
        cursor: pointer;
    }
    .bakery-loyalty-chip:hover { border-color: #c49a3c; background: #fffaf0; }
    .bakery-search-wrap {
        flex: 1 1 220px;
        min-width: 180px;
        margin: 0 !important;
    }
    .bakery-search-wrap .search-input {
        min-height: 40px !important;
        padding: 8px 12px !important;
        font-size: 0.95rem !important;
        margin: 0;
    }

    .pos-shell {
        display: grid;
        gap: 16px;
        min-height: calc(100vh - 80px);
        align-items: stretch;
    }
    .bakery-workspace .pos-shell {
        flex: 1 1 auto;
        min-height: 0;
        height: auto;
        gap: 10px;
    }
    .pos-shell--restaurant {
        grid-template-columns: 280px minmax(0, 1fr) minmax(420px, 500px);
        align-items: stretch;
        gap: 10px;
    }
    @media (min-width: 992px) {
        .pos-shell--restaurant {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            max-height: 100%;
            overflow: hidden;
        }
    }
    .pos-shell--restaurant .pos-catalog {
        min-width: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e7e5e4;
        border-radius: 16px;
        padding: 10px;
        box-shadow: 0 6px 20px rgba(28, 25, 23, 0.06);
        height: 100%;
        min-height: 0;
    }
    .pos-shell--restaurant .pos-catalog-inner { margin: 0; height: 100%; min-height: 0; }
    .pos-shell--restaurant .pos-catalog-main {
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
    }
    .pos-shell--restaurant .pos-catalog-main > :not(#productsGrid) {
        flex-shrink: 0;
    }
    .pos-shell--restaurant .pos-cart-col {
        min-width: 0;
        max-width: 500px;
        height: 100%;
        max-height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        align-self: stretch;
        overflow: hidden;
    }
    .pos-shell--restaurant .cart-card {
        height: 100%;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }
    .pos-shell--restaurant .cart-header,
    .pos-shell--restaurant .open-bill-banner,
    .pos-shell--restaurant .cart-meta-slot,
    .pos-shell--restaurant .cart-footer {
        flex-shrink: 0;
    }
    .pos-shell--restaurant .cart-header {
        padding: 8px 12px;
    }
    .pos-shell--restaurant .cart-header h5 {
        font-size: 1rem;
    }
    .pos-shell--restaurant .cart-meta-slot {
        padding: 8px 12px !important;
    }
    .pos-shell--restaurant .cart-meta-slot .cart-meta-row {
        gap: 5px;
    }
    .pos-shell--restaurant .cart-meta-slot .select-table-btn {
        padding: 7px 8px;
        font-size: 0.78rem;
        border-radius: 10px;
    }
    .pos-shell--restaurant .cart-meta-slot .form-select-modern {
        padding: 6px 8px;
        font-size: 0.78rem;
        border-radius: 10px;
    }
    .pos-shell--restaurant .cart-meta-slot .form-label {
        margin-bottom: 0.15rem !important;
        font-size: 0.68rem;
    }
    .pos-shell--restaurant .cart-meta-slot .transfer-table-btn {
        padding: 7px 5px;
        font-size: 0.68rem;
        border-radius: 10px;
    }
    .pos-shell--restaurant #tableSelectContainer {
        margin-bottom: 0 !important;
    }
    .pos-shell--restaurant .cart-body {
        flex: 1;
        min-height: 0;
        max-height: none;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
        display: flex;
        flex-direction: column;
    }
    .pos-shell--restaurant .cart-body:has(#emptyCart) {
        flex: 1;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }
    .pos-shell--restaurant #emptyCart {
        padding: 0.75rem 0.85rem !important;
        margin: 8px 10px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
        width: calc(100% - 20px);
    }
    .pos-shell--restaurant #emptyCart i {
        font-size: 1.35rem !important;
        margin-bottom: 0.35rem !important;
        color: #cbd5e1;
        opacity: 1 !important;
    }
    .pos-shell--restaurant #emptyCart p {
        color: #94a3b8;
        font-weight: 600;
    }
    .pos-shell--restaurant .cart-item {
        padding: 6px 10px;
        flex-shrink: 0;
    }
    .pos-shell--restaurant .cart-footer {
        margin-top: 0;
        padding: 8px 12px;
    }
    .pos-shell--restaurant .cart-footer .mb-3 {
        margin-bottom: 0.4rem !important;
    }
    .pos-shell--restaurant .cart-footer .mb-2 {
        margin-bottom: 0.2rem !important;
    }
    .pos-shell--restaurant .cart-footer .pt-2 {
        padding-top: 0.3rem !important;
    }
    .pos-shell--restaurant .cart-footer .fs-5 {
        font-size: 1.05rem !important;
    }
    .pos-shell--restaurant .cart-footer .total-display {
        font-size: 1.2rem;
    }
    .pos-shell--restaurant .cart-footer .btn-secondary {
        padding: 0.28rem 0.5rem;
        font-size: 0.85rem;
    }
    .pos-shell--restaurant .cart-footer .pos-btn {
        min-height: 38px !important;
        font-size: 0.88rem;
        border-radius: 10px;
    }
    .pos-shell--restaurant .cart-footer .d-flex.gap-2 {
        flex-wrap: nowrap;
    }
    .pos-shell--restaurant .cart-footer .d-flex.gap-2 .pos-btn,
    .pos-shell--restaurant .cart-footer .d-flex.gap-2 .btn {
        flex: 1 1 0;
        min-width: 0;
    }
    .pos-shell--restaurant .cart-footer .open-bills-btn {
        min-height: 34px !important;
    }
    .pos-shell--restaurant .cart-footer .keypad.rest-cart-keypad {
        gap: 8px !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        grid-template-rows: none !important;
        grid-auto-rows: auto !important;
    }
    .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn {
        aspect-ratio: unset !important;
        width: 100% !important;
        height: clamp(42px, 5.2vh, 52px) !important;
        min-height: 42px !important;
        max-height: 52px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: clamp(1.15rem, 2.2vh, 1.45rem) !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        border-radius: 12px !important;
        border: 1px solid #d6d3d1 !important;
        color: #1c1917 !important;
        background: linear-gradient(180deg, #ffffff 0%, #f5f5f4 100%) !important;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.95),
            0 2px 0 #a8a29e,
            0 4px 10px rgba(28, 25, 23, 0.12) !important;
    }
    .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn:active {
        transform: translateY(2px);
        box-shadow:
            inset 0 2px 6px rgba(28, 25, 23, 0.16),
            0 1px 0 #78716c !important;
    }
    .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn.warning {
        border-color: #fdba74 !important;
        color: #9a3412 !important;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%) !important;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            0 2px 0 #ea580c,
            0 4px 10px rgba(234, 88, 12, 0.22) !important;
    }
    @media (min-width: 992px) and (max-height: 800px) {
        .pos-shell--restaurant .cart-header { padding: 6px 10px; }
        .pos-shell--restaurant .cart-meta-slot { padding: 6px 10px !important; }
        .pos-shell--restaurant .cart-footer { padding: 6px 10px; }
        .pos-shell--restaurant .cart-footer .keypad.rest-cart-keypad {
            gap: 6px !important;
        }
        .pos-shell--restaurant .cart-footer .rest-cart-keypad .touch-btn {
            height: clamp(36px, 4.6vh, 44px) !important;
            min-height: 36px !important;
            max-height: 44px !important;
            font-size: 1.15rem !important;
            border-radius: 10px !important;
        }
        .pos-shell--restaurant .cart-footer .pos-btn { min-height: 34px !important; font-size: 0.82rem; }
        .pos-shell--restaurant .cart-footer .mb-3 { margin-bottom: 0.28rem !important; }
    }
    .pos-shell--restaurant #productsGrid {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        align-content: start;
        display: grid !important;
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        gap: 10px;
    }
    .pos-shell--restaurant .product-img { height: 82px; }
    .pos-shell--restaurant .product-name { font-size: 0.8rem; min-height: 2.2em; }
    .pos-shell--restaurant .product-info { padding: 8px 10px; }
    .pos-shell--restaurant #orderTypeGroup {
        gap: 8px;
        margin-bottom: 10px !important;
    }
    .pos-shell--restaurant .order-type-btn {
        padding: 8px 6px;
        gap: 4px;
        border-radius: 10px;
        font-size: 0.75rem;
    }
    .pos-shell--restaurant .order-type-btn i {
        font-size: 1.05rem;
    }
    .pos-shell--restaurant .search-input {
        padding: 9px 12px;
        font-size: 0.92rem;
        border-radius: 10px;
    }

    .restaurant-cat-rail {
        background: linear-gradient(180deg, #1c1917 0%, #292524 100%);
        border-radius: 16px;
        padding: 12px 10px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(28, 25, 23, 0.18);
        height: 100%;
        min-height: 0;
        min-width: 280px;
    }
    .restaurant-cat-title {
        color: #fbbf24;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 0 8px 12px;
        border-bottom: 1px solid rgba(251, 191, 36, 0.2);
        margin-bottom: 10px;
    }
    .restaurant-cat-list {
        flex: 1 1 auto;
        min-height: 0;
        margin-bottom: 0;
        padding-right: 2px;
        scrollbar-width: thin;
        scrollbar-color: rgba(251, 191, 36, 0.45) transparent;
    }
    .restaurant-cat-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-auto-rows: minmax(100px, 1fr);
        align-content: start;
        gap: 8px;
        overflow-y: auto;
        overflow-x: hidden;
        height: 100%;
    }
    .restaurant-cat-grid[data-count="1"],
    .restaurant-cat-grid[data-count="2"],
    .restaurant-cat-grid[data-count="3"] {
        grid-template-columns: 1fr;
        height: auto;
        max-height: 100%;
        align-content: start;
    }
    .restaurant-cat-grid[data-count="1"] {
        grid-auto-rows: 128px;
    }
    .restaurant-cat-grid[data-count="2"],
    .restaurant-cat-grid[data-count="3"] {
        grid-auto-rows: 112px;
    }
    .restaurant-cat-grid[data-count="1"] .category-btn {
        height: 128px;
        max-height: 128px;
    }
    .restaurant-cat-grid[data-count="2"] .category-btn,
    .restaurant-cat-grid[data-count="3"] .category-btn {
        height: 112px;
        max-height: 112px;
    }
    .restaurant-cat-grid[data-count="5"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="7"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="9"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="11"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="13"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="15"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="17"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="19"] .category-btn:last-child,
    .restaurant-cat-grid[data-count="21"] .category-btn:last-child {
        grid-column: 1 / -1;
    }
    .restaurant-cat-grid .category-btn {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 88px;
        display: block;
        border-radius: 12px;
        border: 2px solid transparent;
        background: rgba(255,255,255,0.06);
        color: #fafaf9;
        padding: 0;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1);
        text-align: left;
    }
    .restaurant-cat-img,
    .restaurant-cat-placeholder {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border-radius: 0;
        object-fit: cover;
        margin: 0;
        border: none;
        background: rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fbbf24;
        font-size: 1.4rem;
    }
    .restaurant-cat-text {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        z-index: 2;
        display: flex;
        width: 100%;
        align-items: flex-end;
        justify-content: space-between;
        gap: 4px;
        padding: 16px 6px 6px;
        background: linear-gradient(180deg, transparent 0%, rgba(28, 25, 23, 0.78) 55%, rgba(28, 25, 23, 0.94) 100%);
        color: #fff;
        pointer-events: none;
    }
    .restaurant-cat-name {
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        white-space: normal;
        text-overflow: ellipsis;
        font-size: 0.74rem;
        font-weight: 800;
        line-height: 1.15;
        text-shadow: 0 1px 2px rgba(0,0,0,0.45);
        flex: 1 1 auto;
        min-width: 0;
    }
    .restaurant-cat-grid .category-btn small {
        font-size: 0.65rem !important;
        opacity: 1;
        font-weight: 700;
        background: rgba(0,0,0,0.4);
        border-radius: 999px;
        padding: 2px 6px;
        flex-shrink: 0;
        color: #fff;
        align-self: flex-end;
    }
    .restaurant-cat-grid .category-btn:hover {
        border-color: rgba(245, 158, 11, 0.55);
        transform: translateY(-1px);
    }
    .restaurant-cat-grid .category-btn.active {
        background: rgba(255,255,255,0.08) !important;
        color: #fafaf9 !important;
        border-color: #fbbf24;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.45), 0 8px 18px rgba(245, 158, 11, 0.25);
    }

    .restaurant-toolbar {
        display: flex;
        align-items: stretch;
        gap: 10px;
        margin-bottom: 10px;
        flex-shrink: 0;
    }
    .restaurant-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        flex: 1 1 auto;
        min-width: 0;
    }
    .restaurant-tool-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e7e5e4;
        background: #fff;
        color: #44403c;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.78rem;
        font-weight: 700;
        min-height: 40px;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .restaurant-tool-btn i { color: #f59e0b; }
    .restaurant-tool-btn:hover {
        border-color: #f59e0b;
        background: #fff7ed;
        color: #92400e;
    }
    .restaurant-last-sale {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #1c1917;
        color: #fafaf9;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.78rem;
        cursor: pointer;
        border: 1px solid #292524;
        flex-shrink: 0;
        white-space: nowrap;
    }
    .restaurant-last-sale .rls-label {
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #a8a29e;
    }
    .restaurant-last-sale strong { color: #fbbf24; }
    .restaurant-last-sale .rls-change { color: #d6d3d1; }
    .restaurant-last-sale .rls-change strong { color: #86efac; }

    @media (max-width: 1199.98px) {
        .pos-shell--restaurant {
            grid-template-columns: 250px minmax(0, 1fr) minmax(380px, 440px);
        }
        .restaurant-cat-rail { min-width: 250px; }
        .pos-shell--restaurant #productsGrid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }
    }

    .pos-shell--bakery {
        --bakery-ink: #3b2a22;
        --bakery-cocoa: #5c3d2e;
        --bakery-butter: #c49a3c;
        --bakery-flour: #f7f3ec;
        --bakery-paper: #fffdf9;
        --bakery-line: #e8dfd2;
        grid-template-columns: 250px minmax(0, 1fr) minmax(340px, 400px);
        gap: 10px;
    }
    body.pos-mode-bakery {
        background:
            radial-gradient(1000px 420px at 8% -10%, rgba(196, 154, 60, 0.16), transparent 55%),
            radial-gradient(800px 360px at 92% 0%, rgba(92, 61, 46, 0.08), transparent 50%),
            linear-gradient(180deg, #faf7f2 0%, #f0ebe3 100%);
    }
    body.pos-mode-bakery .pos-container { padding: 12px 14px; }
    body.pos-mode-bakery .pos-topbar {
        background: linear-gradient(135deg, #3b2a22, #5c3d2e);
        border-bottom-color: #c49a3c;
    }
    body.pos-mode-bakery .pos-topbar .brand { color: #f5e6c8; }

    .bakery-cat-rail {
        background: linear-gradient(180deg, #3b2a22 0%, #5c3d2e 100%);
        border-radius: 18px;
        padding: 16px 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(59, 42, 34, 0.18);
        animation: bakeryRailIn 0.35s ease both;
        height: 100%;
        min-height: 0;
        min-width: 250px;
    }
    @keyframes bakeryRailIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: none; }
    }
    .bakery-cat-title {
        color: #f5e6c8;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 0 8px 12px;
        border-bottom: 1px solid rgba(245, 230, 200, 0.15);
        margin-bottom: 10px;
    }
    .bakery-cat-list {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        gap: 8px;
        overflow-y: auto;
        overflow-x: hidden;
        margin-bottom: 0;
        padding-right: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgba(245, 230, 200, 0.45) transparent;
    }
    .bakery-cat-list::-webkit-scrollbar {
        width: 6px;
    }
    .bakery-cat-list::-webkit-scrollbar-thumb {
        background: rgba(245, 230, 200, 0.4);
        border-radius: 999px;
    }
    .bakery-cat-list .category-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        border-radius: 14px;
        border: 1px solid transparent;
        background: rgba(255,255,255,0.06);
        color: #f5e6c8;
        padding: 10px 12px;
        font-size: 0.95rem;
        font-weight: 650;
        text-align: left;
        min-height: 64px;
    }
    .bakery-cat-img {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(245, 230, 200, 0.2);
    }
    .bakery-cat-text {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }
    .bakery-cat-text > span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.25;
        font-size: 0.95rem;
    }
    .bakery-cat-list .category-btn small {
        font-size: 0.75rem;
        opacity: 0.7;
        font-weight: 700;
        background: rgba(0,0,0,0.18);
        border-radius: 999px;
        padding: 3px 9px;
        flex-shrink: 0;
    }
    .bakery-cat-list .category-btn:hover {
        background: rgba(196, 154, 60, 0.22);
        border-color: rgba(196, 154, 60, 0.35);
        color: #fff;
    }
    .bakery-cat-list .category-btn.active {
        background: linear-gradient(135deg, #c49a3c, #a67c2a);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 6px 16px rgba(196, 154, 60, 0.35);
    }

    .pos-shell--bakery .pos-catalog {
        min-width: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: var(--bakery-paper);
        border: 1px solid var(--bakery-line);
        border-radius: 18px;
        padding: 10px;
        box-shadow: 0 8px 24px rgba(59, 42, 34, 0.06);
        animation: bakeryFadeUp 0.35s ease 0.05s both;
        height: 100%;
        min-height: 0;
    }
    @keyframes bakeryFadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: none; }
    }
    .pos-shell--bakery .pos-catalog-inner { margin: 0; height: 100%; min-height: 0; }
    .pos-shell--bakery .pos-catalog-main {
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        padding: 0 !important;
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) #productsGrid {
        flex: 1;
        overflow-y: auto;
        padding: 2px 2px 8px;
        grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
        gap: 12px;
        align-content: start;
        height: 100%;
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) .product-card {
        border-radius: 18px;
        border: 1px solid var(--bakery-line);
        box-shadow: 0 2px 10px rgba(59, 42, 34, 0.05);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) .product-card:hover {
        border-color: #c49a3c;
        box-shadow: 0 12px 28px rgba(92, 61, 46, 0.14);
        transform: translateY(-3px);
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) .product-img {
        height: 128px;
        object-fit: cover;
        background: var(--bakery-flour);
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) .product-name {
        color: var(--bakery-ink);
        font-weight: 700;
        font-size: 0.9rem;
    }
    .pos-shell--bakery:not(.pos-shell--ice-cream) .product-price {
        background: linear-gradient(135deg, #5c3d2e, #3b2a22);
        border-radius: 10px;
        padding: 5px 12px;
    }
    .bakery-header .search-input:focus {
        border-color: #c49a3c;
        box-shadow: 0 0 0 4px rgba(196, 154, 60, 0.15);
    }
    .bakery-order-types .order-type-btn.active.takeaway {
        background: linear-gradient(135deg, #c49a3c, #a67c2a);
        color: #fff;
        border-color: transparent;
    }

    .bakery-print-actions,
    .bakery-print-toggles {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .bakery-tool-btn,
    .bakery-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid var(--bakery-line, #e8dfd2);
        background: #fff;
        color: var(--bakery-cocoa, #5c3d2e);
        border-radius: 10px;
        padding: 7px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        min-height: 36px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .bakery-tool-btn:hover,
    .bakery-toggle:hover {
        border-color: #c49a3c;
        background: #fffaf0;
    }
    .bakery-tool-btn i { color: #c49a3c; }
    .bakery-toggle.is-on {
        background: linear-gradient(135deg, #5c3d2e, #3b2a22);
        color: #fff;
        border-color: transparent;
    }
    .bakery-toggle.is-on i { color: #f5e6c8; }
    .bakery-last-sale {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #3b2a22;
        color: #f5e6c8;
        border-radius: 12px;
        padding: 6px 12px;
        cursor: pointer;
        font-size: 0.76rem;
        min-height: 36px;
        transition: transform 0.15s ease;
    }
    .bakery-last-sale:hover { transform: translateY(-1px); }
    .bakery-last-sale .bls-label {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.7;
        font-weight: 800;
        font-size: 0.65rem;
    }
    .bakery-last-sale strong { color: #fff; }
    .bakery-last-sale .bls-change { opacity: 0.85; }

    .pos-shell--bakery .pos-cart-col {
        min-width: 0;
        min-height: 0;
        height: 100%;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        align-self: stretch;
        animation: bakeryFadeUp 0.35s ease 0.1s both;
    }
    .pos-shell--bakery .cart-card {
        height: 100%;
        max-height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        border: 1px solid var(--bakery-line);
        box-shadow: 0 12px 32px rgba(59, 42, 34, 0.1);
        overflow: hidden;
        background: #fffdf9;
    }
    .pos-shell--bakery .cart-header,
    .pos-shell--bakery .open-bill-banner,
    .pos-shell--bakery .cart-meta-slot,
    .pos-shell--bakery .cart-footer {
        flex-shrink: 0;
    }
    .pos-shell--bakery .cart-header {
        background: linear-gradient(135deg, #5c3d2e, #3b2a22);
        border-radius: 18px 18px 0 0;
        padding: 10px 12px;
    }
    .pos-shell--bakery .cart-header h5 {
        font-size: 1rem;
        font-weight: 750;
    }
    .pos-shell--bakery .cart-header .badge {
        background: #2563eb !important;
        color: #fff !important;
        border-radius: 999px;
        min-width: 1.55rem;
        font-weight: 800;
    }
    .pos-shell--bakery .cart-actions .cart-action-btn {
        width: 34px;
        height: 34px;
        min-width: 34px;
        padding: 0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .pos-shell--bakery .cart-body {
        flex: 1;
        min-height: 0;
        max-height: none;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        touch-action: pan-y;
        background: #fff;
    }
    .pos-shell--bakery .cart-body:has(#emptyCart) {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .pos-shell--bakery #emptyCart {
        padding: 1rem 0.75rem !important;
    }
    .pos-shell--bakery #emptyCart i {
        font-size: 2.4rem !important;
        opacity: 0.22;
    }
    .pos-shell--bakery .cart-footer {
        background: #fffdf9;
        border-top: 1px solid var(--bakery-line);
        padding: 8px 10px 10px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-height: 0;
    }
    .pos-shell--bakery .cart-summary-block {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .pos-shell--bakery .cart-summary-block .mb-2 {
        margin-bottom: 0.15rem !important;
        font-size: 0.82rem;
    }
    .pos-shell--bakery .cart-total-row {
        margin: 4px 0 2px !important;
        padding: 8px 10px !important;
        border: none !important;
        border-radius: 10px;
        background: linear-gradient(135deg, #5c3d2e, #3b2a22);
        color: #fff;
        align-items: center;
    }
    .pos-shell--bakery .cart-total-row .fs-5 {
        font-size: 0.95rem !important;
        font-weight: 800;
        color: #f5e6c8;
    }
    .pos-shell--bakery .cart-total-row .total-display {
        color: #fff !important;
        font-size: 1.15rem;
        font-weight: 800;
    }
    .pos-shell--bakery .total-display { color: #5c3d2e; }
    .pos-shell--bakery .cart-utility-row .btn {
        min-height: 34px;
        padding: 4px 8px;
        font-size: 0.8rem;
        font-weight: 700;
        border-radius: 10px;
    }
    .pos-shell--bakery .pay-btn {
        background: linear-gradient(135deg, #2f9e6b, #1f7a52);
        min-height: 48px;
        font-size: 1rem;
        font-weight: 800;
        border-radius: 12px;
    }
    .pos-shell--bakery .cart-pay-row {
        margin-bottom: 0 !important;
    }
    .pos-shell--bakery .cart-pay-row .pos-btn {
        min-height: 48px;
    }
    .bakery-pay-methods { margin-top: 0; }
    .bakery-pay-label {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #8a7466;
        margin-bottom: 4px;
    }
    .bakery-pay-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
    .bakery-pay-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        aspect-ratio: auto;
        border: none;
        background: linear-gradient(160deg, #ffffff 0%, #f8fafc 50%, #e8e0d4 100%);
        border-radius: 10px;
        padding: 6px 2px;
        font-size: 0.68rem;
        font-weight: 800;
        color: var(--bakery-ink);
        min-height: 42px;
        max-height: 48px;
        cursor: pointer;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.95),
            0 3px 0 #c4b5a5,
            0 4px 8px rgba(59, 42, 34, 0.1);
        transition: transform 0.08s ease, box-shadow 0.08s ease;
    }
    .bakery-pay-btn i { font-size: 0.95rem; }
    .bakery-pay-btn:hover {
        transform: none;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.95),
            0 3px 0 #c4b5a5,
            0 4px 8px rgba(59, 42, 34, 0.1);
    }
    .bakery-pay-btn.cash i { color: #2f9e6b; }
    .bakery-pay-btn.card i { color: #c49a3c; }
    .bakery-pay-btn.bank i { color: #0e7490; }
    .bakery-pay-btn.credit i { color: #c2410c; }
    .bakery-pay-btn:active {
        transform: translateY(2px);
        box-shadow: inset 0 2px 6px rgba(59,42,34,0.16), 0 1px 0 #a89080;
    }
    /* Box-shaped 3-column cart keypad — tall keys, no square overflow */
    .pos-shell--bakery .bakery-keypad-compact {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 0.4rem !important;
    }
    .pos-shell--bakery .bakery-keypad-compact .touch-btn {
        min-height: 42px !important;
        height: clamp(42px, 5.2vh, 52px) !important;
        max-height: 52px !important;
        aspect-ratio: auto !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: clamp(1.15rem, 2.2vh, 1.45rem);
        font-weight: 800;
        line-height: 1;
        border-radius: 12px;
        border: 1px solid #e8dfd2;
        color: #3b2a22;
        background: linear-gradient(180deg, #ffffff 0%, #f7f3ec 100%);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.95),
            0 2px 0 #c4b5a5,
            0 4px 10px rgba(59, 42, 34, 0.12);
        padding: 0;
    }
    .pos-shell--bakery .bakery-keypad-compact .touch-btn.warning {
        border-color: #e8b86d;
        color: #7c2d12;
        background: linear-gradient(180deg, #fff7ed 0%, #fde68a 100%);
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.8),
            0 2px 0 #d97706,
            0 4px 10px rgba(217, 119, 6, 0.22);
    }
    .pos-shell--bakery .bakery-secondary-actions .pos-btn {
        min-height: 38px;
        font-size: 0.82rem;
        border-radius: 10px;
    }

    @media (min-width: 992px) {
        .pos-shell--bakery {
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
            overflow: hidden;
            height: 100%;
        }
    }
    @media (max-width: 1440px) and (min-width: 992px) {
        .pos-shell--bakery .cart-footer { padding: 6px 8px 8px; gap: 4px; }
        .pos-shell--bakery .bakery-keypad-compact .touch-btn {
            height: clamp(38px, 4.8vh, 48px) !important;
            min-height: 38px !important;
            max-height: 48px !important;
            font-size: 1.2rem;
        }
        .pos-shell--bakery .pay-btn,
        .pos-shell--bakery .cart-pay-row .pos-btn { min-height: 42px; font-size: 0.92rem; }
        .pos-shell--bakery .bakery-secondary-actions .pos-btn { min-height: 34px; }
        .bakery-pay-btn { min-height: 36px; max-height: 40px; font-size: 0.62rem; }
    }

    @media (max-width: 1199.98px) {
        .pos-shell--bakery {
            grid-template-columns: 220px minmax(0, 1fr) minmax(300px, 340px);
        }
        .bakery-cat-rail { min-width: 220px; }
        .pos-shell--bakery .product-img { height: 110px; }
    }
    @media (max-width: 991.98px) {
        .bakery-workspace {
            height: auto;
            min-height: 0;
        }
        .pos-shell--restaurant,
        .pos-shell--bakery {
            grid-template-columns: 1fr;
            height: auto;
            min-height: 0;
        }
        .restaurant-cat-rail,
        .bakery-cat-rail {
            max-height: none;
            border-radius: 14px;
            padding: 10px;
        }
        .restaurant-cat-list,
        .restaurant-cat-grid,
        .bakery-cat-list {
            flex-direction: row;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 4px;
        }
        .restaurant-cat-grid {
            display: flex !important;
            grid-template-columns: unset !important;
        }
        .restaurant-cat-grid .category-btn,
        .bakery-cat-list .category-btn {
            width: auto;
            flex: 0 0 auto;
            white-space: nowrap;
            min-width: 120px;
            min-height: 72px;
        }
        .restaurant-toolbar {
            flex-direction: column;
        }
        .restaurant-last-sale { width: 100%; justify-content: space-between; }
        .restaurant-last-sale { display: none; }
        .pos-shell--restaurant .pos-catalog { max-height: none; overflow: visible; height: auto; }
        .pos-shell--restaurant .pos-catalog-main { overflow: visible; height: auto; }
        .pos-shell--restaurant #productsGrid { overflow: visible; height: auto; grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
        .bakery-last-sale { width: 100%; margin-left: 0; justify-content: space-between; }
        .bakery-customer-slot { flex: 1 1 100%; }
        .pos-shell--bakery .pos-catalog { max-height: none; overflow: visible; height: auto; }
        .pos-shell--bakery .pos-catalog-main { overflow: visible; height: auto; }
        .pos-shell--bakery #productsGrid { overflow: visible; height: auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .bakery-cat-rail,
        .pos-shell--bakery .pos-catalog,
        .pos-shell--bakery .pos-cart-col { animation: none; }
    }

    /* ===== Ice Cream shop UI (10″ tablet) — 2-col cats · 4-col products · bakery cart ===== */
    body.pos-mode-ice-cream {
        background:
            radial-gradient(900px 420px at 6% -8%, rgba(244, 114, 182, 0.18), transparent 55%),
            radial-gradient(780px 360px at 94% 0%, rgba(56, 189, 248, 0.14), transparent 50%),
            linear-gradient(180deg, #fdf2f8 0%, #e0f2fe 55%, #f0f9ff 100%);
    }
    body.pos-mode-ice-cream .pos-container { padding: 8px 10px; }
    body.pos-mode-ice-cream .pos-topbar {
        background: linear-gradient(135deg, #9d174d, #db2777 45%, #0891b2);
        border-bottom-color: #f9a8d4;
        padding: 8px 12px;
    }
    body.pos-mode-ice-cream .pos-topbar .brand { color: #fce7f3; }

    .pos-shell--ice-cream {
        --ice-ink: #831843;
        --ice-mint: #0891b2;
        --ice-berry: #db2777;
        --ice-cream: #fff7fb;
        --ice-line: #fbcfe8;
        /* Match QBakery proportions: cats | 4×4 products | wider cart (no keypad) */
        grid-template-columns: minmax(240px, 280px) minmax(0, 1fr) minmax(400px, 480px) !important;
        gap: 10px;
    }
    @media (min-width: 992px) {
        body.pos-mode-ice-cream .bakery-workspace {
            display: grid;
            grid-template-columns: minmax(240px, 280px) minmax(0, 1fr) minmax(400px, 480px);
            grid-template-rows: auto minmax(0, 1fr);
            align-items: stretch;
            gap: 10px;
        }
        body.pos-mode-ice-cream .bakery-workspace > .bakery-header {
            grid-column: 1 / 3;
            grid-row: 1;
        }
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream {
            display: contents;
        }
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .bakery-cat-rail {
            grid-column: 1;
            grid-row: 2;
            min-width: 0;
            width: 100%;
        }
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .pos-catalog {
            grid-column: 2;
            grid-row: 2;
            min-width: 0;
        }
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .pos-cart-col {
            grid-column: 3;
            grid-row: 1 / 3;
            min-width: 0;
            width: 100%;
            display: block !important;
        }
    }
    .pos-shell--ice-cream .ice-cat-rail {
        background: linear-gradient(180deg, #9d174d 0%, #be185d 45%, #0e7490 140%);
        border-radius: 16px;
        padding: 10px 8px;
        min-width: 0;
        width: 100%;
        box-shadow: 0 10px 28px rgba(157, 23, 77, 0.22);
    }
    .pos-shell--ice-cream .bakery-cat-title {
        color: #fce7f3;
        border-bottom-color: rgba(252, 231, 243, 0.2);
        padding-bottom: 8px;
        margin-bottom: 8px;
        font-size: 0.72rem;
    }
    .pos-shell--ice-cream .ice-cat-list {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-auto-rows: 1fr;
        align-content: stretch;
        gap: 8px;
        overflow: hidden;
        flex-direction: unset;
        height: 100%;
        padding-right: 0;
    }
    .pos-shell--ice-cream .ice-cat-list[data-count="1"],
    .pos-shell--ice-cream .ice-cat-list[data-count="2"],
    .pos-shell--ice-cream .ice-cat-list[data-count="3"] {
        grid-template-columns: 1fr;
    }
    .pos-shell--ice-cream .ice-cat-list[data-count="5"] .category-btn:last-child,
    .pos-shell--ice-cream .ice-cat-list[data-count="7"] .category-btn:last-child {
        grid-column: 1 / -1;
    }
    .pos-shell--ice-cream .ice-cat-list .category-btn {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 0;
        display: block;
        border-radius: 14px;
        border: 2px solid transparent;
        background: rgba(255,255,255,0.1);
        color: #fff;
        padding: 0;
        overflow: hidden;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.12);
    }
    .pos-shell--ice-cream .bakery-cat-img,
    .pos-shell--ice-cream .bakery-cat-placeholder {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border-radius: 0;
        object-fit: cover;
        margin: 0;
        border: none;
        background: rgba(255,255,255,0.12);
    }
    .pos-shell--ice-cream .bakery-cat-placeholder {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fce7f3;
        font-size: 1.7rem;
        background: linear-gradient(160deg, rgba(255,255,255,0.16), rgba(8,145,178,0.25));
    }
    .pos-shell--ice-cream .bakery-cat-text {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        z-index: 2;
        display: flex;
        width: 100%;
        align-items: flex-end;
        justify-content: space-between;
        gap: 4px;
        padding: 18px 6px 7px;
        background: linear-gradient(180deg, transparent 0%, rgba(76, 5, 40, 0.78) 55%, rgba(76, 5, 40, 0.94) 100%);
        color: #fff;
        pointer-events: none;
    }
    .pos-shell--ice-cream .bakery-cat-name {
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        white-space: normal;
        text-overflow: ellipsis;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.15;
        text-shadow: 0 1px 2px rgba(0,0,0,0.45);
        max-width: 100%;
        flex: 1 1 auto;
        min-width: 0;
    }
    .pos-shell--ice-cream .bakery-cat-count {
        font-size: 0.68rem !important;
        font-weight: 700;
        background: rgba(0,0,0,0.4);
        border-radius: 999px;
        padding: 2px 6px;
        flex-shrink: 0;
        color: #fff;
        opacity: 1;
        align-self: flex-end;
    }
    .pos-shell--ice-cream .ice-cat-list .category-btn:hover {
        border-color: rgba(249, 168, 212, 0.75);
        transform: translateY(-1px);
    }
    .pos-shell--ice-cream .ice-cat-list .category-btn.active {
        border-color: #fce7f3;
        box-shadow: 0 0 0 2px rgba(244, 114, 182, 0.55), 0 10px 22px rgba(219, 39, 119, 0.28);
    }

    .pos-shell--ice-cream .pos-catalog {
        background: var(--ice-cream);
        border: 1px solid var(--ice-line);
        border-radius: 16px;
        padding: 8px;
        max-width: 100%;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream #productsGrid.product-grid {
        display: grid !important;
        flex: 1 1 auto !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        grid-template-rows: none !important;
        grid-auto-rows: calc((100% - 21px) / 4) !important;
        gap: 7px !important;
        align-content: start !important;
        height: 100% !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        padding: 2px;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-item {
        min-height: 0 !important;
        height: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        display: flex !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-item.is-filtered-out,
    #productsGrid .product-item.is-filtered-out {
        display: none !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-card {
        position: relative !important;
        border-radius: 12px !important;
        border: 2px solid var(--ice-line) !important;
        overflow: hidden !important;
        aspect-ratio: unset !important;
        background: #9d174d !important;
        min-height: 0 !important;
        height: 100% !important;
        width: 100% !important;
        flex: 1 !important;
        box-shadow: 0 2px 8px rgba(157, 23, 77, 0.1) !important;
        display: block !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-card:hover {
        border-color: #db2777 !important;
        box-shadow: 0 10px 22px rgba(219, 39, 119, 0.18) !important;
        transform: translateY(-2px);
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-img {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        max-height: none !important;
        object-fit: cover !important;
        background: #be185d !important;
        display: block !important;
        border-radius: 0 !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-info {
        position: absolute !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        top: auto !important;
        z-index: 2 !important;
        padding: 18px 6px 6px !important;
        text-align: left !important;
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: flex-end !important;
        gap: 3px !important;
        background: linear-gradient(180deg, transparent 0%, rgba(76, 5, 40, 0.75) 40%, rgba(76, 5, 40, 0.95) 100%) !important;
        color: #fff !important;
        min-height: 0 !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-name {
        flex: 1 1 100% !important;
        color: #fff !important;
        font-weight: 800 !important;
        font-size: 11px !important;
        line-height: 1.15 !important;
        margin: 0 !important;
        min-height: 0 !important;
        -webkit-line-clamp: 2 !important;
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-price {
        color: #fff !important;
        background: rgba(0,0,0,0.4) !important;
        font-weight: 800 !important;
        font-size: 11px !important;
        margin: 0 !important;
        padding: 2px 7px !important;
        border-radius: 999px !important;
        display: inline-block !important;
    }
    body.pos-mode-ice-cream .pos-shell--ice-cream .product-meta {
        color: #fce7f3 !important;
    }
    /* Force ice cream cart visible on tablet widths Bootstrap may hide */
    @media (min-width: 992px) {
        body.pos-mode-ice-cream .pos-cart-col.d-none.d-lg-block {
            display: block !important;
        }
    }
    .pos-shell--ice-cream .pos-cart-col .cart-card {
        border-radius: 16px;
        border-color: var(--ice-line);
        box-shadow: 0 10px 28px rgba(157, 23, 77, 0.1);
        height: 100%;
    }
    .pos-shell--ice-cream .cart-header {
        background: linear-gradient(135deg, #9d174d, #db2777);
        color: #fff;
    }
    .pos-shell--ice-cream .bakery-pay-btn:hover {
        border-color: #f9a8d4;
    }
    .pos-shell--ice-cream .bakery-pay-btn.cash i { color: #059669; }
    .pos-shell--ice-cream .bakery-pay-btn.card i { color: #db2777; }
    .pos-shell--ice-cream .bakery-pay-btn.bank i { color: #0891b2; }

    /* Ice cream: big Pay Now only in cart */
    .pos-shell--ice-cream .ice-pay-now-btn,
    .mobile-cart-drawer .ice-pay-now-btn {
        min-height: 72px;
        font-size: 1.35rem;
        font-weight: 800;
        border-radius: 14px;
        background: linear-gradient(135deg, #059669, #047857);
        box-shadow: 0 8px 20px rgba(5, 150, 105, 0.32);
        border: none;
        color: #fff;
    }
    .pos-shell--ice-cream .ice-checkout-actions {
        margin-top: 4px;
    }
    .pos-shell--ice-cream .cart-footer {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    /* Ice cream payment popup — force dock to right edge (override Bootstrap centering) */
    body.pos-mode-ice-cream #paymentModal.modal {
        padding-right: 0 !important;
    }
    body.pos-mode-ice-cream #paymentModal .payment-modal-dialog--dock-right {
        position: fixed !important;
        top: 12px !important;
        right: 12px !important;
        bottom: 12px !important;
        left: auto !important;
        margin: 0 !important;
        width: min(440px, calc(100vw - 24px)) !important;
        max-width: min(440px, calc(100vw - 24px)) !important;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        transform: none !important;
        display: flex !important;
        align-items: stretch !important;
    }
    body.pos-mode-ice-cream #paymentModal.fade .payment-modal-dialog--dock-right {
        transform: translateX(24px) !important;
    }
    body.pos-mode-ice-cream #paymentModal.show .payment-modal-dialog--dock-right {
        transform: none !important;
    }
    body.pos-mode-ice-cream #paymentModal .payment-modal-dialog--dock-right .payment-modal-content {
        height: 100% !important;
        max-height: calc(100vh - 24px) !important;
        display: flex !important;
        flex-direction: column !important;
        border-radius: 16px !important;
        overflow: hidden;
    }
    body.pos-mode-ice-cream #paymentModal .payment-modal-dialog--dock-right .payment-modal-body {
        flex: 1 1 auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 575.98px) {
        body.pos-mode-ice-cream #paymentModal .payment-modal-dialog--dock-right {
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 0;
        }
        body.pos-mode-ice-cream #paymentModal .payment-modal-dialog--dock-right .payment-modal-content {
            max-height: 100vh !important;
            border-radius: 0 !important;
        }
    }
    body.pos-mode-ice-cream .payment-top-inline {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 10px;
    }
    body.pos-mode-ice-cream .payment-top-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 10px 12px;
        border-radius: 12px;
        min-height: 72px;
    }
    body.pos-mode-ice-cream .payment-total-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }
    body.pos-mode-ice-cream .payment-balance-chip {
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }
    body.pos-mode-ice-cream .payment-total-amount {
        color: #10b981;
        font-size: 1.45rem !important;
        font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 0;
    }
    body.pos-mode-ice-cream .payment-balance-amount {
        color: #dc2626;
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 0;
    }
    body.pos-mode-ice-cream .payment-all-in-one {
        background: #fffafc;
        border: 1px solid #fbcfe8;
        border-radius: 14px;
        padding: 12px;
    }
    body.pos-mode-ice-cream .payment-amount-prefix {
        background: #9d174d !important;
        color: #fff !important;
        border: none !important;
        font-size: 1.05rem;
        padding: 0.75rem 0.9rem;
    }
    body.pos-mode-ice-cream .payment-amount-input {
        border: 1px solid #fbcfe8 !important;
        font-size: 1.6rem !important;
        text-align: right !important;
        background: #fff !important;
        color: #831843 !important;
        min-height: 58px;
    }
    body.pos-mode-ice-cream #paymentModal .bakery-keypad-compact,
    body.pos-mode-ice-cream #paymentModal .ice-pay-keypad,
    body.pos-mode-ice-cream #paymentModal .keypad.ice-pay-keypad {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 10px !important;
        padding: 10px;
        margin-bottom: 10px !important;
        background: linear-gradient(180deg, #fce7f3 0%, #fbcfe8 100%);
        border: 1px solid #f9a8d4;
        border-radius: 12px;
    }
    body.pos-mode-ice-cream #paymentModal .bakery-keypad-compact .touch-btn {
        aspect-ratio: 1 / 1;
        width: 100%;
        min-height: 58px;
        height: auto;
        font-size: 1.75rem;
        font-weight: 900;
        border-radius: 10px;
        color: #831843;
        background: linear-gradient(180deg, #ffffff 0%, #fff1f7 100%);
        border: 1px solid #f9a8d4;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        touch-action: manipulation;
    }
    body.pos-mode-ice-cream #paymentModal .bakery-keypad-compact .touch-btn.warning,
    body.pos-mode-ice-cream #paymentModal .bakery-key-backspace {
        color: #fff;
        background: linear-gradient(180deg, #fb923c 0%, #ea580c 100%);
        border-color: #c2410c;
    }
    body.pos-mode-ice-cream #paymentModal .bakery-key-zero,
    body.pos-mode-ice-cream #paymentModal .ice-pay-keypad .bakery-key-zero {
        grid-column: 1 / -1 !important;
        aspect-ratio: auto !important;
        width: 100% !important;
        height: 52px !important;
        min-height: 52px !important;
        max-height: 52px !important;
        font-size: 1.6rem !important;
    }
    body.pos-mode-ice-cream .payment-quick {
        background: #dbeafe;
        color: #1d4ed8;
        border: none;
        border-radius: 8px;
        font-weight: 800;
        font-size: 0.95rem;
        padding: 10px 4px !important;
        min-height: 46px;
    }
    body.pos-mode-ice-cream .payment-method-tile {
        min-height: 68px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        font-size: 0.95rem;
        touch-action: manipulation;
    }
    body.pos-mode-ice-cream .payment-method-tile i {
        font-size: 1.35rem;
    }
    body.pos-mode-ice-cream .payment-method-btn.active:not(.payment-solid-btn) {
        border-color: #db2777 !important;
        background: #fdf2f8 !important;
        box-shadow: 0 0 0 1px #db2777;
    }

    /* 10″ landscape tablets (~1280×800): keep 4×4 middle, wider cart (no keypad) */
    @media (max-width: 1399.98px) {
        .pos-shell--ice-cream {
            grid-template-columns: minmax(220px, 250px) minmax(0, 1fr) minmax(380px, 440px) !important;
        }
        body.pos-mode-ice-cream .bakery-workspace {
            grid-template-columns: minmax(220px, 250px) minmax(0, 1fr) minmax(380px, 440px);
        }
    }
    @media (max-width: 1199.98px) {
        .pos-shell--ice-cream {
            grid-template-columns: minmax(200px, 230px) minmax(0, 1fr) minmax(340px, 400px) !important;
            gap: 6px;
        }
        body.pos-mode-ice-cream .bakery-workspace {
            grid-template-columns: minmax(200px, 230px) minmax(0, 1fr) minmax(340px, 400px);
        }
        .pos-shell--ice-cream .ice-cat-rail { padding: 8px 6px; }
        .pos-shell--ice-cream .bakery-cat-name { font-size: 0.72rem; }
        body.pos-mode-ice-cream .pos-shell--ice-cream #productsGrid.product-grid {
            gap: 5px !important;
        }
        body.pos-mode-ice-cream .pos-shell--ice-cream .product-name,
        body.pos-mode-ice-cream .pos-shell--ice-cream .product-price { font-size: 10px !important; }
    }
    @media (max-width: 991.98px) {
        body.pos-mode-ice-cream .bakery-workspace {
            display: flex;
            flex-direction: column;
        }
        .pos-shell--ice-cream {
            grid-template-columns: 1fr !important;
        }
        body.pos-mode-ice-cream .bakery-workspace > .bakery-header,
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .bakery-cat-rail,
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .pos-catalog,
        body.pos-mode-ice-cream .bakery-workspace > .pos-shell--ice-cream > .pos-cart-col {
            grid-column: auto;
            grid-row: auto;
        }
        .pos-shell--ice-cream .ice-cat-list {
            grid-template-columns: repeat(4, minmax(100px, 1fr)) !important;
            grid-auto-rows: 96px;
            overflow-x: auto;
            overflow-y: hidden;
            height: auto;
        }
        .pos-shell--ice-cream .ice-cat-list[data-count] .category-btn:last-child {
            grid-column: auto;
        }
        body.pos-mode-ice-cream .pos-shell--ice-cream #productsGrid.product-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            grid-template-rows: none !important;
            grid-auto-rows: 120px;
            height: auto !important;
            overflow: visible;
        }
    }
    @media (max-width: 575.98px) {
        .pos-shell--ice-cream .ice-cat-list {
            grid-template-columns: repeat(3, minmax(90px, 1fr)) !important;
        }
        body.pos-mode-ice-cream .pos-shell--ice-cream #productsGrid.product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    /* Restaurant payment — compact one-view, auto-pay on method tap */
    #paymentModal .modal-dialog.payment-modal-dialog--wide {
        max-width: 780px !important;
        width: calc(100% - 1.25rem) !important;
        margin: 0.5rem auto !important;
        max-height: calc(100vh - 1rem);
        transform: none !important;
        display: flex;
        align-items: flex-start;
    }
    .payment-modal-dialog--wide .payment-modal-content {
        border-radius: 14px;
        border: none;
        background: #fff;
        box-shadow: 0 20px 40px -12px rgba(0,0,0,0.25);
        max-height: calc(100vh - 1rem);
        width: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .payment-modal-header {
        background: linear-gradient(135deg, #f59e0b, #ea580c);
        border-radius: 14px 14px 0 0;
        padding: 8px 14px;
        flex: 0 0 auto;
    }
    .payment-modal-header .modal-title {
        font-size: 1rem;
    }
    .payment-modal-dialog--wide .payment-modal-body {
        padding: 10px 14px 8px !important;
        overflow: hidden !important;
        flex: 0 1 auto;
        background: #fff;
        min-height: 0;
    }
    .payment-modal-dialog--wide .payment-modal-footer {
        flex: 0 0 auto;
        background: #fff;
        padding: 8px 14px !important;
        border-top: 1px solid #f1f5f9;
        gap: 8px;
    }
    .payment-cancel-btn {
        background: #f1f5f9;
        color: #64748b;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        padding: 6px 14px;
    }
    .payment-footer-hint {
        font-size: 0.78rem;
        text-align: right;
        flex: 1;
    }
    .payment-modal-dialog--wide .payment-wide-split {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }
    .payment-wide-methods {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .payment-solid-grid--stack {
        display: grid !important;
        grid-template-columns: 1fr !important;
        grid-template-rows: none;
        gap: 8px;
        height: auto;
    }
    .payment-solid-grid--stack .payment-solid-btn {
        min-height: 50px !important;
        height: 50px !important;
        font-size: 1.05rem !important;
        width: 100%;
        border-radius: 10px !important;
        padding: 0 !important;
        letter-spacing: 0;
    }
    .payment-wide-keypad { min-width: 0; }
    .payment-modal-dialog--wide .rest-pay-keypad {
        max-width: 340px;
        margin-left: auto;
        margin-right: auto;
    }
    .payment-balance-chip.is-change {
        background: #ecfdf5 !important;
        border-color: #a7f3d0 !important;
    }
    .payment-balance-chip.is-change .payment-balance-amount {
        color: #059669 !important;
    }
    @media (max-width: 767.98px) {
        #paymentModal .modal-dialog.payment-modal-dialog--wide {
            max-width: calc(100% - 0.75rem) !important;
            margin: 0.35rem auto !important;
        }
        .payment-modal-dialog--wide .payment-modal-body {
            overflow-y: auto !important;
        }
        .payment-modal-dialog--wide .payment-wide-split {
            grid-template-columns: 1fr;
        }
        .payment-solid-grid--stack {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .payment-solid-grid--stack .payment-solid-btn {
            min-height: 48px !important;
            height: 48px !important;
        }
        .payment-modal-dialog--wide .rest-pay-keypad {
            max-width: none;
        }
        .payment-footer-hint { display: none; }
    }
    .payment-modal-summary {
        margin-bottom: 6px;
    }
    .payment-total-box {
        background: #f0fdf4;
    }
    .payment-total-amount {
        color: #10b981;
        font-size: 1.2rem !important;
        line-height: 1.2;
    }
    .payment-modal-section {
        margin-bottom: 6px;
    }
    #cashInputContainer {
        background: #f8fafc;
        border-radius: 10px;
        padding: 8px;
    }
    .payment-top-inline {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 8px;
    }
    .payment-top-chip {
        border-radius: 10px;
        padding: 8px 10px;
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .payment-total-box.payment-top-chip {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .payment-balance-chip.payment-top-chip {
        background: #fef2f2;
        border: 1px solid #fecaca;
    }
    .payment-total-amount { color: #059669; font-size: 1.2rem; }
    .payment-balance-amount { color: #dc2626; font-size: 1.2rem; }
    .payment-amount-prefix {
        background: #f59e0b !important;
        color: #fff !important;
        border: none !important;
        font-weight: 800;
        font-size: 0.85rem;
        padding: 0 10px;
    }
    .payment-amount-input {
        border: 1px solid #e2e8f0 !important;
        font-size: 1.2rem !important;
        text-align: right !important;
        background: #fff !important;
        min-height: 40px;
        font-weight: 800;
        padding-top: 4px;
        padding-bottom: 4px;
    }
    #paymentModal .rest-pay-keypad,
    #paymentModal .bakery-pay-keypad {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 7px !important;
        padding: 8px;
        margin-bottom: 8px !important;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    #paymentModal .rest-pay-keypad .touch-btn,
    #paymentModal .bakery-pay-keypad .touch-btn {
        aspect-ratio: 1 / 1;
        width: 100%;
        max-width: 68px;
        min-height: 0;
        height: auto;
        margin: 0 auto;
        font-size: 1.2rem;
        font-weight: 800;
        border-radius: 10px;
        color: #0f172a;
        background: linear-gradient(180deg, #ffffff 0%, #e2e8f0 100%);
        border: 1px solid #94a3b8;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.95),
            0 3px 0 #64748b,
            0 4px 8px rgba(15,23,42,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        touch-action: manipulation;
    }
    #paymentModal .rest-pay-keypad .touch-btn:active,
    #paymentModal .bakery-pay-keypad .touch-btn:active {
        transform: translateY(2px);
        box-shadow: inset 0 2px 6px rgba(15,23,42,0.18), 0 1px 0 #64748b;
    }
    #paymentModal .rest-pay-keypad .touch-btn.warning,
    #paymentModal .bakery-pay-keypad .touch-btn.warning,
    #paymentModal .bakery-key-backspace {
        color: #fff !important;
        background: linear-gradient(180deg, #fb923c 0%, #ea580c 100%) !important;
        border-color: #c2410c !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.35), 0 3px 0 #c2410c, 0 4px 8px rgba(234,88,12,0.25) !important;
    }
    #paymentModal .bakery-key-zero {
        grid-column: 1 / -1 !important;
        aspect-ratio: auto !important;
        width: 100% !important;
        max-width: none !important;
        height: 42px !important;
        min-height: 42px !important;
        max-height: 42px !important;
        font-size: 1.15rem !important;
    }
    .payment-quick-grid .payment-quick {
        background: #dbeafe;
        color: #1d4ed8;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 6px 2px !important;
        min-height: 36px;
    }

    /* Solid colored Cash / Card / Credit / Bank */
    .payment-solid-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .payment-solid-btn {
        min-height: 50px;
        border: none;
        border-radius: 10px;
        color: #fff !important;
        font-size: 1.05rem;
        font-weight: 800;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.35),
            0 3px 0 rgba(15,23,42,0.2),
            0 5px 10px rgba(15,23,42,0.1);
        touch-action: manipulation;
        transition: transform 0.08s ease, box-shadow 0.08s ease, filter 0.08s ease;
    }
    .payment-solid-btn.cash { background: linear-gradient(180deg, #34d399, #059669); }
    .payment-solid-btn.card { background: linear-gradient(180deg, #fbbf24, #d97706); }
    .payment-solid-btn.credit { background: linear-gradient(180deg, #f87171, #dc2626); }
    .payment-solid-btn.bank { background: linear-gradient(180deg, #38bdf8, #0284c7); }
    .payment-solid-btn:active,
    .payment-solid-btn.active {
        transform: translateY(2px);
        box-shadow: 0 1px 0 rgba(15,23,42,0.2), inset 0 2px 6px rgba(0,0,0,0.18);
        filter: brightness(0.96);
        border: none !important;
        background-image: none;
    }
    .payment-solid-btn.cash.active { background: #047857 !important; }
    .payment-solid-btn.card.active { background: #b45309 !important; }
    .payment-solid-btn.credit.active { background: #b91c1c !important; }
    .payment-solid-btn.bank.active { background: #0369a1 !important; }

    .payment-method-tile {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        background: #fff;
        padding: 6px 4px !important;
        line-height: 1.15;
    }
    .payment-method-tile i {
        font-size: 1.1rem;
        margin-bottom: 2px;
    }
    .payment-method-tile small {
        font-size: 0.72rem;
    }
    .payment-method-exact {
        border-color: #dbeafe;
        background: #eff6ff;
    }
    .payment-method-btn.active:not(.payment-solid-btn) {
        border-color: #f59e0b !important;
        background: #fffbeb !important;
        box-shadow: 0 0 0 1px #f59e0b;
    }

    .payment-amount-group {
        border-radius: 10px;
        overflow: hidden;
    }
    .payment-key {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #334155;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 8px 4px !important;
        min-height: 40px;
    }
    .payment-key-back {
        background: #fef3c7;
        border-color: #fbbf24;
        color: #b45309;
    }
    .payment-quick {
        background: #dbeafe;
        color: #1d4ed8;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 6px 4px !important;
    }
    .payment-quick-clear {
        background: #fee2e2;
        color: #dc2626;
    }
    .payment-change-box {
        background: #ecfdf5;
    }
    .payment-modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 10px 16px;
    }

    /* FINAL: ice cream payment must sit on the right (beats earlier modal rules) */
    body.pos-mode-ice-cream #paymentModal .modal-dialog.payment-modal-dialog--dock-right {
        position: fixed !important;
        inset: 12px 12px 12px auto !important;
        left: auto !important;
        margin: 0 !important;
        width: min(440px, calc(100vw - 24px)) !important;
        max-width: min(440px, calc(100vw - 24px)) !important;
        height: calc(100vh - 24px) !important;
        min-height: calc(100vh - 24px) !important;
        max-height: calc(100vh - 24px) !important;
        transform: none !important;
    }
    body.pos-mode-ice-cream #paymentModal.show .modal-dialog.payment-modal-dialog--dock-right {
        transform: none !important;
    }
</style>
@endpush

<!-- Initialize POS search / LED after the page is ready (no jQuery — layout loads jQuery later) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    updateOpenBillUI();
    keepPosSearchReady();

    // Keep search ready after closing common POS modals
    ['paymentModal', 'printPreviewModal', 'addCustomerModal', 'deliveryDetailsModal', 'productOptionsModal', 'customItemModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('hidden.bs.modal', () => {
            if (pendingQtyFocusIndex == null) focusProductSearch(true);
        });
    });

    // After any click on empty product area / cart chrome, return to search for next scan
    document.addEventListener('click', (e) => {
        if (document.querySelector('.modal.show')) return;
        if (e.target.closest('input, textarea, select, button, a, .select2-container, .cart-qty-input, .swal2-container')) return;
        focusProductSearch(true);
    });

    window.addEventListener('focus', () => {
        if (!document.querySelector('.modal.show')) focusProductSearch(true);
    });

    const search = document.getElementById('productSearch');
    if (search && posShortcuts.search) {
        search.placeholder = `Search product by name or barcode… (${String(posShortcuts.search).toUpperCase()})`;
    }

    // Auto-reconnect previously permitted rear LED (Analog mode)
    if (typeof AnalogLed !== 'undefined' && AnalogLed.enabled() && AnalogLed.supported()) {
        AnalogLed.ensureConnected().then((ok) => {
            if (ok) AnalogLed.writeState({ mode: 'idle', total: 0, price: 0, change: 0, paying: 0, itemName: '' });
        });
    }
});
</script>
@endsection
