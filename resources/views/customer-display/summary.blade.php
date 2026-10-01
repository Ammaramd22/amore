<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Summary - Customer Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .header .time {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .container-fluid { padding: 30px; }

        /* Order Cards Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }
        .order-card {
            background: linear-gradient(145deg, #0f3460, #16213e);
            border-radius: 20px;
            border: 2px solid rgba(255,255,255,0.1);
            padding: 24px;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            border-color: rgba(233,69,96,0.5);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .order-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fdcb6e;
        }
        .order-type-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .type-dine-in { background: #00b894; }
        .type-takeaway { background: #fdcb6e; color: #1a1a2e; }
        .type-delivery { background: #74b9ff; }
        .type-express { background: #ff7675; }

        .order-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            color: #b2bec3;
            font-size: 0.95rem;
        }
        .order-meta span i {
            margin-right: 6px;
            color: #e94560;
        }

        .items-list {
            margin: 16px 0;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 1rem;
        }
        .item-row:last-child { border-bottom: none; }
        .item-name { color: #dfe6e9; }
        .item-qty {
            background: rgba(233,69,96,0.2);
            color: #fdcb6e;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid rgba(255,255,255,0.1);
        }
        .total-label { color: #b2bec3; font-size: 1rem; }
        .total-amount {
            font-size: 1.6rem;
            font-weight: 800;
            color: #00b894;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .status-pending { background: rgba(253,203,110,0.2); color: #fdcb6e; }
        .status-preparing { background: rgba(116,185,255,0.2); color: #74b9ff; }
        .status-ready { background: rgba(0,184,148,0.2); color: #00b894; }
        .status-served { background: rgba(178,190,195,0.2); color: #b2bec3; }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: #636e72;
        }
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 30px;
            display: block;
        }
        .empty-state h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Footer Nav */
        .footer-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 52, 96, 0.95);
            padding: 15px 40px;
            display: flex;
            justify-content: center;
            gap: 30px;
            backdrop-filter: blur(10px);
        }
        .nav-btn {
            color: #fff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.1); }
        .nav-btn.active {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
        }

        .elapsed-time {
            font-size: 0.85rem;
            color: #74b9ff;
            margin-top: 8px;
        }

        /* Cart Card */
        .cart-card {
            background: linear-gradient(145deg, #0f3460, #16213e);
            border-radius: 20px;
            border: 3px solid #f59e0b;
            padding: 24px;
            animation: cartPulse 2s infinite;
        }
        @keyframes cartPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.4); }
            50% { box-shadow: 0 0 20px 5px rgba(245,158,11,0.2); }
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        .cart-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-badge {
            background: #f59e0b;
            color: #fff;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
            max-height: 400px;
            overflow-y: auto;
        }
        .cart-item {
            background: rgba(255,255,255,0.08);
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 4px solid #f59e0b;
        }
        .cart-item-qty {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #fff;
            min-width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .cart-item-details {
            flex: 1;
        }
        .cart-item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }
        .cart-item-price {
            font-size: 0.9rem;
            color: #94a3b8;
        }
        .cart-item-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #00b894;
        }
        .cart-total {
            text-align: right;
            font-size: 1.4rem;
            font-weight: 700;
            color: #00b894;
        }

        /* Marketing / Idle Screen */
        .marketing-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 1s ease;
        }
        .marketing-screen.active {
            display: flex;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .marketing-poster {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: posterPulse 4s infinite;
        }
        @keyframes posterPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        .marketing-logo {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .marketing-text {
            font-size: 2rem;
            margin-top: 30px;
            text-align: center;
            color: #fdcb6e;
        }
        .tap-hint {
            position: absolute;
            bottom: 40px;
            font-size: 1rem;
            color: rgba(255,255,255,0.5);
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
    @include('partials.business-clock')
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-receipt me-3"></i>Order Summary</h1>
        <div class="time" id="clock">--:--</div>
    </div>

    <div class="container-fluid">
        <!-- Current Cart Section -->
        <div id="cartContainer" style="margin-bottom: 24px;"></div>

        <div id="ordersContainer">
            <div class="empty-state">
                <i class="fas fa-spinner fa-spin"></i>
                <h2>Loading orders...</h2>
            </div>
        </div>
    </div>

    <!-- Marketing / Idle Screen -->
    <div id="marketingScreen" class="marketing-screen" onclick="hideMarketing()">
        <div class="marketing-logo"><i class="fas fa-utensils me-3"></i>ResPOS</div>
        <img id="marketingPoster" src="/storage/marketing/poster.jpg" alt="Special Offers" class="marketing-poster" onerror="this.style.display='none'">
        <div class="marketing-text" id="marketingText">Welcome! Order at the counter</div>
        <div class="tap-hint"><i class="fas fa-hand-pointer me-2"></i>Tap to view orders</div>
    </div>

    <div class="footer-nav">
        <a href="{{ route('customer.display.summary') }}" class="nav-btn active">
            <i class="fas fa-receipt"></i>Order Summary
        </a>
        <a href="{{ route('customer.display') }}" class="nav-btn">
            <i class="fas fa-tv"></i>Order Status
        </a>
    </div>

    <script>
        function updateClock() {
            const el = document.getElementById('clock');
            if (!el) return;
            el.textContent = window.BusinessClock
                ? BusinessClock.formatTime(false)
                : new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function loadOrders() {
            fetch('/customer-display/orders', { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    const container = document.getElementById('ordersContainer');
                    const orders = data.orders || [];

                    if (!orders.length) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-mug-hot"></i>
                                <h2>No Active Orders</h2>
                                <p>Orders will appear here when customers place them</p>
                            </div>
                        `;
                        return;
                    }

                    container.innerHTML = `<div class="orders-grid">${orders.map(o => `
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">#${o.order_number}</div>
                                    <div class="elapsed-time">
                                        <i class="fas fa-clock me-1"></i>${o.elapsed || 'Just now'}
                                    </div>
                                </div>
                                <span class="order-type-badge type-${o.order_type}">
                                    <i class="fas fa-${o.order_type === 'dine_in' ? 'utensils' : o.order_type === 'takeaway' ? 'shopping-bag' : o.order_type === 'delivery' ? 'motorcycle' : 'bolt'} me-1"></i>
                                    ${o.order_type.replace('_', ' ')}
                                </span>
                            </div>

                            <div class="order-meta">
                                <span><i class="fas fa-user"></i>${o.customer || 'Walk-in'}</span>
                                ${o.table ? `<span><i class="fas fa-table"></i>Table ${o.table}</span>` : ''}
                            </div>

                            <div class="items-list">
                                ${o.items ? o.items.map(i => `
                                    <div class="item-row">
                                        <span class="item-name">${i.product_name || i.name}</span>
                                        <span class="item-qty">x${i.quantity}</span>
                                    </div>
                                `).join('') : '<div class="item-row"><span class="text-muted">Items loading...</span></div>'}
                            </div>

                            <div class="order-footer">
                                <span class="status-indicator status-${o.status}">
                                    <i class="fas fa-${o.status === 'pending' ? 'hourglass-half' : o.status === 'preparing' ? 'fire' : o.status === 'ready' ? 'check-circle' : 'check-double'}"></i>
                                    ${o.status.toUpperCase()}
                                </span>
                                <div>
                                    <div class="total-label">Total</div>
                                    <div class="total-amount">LKR ${o.total ? o.total.toFixed(2) : '0.00'}</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}</div>`;
                })
                .catch(e => console.error('Load orders error:', e));
        }

        function loadCart() {
            fetch('/customer-display/cart')
                .then(r => r.json())
                .then(data => {
                    console.log('Cart data:', data);
                    const container = document.getElementById('cartContainer');
                    if (!data.active || !data.cart || !data.cart.items || !data.cart.items.length) {
                        container.innerHTML = '';
                        return;
                    }

                    const cart = data.cart;
                    container.innerHTML = `
                        <div class="cart-card">
                            <div class="cart-header">
                                <div class="cart-title">
                                    <i class="fas fa-shopping-cart"></i>
                                    Current Order
                                </div>
                                <span class="cart-badge">${cart.items.length} items</span>
                            </div>
                            <div class="cart-items">
                                ${cart.items.map(item => `
                                    <div class="cart-item">
                                        <div class="cart-item-qty">${item.qty}</div>
                                        <div class="cart-item-details">
                                            <div class="cart-item-name">${item.name}</div>
                                            <div class="cart-item-price">LKR ${Number(item.unitPrice || 0).toFixed(2)} each</div>
                                            ${Number(item.discount || 0) > 0 ? `<div class="cart-item-price" style="color:#dc2626;font-weight:700;">Discount −LKR ${Number(item.discount).toFixed(2)}</div>` : ''}
                                        </div>
                                        <div class="cart-item-total">LKR ${Number(item.lineTotal || 0).toFixed(2)}</div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="cart-total">
                                Total: LKR ${cart.totals.total.toFixed(2)}
                            </div>
                        </div>
                    `;
                })
                .catch(e => console.error('Load cart error:', e));
        }

        // Marketing / Idle Screen
        let idleTimer;
        let isMarketingActive = false;
        const IDLE_TIMEOUT = 30 * 1000; // 30 seconds

        function showMarketing() {
            const hasOrders = document.getElementById('ordersContainer').innerHTML.includes('order-card');
            const hasCart = document.getElementById('cartContainer').innerHTML.includes('cart-card');

            if (!hasOrders && !hasCart && !isMarketingActive) {
                isMarketingActive = true;
                document.getElementById('marketingScreen').classList.add('active');
                loadMarketingSettings();
            }
        }

        function hideMarketing() {
            isMarketingActive = false;
            document.getElementById('marketingScreen').classList.remove('active');
            resetIdleTimer();
        }

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(showMarketing, IDLE_TIMEOUT);
        }

        function loadMarketingSettings() {
            fetch('/api/marketing-settings')
                .then(r => r.json())
                .then(data => {
                    if (data.poster_url) {
                        document.getElementById('marketingPoster').src = data.poster_url;
                        document.getElementById('marketingPoster').style.display = 'block';
                    }
                    if (data.welcome_text) {
                        document.getElementById('marketingText').textContent = data.welcome_text;
                    }
                })
                .catch(() => {});
        }

        // Activity listeners to reset idle timer
        document.addEventListener('mousemove', resetIdleTimer);
        document.addEventListener('click', resetIdleTimer);
        document.addEventListener('touchstart', resetIdleTimer);

        // Start idle timer
        resetIdleTimer();

        // Poll every 3 seconds
        setInterval(() => {
            loadOrders();
            loadCart();
        }, 3000);
        loadOrders();
        loadCart();
    </script>
</body>
</html>
