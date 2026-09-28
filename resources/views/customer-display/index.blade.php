<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Display - ResPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0c0a09;
            color: #fff7ed;
            font-family: Outfit, system-ui, sans-serif;
            min-height: 100vh;
            overflow: hidden;
        }
        /* ===== HEADER ===== */
        .display-header {
            background: linear-gradient(90deg, #f59e0b, #ea580c);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(234, 88, 12, 0.28);
        }
        .display-header .brand {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .display-header .clock {
            font-size: 2rem;
            font-weight: 300;
            font-family: 'Courier New', monospace;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: calc(100vh - 80px);
        }

        /* ===== STATUS PANELS ===== */
        .status-panel {
            padding: 30px;
            position: relative;
        }
        .status-panel.preparing { border-right: 3px solid #1a1a1a; }
        .panel-title {
            font-size: 2.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 4px;
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid;
        }
        .preparing .panel-title { color: #fbbf24; border-color: #f59e0b; }
        .ready .panel-title { color: #34d399; border-color: #10b981; }

        .order-card {
            background: linear-gradient(135deg, #1c1917, #292524);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 15px;
            border: 2px solid rgba(255,247,237,0.08);
            animation: slideIn 0.4s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .order-card .order-num {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .preparing .order-num { color: #fbbf24; }
        .ready .order-num { color: #34d399; }
        .order-card .order-info {
            font-size: 0.95rem;
            color: #aaa;
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .type-dine-in { background: rgba(232, 126, 34, 0.2); color: #e67e22; border: 1px solid rgba(232, 126, 34, 0.4); }
        .type-takeaway { background: rgba(52, 152, 219, 0.2); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.4); }
        .type-delivery { background: rgba(155, 89, 182, 0.2); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.4); }
        .type-express { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); }
        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            background: rgba(241, 196, 15, 0.15);
            color: #f1c40f;
            border: 1px solid rgba(241, 196, 15, 0.3);
        }
        .customer-name {
            font-size: 0.95rem;
            color: #ccc;
            margin-top: 6px;
        }
        .ready-pulse {
            animation: readyPulse 1.5s infinite;
        }
        @keyframes readyPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            50% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); }
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.25;
        }
        .empty-state i { font-size: 4rem; margin-bottom: 15px; display: block; }
        .empty-state span { font-size: 1.3rem; }

        /* ===== MARKETING / IDLE MODE ===== */
        #idleScreen {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 100;
            background: linear-gradient(135deg, #1c1917, #0c0a09);
        }
        .idle-header {
            background: linear-gradient(90deg, #f59e0b, #ea580c);
            padding: 25px 40px;
            text-align: center;
        }
        .idle-header h1 {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 4px;
            margin: 0;
        }
        .idle-header p {
            font-size: 1.3rem;
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .idle-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: calc(100vh - 130px);
        }
        .promo-section {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .promo-card {
            background: linear-gradient(135deg, #292524, #1c1917);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            border: 2px solid rgba(255,247,237,0.1);
            max-width: 500px;
            width: 100%;
        }
        .promo-card .promo-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .promo-card .promo-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .promo-card .promo-text {
            font-size: 1.2rem;
            color: #aaa;
            line-height: 1.6;
        }
        .promo-card .promo-price {
            font-size: 3rem;
            font-weight: 800;
            color: #f59e0b;
            margin: 15px 0;
        }
        .qr-box {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            display: inline-block;
        }
        .qr-box i {
            font-size: 5rem;
            color: #1a1a2e;
        }
        .slide-dots {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .slide-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .slide-dot.active { background: #f59e0b; transform: scale(1.3); }

        /* ===== BILL DISPLAY MODE (QPOS-style cart) ===== */
        #billScreen {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 200;
            background: #050505;
            color: #f5f5f5;
            font-family: Outfit, system-ui, sans-serif;
        }
        #billScreen .qpos-shell {
            display: grid;
            grid-template-rows: auto 1fr auto;
            height: 100vh;
        }
        #billScreen .qpos-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.7rem 1.25rem; background: #000; border-bottom: 1px solid #1a1a1a;
        }
        #billScreen .qpos-brand { display: flex; align-items: center; gap: 0.65rem; font-weight: 800; font-size: 1.15rem; }
        #billScreen .qpos-brand img { width: 34px; height: 34px; object-fit: contain; border-radius: 8px; }
        #billScreen .qpos-right { display: flex; align-items: center; gap: 1rem; }
        #billScreen .qpos-live { color: #22c55e; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em; display: inline-flex; align-items: center; gap: 0.4rem; }
        #billScreen .qpos-live::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; }
        #billScreen .qpos-main { display: grid; grid-template-columns: 1.35fr 0.9fr; min-height: 0; }
        #billScreen .qpos-left {
            display: grid; grid-template-rows: auto auto 1fr auto; min-height: 0;
            border-right: 1px solid #161616; background: #0a0a0a;
        }
        #billScreen .qpos-right-panel {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #000; position: relative;
        }
        #billScreen .qpos-customer {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: 0.85rem 1.25rem; border-bottom: 1px solid #161616; background: #0d0d0d;
        }
        #billScreen .qpos-meta { display: flex; flex-wrap: wrap; gap: 0.4rem; justify-content: flex-end; }
        #billScreen .qpos-badge {
            display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.28rem 0.65rem;
            border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.04em; border: 1px solid #333; color: #d4d4d4; background: #151515;
        }
        #billScreen .qpos-badge.type { border-color: rgba(245,184,0,.35); color: #f5b800; }
        #billScreen .qpos-badge.table { border-color: rgba(59,130,246,.4); color: #60a5fa; }
        #billScreen .qpos-summary {
            display: grid; grid-template-columns: 1fr 1fr 1.25fr; gap: 0.75rem;
            padding: 0.9rem 1.1rem; border-bottom: 1px solid #161616;
        }
        #billScreen .qpos-card {
            background: #111; border: 1px solid #222; border-radius: 10px; padding: 0.85rem 1rem; min-height: 84px;
        }
        #billScreen .qpos-card .label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #737373; margin-bottom: 0.35rem; }
        #billScreen .qpos-card .sub { font-size: 0.78rem; color: #737373; margin-bottom: 0.25rem; }
        #billScreen .qpos-card .val { font-size: 1.35rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        #billScreen .qpos-card.savings .val { color: #22c55e; }
        #billScreen .qpos-card.total { border-color: #f5b800; }
        #billScreen .qpos-card.total .label, #billScreen .qpos-card.total .val { color: #f5b800; }
        #billScreen .qpos-card.total .val { font-size: 1.55rem; }
        #billScreen .qpos-items { min-height: 0; display: flex; flex-direction: column; padding: 0.75rem 1.1rem 0.5rem; }
        #billScreen .qpos-items-title { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #737373; margin-bottom: 0.65rem; }
        #billScreen .qpos-items-list { flex: 1; overflow-y: auto; }
        #billScreen .qpos-item {
            display: grid; grid-template-columns: auto 1fr auto; gap: 0.85rem; align-items: center;
            padding: 0.75rem 0.85rem; margin-bottom: 0.45rem; background: #0f0f0f; border: 1px solid #1c1c1c; border-radius: 10px;
        }
        #billScreen .qpos-qty {
            width: 36px; height: 36px; border-radius: 9px; background: rgba(245,184,0,.18); color: #f5b800;
            display: grid; place-items: center; font-weight: 800;
        }
        #billScreen .qpos-pay {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;
            padding: 0.85rem 1.1rem 1rem; border-top: 1px solid #161616; background: #0a0a0a;
        }
        #billScreen .qpos-pay .k { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #737373; }
        #billScreen .qpos-pay .v { font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        #billScreen .qpos-pay .balance .v { color: #ef4444; }
        #billScreen .qpos-pay .change .v { color: #22c55e; }
        #billScreen .qpos-brand-panel { text-align: center; padding: 2rem; z-index: 1; }
        #billScreen .qpos-brand-panel img { max-width: min(42vw, 240px); max-height: min(42vw, 240px); object-fit: contain; margin-bottom: 1rem; }
        #billScreen .qpos-brand-panel h1 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; letter-spacing: 0.06em; }
        #billScreen .qpos-brand-panel .by { color: #3b82f6; font-weight: 700; letter-spacing: 0.18em; font-size: 0.85rem; }
        #billScreen .qpos-footer {
            display: flex; align-items: center; justify-content: center; padding: 0.45rem 1rem;
            background: #000; border-top: 1px solid #1a1a1a; color: #737373; font-size: 0.78rem;
        }
        #billScreen .btn-fs {
            border: 1px solid #f5b800; color: #f5b800; background: transparent; border-radius: 6px;
            padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <div class="display-header">
        <div class="brand"><i class="fas fa-utensils me-3"></i>ResPOS</div>
        <div class="text-center">
            <div style="font-size: 1.1rem; opacity: 0.8;">Dual Display</div>
        </div>
        <div class="clock" id="clock">00:00</div>
    </div>

    <!-- ===== MAIN: Order Status ===== -->
    <div class="main-container" id="mainScreen">
        <div class="status-panel preparing">
            <h2 class="panel-title"><i class="fas fa-fire me-3"></i>Preparing</h2>
            <div id="preparingContainer">
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <span>No orders preparing</span>
                </div>
            </div>
        </div>
        <div class="status-panel ready">
            <h2 class="panel-title"><i class="fas fa-check-circle me-3"></i>Ready</h2>
            <div id="readyContainer">
                <div class="empty-state">
                    <i class="fas fa-smile"></i>
                    <span>No orders ready</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== IDLE / MARKETING SCREEN ===== -->
    <div id="idleScreen">
        <div class="idle-header">
            <h1><i class="fas fa-utensils me-3"></i>Welcome to ResPOS</h1>
            <p>Place your order at the counter & watch it appear here!</p>
        </div>
        <div class="idle-content">
            <div class="promo-section">
                <div class="promo-card" id="promoCard">
                    <div class="promo-icon" id="promoIcon"><i class="fas fa-hamburger"></i></div>
                    <div class="promo-title" id="promoTitle">Today's Special</div>
                    <div class="promo-text" id="promoText">Try our chef's signature burger with fresh ingredients</div>
                    <div class="promo-price" id="promoPrice">$12.99</div>
                </div>
                <div class="slide-dots" id="slideDots"></div>
            </div>
            <div class="promo-section" style="background: rgba(0,0,0,0.2);">
                <div class="promo-card">
                    <div class="promo-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="promo-title">Scan to Order</div>
                    <div class="promo-text">Use your phone to browse our menu and place orders</div>
                    <div class="qr-box">
                        <i class="fas fa-qrcode"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BILL DISPLAY (QPOS-style) ===== -->
    @php
        $brand = $companyName ?? 'ResPOS';
        $phone = $companyPhone ?? '';
        $cur = $currency ?? 'Rs';
        $logo = $logoUrl ?? null;
    @endphp
    <div id="billScreen">
        <div class="qpos-shell">
            <div class="qpos-topbar">
                <div class="qpos-brand">
                    @if($logo)<img src="{{ $logo }}" alt="{{ $brand }}">@endif
                    <span>{{ $brand }}</span>
                </div>
                <div class="qpos-right">
                    <span class="qpos-live">LIVE</span>
                    <span class="clock" id="billClock">--:--</span>
                    <button type="button" class="btn-fs" onclick="document.documentElement.requestFullscreen?.()"><i class="fas fa-expand me-1"></i>Full Screen</button>
                </div>
            </div>
            <div class="qpos-main">
                <section class="qpos-left">
                    <div class="qpos-customer">
                        <div style="display:flex;align-items:center;gap:.6rem;font-weight:600;">
                            <i class="fas fa-user-circle" style="color:#737373;"></i>
                            <span id="billCustomerName">Walk-in Customer</span>
                        </div>
                        <div class="qpos-meta" id="billMetaBadges"></div>
                    </div>
                    <div class="qpos-summary">
                        <div class="qpos-card">
                            <div class="label">Subtotal</div>
                            <div class="sub" id="billItemCount">0 Items</div>
                            <div class="val" id="billSubtotal">{{ $cur }} 0.00</div>
                        </div>
                        <div class="qpos-card savings">
                            <div class="label">Savings</div>
                            <div class="sub">Discounts & offers</div>
                            <div class="val" id="billSavings">{{ $cur }} 0.00</div>
                        </div>
                        <div class="qpos-card total">
                            <div class="label">Total Amount Payable</div>
                            <div class="sub">&nbsp;</div>
                            <div class="val" id="bigTotalAmount">{{ $cur }} 0.00</div>
                        </div>
                    </div>
                    <div class="qpos-items">
                        <div class="qpos-items-title">Scanned Items</div>
                        <div class="qpos-items-list" id="billItems"></div>
                    </div>
                    <div class="qpos-pay">
                        <div>
                            <div class="k">Paying</div>
                            <div class="v" id="billPaying">{{ $cur }} 0.00</div>
                        </div>
                        <div class="change">
                            <div class="k">Change Return</div>
                            <div class="v" id="billChange">{{ $cur }} 0.00</div>
                        </div>
                        <div class="balance">
                            <div class="k">Balance</div>
                            <div class="v" id="billBalance">{{ $cur }} 0.00</div>
                        </div>
                    </div>
                </section>
                <aside class="qpos-right-panel">
                    <div class="qpos-brand-panel">
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $brand }}">
                        @else
                            <div style="width:160px;height:160px;margin:0 auto 1rem;border-radius:36px;background:radial-gradient(circle at 30% 30%,#3b82f6,#1e3a8a 70%);display:grid;place-items:center;font-size:4rem;font-weight:800;">{{ strtoupper(substr($brand,0,1)) }}</div>
                        @endif
                        <h1>{{ strtoupper($brand) }}</h1>
                        <div class="by">BY AVENQUE</div>
                    </div>
                </aside>
            </div>
            <div class="qpos-footer">QRPOS By Avenque (Pvt) Ltd | 076 822 2201</div>
        </div>
    </div>

    <!-- Audio -->
    <audio id="readySound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3" type="audio/mpeg">
    </audio>

    <script>
        // ===== CLOCK =====
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
            document.querySelectorAll('.clock').forEach(el => el.textContent = time);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ===== STATE =====
        let previousReadyIds = [];
        let isIdle = false;
        let currentMode = 'status'; // status, idle, bill
        let idleTimeout = null;

        // ===== PROMO SLIDES =====
        const promos = [
            { icon: 'fa-hamburger', title: "Chef's Special Burger", text: 'Premium beef patty with fresh veggies & secret sauce', price: '$12.99' },
            { icon: 'fa-pizza-slice', title: 'Wood-Fired Pizza', text: 'Hand-stretched dough with mozzarella & basil', price: '$15.99' },
            { icon: 'fa-glass-whiskey', title: 'Happy Hour', text: 'Buy 1 Get 1 Free on all beverages 2PM-5PM', price: '50% OFF' },
            { icon: 'fa-birthday-cake', title: 'Birthday Deal', text: 'Free dessert with any main course on your birthday', price: 'FREE' },
            { icon: 'fa-users', title: 'Family Feast', text: 'Feed 4 people with our combo meal deal', price: '$39.99' },
        ];
        let currentSlide = 0;

        function updatePromo() {
            const p = promos[currentSlide];
            document.getElementById('promoIcon').innerHTML = `<i class="fas ${p.icon}"></i>`;
            document.getElementById('promoTitle').textContent = p.title;
            document.getElementById('promoText').textContent = p.text;
            document.getElementById('promoPrice').textContent = p.price;

            const dots = document.getElementById('slideDots');
            dots.innerHTML = promos.map((_, i) =>
                `<div class="slide-dot ${i === currentSlide ? 'active' : ''}"></div>`
            ).join('');
        }
        setInterval(() => {
            currentSlide = (currentSlide + 1) % promos.length;
            updatePromo();
        }, 5000);
        updatePromo();

        // ===== MODE SWITCHING =====
        function showMode(mode) {
            currentMode = mode;
            document.getElementById('mainScreen').style.display = mode === 'status' ? 'grid' : 'none';
            document.getElementById('idleScreen').style.display = mode === 'idle' ? 'block' : 'none';
            document.getElementById('billScreen').style.display = mode === 'bill' ? 'block' : 'none';
        }

        // ===== AUDIO =====
        function playReadySound() {
            const sound = document.getElementById('readySound');
            sound.currentTime = 0;
            sound.play().catch(e => {});
        }

        // ===== ORDERS =====
        function loadOrders() {
            fetch('/customer-display/orders', { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    const preparing = data.preparing || [];
                    const ready = data.ready || [];
                    const hasOrders = preparing.length > 0 || ready.length > 0;

                    // Check for new ready orders
                    const currentReadyIds = ready.map(o => o.order_number);
                    const newlyReady = currentReadyIds.filter(id => !previousReadyIds.includes(id));
                    if (newlyReady.length > 0 && previousReadyIds.length > 0) {
                        playReadySound();
                    }
                    previousReadyIds = currentReadyIds;

                    // Helper for type badge
                    function typeBadge(o) {
                        const map = {
                            'dine_in':   { cls: 'type-dine-in',   icon: 'fa-utensils',   label: 'Dine In' },
                            'dine in':   { cls: 'type-dine-in',   icon: 'fa-utensils',   label: 'Dine In' },
                            'takeaway':  { cls: 'type-takeaway',  icon: 'fa-shopping-bag', label: 'Takeaway' },
                            'delivery':  { cls: 'type-delivery',  icon: 'fa-truck',      label: 'Delivery' },
                            'express':   { cls: 'type-express',   icon: 'fa-bolt',       label: 'Express' },
                        };
                        const key = (o.type_raw || o.type || '').toLowerCase().replace(/\s+/g, '_');
                        const t = map[key] || map[o.type?.toLowerCase()] || { cls: 'type-dine-in', icon: 'fa-utensils', label: o.type || 'Dine In' };
                        return `<span class="type-badge ${t.cls}"><i class="fas ${t.icon}"></i>${t.label}</span>`;
                    }

                    function tableBadge(o) {
                        if (!o.table) return '';
                        const name = String(o.table).replace(/^table\s+/i, '');
                        return `<span class="table-badge"><i class="fas fa-table"></i>Table ${name}</span>`;
                    }

                    // Update preparing
                    const prepContainer = document.getElementById('preparingContainer');
                    if (preparing.length) {
                        prepContainer.innerHTML = preparing.map(o => `
                            <div class="order-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="order-num">${o.order_number}</div>
                                    <span style="font-size: 0.9rem; color: #888;">${o.elapsed || ''}</span>
                                </div>
                                <div class="order-info">
                                    ${typeBadge(o)}
                                    ${tableBadge(o)}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        prepContainer.innerHTML = `<div class="empty-state"><i class="fas fa-utensils"></i><span>No orders preparing</span></div>`;
                    }

                    // Update ready
                    const readyContainer = document.getElementById('readyContainer');
                    if (ready.length) {
                        readyContainer.innerHTML = ready.map(o => `
                            <div class="order-card ready-pulse">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="order-num">${o.order_number}</div>
                                    <span style="font-size: 1.4rem; color: #34d399;"><i class="fas fa-check-circle"></i></span>
                                </div>
                                <div class="order-info">
                                    ${typeBadge(o)}
                                    ${tableBadge(o)}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        readyContainer.innerHTML = `<div class="empty-state"><i class="fas fa-smile"></i><span>No orders ready</span></div>`;
                    }

                    // Auto-switch to idle only when completely empty for 60s
                    if (!hasOrders && currentMode === 'status') {
                        clearTimeout(idleTimeout);
                        idleTimeout = setTimeout(() => {
                            if (currentMode === 'status') showMode('idle');
                        }, 60000);
                    }
                    if (hasOrders && currentMode === 'idle') {
                        clearTimeout(idleTimeout);
                        showMode('status');
                    }
                })
                .catch(() => {});
        }

        // ===== BILL DISPLAY (called from POS cart poll) =====
        const BILL_CUR = @json($cur);
        function billMoney(n) {
            return BILL_CUR + ' ' + Number(n || 0).toFixed(2);
        }
        function escapeBill(s) {
            return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        function typeLabel(raw) {
            return ({ dine_in: 'Dine In', takeaway: 'Takeaway', delivery: 'Delivery', express: 'Express' })[raw]
                || (raw ? String(raw).replace(/_/g, ' ') : '');
        }
        window.showBill = function(items, totals, meta = {}, payment = {}) {
            showMode('bill');
            const count = (items || []).reduce((s, i) => s + Number(i.qty || 0), 0);
            document.getElementById('billCustomerName').textContent = meta.customer || 'Walk-in Customer';
            const badges = [];
            if (meta.invoice) badges.push(`<span class="qpos-badge"><i class="fas fa-hashtag"></i>${escapeBill(meta.invoice)}</span>`);
            if (meta.order_type) badges.push(`<span class="qpos-badge type"><i class="fas fa-tag"></i>${escapeBill(typeLabel(meta.order_type))}</span>`);
            if (meta.order_type === 'dine_in' && meta.table) {
                const t = String(meta.table).replace(/^table\s+/i, '');
                badges.push(`<span class="qpos-badge table"><i class="fas fa-chair"></i>Table ${escapeBill(t)}</span>`);
            }
            document.getElementById('billMetaBadges').innerHTML = badges.join('');
            document.getElementById('billItemCount').textContent = count + (count === 1 ? ' Item' : ' Items');
            document.getElementById('billSubtotal').textContent = billMoney(totals.subtotal);
            document.getElementById('billSavings').textContent = billMoney(totals.discount || 0);
            document.getElementById('bigTotalAmount').textContent = billMoney(totals.total);
            document.getElementById('billPaying').textContent = billMoney(payment.paying || 0);
            document.getElementById('billChange').textContent = billMoney(payment.change || 0);
            document.getElementById('billBalance').textContent = billMoney(payment.balance != null ? payment.balance : totals.total);
            document.getElementById('billItems').innerHTML = (items || []).map(item => `
                <div class="qpos-item">
                    <div class="qpos-qty">${Number(item.qty)}</div>
                    <div>
                        <div style="font-weight:600;">${escapeBill(item.name)}</div>
                        <div style="font-size:.78rem;color:#737373;">${billMoney(item.unitPrice || (item.lineTotal / item.qty))} each</div>
                        ${Number(item.discount || 0) > 0 ? `<div style="font-size:.78rem;color:#dc2626;font-weight:600;">Discount −${billMoney(item.discount)}</div>` : ''}
                    </div>
                    <div style="font-weight:700;font-variant-numeric:tabular-nums;">${billMoney(item.lineTotal)}</div>
                </div>
            `).join('');
        };
        window.hideBill = function() {
            showMode('idle');
        };

        // ===== CART POLLING =====
        function loadCart() {
            fetch('/customer-display/cart')
                .then(r => r.json())
                .then(data => {
                    if (data.active && data.cart && (data.cart.items || []).length) {
                        const cart = data.cart;
                        const items = cart.items.map(item => ({
                            name: item.name,
                            qty: item.qty,
                            unitPrice: item.unitPrice,
                            discount: item.discount || 0,
                            lineTotal: item.lineTotal || (item.unitPrice * item.qty)
                        }));
                        window.showBill(items, cart.totals || {}, cart.meta || {}, cart.payment || {});
                    } else if (currentMode === 'bill') {
                        window.hideBill();
                    }
                });
        }
        setInterval(loadCart, 1000);

        // ===== INIT =====
        setInterval(loadOrders, 2000);
        loadOrders();
        loadCart();
    </script>
</body>
</html>
