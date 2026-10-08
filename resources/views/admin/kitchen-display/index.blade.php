<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen Display — ResPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0a09;
            --panel: #1c1917;
            --card: #292524;
            --line: rgba(255,247,237,0.1);
            --amber: #f59e0b;
            --amber2: #ea580c;
            --cream: #fff7ed;
            --muted: #a8a29e;
            --ready: #10b981;
            --urgent: #ef4444;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse 60% 40% at 10% 0%, rgba(245,158,11,0.18), transparent 55%),
                radial-gradient(ellipse 50% 35% at 100% 100%, rgba(234,88,12,0.12), transparent 50%),
                var(--bg);
            color: var(--cream);
            font-family: Outfit, system-ui, sans-serif;
            touch-action: manipulation;
        }
        .kds-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.35rem;
            background: linear-gradient(135deg, #1c1917, #292524);
            border-bottom: 2px solid var(--amber);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .kds-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .kds-mark {
            width: 46px; height: 46px;
            border-radius: 14px;
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--amber), var(--amber2));
            font-size: 1.15rem;
            color: #fff;
        }
        .kds-brand h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .kds-brand p {
            margin: 0;
            font-size: 0.78rem;
            color: var(--muted);
            font-weight: 600;
        }
        .kds-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
        }
        .kds-select {
            min-width: 200px;
            background: #0c0a09 !important;
            color: var(--cream) !important;
            border: 1px solid var(--line) !important;
            border-radius: 12px !important;
            padding: 0.65rem 0.9rem !important;
            font-weight: 600;
        }
        .kds-clock {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--amber);
            min-width: 4.5rem;
            text-align: right;
        }
        .kds-wrap { padding: 1.25rem 1.35rem 2rem; }
        .kds-section-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0 0 1rem;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .kds-section-title.is-prep { color: var(--amber); }
        .kds-section-title.is-ready { color: var(--ready); margin-top: 1.75rem; }
        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .order-card {
            background: linear-gradient(165deg, #292524, #1c1917);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 1.1rem 1.15rem;
            border-left: 5px solid var(--amber);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .order-card.is-pending { border-left-color: #f87171; }
        .order-card.is-preparing { border-left-color: var(--amber); }
        .order-card.is-ready { border-left-color: var(--ready); }
        .order-card.urgent {
            animation: urgentPulse 1.4s infinite;
        }
        @keyframes urgentPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.35); }
            50% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
        }
        .order-card:hover { transform: translateY(-2px); }
        .order-head {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .kot-number {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--amber);
            letter-spacing: -0.02em;
        }
        .order-meta { font-size: 0.8rem; color: var(--muted); margin-top: 0.15rem; }
        .timer {
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            font-size: 1.1rem;
            color: #fb7185;
        }
        .badge-row { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: flex-end; margin-bottom: 0.35rem; }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .pill.type { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .pill.status-pending { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .pill.status-preparing { background: rgba(245,158,11,0.18); color: #fbbf24; }
        .pill.status-ready { background: rgba(16,185,129,0.18); color: #6ee7b7; }
        .table-line {
            font-size: 0.92rem;
            font-weight: 650;
            margin-bottom: 0.75rem;
            color: #fdba74;
        }
        .item-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 1rem;
        }
        .item-row:last-child { border-bottom: 0; }
        .item-row.is-active {
            background: rgba(245,158,11,0.1);
            border-radius: 12px;
            padding: 0.65rem 0.7rem;
            margin: 0.2rem 0;
            border-bottom: 0;
            border: 1px solid rgba(245,158,11,0.28);
        }
        .item-row.is-done {
            opacity: 0.55;
        }
        .item-row.is-done .item-name {
            text-decoration: line-through;
            color: var(--muted);
        }
        .item-row.is-waiting { opacity: 0.85; }
        .item-main { flex: 1; min-width: 0; }
        .item-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.4rem;
            flex: 0 0 auto;
        }
        .item-name { font-weight: 650; }
        .item-note { display: block; font-size: 0.78rem; color: #fbbf24; margin-top: 0.2rem; }
        .item-done-label {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6ee7b7;
            margin-top: 0.25rem;
        }
        .item-qty {
            flex: 0 0 auto;
            min-width: 2rem;
            text-align: center;
            font-weight: 800;
            color: var(--amber);
            background: rgba(245,158,11,0.12);
            border-radius: 8px;
            padding: 0.15rem 0.45rem;
            height: fit-content;
        }
        .btn-item-complete {
            border: 0;
            border-radius: 10px;
            padding: 0.45rem 0.7rem;
            font: inherit;
            font-weight: 800;
            font-size: 0.82rem;
            cursor: pointer;
            min-height: 40px;
            background: linear-gradient(135deg, #34d399, #059669);
            color: #fff;
            white-space: nowrap;
        }
        .btn-item-complete:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .actions { display: flex; gap: 0.5rem; margin-top: 0.95rem; }
        .btn-kds {
            flex: 1;
            border: 0;
            border-radius: 14px;
            padding: 1rem 0.85rem;
            font: inherit;
            font-weight: 800;
            font-size: 1.05rem;
            cursor: pointer;
            min-height: 64px;
        }
        @media (max-width: 1024px) {
            .order-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important; gap: 1rem; }
            .kot-number { font-size: 1.55rem; }
            .item-row { font-size: 1.1rem; padding: 0.7rem 0; }
            .btn-kds { min-height: 72px; font-size: 1.15rem; }
            .kds-top { padding: 0.85rem 1rem; }
        }
        .btn-kds.start {
            background: linear-gradient(135deg, #fbbf24, var(--amber));
            color: #1c1917;
        }
        .btn-kds.ready {
            background: linear-gradient(135deg, #34d399, #059669);
            color: #fff;
        }
        .btn-kds.served {
            background: linear-gradient(135deg, var(--amber), var(--amber2));
            color: #fff;
        }
        .btn-kds:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }
        .empty i { font-size: 2.5rem; opacity: 0.35; margin-bottom: 0.75rem; display: block; }
        @media (max-width: 575.98px) {
            .kds-top { padding: 0.85rem 1rem; }
            .kds-wrap { padding: 1rem; }
            .order-grid { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.business-clock')
</head>
<body>
    <header class="kds-top">
        <div class="kds-brand">
            <div class="kds-mark"><i class="fas fa-fire"></i></div>
            <div>
                <h1>Kitchen Display</h1>
                <p>ResPOS · live tickets</p>
            </div>
        </div>
        <div class="kds-controls">
            <select id="kitchenSelect" class="form-select kds-select">
                <option value="">All kitchens</option>
                @foreach($kitchens as $kitchen)
                <option value="{{ $kitchen->id }}">{{ $kitchen->name }}</option>
                @endforeach
            </select>
            <div class="kds-clock" id="clock">--:--</div>
        </div>
    </header>

    <div class="kds-wrap">
        <h3 class="kds-section-title is-prep"><i class="fas fa-fire"></i> Preparing</h3>
        <div id="ordersContainer" class="order-grid"></div>

        <h3 class="kds-section-title is-ready"><i class="fas fa-check-circle"></i> Ready — waiter will Serve when food is given</h3>
        <div id="readyContainer" class="order-grid"></div>
    </div>

    <audio id="bellSound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <script>
        let previousOrderIds = [];
        let currentKitchenId = '';
        let lastOrdersFingerprint = '';
        let lastReadyFingerprint = '';
        const SOUND_ENABLED = @json((bool) ($soundEnabled ?? true));

        if (window.BusinessClock) {
            BusinessClock.bind('#clock', { seconds: false });
        } else {
            function tickClock() {
                const now = new Date();
                document.getElementById('clock').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            setInterval(tickClock, 1000);
            tickClock();
        }

        document.getElementById('kitchenSelect').addEventListener('change', function () {
            currentKitchenId = this.value;
            loadOrders();
            loadReadyOrders();
        });

        function playBell() {
            if (!SOUND_ENABLED) return;
            const bell = document.getElementById('bellSound');
            bell.currentTime = 0;
            bell.volume = 0.6;
            bell.play().catch(() => {});
        }

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function isItemDone(item) {
            return item.status === 'ready' || item.status === 'served';
        }

        /** Active first (by id), completed items sit at the bottom. */
        function sortedItems(items) {
            const list = [...(items || [])];
            list.sort((a, b) => {
                const aDone = isItemDone(a) ? 1 : 0;
                const bDone = isItemDone(b) ? 1 : 0;
                if (aDone !== bDone) return aDone - bDone;
                return (Number(a.id) || 0) - (Number(b.id) || 0);
            });
            return list;
        }

        function activeItemId(items) {
            const pending = (items || [])
                .filter(i => !isItemDone(i))
                .sort((a, b) => (Number(a.id) || 0) - (Number(b.id) || 0));
            return pending.length ? pending[0].id : null;
        }

        function renderItems(order) {
            const items = sortedItems(order.items);
            const activeId = order.status === 'preparing' ? activeItemId(order.items) : null;
            if (!items.length) return '';

            return items.map(item => {
                const done = isItemDone(item);
                const active = !done && activeId !== null && Number(item.id) === Number(activeId);
                const rowClass = done ? 'is-done' : (active ? 'is-active' : 'is-waiting');
                const completeBtn = active
                    ? `<button class="btn-item-complete" onclick="markItemReady(${order.id}, ${item.id}, this)"><i class="fas fa-check me-1"></i>Complete</button>`
                    : '';
                const doneLabel = done
                    ? `<span class="item-done-label"><i class="fas fa-check-circle"></i>Completed</span>`
                    : '';

                return `
                    <div class="item-row ${rowClass}">
                        <div class="item-main">
                            <span class="item-name">${esc(item.name)}</span>
                            ${(item.options || []).map(o => `<span class="item-note">${esc(o.name || ((o.option_set_name || '') + ': ' + (o.option_name || '')))}</span>`).join('')}
                            ${(item.addons || []).map(a => `<span class="item-note">+ ${esc(a.name)}</span>`).join('')}
                            ${item.instructions ? `<span class="item-note"><i class="fas fa-exclamation-circle me-1"></i>${esc(item.instructions)}</span>` : ''}
                            ${doneLabel}
                        </div>
                        <div class="item-side">
                            <span class="item-qty">×${esc(item.quantity)}</span>
                            ${completeBtn}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderTicketActions(order) {
            if (order.status === 'pending') {
                return `
                    <div class="actions">
                        <button class="btn-kds start" onclick="markStarted(${order.id})"><i class="fas fa-play me-1"></i>Start</button>
                    </div>`;
            }
            // Preparing: item-level Complete drives readiness; no whole-ticket Ready.
            return `
                <div class="actions">
                    <button class="btn-kds start" disabled><i class="fas fa-play me-1"></i>Started</button>
                </div>`;
        }

        function loadOrders() {
            const url = currentKitchenId
                ? `/kitchen/orders?kitchen_id=${currentKitchenId}`
                : '/kitchen/orders';

            fetch(url)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(data => {
                    const container = document.getElementById('ordersContainer');
                    if (!data.orders || !data.orders.length) {
                        container.innerHTML = `
                            <div class="empty">
                                <i class="fas fa-utensils"></i>
                                <h3>No active tickets</h3>
                                <p>New KOTs will appear here automatically</p>
                                <small>Last check: ${window.BusinessClock ? BusinessClock.formatTime(true) : new Date().toLocaleTimeString()}</small>
                            </div>`;
                        previousOrderIds = [];
                        lastOrdersFingerprint = '';
                        return;
                    }

                    const currentIds = data.orders.map(o => o.id);
                    const newOrders = currentIds.filter(id => !previousOrderIds.includes(id));
                    if (newOrders.length > 0 && previousOrderIds.length > 0) playBell();
                    previousOrderIds = currentIds;

                    const fp = JSON.stringify(data.orders || []);
                    if (fp === lastOrdersFingerprint) return;
                    lastOrdersFingerprint = fp;
                    container.innerHTML = data.orders.map(order => `
                        <div class="order-card is-${esc(order.status)} ${order.elapsed_minutes > 15 ? 'urgent' : ''}">
                            <div class="order-head">
                                <div>
                                    <div class="kot-number">${esc(order.order_number)}</div>
                                    <div class="order-meta">${esc(order.kot_number)}</div>
                                </div>
                                <div class="text-end">
                                    <div class="badge-row">
                                        <span class="pill type">${esc((order.order_type || '').replace('_', ' '))}</span>
                                        <span class="pill status-${esc(order.status)}">${order.status === 'preparing' ? 'PREPARING' : 'PENDING'}</span>
                                    </div>
                                    <div class="timer">${Number(order.elapsed_minutes) || 0}m</div>
                                </div>
                            </div>
                            ${order.table_name ? `<div class="table-line"><i class="fas fa-chair me-1"></i>${esc(order.table_name)}</div>` : ''}
                            ${order.waiter_name ? `<div class="table-line" style="color:#a5b4fc;"><i class="fas fa-user me-1"></i>Waiter: ${esc(order.waiter_name)}</div>` : ''}
                            <div class="items-list">
                                ${renderItems(order)}
                            </div>
                            ${renderTicketActions(order)}
                        </div>
                    `).join('');
                })
                .catch(err => {
                    document.getElementById('ordersContainer').innerHTML = `
                        <div class="empty">
                            <i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i>
                            <h3>Connection error</h3>
                            <p>Retrying…</p>
                            <small>${esc(err.message)}</small>
                        </div>`;
                });
        }

        function markItemReady(kotId, itemId, btn) {
            if (btn) btn.disabled = true;
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const body = new URLSearchParams();
            body.set('item_id', String(itemId));
            // Uses existing /ready route (no new route needed on cPanel / route cache).
            fetch(`/kitchen/orders/${kotId}/ready`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            }).then(async r => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(data.message || ('HTTP ' + r.status));
                return data;
            }).then(data => {
                if (data.success) {
                    if (data.ticket_ready) playBell();
                    loadOrders();
                    loadReadyOrders();
                } else if (btn) {
                    btn.disabled = false;
                    alert(data.message || 'Could not complete item');
                }
            }).catch(err => {
                if (btn) btn.disabled = false;
                alert(err.message || 'Could not complete item');
            });
        }

        function markReady(kotId) {
            fetch(`/kitchen/orders/${kotId}/ready`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            }).then(r => r.json()).then(data => {
                if (data.success) { playBell(); loadOrders(); loadReadyOrders(); }
            });
        }

        function markStarted(kotId) {
            fetch(`/kitchen/orders/${kotId}/started`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            }).then(r => r.json()).then(data => {
                if (data.success) loadOrders();
            });
        }

        function markServed(kotId) {
            fetch(`/kitchen/orders/${kotId}/served`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            }).then(r => r.json()).then(data => {
                if (data.success) loadReadyOrders();
            });
        }

        function loadReadyOrders() {
            const url = currentKitchenId
                ? `/kitchen/ready-orders?kitchen_id=${currentKitchenId}`
                : '/kitchen/ready-orders';

            fetch(url)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(data => {
                    const container = document.getElementById('readyContainer');
                    const orders = data.orders || [];
                    if (!orders.length) {
                        lastReadyFingerprint = '';
                        container.innerHTML = '<div class="empty"><i class="fas fa-check-circle" style="color:#10b981;"></i><p>No ready orders</p></div>';
                        return;
                    }
                    const rfp = JSON.stringify(orders);
                    if (rfp === lastReadyFingerprint) return;
                    lastReadyFingerprint = rfp;
                    container.innerHTML = orders.map(order => `
                        <div class="order-card is-ready">
                            <div class="order-head">
                                <div>
                                    <div class="kot-number" style="color:#34d399;">${esc(order.order_number)}</div>
                                    <div class="order-meta">${esc(order.kot_number)}</div>
                                </div>
                                <span class="pill type">${esc((order.order_type || '').replace('_', ' '))}</span>
                            </div>
                            ${order.table_name ? `<div class="table-line"><i class="fas fa-chair me-1"></i>${esc(order.table_name)}</div>` : ''}
                            ${order.waiter_name ? `<div class="table-line" style="color:#a5b4fc;"><i class="fas fa-user me-1"></i>Waiter: ${esc(order.waiter_name)}</div>` : ''}
                            <div class="items-list">
                                ${(order.items || []).map(item => `
                                    <div class="item-row">
                                        <span class="item-name">${esc(item.name)}</span>
                                        <span class="item-qty">×${esc(item.quantity)}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="actions">
                                <button class="btn-kds served" onclick="markServed(${order.id})"><i class="fas fa-utensils me-1"></i>Served</button>
                            </div>
                        </div>
                    `).join('');
                })
                .catch(e => console.error('Load ready orders failed:', e));
        }


        function smartKdsInterval(fn, ms) {
            return setInterval(function () {
                if (document.hidden) return;
                fn();
            }, ms);
        }
        smartKdsInterval(loadOrders, 5000);
        smartKdsInterval(loadReadyOrders, 5000);
        loadOrders();
        loadReadyOrders();
    </script>
</body>
</html>
