<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dine In — ResPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #1a1a2e; color: #fff; font-family: 'Segoe UI', sans-serif; margin: 0; min-height: 100vh; }
        .di-header {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            padding: 16px 24px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .di-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .di-header .nav-links { display: flex; gap: 10px; }
        .di-header a, .di-header button {
            color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 10px;
            background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);
            font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: all 0.2s;
        }
        .di-header a:hover, .di-header button:hover { background: rgba(255,255,255,0.25); }

        .di-container { padding: 24px; }

        /* Floor Tabs */
        .floor-tabs { display: flex; gap: 10px; margin-bottom: 24px; }
        .floor-tab {
            padding: 12px 24px; border-radius: 12px; border: 2px solid #0f3460;
            background: #16213e; color: #aaa; font-weight: 600; cursor: pointer;
            transition: all 0.2s;
        }
        .floor-tab.active {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff; border-color: transparent;
        }

        /* Table Grid */
        .tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; }
        .table-card {
            background: linear-gradient(135deg, #16213e, #1a1a2e);
            border-radius: 16px; border: 2px solid #0f3460; padding: 20px;
            text-align: center; cursor: pointer; transition: all 0.3s ease; position: relative;
        }
        .table-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .table-card.available { border-color: #00b894; }
        .table-card.available:hover { border-color: #00d68f; }
        .table-card.occupied { border-color: #e94560; }
        .table-card.occupied:hover { border-color: #ff6b6b; }
        .table-card .table-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .table-card.available .table-icon { color: #00b894; }
        .table-card.occupied .table-icon { color: #e94560; }
        .table-card .table-name { font-size: 1.1rem; font-weight: 700; }
        .table-card .table-info { font-size: 0.8rem; color: #888; margin-top: 4px; }
        .table-card .status-badge {
            position: absolute; top: 10px; right: 10px;
            padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
        }
        .table-card.available .status-badge { background: rgba(0,184,148,0.2); color: #00b894; }
        .table-card.occupied .status-badge { background: rgba(233,69,96,0.2); color: #e94560; }
        .table-card .order-info {
            margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem; color: #fdcb6e;
        }

        /* Tab Layout */
        .nav-tabs { border-bottom: 2px solid #0f3460; margin-bottom: 24px; }
        .nav-tabs .nav-link {
            color: #888; background: transparent; border: none;
            padding: 12px 24px; font-weight: 600; border-radius: 12px 12px 0 0;
        }
        .nav-tabs .nav-link.active {
            color: #fff; background: linear-gradient(135deg, #f59e0b, #ea580c);
        }
        .tab-content { padding: 0; }

        /* Two Column Layout */
        .di-split { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .di-left { background: #16213e; border-radius: 16px; padding: 20px; }
        .di-right {
            background: linear-gradient(135deg, #16213e, #1a1a2e);
            border-radius: 16px; border: 2px solid #0f3460; padding: 24px;
            min-height: 400px;
        }
        .di-right.selected { border-color: #f59e0b; }

        /* Selected Table Info */
        .selected-table-header { text-align: center; margin-bottom: 20px; }
        .selected-table-header .big-icon { font-size: 3rem; color: #f59e0b; margin-bottom: 10px; }
        .selected-table-header h3 { font-size: 1.5rem; margin: 0; }
        .selected-table-header .subtitle { color: #888; font-size: 0.9rem; }

        .order-details-list { margin: 16px 0; }
        .order-item-row {
            display: flex; justify-content: space-between; padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem;
        }
        .action-buttons { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .action-btn {
            padding: 12px 16px; border-radius: 10px; border: none;
            font-weight: 600; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .action-btn.pos { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
        .action-btn.change { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #fff; }
        .action-btn.bill { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
        .action-btn:hover { transform: translateY(-2px); opacity: 0.9; }

        /* Orders List */
        .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .order-card {
            background: linear-gradient(135deg, #16213e, #1a1a2e);
            border-radius: 16px; border: 2px solid rgba(255,255,255,0.06); padding: 20px;
        }
        .order-card .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .order-card .order-num { font-size: 1.2rem; font-weight: 700; color: #fdcb6e; }
        .order-card .order-table { font-size: 0.85rem; color: #888; }
        .order-card .order-items { font-size: 0.9rem; color: #ccc; margin-bottom: 12px; }
        .order-card .order-footer { display: flex; justify-content: space-between; align-items: center; }
        .order-card .order-total { font-size: 1.1rem; font-weight: 700; color: #00b894; }
        .order-card .btn-change-table {
            padding: 6px 14px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff; font-size: 0.8rem; font-weight: 600; cursor: pointer;
        }
        .order-card .btn-bill {
            padding: 6px 14px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff; font-size: 0.8rem; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-block;
        }
        .empty-state { text-align: center; padding: 60px 20px; color: #555; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; display: block; }
        .table-card.selected-card { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.3); }

        /* Billing Panel */
        .billing-panel { display: none; }
        .billing-panel.active { display: block; }
        .product-grid-bill {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 16px;
        }
        .product-btn {
            background: linear-gradient(135deg, #16213e, #1a1a2e);
            border: 2px solid #0f3460;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        .product-btn:hover { border-color: #f59e0b; transform: translateY(-2px); }
        .product-btn .price { color: #00b894; font-weight: 700; margin-top: 4px; }
        .bill-cart {
            background: rgba(15, 52, 96, 0.5);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            max-height: 200px;
            overflow-y: auto;
        }
        .bill-cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .bill-cart-item:last-child { border-bottom: none; }
        .bill-total {
            font-size: 1.4rem;
            font-weight: 700;
            color: #00b894;
            text-align: right;
            margin: 16px 0;
        }
        .btn-checkout {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-checkout:hover { opacity: 0.9; }
        .category-tabs-bill {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            overflow-x: auto;
        }
        .category-tab-bill {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #0f3460;
            background: #16213e;
            color: #aaa;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
        }
        .category-tab-bill.active {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff;
            border-color: transparent;
        }
    </style>
</head>
<body>
    <div class="di-header">
        <h1><i class="fas fa-utensils me-2"></i>Dine In Manager</h1>
        <div class="nav-links">
            <a href="{{ route('pos.index') }}" target="_blank"><i class="fas fa-cash-register me-1"></i>POS Billing</a>
            @if(\App\Models\Setting::get('kitchen_display_enabled', true) && \App\Models\Setting::get('kot_confirmation_enabled', true))
            <a href="{{ route('kitchen.display') }}" target="_blank"><i class="fas fa-fire me-1"></i>Kitchen</a>
            @endif
            <a href="{{ route('customer.display') }}" target="_blank"><i class="fas fa-tv me-1"></i>Display</a>
            <button onclick="loadDineIn()"><i class="fas fa-sync me-1"></i>Refresh</button>
        </div>
    </div>

    <div class="di-container">
        <!-- Main Tabs: Tables | Active Orders -->
        <ul class="nav nav-tabs" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tables-tab" data-bs-toggle="tab" data-bs-target="#tables-pane" type="button" role="tab">
                    <i class="fas fa-th-large me-2"></i>Tables
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab">
                    <i class="fas fa-fire me-2"></i>Active Orders
                </button>
            </li>
        </ul>

        <div class="tab-content" id="mainTabContent">
            <!-- Tables Tab: Left (Table Grid) + Right (Selected Table Info) -->
            <div class="tab-pane fade show active" id="tables-pane" role="tabpanel">
                <div class="di-split">
                    <!-- Left: Table Grid with Floor Tabs -->
                    <div class="di-left">
                        <div class="floor-tabs" id="floorTabs">
                            <button class="floor-tab active" onclick="filterFloor('all', this)">All Floors</button>
                        </div>
                        <div class="tables-grid" id="tablesGrid">
                            <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading tables...</p></div>
                        </div>
                    </div>

                    <!-- Right: Selected Table Details -->
                    <div class="di-right" id="selectedTablePanel">
                        <div class="empty-state">
                            <i class="fas fa-hand-pointer"></i>
                            <p>Select a table to view details</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Orders Tab -->
            <div class="tab-pane fade" id="orders-pane" role="tabpanel">
                <div class="orders-grid" id="ordersList">
                    <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading orders...</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Table Modal -->
    <div class="modal fade" id="changeTableModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #16213e; color: #fff; border: 1px solid #0f3460; border-radius: 16px;">
                <div class="modal-header" style="border-bottom: 1px solid #0f3460;">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Change Table</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="changeTableText">Select a new table for this order:</p>
                    <div id="availableTablesList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Preview Modal -->
    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-hidden="true">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentOrderId = null;
        let allTables = [];
        let allOrders = [];
        let currentFloor = 'all';
        let selectedTableId = null;

        function filterFloor(floor, btn) {
            currentFloor = floor;
            document.querySelectorAll('.floor-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderTables();
        }

        function loadDineIn() {
            fetch('/pos/dine-in/orders')
                .then(r => r.json())
                .then(data => {
                    allTables = data.tables || [];
                    allOrders = data.orders || [];
                    renderFloors(data.tables);
                    renderTables();
                    renderOrders(allOrders);
                    if (selectedTableId) renderSelectedPanel();
                })
                .catch(e => console.error('Load dine-in failed:', e));
        }

        function renderFloors(tables) {
            const floors = [...new Set(tables.map(t => t.floor_name))];
            const container = document.getElementById('floorTabs');
            container.innerHTML = `
                <button class="floor-tab ${currentFloor === 'all' ? 'active' : ''}" onclick="filterFloor('all', this)">All Floors</button>
                ${floors.map(f => `
                    <button class="floor-tab ${currentFloor === f ? 'active' : ''}" onclick="filterFloor('${f}', this)">${f}</button>
                `).join('')}
            `;
        }

        function renderTables() {
            const container = document.getElementById('tablesGrid');
            const tables = currentFloor === 'all'
                ? allTables
                : allTables.filter(t => t.floor_name === currentFloor);

            if (!tables.length) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-th-large"></i><p>No tables on this floor</p></div>';
                return;
            }

            container.innerHTML = tables.map(t => `
                <div class="table-card ${t.status} ${selectedTableId === t.id ? 'selected-card' : ''}" onclick="selectTable(${t.id})">
                    <span class="status-badge">${t.status === 'available' ? 'FREE' : 'OCCUPIED'}</span>
                    <div class="table-icon"><i class="fas fa-chair"></i></div>
                    <div class="table-name">${t.name}</div>
                    <div class="table-info">${t.capacity} seats &middot; ${t.floor_name}</div>
                    ${t.status === 'occupied' ? `<div class="order-info"><i class="fas fa-receipt me-1"></i>Has Order</div>` : ''}
                </div>
            `).join('');
        }

        function renderOrders(orders) {
            const container = document.getElementById('ordersList');
            if (!orders.length) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-utensils"></i><p>No active dine-in orders</p></div>';
                return;
            }

            container.innerHTML = orders.map(o => `
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-num">${o.order_number}</div>
                            <div class="order-table"><i class="fas fa-table me-1"></i>${o.table_name} (${o.floor_name}) &middot; ${o.elapsed}</div>
                        </div>
                        <span class="badge bg-${o.status === 'pending' ? 'warning text-dark' : o.status === 'preparing' ? 'info' : 'success'}">${o.status}</span>
                    </div>
                    <div class="order-items">
                        ${o.items.map(i => `${i.quantity}x ${i.product_name}`).join(', ')}
                    </div>
                    <div class="order-footer">
                        <div class="order-total">LKR ${o.total.toFixed(2)}</div>
                        <div class="d-flex gap-2">
                            <button class="btn-change-table" onclick="event.stopPropagation(); showChangeTableModalForOrder(${o.id}, '${o.table_name}')">
                                <i class="fas fa-exchange-alt me-1"></i>Move Table
                            </button>
                            <a href="{{ route('pos.index') }}" target="_blank" class="btn-bill" onclick="event.stopPropagation(); localStorage.setItem('pos_table_id', '${o.table_id}');">
                                <i class="fas fa-receipt me-1"></i>Bill
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function selectTable(tableId) {
            selectedTableId = tableId;
            renderTables(); // Re-render to show selected state
            renderSelectedPanel();
        }

        function renderSelectedPanel() {
            const panel = document.getElementById('selectedTablePanel');
            const table = allTables.find(t => t.id === selectedTableId);
            if (!table) {
                panel.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Select a table to view details</p>
                    </div>
                `;
                panel.classList.remove('selected');
                return;
            }

            panel.classList.add('selected');
            const order = allOrders.find(o => o.table_id === tableId);

            panel.innerHTML = `
                <div class="selected-table-header">
                    <div class="big-icon"><i class="fas fa-chair"></i></div>
                    <h3>${table.name}</h3>
                    <div class="subtitle">${table.capacity} seats &middot; ${table.floor_name}</div>
                    <span class="badge mt-2 bg-${table.status === 'available' ? 'success' : 'danger'}">${table.status === 'available' ? 'AVAILABLE' : 'OCCUPIED'}</span>
                </div>

                ${order ? `
                    <div class="order-details-list">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Order:</span>
                            <span class="fw-bold" style="color: #fdcb6e;">${order.order_number}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Customer:</span>
                            <span>${order.customer}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-${order.status === 'pending' ? 'warning text-dark' : order.status === 'preparing' ? 'info' : 'success'}">${order.status}</span>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        ${order.items.map(i => `
                            <div class="order-item-row">
                                <span>${i.quantity}x ${i.product_name}</span>
                                <span>LKR ${i.total_price.toFixed(2)}</span>
                            </div>
                        `).join('')}
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between fw-bold fs-5" style="color: #00b894;">
                            <span>Total</span>
                            <span>LKR ${order.total.toFixed(2)}</span>
                        </div>
                    </div>
                ` : '<div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x mb-2" style="color: #00b894;"></i><p>Table is free</p></div>'}

                <div class="action-buttons">
                    ${order ? `
                        <button class="action-btn change" onclick="showChangeTableModalForOrder(${order.id}, '${table.name}')">
                            <i class="fas fa-exchange-alt"></i>Change Table
                        </button>
                        <button class="action-btn bill" onclick="openBillingPanel(${table.id}, '${table.name}')">
                            <i class="fas fa-receipt"></i>Bill / Add Items
                        </button>
                    ` : `
                        <button class="action-btn pos" onclick="openBillingPanel(${table.id}, '${table.name}')">
                            <i class="fas fa-plus"></i>Start Order
                        </button>
                    `}
                </div>

                <!-- Billing Panel -->
                <div id="billingPanel" class="billing-panel mt-4">
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 16px 0;">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>Table ${table.name} - New Order</h5>

                    <!-- Categories -->
                    <div class="category-tabs-bill" id="billCategories"></div>

                    <!-- Products -->
                    <div class="product-grid-bill" id="billProducts"></div>

                    <!-- Cart -->
                    <div class="bill-cart" id="billCart">
                        <p class="text-muted text-center mb-0">No items added</p>
                    </div>

                    <!-- Total -->
                    <div class="bill-total" id="billTotal">LKR 0.00</div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <button class="btn btn-secondary flex-grow-1" onclick="closeBillingPanel()">Cancel</button>
                        <button class="btn-checkout flex-grow-2" onclick="checkoutTable(${table.id})" style="flex: 2;">
                            <i class="fas fa-check me-2"></i>Place Order
                        </button>
                    </div>
                </div>
            `;
        }

        // Billing State
        let billCategories = [];
        let billProducts = [];
        let billCart = [];
        let activeBillTable = null;

        function openBillingPanel(tableId, tableName) {
            activeBillTable = tableId;
            billCart = [];
            document.getElementById('billingPanel').classList.add('active');
            loadBillCategories();
            updateBillCart();
        }

        function closeBillingPanel() {
            document.getElementById('billingPanel').classList.remove('active');
            activeBillTable = null;
            billCart = [];
        }

        function loadBillCategories() {
            fetch('/pos/categories')
                .then(r => r.json())
                .then(data => {
                    billCategories = data.categories || [];
                    renderBillCategories();
                    if (billCategories.length) loadBillProducts(billCategories[0].id);
                });
        }

        function renderBillCategories() {
            const container = document.getElementById('billCategories');
            container.innerHTML = billCategories.map((c, i) => `
                <button class="category-tab-bill ${i === 0 ? 'active' : ''}" onclick="switchBillCategory(${c.id}, this)">${c.name}</button>
            `).join('');
        }

        function switchBillCategory(catId, btn) {
            document.querySelectorAll('.category-tab-bill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadBillProducts(catId);
        }

        function loadBillProducts(categoryId) {
            fetch(`/pos/products?category_id=${categoryId}`)
                .then(r => r.json())
                .then(data => {
                    billProducts = data.products || [];
                    renderBillProducts();
                });
        }

        function renderBillProducts() {
            const container = document.getElementById('billProducts');
            container.innerHTML = billProducts.map(p => `
                <div class="product-btn" onclick="addToBillCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
                    <div>${p.name}</div>
                    <div class="price">LKR ${p.price.toFixed(2)}</div>
                </div>
            `).join('');
        }

        function addToBillCart(productId, productName, price) {
            const existing = billCart.find(i => i.product_id === productId);
            if (existing) {
                existing.quantity++;
                existing.total = existing.quantity * existing.unit_price;
            } else {
                billCart.push({
                    product_id: productId,
                    product_name: productName,
                    quantity: 1,
                    unit_price: price,
                    total: price
                });
            }
            updateBillCart();
        }

        function removeFromBillCart(index) {
            billCart.splice(index, 1);
            updateBillCart();
        }

        function updateBillCart() {
            const container = document.getElementById('billCart');
            const totalEl = document.getElementById('billTotal');

            if (!billCart.length) {
                container.innerHTML = '<p class="text-muted text-center mb-0">No items added</p>';
                totalEl.textContent = 'LKR 0.00';
                return;
            }

            const total = billCart.reduce((s, i) => s + i.total, 0);

            container.innerHTML = billCart.map((item, i) => `
                <div class="bill-cart-item">
                    <div>
                        <div class="fw-bold">${item.product_name}</div>
                        <small class="text-muted">${item.quantity} x LKR ${item.unit_price.toFixed(2)}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold" style="color: #00b894;">LKR ${item.total.toFixed(2)}</span>
                        <button class="btn btn-sm btn-danger" onclick="removeFromBillCart(${i})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');

            totalEl.textContent = `LKR ${total.toFixed(2)}`;
        }

        function openPrintPreview(url) {
            const modal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
            const frame = document.getElementById('printPreviewFrame');
            const separator = url.includes('?') ? '&' : '?';
            frame.src = url + separator + 'format=html';
            modal.show();
        }

        function printPreviewFrame() {
            const frame = document.getElementById('printPreviewFrame');
            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        }

        function checkoutTable(tableId) {
            if (!billCart.length) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please add items to the order', showConfirmButton: false, timer: 3000 });
                return;
            }

            const payload = {
                table_id: tableId,
                order_type: 'dine_in',
                items: billCart,
                customer_id: null
            };

            fetch('/pos/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    billCart = [];
                    closeBillingPanel();
                    loadDineIn();
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: `Order #${data.order_number} placed!`,
                        showConfirmButton: false, timer: 3000
                    });
                    if (data.print_url) openPrintPreview(data.print_url);
                    if (data.print_jobs && data.print_jobs.length) {
                        data.print_jobs.forEach(job => {
                            if (job.auto_print) {
                                openPrintPreview(job.url);
                            }
                        });
                    }
                } else {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: data.message || 'Error placing order', showConfirmButton: false, timer: 3000 });
                }
            })
            .catch(e => {
                console.error('Checkout error:', e);
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Error placing order', showConfirmButton: false, timer: 3000 });
            });
        }

        function showChangeTableModal(tableId, tableName) {
            selectTable(tableId);
            // Find order for this table
            const order = allOrders.find(o => o.table_id === tableId);
            if (order) {
                showChangeTableModalForOrder(order.id, tableName);
            }
        }

        function showChangeTableModalForOrder(orderId, currentTableName) {
            currentOrderId = orderId;
            document.getElementById('changeTableText').textContent = `Current: ${currentTableName}. Select new table:`;

            const available = allTables.filter(t => t.status === 'available');
            const list = document.getElementById('availableTablesList');

            if (!available.length) {
                list.innerHTML = '<p class="text-muted text-center py-3">No available tables</p>';
            } else {
                list.innerHTML = `
                    <div class="row g-2">
                        ${available.map(t => `
                            <div class="col-4">
                                <button class="btn w-100" onclick="changeTable(${t.id})" style="background: #0f3460; color: #fff; border-radius: 10px; padding: 12px; border: none;">
                                    <i class="fas fa-chair d-block mb-1"></i>${t.name}<br><small class="text-muted">${t.capacity}p</small>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            const modal = new bootstrap.Modal(document.getElementById('changeTableModal'));
            modal.show();
        }

        function changeTable(newTableId) {
            if (!currentOrderId) return;

            fetch('/pos/dine-in/change-table', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ order_id: currentOrderId, table_id: newTableId })
            })
            .then(r => r.json())
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('changeTableModal')).hide();
                if (data.success) {
                    loadDineIn();
                    // Show toast if available
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 2000 });
                    }
                }
            });
        }

        function openPosForTable(tableId) {
            localStorage.setItem('pos_table_id', tableId);
            window.open('{{ route('pos.index') }}', '_blank');
        }

        // Auto refresh every 5 seconds
        setInterval(loadDineIn, 5000);
        loadDineIn();
    </script>
</body>
</html>
