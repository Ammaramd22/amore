@extends('layouts.admin')
@section('title', 'Barcode Labels')
@section('page_title', 'Barcode Labels')
@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="bl-page">
    <div class="bl-head">
        <div>
            <h2 class="bl-title">Print Labels</h2>
            <p class="bl-sub">Add Direct / stock products, then print in the browser designer — or export CSV for BarTender.</p>
        </div>
        <div class="bl-head-actions">
            <a href="{{ route('barcode-labels.designer') }}" class="btn bl-btn-ghost"><i class="fas fa-sliders-h me-1"></i>Open Designer</a>
            <a href="{{ route('products.index') }}" class="btn bl-btn-ghost">Products</a>
            <a href="{{ route('settings.index') }}#bartender" class="btn bl-btn-ghost">BarTender Settings</a>
            <a href="{{ route('barcode-labels.batch') }}" class="btn bl-btn-ghost">Download batch</a>
        </div>
    </div>

    <section class="bl-panel">
        <div class="bl-panel-title">Add products to grid</div>

        <div class="bl-add-row">
            <div class="bl-search">
                <label for="productSearch">Search product</label>
                <select id="productSearch" class="form-control bl-product-select">
                    <option value=""></option>
                    @foreach($products as $p)
                    <option
                        value="{{ $p['id'] }}"
                        data-name="{{ e($p['name']) }}"
                        data-code="{{ e($p['code'] ?? '') }}"
                        data-barcode="{{ e($p['barcode'] ?? '') }}"
                        data-selling_price="{{ $p['selling_price'] }}"
                        data-cost_price="{{ $p['cost_price'] }}"
                        data-category="{{ e($p['category'] ?? '') }}"
                        data-category-id="{{ $p['category_id'] }}"
                    >
                        {{ $p['name'] }}
                        @if(!empty($p['code'])) ({{ $p['code'] }}) @endif
                        @if(!empty($p['barcode'])) · {{ $p['barcode'] }} @endif
                        — {{ number_format($p['selling_price'], 2) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="bl-copies">
                <label for="defaultQty">Copies</label>
                <input type="number" id="defaultQty" class="form-control" value="1" min="1" max="999">
            </div>
            <div class="bl-add-wrap">
                <label class="invisible d-block">Add</label>
                <button type="button" class="btn bl-btn-primary" id="btnAddSelected">
                    <i class="fas fa-plus me-1"></i>Add
                </button>
            </div>
        </div>

        <div class="bl-hint">
            @if($directCount > 0)
                {{ $directCount }} direct product(s) in Select2 — type to search, then Add.
            @else
                No Direct products yet. Create a category with type <strong>Direct</strong> and add products.
            @endif
        </div>

        <div class="bl-bulk">
            <select id="bulkCategory" class="form-select">
                <option value="">Bulk add… Direct category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn bl-btn-ghost" id="btnBulkAdd">Add all in category</button>
        </div>
    </section>

    <section class="bl-panel bl-queue-panel">
        <div class="bl-queue-head">
            <div class="bl-panel-title mb-0">
                Print queue <span class="bl-count" id="queueCount">0</span>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button type="button" class="btn bl-btn-ghost btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Columns</button>
                    <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:220px;">
                        @foreach($columns as $key => $label)
                        <div class="form-check mb-1">
                            <input class="form-check-input col-check" type="checkbox" value="{{ $key }}" id="col_{{ $key }}"
                                   {{ in_array($key, ['sku','barcode','name','selling_price','qty'], true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="col_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <button type="button" class="btn bl-btn-ghost btn-sm" id="btnClearQueue">Clear</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table bl-table mb-0" id="queueTable" style="display:none;">
                <thead>
                    <tr>
                        <th style="width:48px">#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th>Price</th>
                        <th style="width:110px">Copies</th>
                        <th style="width:52px"></th>
                    </tr>
                </thead>
                <tbody id="queueBody"></tbody>
            </table>
        </div>

        <div class="bl-empty" id="queueEmpty">
            <div class="bl-empty-icon"><i class="fas fa-barcode"></i></div>
            <div class="bl-empty-title">No products in the print queue yet</div>
            <div class="bl-empty-text">Search a direct product above and click Add</div>
        </div>
    </section>

    <div class="bl-footer bl-footer-qpos">
        <div class="bl-url-box">
            <div class="bl-url-meta mb-2">
                Saved browser layout: <b>{{ $defaultLayout->name }}</b>
                ({{ ($defaultLayout->normalizedConfig()['width_mm'] ?? '?') }}×{{ ($defaultLayout->normalizedConfig()['height_mm'] ?? '?') }}mm)
            </div>
            <label>BarTender CSV URL</label>
            <div class="bl-url-row">
                <input type="text" class="form-control" id="fixedCsvUrl" value="{{ $fixedUrl }}" readonly>
                <button type="button" class="btn bl-btn-ghost" id="btnCopyUrl" title="Copy URL"><i class="fas fa-copy"></i></button>
            </div>
            <div class="bl-url-meta">
                Save server: <b>{{ $saveServer ? 'ON' : 'OFF' }}</b>
                · Browser download: <b>{{ $downloadBrowser ? 'ON' : 'OFF' }}</b>
            </div>
        </div>
        <div class="bl-footer-actions">
            <input type="hidden" id="browserLayout" value="{{ $defaultLayout->id }}">
            <button type="button" class="btn bl-btn-browser" id="btnBrowserPrint">
                <i class="fas fa-print me-2"></i>Print Labels (Browser)
            </button>
            <button type="button" class="btn bl-btn-ghost" id="btnExport">
                <i class="fas fa-file-export me-2"></i>Export for BarTender
            </button>
        </div>
    </div>

    @if($showOpenBtn)
    <div class="alert alert-secondary small mt-3 mb-0">
        Browsers cannot open local BarTender. Use the desktop batch shortcut.
        Label path: <code>{{ $labelPath }}</code>
    </div>
    @endif
</div>

{{-- Product payload for bulk add (category_id keyed) --}}
<script type="application/json" id="directProductsJson">@json($products)</script>
@endsection

@push('styles')
<style>
.bl-page {
    --bl-ink: #0f172a;
    --bl-muted: #64748b;
    --bl-line: #e2e8f0;
    --bl-soft: #f8fafc;
    --bl-accent: #2563eb;
    --bl-accent-dark: #1d4ed8;
    max-width: 1100px;
}
.bl-head {
    display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem;
    align-items: flex-start; margin-bottom: 1.25rem;
}
.bl-title { margin: 0; font-size: 1.45rem; font-weight: 750; color: var(--bl-ink); letter-spacing: -0.02em; }
.bl-sub { margin: 0.35rem 0 0; color: var(--bl-muted); font-size: 0.9rem; max-width: 42rem; }
.bl-head-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.bl-panel {
    background: #fff; border: 1px solid var(--bl-line); border-radius: 14px;
    padding: 1.15rem 1.25rem 1.25rem; margin-bottom: 1rem;
    overflow: visible;
}
.bl-panel-title {
    font-size: 0.78rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.06em;
    color: #94a3b8; margin-bottom: 0.85rem;
}
.bl-add-row {
    display: grid;
    grid-template-columns: 1fr 90px auto;
    gap: 0.75rem;
    align-items: end;
}
.bl-add-row label {
    display: block; font-size: 0.78rem; font-weight: 650; color: #475569; margin-bottom: 0.35rem;
}
.bl-hint { margin-top: 0.55rem; font-size: 0.8rem; color: var(--bl-muted); }
.bl-bulk {
    display: flex; flex-wrap: wrap; gap: 0.55rem; margin-top: 1rem; padding-top: 1rem;
    border-top: 1px dashed var(--bl-line); align-items: center;
}
.bl-bulk .form-select { max-width: 280px; }
.bl-btn-primary {
    background: linear-gradient(135deg, var(--bl-accent), var(--bl-accent-dark));
    border: none; color: #fff; font-weight: 700; border-radius: 10px; padding: 0.65rem 1.15rem;
}
.bl-btn-primary:hover { filter: brightness(1.05); color: #fff; }
.bl-btn-ghost {
    background: #fff; border: 1px solid var(--bl-line); color: #334155; font-weight: 650;
    border-radius: 10px; padding: 0.55rem 0.9rem;
}
.bl-btn-ghost:hover { background: var(--bl-soft); border-color: #cbd5e1; color: var(--bl-ink); }
.bl-queue-head {
    display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;
}
.bl-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 1.6rem; height: 1.6rem; padding: 0 0.4rem;
    border-radius: 999px; background: #eff6ff; color: var(--bl-accent); font-size: 0.78rem; font-weight: 800;
}
.bl-table thead th {
    background: var(--bl-soft); color: #64748b; font-size: 0.72rem; text-transform: uppercase;
    letter-spacing: 0.05em; border-bottom: 1px solid var(--bl-line); white-space: nowrap;
}
.bl-table td { vertical-align: middle; border-color: #f1f5f9; color: #334155; }
.bl-name { font-weight: 700; color: var(--bl-ink); }
.bl-empty { text-align: center; padding: 2.75rem 1rem 2.25rem; color: var(--bl-muted); }
.bl-empty-icon {
    width: 4rem; height: 4rem; margin: 0 auto 0.85rem; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    background: var(--bl-soft); border: 1px solid var(--bl-line); color: #94a3b8; font-size: 1.5rem;
}
.bl-empty-title { font-weight: 750; color: #334155; margin-bottom: 0.25rem; }
.bl-empty-text { font-size: 0.85rem; }
.bl-footer {
    display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; justify-content: space-between;
    background: #fff; border: 1px solid var(--bl-line); border-radius: 14px; padding: 1rem 1.15rem;
}
.bl-footer-actions { display: flex; flex-wrap: wrap; gap: .55rem; justify-content: flex-end; }
.bl-btn-print {
    background: linear-gradient(135deg, #0f766e, #0d9488);
    border: none; color: #fff; font-weight: 700; border-radius: 10px; padding: 0.85rem 1.2rem;
}
.bl-btn-print:hover { filter: brightness(1.05); color: #fff; }
.bl-btn-browser {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    border: none; color: #fff; font-weight: 750; border-radius: 10px;
    padding: 0.95rem 1.45rem; font-size: 1rem; min-width: 240px;
}
.bl-btn-browser:hover { filter: brightness(1.06); color: #fff; }
.bl-footer-qpos .bl-footer-actions {
    flex-direction: column; align-items: stretch;
}
.bl-url-box { flex: 1; min-width: 240px; }
.bl-url-box label {
    display: block; font-size: 0.72rem; font-weight: 750; text-transform: uppercase; letter-spacing: 0.05em;
    color: #94a3b8; margin-bottom: 0.35rem;
}
.bl-url-row { display: flex; gap: 0.4rem; }
.bl-url-meta { margin-top: 0.4rem; font-size: 0.78rem; color: var(--bl-muted); }
.bl-export { white-space: nowrap; padding: 0.85rem 1.35rem; font-size: 0.95rem; }
/* Select2 — match blue theme, attach to body so list isn't clipped */
.bl-page .select2-container { width: 100% !important; }
.bl-page .select2-container .select2-selection--single {
    height: 44px !important; border: 1px solid var(--bl-line) !important; border-radius: 10px !important;
    display: flex !important; align-items: center;
}
.bl-page .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 42px !important; padding-left: 12px; color: #0f172a;
}
.bl-page .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px !important;
}
.bl-page .select2-container--default.select2-container--open .select2-selection--single,
.bl-page .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37,99,235,.15) !important;
}
.select2-container--open .select2-dropdown {
    border-color: #cbd5e1; border-radius: 10px; z-index: 99999;
}
.select2-results__option--highlighted { background: #2563eb !important; }
@media (max-width: 768px) {
    .bl-add-row { grid-template-columns: 1fr; }
    .bl-footer { flex-direction: column; align-items: stretch; }
    .bl-footer-actions { width: 100%; }
    .bl-export, .bl-btn-print, .bl-btn-browser { width: 100%; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const saveServer = @json($saveServer);
    const queue = new Map();

    let products = [];
    try {
        products = JSON.parse(document.getElementById('directProductsJson')?.textContent || '[]');
    } catch (e) {
        products = [];
    }

    function money(n) { return Number(n || 0).toFixed(2); }
    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function selectedFromDom() {
        const el = document.getElementById('productSearch');
        const opt = el?.options?.[el.selectedIndex];
        if (!opt || !opt.value) return null;
        return {
            id: Number(opt.value),
            name: opt.getAttribute('data-name') || opt.textContent.trim(),
            code: opt.getAttribute('data-code') || '',
            barcode: opt.getAttribute('data-barcode') || '',
            selling_price: Number(opt.getAttribute('data-selling_price') || 0),
            cost_price: Number(opt.getAttribute('data-cost_price') || 0),
            category: opt.getAttribute('data-category') || '',
            category_id: Number(opt.getAttribute('data-category-id') || 0),
        };
    }

    function renderQueue() {
        const body = document.getElementById('queueBody');
        const empty = document.getElementById('queueEmpty');
        const table = document.getElementById('queueTable');
        const rows = [...queue.values()];
        document.getElementById('queueCount').textContent = String(rows.length);
        body.innerHTML = '';

        if (!rows.length) {
            empty.style.display = '';
            table.style.display = 'none';
            return;
        }
        empty.style.display = 'none';
        table.style.display = '';

        rows.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.dataset.id = item.id;
            tr.innerHTML = `
                <td class="text-muted">${idx + 1}</td>
                <td>
                    <div class="bl-name">${escapeHtml(item.name)}</div>
                    <div class="small text-muted">${escapeHtml(item.category || '')}</div>
                </td>
                <td><code>${escapeHtml(item.code || '—')}</code></td>
                <td><code>${escapeHtml(item.barcode || '—')}</code></td>
                <td>${money(item.selling_price)}</td>
                <td><input type="number" class="form-control form-control-sm qty-input" min="1" max="999" value="${item.qty}"></td>
                <td><button type="button" class="btn btn-sm bl-btn-ghost btn-remove" aria-label="Remove"><i class="fas fa-times"></i></button></td>
            `;
            body.appendChild(tr);
        });
    }

    function addProduct(p, qty) {
        const id = Number(p?.id);
        if (!id) return;
        const copies = Math.max(1, parseInt(qty || document.getElementById('defaultQty').value || 1, 10));
        if (queue.has(id)) {
            queue.get(id).qty = Math.min(999, queue.get(id).qty + copies);
        } else {
            queue.set(id, {
                id,
                name: p.name || '',
                code: p.code || '',
                barcode: p.barcode || p.code || '',
                selling_price: Number(p.selling_price || 0),
                cost_price: Number(p.cost_price || 0),
                category: p.category || '',
                qty: copies,
            });
        }
        renderQueue();
    }

    function initProductSelect2() {
        const $el = $('#productSearch');
        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.off('select2:select').select2('destroy');
        }

        $el.select2({
            width: '100%',
            placeholder: products.length ? 'Search name, SKU, barcode…' : 'No direct products',
            allowClear: true,
            dropdownParent: $(document.body),
            minimumResultsForSearch: 0,
            matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (!data.id) {
                    return null;
                }
                const term = params.term.toLowerCase();
                const text = String(data.text || '').toLowerCase();
                const $opt = $(data.element);
                const extra = [
                    $opt.attr('data-name'),
                    $opt.attr('data-code'),
                    $opt.attr('data-barcode'),
                    $opt.attr('data-category'),
                ].filter(Boolean).join(' ').toLowerCase();
                return (text.includes(term) || extra.includes(term)) ? data : null;
            },
        });
    }

    // Wait for layout scripts (global $('.select2') init) then init ours
    $(function () {
        setTimeout(initProductSelect2, 0);
    });

    document.getElementById('btnAddSelected').addEventListener('click', () => {
        const p = selectedFromDom();
        if (!p) {
            Swal.fire({ icon: 'info', title: 'Select a product first', timer: 1500, showConfirmButton: false });
            return;
        }
        addProduct(p);
        $('#productSearch').val(null).trigger('change');
    });

    document.getElementById('btnBulkAdd').addEventListener('click', () => {
        const catId = document.getElementById('bulkCategory').value;
        if (!catId) {
            Swal.fire({ icon: 'info', title: 'Pick a Direct category first', timer: 1500, showConfirmButton: false });
            return;
        }
        const list = products.filter(p => String(p.category_id) === String(catId));
        if (!list.length) {
            Swal.fire({ icon: 'info', title: 'No products in this category' });
            return;
        }
        list.forEach(p => addProduct(p, document.getElementById('defaultQty').value));
        Swal.fire({ icon: 'success', title: 'Added ' + list.length + ' product(s)', timer: 1400, showConfirmButton: false });
    });

    document.getElementById('queueBody').addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove');
        if (!btn) return;
        queue.delete(Number(btn.closest('tr')?.dataset.id));
        renderQueue();
    });

    document.getElementById('queueBody').addEventListener('change', (e) => {
        if (!e.target.classList.contains('qty-input')) return;
        const id = Number(e.target.closest('tr')?.dataset.id);
        if (!queue.has(id)) return;
        queue.get(id).qty = Math.max(1, Math.min(999, parseInt(e.target.value || 1, 10)));
    });

    document.getElementById('btnClearQueue').addEventListener('click', () => {
        queue.clear();
        renderQueue();
    });

    document.getElementById('btnCopyUrl')?.addEventListener('click', async () => {
        const val = document.getElementById('fixedCsvUrl').value;
        try {
            await navigator.clipboard.writeText(val);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'URL copied', showConfirmButton: false, timer: 1400 });
        } catch (e) {
            document.getElementById('fixedCsvUrl').select();
        }
    });

    document.getElementById('btnBrowserPrint').addEventListener('click', () => {
        const items = [...queue.values()].map(i => ({ id: i.id, qty: i.qty }));
        if (!items.length) {
            Swal.fire({ icon: 'warning', title: 'Add products to the queue first' });
            return;
        }
        const layoutId = document.getElementById('browserLayout')?.value;
        if (!layoutId) {
            Swal.fire({ icon: 'warning', title: 'Select a label layout' });
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = @json(route('barcode-labels.browser-print'));
        form.target = '_blank';

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf;
        form.appendChild(token);

        const layoutInput = document.createElement('input');
        layoutInput.type = 'hidden';
        layoutInput.name = 'layout_id';
        layoutInput.value = layoutId;
        form.appendChild(layoutInput);

        items.forEach((item, idx) => {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `items[${idx}][id]`;
            idInput.value = item.id;
            form.appendChild(idInput);
            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `items[${idx}][qty]`;
            qtyInput.value = item.qty;
            form.appendChild(qtyInput);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
    });

    document.getElementById('btnExport').addEventListener('click', () => {
        const items = [...queue.values()].map(i => ({ id: i.id, qty: i.qty }));
        if (!items.length) {
            Swal.fire({ icon: 'warning', title: 'Add products to the queue first' });
            return;
        }
        const columns = [...document.querySelectorAll('.col-check:checked')].map(el => el.value);
        if (!columns.length) {
            Swal.fire({ icon: 'warning', title: 'Select at least one column' });
            return;
        }

        fetch(@json(route('barcode-labels.export')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json, text/csv, */*',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ items, columns }),
        }).then(async (res) => {
            const type = res.headers.get('Content-Type') || '';
            if (type.includes('text/csv') || type.includes('octet-stream') || type.includes('application/csv')) {
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'barcode_labels.csv';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
                Swal.fire({
                    icon: 'success',
                    title: 'CSV exported',
                    html: saveServer ? 'Browser download + fixed server URL updated' : 'Downloaded',
                    timer: 2000,
                    showConfirmButton: false,
                });
                return;
            }
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success !== false) {
                Swal.fire({ icon: 'success', title: data.message || 'CSV saved', html: data.url ? `<small>${data.url}</small>` : '' });
            } else {
                const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Export failed');
                Swal.fire({ icon: 'error', title: 'Export failed', html: msg });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Export failed' }));
    });

    renderQueue();
})();
</script>
@endpush
