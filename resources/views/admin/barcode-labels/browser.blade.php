<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Print Labels (Browser) — {{ $labelCount }} Labels</title>
    <link id="googleFontLink" rel="stylesheet" href="{{ $fontUrl }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #1a1a24;
            --panel: #22222e;
            --panel-border: #2e2e3c;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --input: #2a2a38;
            --input-border: #3f3f50;
            --accent: #7166f0;
            --blue: #38bdf8;
            --green: #22c55e;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, sans-serif;
        }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 1.25rem 1.25rem 2.5rem; }
        .page-title { margin: 0; font-size: 1.55rem; font-weight: 750; letter-spacing: -0.02em; }
        .page-hint { margin: .45rem 0 1.15rem; color: var(--muted); font-size: .88rem; line-height: 1.45; }

        .panels {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .85rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 1100px) { .panels { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 700px) { .panels { grid-template-columns: 1fr; } }

        .card {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: .9rem 1rem 1rem;
        }
        .card h3 {
            margin: 0 0 .75rem;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #cbd5e1;
        }
        label.fld {
            display: block;
            font-size: .78rem;
            color: var(--muted);
            margin: .55rem 0 .28rem;
        }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; }
        .row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .45rem; }
        input[type="text"], input[type="number"], select {
            width: 100%;
            background: var(--input);
            border: 1px solid var(--input-border);
            color: var(--text);
            border-radius: 8px;
            padding: .48rem .6rem;
            font-size: .9rem;
        }
        input:focus, select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(113,102,240,.2); }
        .hint { font-size: .72rem; color: #64748b; margin-top: .35rem; }

        .checks { display: grid; grid-template-columns: 1fr 1fr; gap: .45rem .75rem; }
        .checks label {
            display: flex; align-items: center; gap: .45rem;
            font-size: .88rem; color: #e2e8f0; cursor: pointer;
        }
        .checks input { width: 16px; height: 16px; accent-color: var(--accent); }

        .actions {
            display: flex; flex-wrap: wrap; gap: .55rem;
            margin: .35rem 0 1.25rem;
        }
        .btn {
            border: 0; border-radius: 8px; padding: .65rem 1.05rem;
            font-weight: 700; font-size: .9rem; cursor: pointer; color: #fff;
            display: inline-flex; align-items: center; gap: .4rem;
        }
        .btn-apply { background: #0ea5e9; }
        .btn-save { background: var(--accent); }
        .btn-print { background: var(--green); }
        .btn-close { background: #475569; }
        .btn:hover { filter: brightness(1.08); }

        .preview-area { margin-top: .25rem; }
        .preview-label-title {
            font-size: .75rem; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .06em; margin-bottom: .55rem;
        }
        .preview-stage {
            display: inline-block;
            background: #111118;
            border: 1px dashed #3f3f50;
            border-radius: 10px;
            padding: 1.1rem;
        }
        .label-preview {
            width: 38mm;
            height: 25mm;
            background: #fff;
            color: #111;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            align-items: stretch;
            font-family: 'Roboto', sans-serif;
            box-shadow: 0 8px 24px rgba(0,0,0,.35);
            transform: none;
            writing-mode: horizontal-tb;
        }
        /* Horizontal label: text column + barcode column (0° orientation) */
        .lp-row {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            width: 100%;
            height: 100%;
            min-height: 0;
            transform: none;
            writing-mode: horizontal-tb;
        }
        .lp-row.lp-barcode-left { flex-direction: row-reverse; }
        .lp-info {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.4mm;
            padding: 1.2mm 1.5mm;
            writing-mode: horizontal-tb;
        }
        .lp-business, .lp-item, .lp-type, .lp-sku, .lp-price, .lp-cost {
            text-align: left;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transform: none;
            writing-mode: horizontal-tb;
        }
        .lp-business { font-weight: 700; }
        .lp-item { font-weight: 600; }
        .lp-type { font-weight: 500; opacity: 0.92; }
        .lp-barcode-wrap {
            flex: 0 0 auto;
            width: 46%;
            max-width: 55%;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1mm 1.2mm 1mm 0.5mm;
            transform: none;
        }
        .lp-barcode-wrap svg {
            max-width: 100%;
            max-height: 100%;
            height: auto;
            transform: none;
        }
        .lp-sku { font-weight: 500; text-align: center; max-width: 100%; }
        .lp-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 1mm 2mm;
            margin-top: 0.4mm;
        }
        .lp-cost { font-weight: 600; }
        .lp-price { font-weight: 700; }

        /* Print sheet — one label = one page sized to label mm */
        .print-sheet { display: none; }
        .print-label {
            width: 100%;
            height: 100%;
            background: #fff;
            color: #000;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            align-items: stretch;
            position: relative;
            page-break-after: always;
            break-after: page;
            transform: none;
            writing-mode: horizontal-tb;
        }
        .print-label:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        @media print {
            html, body {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                transform: none !important;
                writing-mode: horizontal-tb !important;
            }
            .no-print { display: none !important; }
            .print-sheet {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                transform: none !important;
            }
            .print-label {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                transform: none !important;
                writing-mode: horizontal-tb !important;
            }
            .lp-row, .lp-info, .lp-barcode-wrap, .lp-barcode-wrap svg {
                transform: none !important;
            }
            .lp-barcode-wrap svg { max-height: 100%; }
        }
    </style>
</head>
<body>
@php
    $taxRate = $taxRate ?? (float) \App\Models\Setting::get('tax_rate', 0);
    $taxEnabled = $taxEnabled ?? (bool) \App\Models\Setting::get('tax_enabled', false);
@endphp

<div class="wrap no-print">
    <h1 class="page-title">Print Labels (Browser) — {{ $labelCount }} Labels</h1>
    <p class="page-hint">
        Horizontal label: product info on one side, barcode on the other (no rotation).
        Use Barcode position for Left / Right. Save layout keeps your settings next time.
    </p>

    <div class="panels">
        <section class="card">
            <h3>Label size</h3>
            <div class="row-2">
                <div>
                    <label class="fld" for="widthMm">Width (mm)</label>
                    <input type="number" id="widthMm" step="0.5" min="10" max="200" value="{{ $config['width_mm'] }}">
                </div>
                <div>
                    <label class="fld" for="heightMm">Height (mm)</label>
                    <input type="number" id="heightMm" step="0.5" min="10" max="200" value="{{ $config['height_mm'] }}">
                </div>
            </div>
            <label class="fld" for="fontFamily">Google Font</label>
            <select id="fontFamily">
                @foreach($googleFonts as $name => $spec)
                    <option value="{{ $name }}" @selected(($config['font_family'] ?? 'Roboto') === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </section>

        <section class="card">
            <h3>Font sizes (live preview)</h3>
            <label class="fld" for="businessName">Business name on label</label>
            <input type="text" id="businessName" value="{{ $config['business_name'] }}">

            <div class="row-2">
                <div>
                    <label class="fld" for="fsBusiness">Business Name</label>
                    <input type="number" id="fsBusiness" step="0.5" min="4" max="48" value="{{ $config['fonts']['business_name'] }}">
                </div>
                <div>
                    <label class="fld" for="fsItem">Item Name</label>
                    <input type="number" id="fsItem" step="0.5" min="4" max="48" value="{{ $config['fonts']['item_name'] }}">
                </div>
            </div>
            <div class="row-3">
                <div>
                    <label class="fld" for="fsSku">SKU</label>
                    <input type="number" id="fsSku" step="0.5" min="4" max="48" value="{{ $config['fonts']['sku'] }}">
                </div>
                <div>
                    <label class="fld" for="fsPrice">Price</label>
                    <input type="number" id="fsPrice" step="0.5" min="4" max="48" value="{{ $config['fonts']['price'] }}">
                </div>
                <div>
                    <label class="fld" for="fsCost">Cost Code</label>
                    <input type="number" id="fsCost" step="0.5" min="4" max="48" value="{{ $config['fonts']['cost_code'] }}">
                </div>
            </div>
            <label class="fld" for="priceType">Price Type</label>
            <select id="priceType">
                <option value="inc_tax" @selected(($config['price_type'] ?? '') === 'inc_tax')>Inc. tax</option>
                <option value="ex_tax" @selected(($config['price_type'] ?? '') === 'ex_tax')>Ex. tax</option>
            </select>
            <label class="fld" for="currencyPrefix">Price prefix</label>
            <input type="text" id="currencyPrefix" value="{{ $config['currency_prefix'] }}">
        </section>

        <section class="card">
            <h3>Barcode</h3>
            <label class="fld" for="barcodePos">Barcode position</label>
            @php
                $bcPos = $config['barcode']['position'] ?? 'right';
                if (in_array($bcPos, ['top', 'left'], true)) {
                    $bcPos = 'left';
                } else {
                    $bcPos = 'right';
                }
            @endphp
            <select id="barcodePos">
                <option value="right" @selected($bcPos === 'right')>Right (horizontal)</option>
                <option value="left" @selected($bcPos === 'left')>Left (horizontal)</option>
            </select>
            <div class="row-2">
                <div>
                    <label class="fld" for="barcodeWidth">Barcode width</label>
                    <input type="number" id="barcodeWidth" min="20" max="120" value="{{ $config['barcode']['width'] }}">
                </div>
                <div>
                    <label class="fld" for="barcodeHeight">Barcode height</label>
                    <input type="number" id="barcodeHeight" min="8" max="80" value="{{ $config['barcode']['height'] }}">
                </div>
            </div>
            <label class="fld" for="barcodeThickness">Bar thickness</label>
            <input type="number" id="barcodeThickness" min="1" max="5" step="1" value="{{ $config['barcode']['thickness'] }}">
            <div class="hint">1–5 (click Rebuild to update image)</div>
        </section>

        <section class="card">
            <h3>Show on label</h3>
            <div class="checks">
                <label><input type="checkbox" id="showBusiness" @checked(!empty($config['show']['business_name']))> Business Name</label>
                <label><input type="checkbox" id="showItem" @checked(!empty($config['show']['item_name']))> Item Name</label>
                <label><input type="checkbox" id="showBarcode" @checked(!empty($config['show']['barcode']))> Barcode</label>
                <label><input type="checkbox" id="showSku" @checked(!empty($config['show']['sku']))> SKU</label>
                <label><input type="checkbox" id="showPrice" @checked(!empty($config['show']['price']))> Price</label>
                <label><input type="checkbox" id="showCost" @checked(!empty($config['show']['cost_code']))> Cost Code</label>
            </div>
            <div class="hint" style="margin-top:.75rem;">Layout is horizontal (landscape). Product type uses the product category.</div>
        </section>
    </div>

    <div class="actions">
        <button type="button" class="btn btn-apply" id="btnApply"><i class="fas fa-sync-alt"></i> Apply / Rebuild barcode</button>
        <button type="button" class="btn btn-save" id="btnSave"><i class="fas fa-save"></i> Save layout</button>
        <button type="button" class="btn btn-print" id="btnPrint"><i class="fas fa-print"></i> Print</button>
        <a href="{{ $closeUrl }}" class="btn btn-close"><i class="fas fa-times"></i> Close</a>
    </div>

    <div class="preview-area">
        <div class="preview-label-title">Live preview (first label)</div>
        <div class="preview-stage">
            <div class="label-preview" id="labelPreview"></div>
        </div>
    </div>
</div>

<div class="print-sheet" id="printSheet" aria-hidden="true"></div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const labels = @json($labels);
    const layoutName = @json($layout->name);
    const saveUrl = @json($saveUrl);
    const fontsMap = @json($googleFonts);
    const taxRate = @json((float) $taxRate);
    const taxEnabled = @json((bool) $taxEnabled);
    let barcodeDirty = true;

    const el = (id) => document.getElementById(id);

    function readConfig() {
        let pos = el('barcodePos').value || 'right';
        if (pos === 'top') pos = 'left';
        if (pos === 'middle' || pos === 'bottom') pos = 'right';
        let widthMm = Number(el('widthMm').value) || 38;
        let heightMm = Number(el('heightMm').value) || 25;
        // Horizontal / landscape sticker: width must be the longer edge
        if (heightMm > widthMm) {
            const swap = widthMm;
            widthMm = heightMm;
            heightMm = swap;
        }
        return {
            width_mm: widthMm,
            height_mm: heightMm,
            business_name: el('businessName').value || '',
            font_family: el('fontFamily').value || 'Roboto',
            price_type: el('priceType').value || 'inc_tax',
            currency_prefix: el('currencyPrefix').value || '',
            barcode: {
                position: pos === 'left' ? 'left' : 'right',
                width: Number(el('barcodeWidth').value) || 94,
                height: Number(el('barcodeHeight').value) || 25,
                thickness: Number(el('barcodeThickness').value) || 2,
            },
            fonts: {
                business_name: Number(el('fsBusiness').value) || 12,
                item_name: Number(el('fsItem').value) || 9,
                sku: Number(el('fsSku').value) || 8,
                price: Number(el('fsPrice').value) || 11,
                cost_code: Number(el('fsCost').value) || 8,
            },
            show: {
                business_name: el('showBusiness').checked,
                item_name: el('showItem').checked,
                barcode: el('showBarcode').checked,
                sku: el('showSku').checked,
                price: el('showPrice').checked,
                cost_code: el('showCost').checked,
            },
        };
    }

    function loadFont(family) {
        const spec = fontsMap[family] || 'Roboto:wght@400;700';
        el('googleFontLink').href = 'https://fonts.googleapis.com/css2?family=' + spec + '&display=swap';
    }

    function priceText(item, cfg) {
        let price = Number(item.selling_price || 0);
        if (cfg.price_type === 'ex_tax' && taxEnabled && taxRate > 0) {
            price = price / (1 + (taxRate / 100));
        }
        return (cfg.currency_prefix || '') + price.toFixed(2);
    }

    function buildLabelHtml(item, cfg, forPrint) {
        const show = cfg.show;
        const fonts = cfg.fonts;
        let pos = cfg.barcode.position || 'right';
        if (pos === 'top') pos = 'left';
        if (pos === 'middle' || pos === 'bottom') pos = 'right';
        const barcodeLeft = pos === 'left';

        let info = '<div class="lp-info">';
        if (show.business_name && cfg.business_name) {
            info += `<div class="lp-business" style="font-size:${fonts.business_name}pt">${escapeHtml(cfg.business_name)}</div>`;
        }
        if (show.item_name) {
            info += `<div class="lp-item" style="font-size:${fonts.item_name}pt">${escapeHtml(item.name || '')}</div>`;
        }
        if (item.category) {
            info += `<div class="lp-type" style="font-size:${Math.max(4, (fonts.sku || 8) * 0.95)}pt">Type: ${escapeHtml(item.category)}</div>`;
        }
        let meta = '<div class="lp-meta">';
        if (show.cost_code) {
            meta += `<div class="lp-cost" style="font-size:${fonts.cost_code}pt">${escapeHtml(item.cost_code || '')}</div>`;
        }
        if (show.price) {
            meta += `<div class="lp-price" style="font-size:${fonts.price}pt">${escapeHtml(priceText(item, cfg))}</div>`;
        }
        meta += '</div>';
        if (show.cost_code || show.price) {
            info += meta;
        }
        info += '</div>';

        let barcodeBlock = '';
        if (show.barcode || show.sku) {
            barcodeBlock += `<div class="lp-barcode-wrap">`;
            if (show.barcode) {
                const w = Math.max(20, Math.min(120, cfg.barcode.width));
                barcodeBlock += `<svg class="bc" style="width:${w}%;transform:none;"></svg>`;
            }
            if (show.sku) {
                barcodeBlock += `<div class="lp-sku" style="font-size:${fonts.sku}pt">${escapeHtml(item.barcode || item.code || '')}</div>`;
            }
            barcodeBlock += `</div>`;
        }

        const rowClass = 'lp-row' + (barcodeLeft ? ' lp-barcode-left' : '');
        return `<div class="${rowClass}">${info}${barcodeBlock}</div>`;
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function drawBarcodes(root, item, cfg) {
        root.querySelectorAll('svg.bc').forEach(svg => {
            try {
                JsBarcode(svg, String(item.barcode || item.code || '0'), {
                    format: 'CODE128',
                    displayValue: false,
                    margin: 0,
                    height: Math.max(12, Number(cfg.barcode.height) || 25),
                    width: Math.max(1, Math.min(5, Number(cfg.barcode.thickness) || 2)) * 0.9,
                });
            } catch (e) {
                svg.style.display = 'none';
            }
        });
    }

    function setPrintPageSize(cfg) {
        let style = document.getElementById('dynamicPageSize');
        if (!style) {
            style = document.createElement('style');
            style.id = 'dynamicPageSize';
            document.head.appendChild(style);
        }
        let w = Number(cfg.width_mm) || 38;
        let h = Number(cfg.height_mm) || 25;
        if (h > w) {
            const swap = w;
            w = h;
            h = swap;
        }
        // Landscape page matching physical sticker (width × height), no CSS rotation
        style.textContent = `
            @page {
                size: ${w}mm ${h}mm;
                margin: 0;
            }
            @media print {
                html, body, .print-sheet, .print-label {
                    width: ${w}mm !important;
                    height: ${h}mm !important;
                    transform: none !important;
                    writing-mode: horizontal-tb !important;
                }
            }
        `;
    }

    function renderPreview() {
        const cfg = readConfig();
        loadFont(cfg.font_family);
        const item = labels[0] || { name: 'Sample', barcode: '0000', selling_price: 0, cost_code: '', category: '' };
        const preview = el('labelPreview');
        // Keep designer inputs in sync if portrait values were swapped to landscape
        if (el('widthMm') && Number(el('widthMm').value) !== cfg.width_mm) {
            el('widthMm').value = cfg.width_mm;
        }
        if (el('heightMm') && Number(el('heightMm').value) !== cfg.height_mm) {
            el('heightMm').value = cfg.height_mm;
        }
        preview.style.width = cfg.width_mm + 'mm';
        preview.style.height = cfg.height_mm + 'mm';
        preview.style.fontFamily = "'" + cfg.font_family + "', sans-serif";
        preview.style.transform = 'none';
        preview.style.writingMode = 'horizontal-tb';
        preview.innerHTML = buildLabelHtml(item, cfg, false);
        drawBarcodes(preview, item, cfg);
        barcodeDirty = false;
    }

    function renderPrintSheet() {
        const cfg = readConfig();
        setPrintPageSize(cfg);
        const sheet = el('printSheet');
        sheet.innerHTML = '';
        labels.forEach(item => {
            const div = document.createElement('div');
            div.className = 'print-label';
            div.style.fontFamily = "'" + cfg.font_family + "', sans-serif";
            div.style.transform = 'none';
            div.style.writingMode = 'horizontal-tb';
            div.innerHTML = buildLabelHtml(item, cfg, true);
            sheet.appendChild(div);
            drawBarcodes(div, item, cfg);
        });
    }

    async function saveLayout() {
        const cfg = readConfig();
        const res = await fetch(saveUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                name: layoutName,
                is_default: true,
                config: cfg,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            alert(data.message || 'Could not save layout');
            return;
        }
        const btn = el('btnSave');
        const old = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Saved';
        setTimeout(() => btn.innerHTML = old, 1200);
    }

    ['widthMm','heightMm','businessName','fsBusiness','fsItem','fsSku','fsPrice','fsCost','priceType','currencyPrefix','barcodePos','barcodeWidth','barcodeHeight','fontFamily','showBusiness','showItem','showBarcode','showSku','showPrice','showCost']
        .forEach(id => {
            el(id)?.addEventListener('input', () => { barcodeDirty = true; renderPreview(); });
            el(id)?.addEventListener('change', () => { barcodeDirty = true; renderPreview(); });
        });

    el('barcodeThickness')?.addEventListener('input', () => { barcodeDirty = true; });
    el('barcodeThickness')?.addEventListener('change', () => { barcodeDirty = true; });

    el('btnApply')?.addEventListener('click', renderPreview);
    el('btnSave')?.addEventListener('click', saveLayout);
    el('btnPrint')?.addEventListener('click', () => {
        const cfg = readConfig();
        setPrintPageSize(cfg);
        renderPreview();
        renderPrintSheet();
        setTimeout(() => window.print(), 200);
    });

    // Keep @page size in sync when editing dimensions
    ['widthMm', 'heightMm'].forEach(id => {
        el(id)?.addEventListener('change', () => setPrintPageSize(readConfig()));
    });
    setPrintPageSize(readConfig());

    renderPreview();
})();
</script>
</body>
</html>
