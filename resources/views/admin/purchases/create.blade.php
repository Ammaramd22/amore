@extends('layouts.admin')
@section('title', 'New Purchase')
@section('page_title', 'New Purchase')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Create Purchase</h3>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('purchases.store') }}" id="purchaseForm" class="purchase-form">
            @csrf
            @if($errors->has('error'))
            <div class="alert alert-danger">{{ $errors->first('error') }}</div>
            @endif

            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Purchase Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                            <div class="input-group supplier-input-group">
                                <div class="flex-grow-1">
                                    <select id="supplier_id" name="supplier_id" class="form-select select2" required>
                                        <option value="">Select supplier</option>
                                        @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal" title="Add Supplier">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="form-text">Direct products list is limited to this supplier’s products.</div>
                        </div>
                        <div class="col-lg-3">
                            <label for="invoice_number" class="form-label">Supplier Invoice</label>
                            <input type="text" id="invoice_number" name="invoice_number" class="form-control" placeholder="Optional supplier invoice #">
                            <div class="form-text">Purchase No (PUR-xxxx) is assigned automatically.</div>
                        </div>
                        <div class="col-lg-3">
                            <label for="purchase_date" class="form-label">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" id="purchase_date" name="purchase_date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Any notes about this purchase..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><i class="fas fa-list-ul me-2 text-primary"></i>Purchase Items</h6>
                        <small class="text-muted">Set <strong>Expiry date</strong> on each line — updates product/ingredient for dashboard reminders.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i>Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover purchase-items-table mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 130px;">Type</th>
                                    <th>Item</th>
                                    <th style="width: 110px;">Qty</th>
                                    <th style="width: 140px;">Unit Price</th>
                                    <th style="width: 130px;">Total</th>
                                    <th style="width: 140px;">Expiry date</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][item_type]" class="form-select form-select-sm item-type-select" onchange="changeItemType(this)">
                                            <option value="ingredient">Ingredient</option>
                                            <option value="product">Direct Product</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="ingredient-select-wrapper">
                                            <select name="items[0][ingredient_id]" class="form-select form-select-sm item-select ingredient-select" onchange="showItemInfo(this)" required>
                                                <option value="">Select ingredient</option>
                                                @foreach($ingredients as $ing)<option value="{{ $ing->id }}" data-last-price="{{ $lastPrices['ing_'.$ing->id] ?? '' }}">{{ $ing->name }}</option>@endforeach
                                            </select>
                                            <div class="item-info text-muted small mt-1"></div>
                                        </div>
                                        <div class="product-select-wrapper d-none">
                                            <div class="input-group input-group-sm">
                                                <select name="items[0][product_id]" class="form-select form-select-sm item-select product-select" onchange="showItemInfo(this)">
                                                    <option value="">Select product</option>
                                                    {{-- Options filled by JS from supplier filter --}}
                                                </select>
                                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addProductModal" onclick="setProductRowIndex(this)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <div class="item-info text-muted small mt-1"></div>
                                        </div>
                                    </td>
                                    <td><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm qty-input" required oninput="calculateRowTotal(this)"></td>
                                    <td><input type="number" step="0.0001" name="items[0][unit_price]" class="form-control form-control-sm price-input" required oninput="calculateRowTotal(this)"></td>
                                    <td><input type="text" class="form-control form-control-sm row-total" readonly value="0.00"></td>
                                    <td><input type="date" name="items[0][expiry_date]" class="form-control form-control-sm" title="Optional — updates product/ingredient expiry"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Grand Total</th>
                                    <th class="grand-total">0.00</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0"><i class="fas fa-credit-card me-2 text-primary"></i>Payment</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select id="payment_method" name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label for="payment_amount" class="form-label">Amount Paid</label>
                            <input type="number" step="0.01" id="payment_amount" name="payment_amount" class="form-control" placeholder="0.00" oninput="updateBalance()">
                        </div>
                        <div class="col-lg-3">
                            <label for="payment_reference" class="form-label">Reference</label>
                            <input type="text" id="payment_reference" name="payment_reference" class="form-control" placeholder="Ref #">
                        </div>
                        <div class="col-lg-3">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" value="{{ today()->format('Y-m-d') }}">
                        </div>
                    </div>
                    @if(\App\Models\Cheque::managementEnabled())
                    <div id="chequeFields" class="row g-3 mt-1 d-none">
                        <div class="col-12"><hr class="my-1"><div class="small fw-semibold text-muted">Cheque details</div></div>
                        <div class="col-lg-3">
                            <label class="form-label">Cheque number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_number" id="cheque_number" class="form-control">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Due date <span class="text-danger">*</span></label>
                            <input type="date" name="cheque_due_date" id="cheque_due_date" class="form-control" value="{{ today()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Bank name</label>
                            <input type="text" name="cheque_bank_name" class="form-control">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Cheque date</label>
                            <input type="date" name="cheque_date" class="form-control" value="{{ today()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <script>
                        (function () {
                            const method = document.getElementById('payment_method');
                            const box = document.getElementById('chequeFields');
                            const sync = () => box.classList.toggle('d-none', method.value !== 'cheque');
                            method?.addEventListener('change', sync);
                            sync();
                        })();
                    </script>
                    @endif
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end gap-4">
                                <div class="text-end">
                                    <div class="text-muted small">Total Amount</div>
                                    <div class="fw-bold fs-5" id="summaryTotal">0.00</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Paid</div>
                                    <div class="fw-bold fs-5 text-success" id="summaryPaid">0.00</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Balance</div>
                                    <div class="fw-bold fs-5 text-danger" id="summaryBalance">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('purchases.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Purchase</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-primary"></i>Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="supplierForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">City</label><input type="text" name="city" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Tax Number</label><input type="text" name="tax_number" class="form-control"></div>
                        <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-tags me-2 text-primary"></i>Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="category_type" required>
                                <option value="kot">KOT Product</option>
                                <option value="direct" selected>Direct Item</option>
                                <option value="bot">BOT Product</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control" value="#e74c3c">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kitchen / Bar</label>
                            <select name="kitchen_id" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach($kitchens as $kitchen)
                                <option value="{{ $kitchen->id }}">{{ $kitchen->name }} ({{ strtoupper((string) ($kitchen->type ?? '')) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="show_in_pos" value="1" id="cat_show_in_pos" checked>
                                <label class="form-check-label" for="cat_show_in_pos">Show in POS</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="show_in_qr" value="1" id="cat_show_in_qr" checked>
                                <label class="form-check-label" for="cat_show_in_qr">Show in QR</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Save Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-box-open me-2 text-primary"></i>Add Direct Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    @csrf
                    <input type="hidden" id="product_row_index" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select name="category_id" class="form-select" id="product_category_id" required onchange="loadSubcategories(this)">
                                    <option value="">Select category</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" data-type="{{ $cat->type }}">{{ $cat->name }} ({{ strtoupper((string) ($cat->type ?? '')) }})</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subcategory</label>
                            <select name="subcategory_id" class="form-select" id="product_subcategory_id">
                                <option value="">Select subcategory</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-generated if empty">
                            <div class="form-text">Leave empty to auto-generate from product name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" required value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="track_stock" value="1" id="track_stock" checked>
                                <label class="form-check-label" for="track_stock">Track stock</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
            </div>
        </div>
    </div>
</div>

<style>
    .purchase-items-table .select2-container {
        width: 100% !important;
    }
    .purchase-items-table .select2-container .select2-selection--single {
        height: 36px !important;
        min-height: 36px !important;
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
    }
    .purchase-items-table .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 34px !important;
        padding-left: 10px !important;
        font-size: 0.85rem;
    }
    .purchase-items-table .select2-container .select2-selection--single .select2-selection__arrow {
        height: 34px !important;
    }
    .purchase-items-table .input-group-sm .select2-container .select2-selection--single {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .purchase-items-table .form-control-sm,
    .purchase-items-table .form-select-sm {
        min-height: 36px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    .purchase-items-table .input-group-sm .btn {
        padding: 6px 10px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .purchase-form ::placeholder {
        color: #94a3b8;
        opacity: 1;
    }
    .supplier-input-group {
        display: flex;
        align-items: stretch;
    }
    .supplier-input-group .flex-grow-1 {
        flex: 1 1 auto;
        min-width: 0;
    }
    .supplier-input-group .select2-container {
        width: 100% !important;
        display: block !important;
    }
    .supplier-input-group .select2-container .select2-selection--single {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
        height: 44px !important;
        min-height: 44px !important;
        border-right: none !important;
    }
    .supplier-input-group .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important;
    }
    .supplier-input-group .select2-container .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
    }
    .supplier-input-group .btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        padding: 10px 14px;
        display: flex;
        align-items: center;
    }
    .row-total, .grand-total {
        font-weight: 700;
        font-size: 0.95rem;
    }
    .purchase-form .form-label {
        color: #334155;
        font-size: 0.9rem;
        font-weight: 600;
    }
    .purchase-items-table tfoot th {
        background: #f8fafc;
    }
    .purchase-items-table .btn-link {
        text-decoration: none;
        font-size: 1rem;
        padding: 4px 8px;
    }
    .item-info {
        font-size: 0.75rem;
        line-height: 1.3;
    }
    .item-info .badge {
        font-size: 0.7rem;
        font-weight: 500;
    }
</style>

<script>
let itemIndex = 1;
let currentProductRow = 0;
const ingredientsOptions = `@foreach($ingredients as $ing)<option value="{{ $ing->id }}" data-last-price="{{ $lastPrices['ing_'.$ing->id] ?? '' }}">{{ $ing->name }}</option>@endforeach`;
const allProducts = @json($productsJson);

function productMatchesSupplier(product, supplierId) {
    if (!supplierId) return true;
    if (!product.supplier_ids || product.supplier_ids.length === 0) return false;
    return product.supplier_ids.map(String).includes(String(supplierId));
}

function filteredProducts() {
    const supplierId = document.getElementById('supplier_id')?.value || '';
    return allProducts.filter(p => productMatchesSupplier(p, supplierId));
}

function buildProductOptionsHtml(selectedId) {
    const products = filteredProducts();
    let html = '<option value="">Select product</option>';
    products.forEach(p => {
        const selected = selectedId && String(selectedId) === String(p.id) ? ' selected' : '';
        const stock = Number(p.stock).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 3 });
        html += `<option value="${p.id}" data-last-price="${p.last_price || ''}" data-selling-price="${p.selling_price || '0'}"${selected}>${p.name} (Stock: ${stock})</option>`;
    });
    return html;
}

function refreshProductSelects(clearInvalid = true) {
    document.querySelectorAll('.product-select').forEach(select => {
        const prev = $(select).val();
        const stillValid = prev && filteredProducts().some(p => String(p.id) === String(prev));
        const keep = clearInvalid ? (stillValid ? prev : '') : prev;
        const wasSelect2 = $(select).hasClass('select2-hidden-accessible');
        if (wasSelect2) {
            $(select).select2('destroy');
        }
        select.innerHTML = buildProductOptionsHtml(keep);
        $(select).select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#purchaseForm') })
            .on('select2:select', function () { showItemInfo(this); });
        if (keep) {
            $(select).val(keep).trigger('change');
        } else if (prev && clearInvalid) {
            $(select).val('').trigger('change');
            const info = select.closest('td')?.querySelector('.product-select-wrapper .item-info');
            if (info) info.innerHTML = '';
        }
    });
}

function addItem() {
    const html = `
        <tr>
            <td>
                <select name="items[${itemIndex}][item_type]" class="form-select form-select-sm item-type-select" onchange="changeItemType(this)">
                    <option value="ingredient">Ingredient</option>
                    <option value="product">Direct Product</option>
                </select>
            </td>
            <td>
                <div class="ingredient-select-wrapper">
                    <select name="items[${itemIndex}][ingredient_id]" class="form-select form-select-sm item-select ingredient-select" onchange="showItemInfo(this)" required>
                        <option value="">Select ingredient</option>
                        ${ingredientsOptions}
                    </select>
                    <div class="item-info text-muted small mt-1"></div>
                </div>
                <div class="product-select-wrapper d-none">
                    <div class="input-group input-group-sm">
                        <select name="items[${itemIndex}][product_id]" class="form-select form-select-sm item-select product-select" onchange="showItemInfo(this)">
                            ${buildProductOptionsHtml()}
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addProductModal" onclick="setProductRowIndex(this)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="item-info text-muted small mt-1"></div>
                </div>
            </td>
            <td><input type="number" step="0.001" name="items[${itemIndex}][quantity]" class="form-control form-control-sm qty-input" required oninput="calculateRowTotal(this)"></td>
            <td><input type="number" step="0.0001" name="items[${itemIndex}][unit_price]" class="form-control form-control-sm price-input" required oninput="calculateRowTotal(this)"></td>
            <td><input type="text" class="form-control form-control-sm row-total" readonly value="0.00"></td>
            <td><input type="date" name="items[${itemIndex}][expiry_date]" class="form-control form-control-sm" title="Optional — updates product/ingredient expiry"></td>
            <td><button type="button" class="btn btn-sm btn-link text-danger" onclick="this.closest('tr').remove(); recalculateTotal()"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
    const row = document.querySelector('#itemsTable tbody').lastElementChild;
    $(row).find('.item-select').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#purchaseForm') })
        .on('select2:select', function(e) { showItemInfo(this); });
    itemIndex++;
}

function changeItemType(select) {
    const row = select.closest('tr');
    const type = select.value;
    const ingredientWrapper = row.querySelector('.ingredient-select-wrapper');
    const productWrapper = row.querySelector('.product-select-wrapper');
    const ingredientSelect = row.querySelector('.ingredient-select');
    const productSelect = row.querySelector('.product-select');

    if (type === 'product') {
        ingredientWrapper.classList.add('d-none');
        ingredientSelect.removeAttribute('required');
        $(ingredientSelect).val('').trigger('change');
        productWrapper.classList.remove('d-none');
        productSelect.setAttribute('required', 'required');
    } else {
        productWrapper.classList.add('d-none');
        productSelect.removeAttribute('required');
        $(productSelect).val('').trigger('change');
        ingredientWrapper.classList.remove('d-none');
        ingredientSelect.setAttribute('required', 'required');
    }
    const visibleSelect = row.querySelector('.ingredient-select-wrapper:not(.d-none) .ingredient-select, .product-select-wrapper:not(.d-none) .product-select');
    showItemInfo(visibleSelect);
}

function showItemInfo(select) {
    if (!select) return;
    const wrapper = select.closest('.ingredient-select-wrapper, .product-select-wrapper');
    if (!wrapper) return;
    const infoDiv = wrapper.querySelector('.item-info');
    if (!infoDiv) return;
    const selected = select.options[select.selectedIndex];
    if (!selected || !selected.value) {
        infoDiv.innerHTML = '';
        return;
    }
    const lastPrice = selected.getAttribute('data-last-price');
    const sellingPrice = selected.getAttribute('data-selling-price');
    let html = '';
    if (lastPrice && parseFloat(lastPrice) > 0) {
        html += `<span class="badge bg-light text-dark border me-1">Last: LKR ${parseFloat(lastPrice).toFixed(2)}</span>`;
    }
    if (sellingPrice && parseFloat(sellingPrice) > 0) {
        html += `<span class="badge bg-light text-dark border">Selling: LKR ${parseFloat(sellingPrice).toFixed(2)}</span>`;
    }
    infoDiv.innerHTML = html;
}

function setProductRowIndex(btn) {
    const row = btn.closest('tr');
    const rows = Array.from(document.querySelectorAll('#itemsTable tbody tr'));
    currentProductRow = rows.indexOf(row);
}

function calculateRowTotal(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const total = qty * price;
    row.querySelector('.row-total').value = total.toFixed(2);
    recalculateTotal();
}

function recalculateTotal() {
    let grand = 0;
    document.querySelectorAll('.row-total').forEach(el => {
        grand += parseFloat(el.value) || 0;
    });
    document.querySelector('.grand-total').textContent = grand.toFixed(2);
    updateBalance();
}

function updateBalance() {
    const total = parseFloat(document.querySelector('.grand-total').textContent) || 0;
    const paid = parseFloat(document.getElementById('payment_amount').value) || 0;
    const balance = Math.max(0, total - paid);
    document.getElementById('summaryTotal').textContent = total.toFixed(2);
    document.getElementById('summaryPaid').textContent = paid.toFixed(2);
    document.getElementById('summaryBalance').textContent = balance.toFixed(2);
}

function saveSupplier() {
    const form = document.getElementById('supplierForm');
    const formData = new FormData(form);
    fetch('{{ route('suppliers.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': formData.get('_token'), 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const supplierSelect = document.getElementById('supplier_id');
            const option = new Option(data.supplier.name, data.supplier.id, true, true);
            supplierSelect.add(option);
            $(supplierSelect).trigger('change');
            bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
            form.reset();
            showToast('success', 'Supplier added');
        } else {
            showToast('error', 'Failed to add supplier');
        }
    })
    .catch(() => showToast('error', 'Network error'));
}

const subcategoryUrlTemplate = '{{ route('products.subcategories', ['category' => '__ID__']) }}';
function loadSubcategories(select) {
    const categoryId = select.value;
    const subSelect = document.getElementById('product_subcategory_id');
    subSelect.innerHTML = '<option value="">Select subcategory</option>';
    if (!categoryId) return;
    const url = subcategoryUrlTemplate.replace('__ID__', categoryId);
    fetch(url)
        .then(r => r.json())
        .then(data => {
            data.forEach(sub => {
                const option = new Option(sub.name, sub.id);
                subSelect.add(option);
            });
        })
        .catch(() => {});
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    fetch('{{ route('categories.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': formData.get('_token'), 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const categorySelect = document.getElementById('product_category_id');
            const option = new Option(`${data.category.name} (${data.category.type.toUpperCase()})`, data.category.id, true, true);
            categorySelect.add(option);
            loadSubcategories(categorySelect);
            bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
            form.reset();
            showToast('success', 'Category added');
        } else {
            showToast('error', 'Failed to add category');
        }
    })
    .catch(() => showToast('error', 'Network error'));
}

function saveProduct() {
    const form = document.getElementById('productForm');
    const formData = new FormData(form);
    formData.append('is_available', '1');
    formData.append('show_in_pos', '1');
    formData.append('show_in_qr', '1');
    formData.append('stock_quantity', '0');
    const supplierId = document.getElementById('supplier_id')?.value;
    if (supplierId) {
        formData.append('supplier_ids[]', supplierId);
    }
    fetch('{{ route('products.store') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': formData.get('_token'), 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async r => {
        const data = await r.json();
        if (!r.ok) {
            const messages = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'Validation failed');
            throw new Error(messages);
        }
        return data;
    })
    .then(data => {
        const p = data.product;
        allProducts.push({
            id: p.id,
            name: p.name,
            stock: 0,
            selling_price: String(p.selling_price || '0'),
            last_price: '0',
            supplier_ids: supplierId ? [parseInt(supplierId, 10)] : [],
        });
        refreshProductSelects(false);
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        const row = rows[currentProductRow];
        const productSelect = row.querySelector('.product-select');
        $(productSelect).val(String(p.id)).trigger('change');
        showItemInfo(productSelect);
        bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
        form.reset();
        document.getElementById('product_subcategory_id').innerHTML = '<option value="">Select subcategory</option>';
        showToast('success', 'Product added');
    })
    .catch(err => showToast('error', err.message || 'Failed to add product'));
}

$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#purchaseForm') });
    refreshProductSelects(false);
    $('#supplier_id').on('change', function () {
        refreshProductSelects(true);
    });
    $('.ingredient-select').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#purchaseForm') })
        .on('select2:select', function(e) { showItemInfo(this); });
});
</script>
@endsection
