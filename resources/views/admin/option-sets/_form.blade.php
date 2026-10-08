@php
    $isEdit = isset($set);
    $oldOptions = old('options');
    if (is_array($oldOptions) && count($oldOptions)) {
        $rows = array_values($oldOptions);
    } else {
        $rows = $optionRows ?? [['id' => null, 'name' => '', 'color' => '', 'is_active' => true, 'variation_count' => 0, 'used_in_orders' => false]];
    }
    $selectedProductIds = old('product_ids', $selectedProducts ?? []);
    $setName = old('name', $set->name ?? 'Option set');
    $setType = old('type', $set->type ?? 'text');
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Details</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Option set name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="optionSetName" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $set->name ?? '') }}" placeholder="e.g. Milk Type" required
                       oninput="updateOptionsHeading()">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Display name</label>
                <input type="text" name="display_name" class="form-control"
                       value="{{ old('display_name', $set->display_name ?? '') }}" placeholder="e.g. Milk (shown in POS)">
            </div>
            <div class="col-md-12">
                <label class="form-label d-block">Option set type</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="type_text" value="text" @checked($setType === 'text') onchange="toggleColorCols()">
                    <label class="form-check-label" for="type_text">Text</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="type" id="type_text_color" value="text_color" @checked($setType === 'text_color') onchange="toggleColorCols()">
                    <label class="form-check-label" for="type_text_color">Text and color</label>
                </div>
                <div class="form-text">Restaurant sets (Milk, Sugar, Ice, Spice) usually use Text.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Display order</label>
                <input type="number" min="0" name="display_order" class="form-control" value="{{ old('display_order', $set->display_order ?? 0) }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="require_selection" value="0">
                    <input class="form-check-input" type="checkbox" name="require_selection" value="1" id="require_selection"
                        @checked(old('require_selection', $set->require_selection ?? true))>
                    <label class="form-check-label" for="require_selection">Require a selection in POS</label>
                </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        @checked(old('is_active', $set->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong id="optionsHeading">{{ $setName }} options</strong>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush" id="optionRowsList">
            @foreach($rows as $i => $row)
            <li class="list-group-item option-row {{ !empty($row['remove']) ? 'd-none' : '' }}" draggable="true" data-index="{{ $i }}">
                <input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}" class="opt-id">
                <input type="hidden" name="options[{{ $i }}][remove]" value="{{ !empty($row['remove']) ? '1' : '0' }}" class="opt-remove">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted drag-handle" title="Drag to reorder" style="cursor:grab;"><i class="fas fa-bars"></i></span>
                    <input type="text" name="options[{{ $i }}][name]" class="form-control form-control-sm flex-grow-1" style="min-width:160px;max-width:280px;"
                           value="{{ $row['name'] ?? '' }}" placeholder="e.g. Oat Milk">
                    <input type="color" name="options[{{ $i }}][color]" class="form-control form-control-sm form-control-color opt-color {{ $setType === 'text_color' ? '' : 'd-none' }}"
                           value="{{ $row['color'] ?: '#cccccc' }}" title="Color">
                    <span class="text-muted small text-nowrap opt-usage">
                        {{ (int) ($row['variation_count'] ?? 0) }} item variation{{ (int) ($row['variation_count'] ?? 0) === 1 ? '' : 's' }}
                    </span>
                    <input type="hidden" name="options[{{ $i }}][is_active]" value="0">
                    <div class="form-check form-switch m-0" title="Available">
                        <input class="form-check-input" type="checkbox" name="options[{{ $i }}][is_active]" value="1" @checked($row['is_active'] ?? true)>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="markOptionRemoved(this)" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @if(!empty($row['used_in_orders']))
                <div class="small text-warning mt-1">Used on past orders — remove will deactivate (history kept).</div>
                @endif
            </li>
            @endforeach
        </ul>
        <div class="p-3 border-top">
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" onclick="addOptionRow()">
                <i class="fas fa-plus me-1"></i>Add option
            </button>
            <div class="form-text mt-2">To delete an option set, first delete all options. Drag rows to reorder (saved on Save).</div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Apply to products</strong></div>
    <div class="card-body">
        <div class="border rounded p-3" style="max-height:260px;overflow:auto;">
            @forelse($products as $product)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="product_ids[]" value="{{ $product->id }}" id="opt_prod_{{ $product->id }}"
                    @checked(in_array($product->id, $selectedProductIds))>
                <label class="form-check-label" for="opt_prod_{{ $product->id }}">{{ $product->name }}</label>
            </div>
            @empty
            <div class="text-muted">No products available.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
let optionRowIdx = {{ count($rows) }};
let dragEl = null;

function updateOptionsHeading() {
    const name = document.getElementById('optionSetName')?.value?.trim() || 'Option set';
    const el = document.getElementById('optionsHeading');
    if (el) el.textContent = name + ' options';
}

function toggleColorCols() {
    const show = document.getElementById('type_text_color')?.checked;
    document.querySelectorAll('.opt-color').forEach(el => {
        el.classList.toggle('d-none', !show);
    });
}

function addOptionRow() {
    const i = optionRowIdx++;
    const showColor = document.getElementById('type_text_color')?.checked;
    const li = document.createElement('li');
    li.className = 'list-group-item option-row';
    li.draggable = true;
    li.dataset.index = String(i);
    li.innerHTML = `
        <input type="hidden" name="options[${i}][id]" value="" class="opt-id">
        <input type="hidden" name="options[${i}][remove]" value="0" class="opt-remove">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-muted drag-handle" title="Drag to reorder" style="cursor:grab;"><i class="fas fa-bars"></i></span>
            <input type="text" name="options[${i}][name]" class="form-control form-control-sm flex-grow-1" style="min-width:160px;max-width:280px;" value="" placeholder="New option">
            <input type="color" name="options[${i}][color]" class="form-control form-control-sm form-control-color opt-color ${showColor ? '' : 'd-none'}" value="#cccccc" title="Color">
            <span class="text-muted small text-nowrap opt-usage">0 item variations</span>
            <input type="hidden" name="options[${i}][is_active]" value="0">
            <div class="form-check form-switch m-0" title="Available">
                <input class="form-check-input" type="checkbox" name="options[${i}][is_active]" value="1" checked>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="markOptionRemoved(this)" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    document.getElementById('optionRowsList').appendChild(li);
    bindDrag(li);
}

function markOptionRemoved(btn) {
    const li = btn.closest('.option-row');
    if (!li) return;
    const id = li.querySelector('.opt-id')?.value;
    const removeInput = li.querySelector('.opt-remove');
    if (id) {
        if (!confirm('Remove this option from the set?')) return;
        if (removeInput) removeInput.value = '1';
        li.classList.add('d-none');
    } else {
        li.remove();
    }
}

function bindDrag(li) {
    li.addEventListener('dragstart', (e) => {
        dragEl = li;
        li.classList.add('opacity-50');
        e.dataTransfer.effectAllowed = 'move';
    });
    li.addEventListener('dragend', () => {
        li.classList.remove('opacity-50');
        dragEl = null;
        reindexOptionNames();
    });
    li.addEventListener('dragover', (e) => {
        e.preventDefault();
        const list = document.getElementById('optionRowsList');
        const after = [...list.querySelectorAll('.option-row:not(.d-none)')].find(row => {
            const box = row.getBoundingClientRect();
            return e.clientY < box.top + box.height / 2;
        });
        if (!dragEl || dragEl === after) return;
        if (after) list.insertBefore(dragEl, after);
        else list.appendChild(dragEl);
    });
}

function reindexOptionNames() {
    // Keep existing name attributes; order in DOM is what syncOptions uses (array order).
}

document.querySelectorAll('#optionRowsList .option-row').forEach(bindDrag);
updateOptionsHeading();
toggleColorCols();
</script>
