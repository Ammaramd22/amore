@php
    $isEdit = isset($group);
    $oldModifiers = old('modifiers');
    if (is_array($oldModifiers) && count($oldModifiers)) {
        $rows = array_values($oldModifiers);
    } else {
        $rows = $modifierRows ?? [['id' => null, 'name' => '', 'price' => 0, 'is_preselected' => false, 'is_available' => true]];
    }
    $selectedProductIds = old('product_ids', $selectedProducts ?? []);
    $selectedBranchIds = old('branch_ids', $defaultBranchIds ?? []);
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Basic information</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $group->name ?? '') }}" placeholder="e.g. Ice Coffee Add-ons" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Display name</label>
                <input type="text" name="display_name" class="form-control"
                       value="{{ old('display_name', $group->display_name ?? '') }}" placeholder="Shown in POS (optional)">
                <div class="form-text">If empty, Name is used in POS.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Modifier type</label>
                <select name="selection_type" class="form-select">
                    <option value="list" @selected(old('selection_type', $group->selection_type ?? 'list') === 'list')>List modifier</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Display order</label>
                <input type="number" min="0" name="display_order" class="form-control"
                       value="{{ old('display_order', $group->display_order ?? 0) }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        @checked(old('is_active', $group->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($multiBranch) && ($branches ?? collect())->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><strong>Locations</strong></div>
    <div class="card-body">
        <label class="form-label">Branches</label>
        <select name="branch_ids[]" class="form-select select2" multiple data-placeholder="All branches">
            @foreach($branches as $b)
            <option value="{{ $b->id }}" @selected(collect($selectedBranchIds)->contains($b->id))>{{ $b->name }}</option>
            @endforeach
        </select>
        <div class="form-text">Leave all selected (or empty after save sync) to mean available at all branches. Uses the existing branch system.</div>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header"><strong>Channels</strong></div>
    <div class="card-body">
        <div class="form-check form-switch">
            <input type="hidden" name="show_in_pos" value="0">
            <input class="form-check-input" type="checkbox" name="show_in_pos" value="1" id="show_in_pos"
                @checked(old('show_in_pos', $group->show_in_pos ?? true))>
            <label class="form-check-label" for="show_in_pos">Points of sale (POS)</label>
        </div>
        <div class="form-text">Show this modifier set when cashiers customize an item in POS / Waiter.</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Modifier list</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addModifierRow()"><i class="fas fa-plus me-1"></i>Add modifier</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="modifierRowsTable">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Name</th>
                        <th style="width:140px;">Price</th>
                        <th style="width:90px;" class="text-center">Pre-select</th>
                        <th style="width:110px;" class="text-center">Available</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="modifierRowsBody">
                    @foreach($rows as $i => $row)
                    <tr class="modifier-row">
                        <td class="text-muted text-center"><i class="fas fa-grip-vertical"></i></td>
                        <td>
                            <input type="hidden" name="modifiers[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                            <input type="text" name="modifiers[{{ $i }}][name]" class="form-control" value="{{ $row['name'] ?? '' }}" placeholder="New modifier">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">LKR</span>
                                <input type="number" step="0.01" min="0" name="modifiers[{{ $i }}][price]" class="form-control" value="{{ number_format((float)($row['price'] ?? 0), 2, '.', '') }}">
                            </div>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="modifiers[{{ $i }}][is_preselected]" value="0">
                            <input type="checkbox" class="form-check-input" name="modifiers[{{ $i }}][is_preselected]" value="1" @checked(!empty($row['is_preselected']))>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="modifiers[{{ $i }}][is_available]" value="0">
                            <div class="form-check form-switch d-inline-block m-0">
                                <input class="form-check-input" type="checkbox" name="modifiers[{{ $i }}][is_available]" value="1" @checked(($row['is_available'] ?? true))>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()" title="Remove"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2 text-muted small">Additional price only — does not replace variation/base price. Zero price is allowed (e.g. No Sugar).</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Selection rules</strong></div>
    <div class="card-body">
        <p class="text-muted small">Defaults for this set. Required means the cashier must pick before adding the item.</p>
        <div class="form-check form-switch mb-3">
            <input type="hidden" name="require_selection" value="0">
            <input class="form-check-input" type="checkbox" name="require_selection" value="1" id="require_selection"
                @checked(old('require_selection', $group->require_selection ?? false))>
            <label class="form-check-label" for="require_selection">Require a selection</label>
        </div>
        <div class="form-check form-switch">
            <input type="hidden" name="allow_multiple" value="0">
            <input class="form-check-input" type="checkbox" name="allow_multiple" value="1" id="allow_multiple"
                @checked(old('allow_multiple', $group->allow_multiple ?? true))>
            <label class="form-check-label" for="allow_multiple">Allow more than one modifier to be selected</label>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Display settings</strong></div>
    <div class="card-body">
        <div class="form-check form-switch">
            <input type="hidden" name="hide_on_receipt" value="0">
            <input class="form-check-input" type="checkbox" name="hide_on_receipt" value="1" id="hide_on_receipt"
                @checked(old('hide_on_receipt', $group->hide_on_receipt ?? false))>
            <label class="form-check-label" for="hide_on_receipt">Hide modifiers on customer receipts</label>
        </div>
        <div class="form-text">Modifiers are still stored and shown on KOT/BOT / admin order details when hidden from the receipt.</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Apply to products</strong></div>
    <div class="card-body">
        <div class="border rounded p-3" style="max-height: 280px; overflow:auto;">
            @forelse($products as $product)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="product_ids[]" value="{{ $product->id }}" id="prod_{{ $product->id }}"
                    @checked(in_array($product->id, $selectedProductIds))>
                <label class="form-check-label" for="prod_{{ $product->id }}">{{ $product->name }}</label>
            </div>
            @empty
            <div class="text-muted">No products available.</div>
            @endforelse
        </div>
        <div class="form-text mt-2">One set can apply to many products. Products can have multiple sets.</div>
    </div>
</div>

<script>
let modifierRowIdx = {{ count($rows) }};
function addModifierRow() {
    const i = modifierRowIdx++;
    const tr = document.createElement('tr');
    tr.className = 'modifier-row';
    tr.innerHTML = `
        <td class="text-muted text-center"><i class="fas fa-grip-vertical"></i></td>
        <td>
            <input type="hidden" name="modifiers[${i}][id]" value="">
            <input type="text" name="modifiers[${i}][name]" class="form-control" value="" placeholder="New modifier">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">LKR</span>
                <input type="number" step="0.01" min="0" name="modifiers[${i}][price]" class="form-control" value="0.00">
            </div>
        </td>
        <td class="text-center">
            <input type="hidden" name="modifiers[${i}][is_preselected]" value="0">
            <input type="checkbox" class="form-check-input" name="modifiers[${i}][is_preselected]" value="1">
        </td>
        <td class="text-center">
            <input type="hidden" name="modifiers[${i}][is_available]" value="0">
            <div class="form-check form-switch d-inline-block m-0">
                <input class="form-check-input" type="checkbox" name="modifiers[${i}][is_available]" value="1" checked>
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()" title="Remove"><i class="fas fa-times"></i></button>
        </td>`;
    document.getElementById('modifierRowsBody').appendChild(tr);
}
</script>
