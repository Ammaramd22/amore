<!DOCTYPE html>
<html lang="en" data-theme="{{ $settings['theme'] }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings['company_name'] }} · Table {{ $table->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        /* Theme: Amber Spice */
        [data-theme="amber"] {
            --bg: #1c1917; --bg2: #292524; --card: #44403c; --line: #57534e;
            --accent: #f59e0b; --accent2: #ea580c; --text: #fafaf9; --muted: #a8a29e;
            --ok: #22c55e; --danger: #ef4444; --pill: #78350f;
            --font-display: 'Fraunces', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;
            --hero: radial-gradient(ellipse at 20% 0%, rgba(245,158,11,.28), transparent 50%),
                     radial-gradient(ellipse at 90% 10%, rgba(234,88,12,.18), transparent 45%),
                     var(--bg);
        }
        /* Theme: Ocean Fresh */
        [data-theme="ocean"] {
            --bg: #0b1220; --bg2: #111827; --card: #1e293b; --line: #334155;
            --accent: #14b8a6; --accent2: #0ea5e9; --text: #f8fafc; --muted: #94a3b8;
            --ok: #34d399; --danger: #f87171; --pill: #115e59;
            --font-display: 'Outfit', system-ui, sans-serif;
            --font-body: 'Outfit', system-ui, sans-serif;
            --hero: radial-gradient(ellipse at 15% 0%, rgba(20,184,166,.25), transparent 50%),
                     radial-gradient(ellipse at 85% 5%, rgba(14,165,233,.2), transparent 45%),
                     var(--bg);
        }
        /* Theme: Night Luxe */
        [data-theme="luxe"] {
            --bg: #0a0a0a; --bg2: #141414; --card: #1a1a1a; --line: #2a2a2a;
            --accent: #d4af37; --accent2: #f5e6a3; --text: #f5f5f4; --muted: #a3a3a3;
            --ok: #86efac; --danger: #fca5a5; --pill: #3f3f06;
            --font-display: 'Playfair Display', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;
            --hero: radial-gradient(ellipse at 50% 0%, rgba(212,175,55,.16), transparent 55%),
                     var(--bg);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-body);
            background: var(--hero);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 90px;
        }
        .top {
            position: sticky; top: 0; z-index: 30;
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; padding: .9rem 1rem;
            background: color-mix(in srgb, var(--bg) 88%, transparent);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .brand { display: flex; align-items: center; gap: .7rem; min-width: 0; }
        .brand img, .brand-mark {
            width: 42px; height: 42px; border-radius: 12px; object-fit: cover; flex-shrink: 0;
        }
        .brand-mark {
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #111; font-weight: 800;
        }
        .brand h1 {
            font-family: var(--font-display);
            font-size: 1.05rem; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .brand small { color: var(--muted); font-size: .75rem; }
        .table-chip {
            background: var(--pill); color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);
            border-radius: 999px; padding: .4rem .8rem; font-weight: 800; font-size: .8rem;
            white-space: nowrap;
        }

        .gate {
            max-width: 420px; margin: 12vh auto 0; padding: 1.25rem;
            text-align: center;
        }
        .gate-card {
            background: var(--bg2); border: 1px solid var(--line);
            border-radius: 22px; padding: 1.75rem 1.35rem;
            box-shadow: 0 20px 50px rgba(0,0,0,.35);
        }
        .gate h2 {
            font-family: var(--font-display);
            font-size: 1.65rem; margin-bottom: .4rem;
        }
        .gate p { color: var(--muted); margin-bottom: 1.25rem; font-size: .95rem; }
        .code-input {
            width: 100%; text-align: center; letter-spacing: .45em;
            font-size: 1.8rem; font-weight: 800; padding: .85rem;
            border-radius: 14px; border: 2px solid var(--line);
            background: var(--bg); color: var(--text); outline: none;
        }
        .code-input:focus { border-color: var(--accent); }
        .btn-main {
            width: 100%; margin-top: 1rem; border: 0; border-radius: 14px;
            padding: 1rem; font: inherit; font-weight: 800; font-size: 1.05rem;
            color: #111; cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }
        .gate-err { color: var(--danger); margin-top: .75rem; min-height: 1.2em; font-weight: 600; }

        .cats {
            display: flex; gap: .5rem; overflow-x: auto; padding: .9rem 1rem .4rem;
            position: sticky; top: 66px; z-index: 20;
            background: color-mix(in srgb, var(--bg) 92%, transparent);
            backdrop-filter: blur(8px);
        }
        .cat {
            flex: 0 0 auto; border: 1px solid var(--line); background: var(--bg2);
            color: var(--muted); border-radius: 999px; padding: .45rem .9rem;
            font-weight: 700; font-size: .82rem; cursor: pointer;
        }
        .cat.active { background: var(--accent); color: #111; border-color: var(--accent); }

        .menu { padding: .5rem 1rem 1rem; }
        .section-title {
            font-family: var(--font-display);
            font-size: 1.25rem; margin: 1rem 0 .65rem; color: var(--accent);
        }
        .grid { display: grid; grid-template-columns: 1fr; gap: .75rem; }
        @media (min-width: 520px) { .grid { grid-template-columns: 1fr 1fr; } }

        .item {
            display: grid; grid-template-columns: 1fr auto; gap: .75rem;
            align-items: center; background: var(--bg2);
            border: 1px solid var(--line); border-radius: 16px; padding: .9rem;
        }
        .item h3 { font-size: 1rem; font-weight: 700; margin-bottom: .2rem; }
        .item .desc { color: var(--muted); font-size: .78rem; line-height: 1.35; }
        .item .price { color: var(--accent); font-weight: 800; margin-top: .35rem; }
        .qty {
            display: flex; align-items: center; gap: .35rem;
        }
        .qty button {
            width: 36px; height: 36px; border-radius: 10px; border: 0;
            background: var(--card); color: var(--text); font-size: 1.1rem; font-weight: 800; cursor: pointer;
        }
        .qty button.plus { background: var(--accent); color: #111; }
        .qty span { min-width: 1.2rem; text-align: center; font-weight: 800; }

        .cart-bar {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 40;
            padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom));
            background: color-mix(in srgb, var(--bg2) 94%, transparent);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(12px);
            display: none;
        }
        .cart-bar.show { display: block; }
        .cart-inner {
            max-width: 640px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        }
        .cart-meta { font-weight: 700; }
        .cart-meta small { display: block; color: var(--muted); font-weight: 600; }
        .cart-btn {
            border: 0; border-radius: 14px; padding: .85rem 1.1rem;
            font: inherit; font-weight: 800; color: #111; cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            white-space: nowrap;
        }

        .sheet {
            display: none; position: fixed; inset: 0; z-index: 50;
            background: rgba(0,0,0,.55);
        }
        .sheet.open { display: block; }
        .sheet-panel {
            position: absolute; left: 0; right: 0; bottom: 0;
            max-height: 85vh; overflow: auto;
            background: var(--bg2); border-radius: 22px 22px 0 0;
            padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom));
            border-top: 2px solid var(--accent);
        }
        .sheet-head {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .sheet-head h3 { font-family: var(--font-display); font-size: 1.35rem; }
        .sheet-close {
            border: 0; background: var(--card); color: var(--text);
            width: 40px; height: 40px; border-radius: 12px; cursor: pointer;
        }
        .cart-line {
            display: flex; justify-content: space-between; gap: .75rem;
            padding: .75rem 0; border-bottom: 1px solid var(--line);
        }
        .notes {
            width: 100%; margin-top: .75rem; border-radius: 12px;
            border: 1px solid var(--line); background: var(--bg); color: var(--text);
            padding: .75rem; font: inherit; resize: vertical; min-height: 70px;
        }
        .submit {
            width: 100%; margin-top: 1rem; border: 0; border-radius: 14px;
            padding: 1rem; font: inherit; font-weight: 800; font-size: 1.05rem;
            color: #111; cursor: pointer;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }
        .success-box {
            display: none; text-align: center; padding: 2rem 1rem;
        }
        .success-box.show { display: block; }
        .success-box i { font-size: 3rem; color: var(--ok); margin-bottom: .75rem; }
        .empty-menu { text-align: center; color: var(--muted); padding: 3rem 1rem; }
        .footer-note {
            text-align: center; color: var(--muted); font-size: .75rem;
            padding: 1.5rem 1rem 0; letter-spacing: .06em;
        }
    </style>
</head>
<body>
@php
    $showPrices = $settings['qr_show_prices'];
    $cur = $settings['currency'];
@endphp

<header class="top">
    <div class="brand">
        @if($settings['logo_url'])
            <img src="{{ $settings['logo_url'] }}" alt="">
        @else
            <div class="brand-mark">{{ strtoupper(substr($settings['company_name'], 0, 1)) }}</div>
        @endif
        <div style="min-width:0">
            <h1>{{ $settings['company_name'] }}</h1>
            <small>QR Menu</small>
        </div>
    </div>
    <div class="table-chip"><i class="fas fa-chair me-1"></i>{{ $table->name }}</div>
</header>

@unless($verified)
<section class="gate" id="gate">
    <div class="gate-card">
        <h2>Table code</h2>
        <p>Enter the 4-digit code on the card at your table so we know you’re here.</p>
        <input class="code-input" id="codeInput" type="tel" inputmode="numeric" maxlength="4" placeholder="••••" autocomplete="one-time-code">
        <button type="button" class="btn-main" id="verifyBtn" onclick="verifyCode()">Unlock menu</button>
        <div class="gate-err" id="gateErr"></div>
    </div>
</section>
@endunless

<div id="menuApp" style="{{ $verified ? '' : 'display:none' }}">
    <div class="cats" id="cats">
        <button type="button" class="cat active" data-cat="all" onclick="filterCat('all', this)">All</button>
        @foreach($categories as $cat)
            @if($cat->products->count())
            <button type="button" class="cat" data-cat="{{ $cat->id }}" onclick="filterCat('{{ $cat->id }}', this)">{{ $cat->name }}</button>
            @endif
        @endforeach
    </div>

    <div class="menu" id="menu">
        @forelse($categories as $cat)
            @continue($cat->products->isEmpty())
            <div class="cat-block" data-cat="{{ $cat->id }}">
                <div class="section-title">{{ $cat->name }}</div>
                <div class="grid">
                    @foreach($cat->products as $p)
                    @php $price = (float) ($p->final_price ?? $p->selling_price); @endphp
                    <article class="item" data-id="{{ $p->id }}">
                        <div>
                            <h3>{{ $p->name }}</h3>
                            @if($p->description)<div class="desc">{{ \Illuminate\Support\Str::limit($p->description, 80) }}</div>@endif
                            @if($showPrices)<div class="price">{{ $cur }} {{ number_format($price, 2) }}</div>@endif
                        </div>
                        <div class="qty">
                            <button type="button" onclick="changeQty({{ $p->id }}, -1)">−</button>
                            <span id="qty-{{ $p->id }}">0</span>
                            <button type="button" class="plus" onclick="changeQty({{ $p->id }}, 1)">+</button>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-menu">No menu items available yet</div>
        @endforelse
    </div>

    <div class="footer-note">QRPOS BY AVENQUE</div>
</div>

<div class="cart-bar" id="cartBar">
    <div class="cart-inner">
        <div class="cart-meta">
            <span id="cartCount">0 items</span>
            <small id="cartTotal">{{ $cur }} 0.00</small>
        </div>
        <button type="button" class="cart-btn" onclick="openCart()">View cart</button>
    </div>
</div>

<div class="sheet" id="cartSheet" onclick="if(event.target===this)closeCart()">
    <div class="sheet-panel">
        <div class="sheet-head">
            <h3>Your order</h3>
            <button type="button" class="sheet-close" onclick="closeCart()"><i class="fas fa-times"></i></button>
        </div>
        <div id="cartLines"></div>
        <input class="notes" id="guestName" type="text" placeholder="Your name (optional)" maxlength="100">
        <textarea class="notes" id="orderNotes" placeholder="Notes for the kitchen (optional)"></textarea>
        <button type="button" class="submit" id="submitBtn" onclick="submitOrder()">Send to waiter</button>
        <div class="success-box" id="successBox">
            <i class="fas fa-check-circle"></i>
            <h3 id="successTitle">Order sent</h3>
            <p id="successMsg" style="color:var(--muted);margin-top:.5rem;"></p>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const tableId = {{ $table->id }};
const currency = @json($cur);
const showPrices = @json($showPrices);
const needsApproval = @json($settings['qr_order_approval']);
const products = @json($productCatalog);

const cart = {};
products.forEach(p => { cart[p.id] = 0; });
const byId = Object.fromEntries(products.map(p => [p.id, p]));

function changeQty(id, delta) {
    cart[id] = Math.max(0, Math.min(50, (cart[id] || 0) + delta));
    const el = document.getElementById('qty-' + id);
    if (el) el.textContent = cart[id];
    updateCartBar();
}

function cartItems() {
    return Object.entries(cart)
        .filter(([, q]) => q > 0)
        .map(([id, q]) => ({ ...byId[id], quantity: q }));
}

function updateCartBar() {
    const items = cartItems();
    const count = items.reduce((s, i) => s + i.quantity, 0);
    const total = items.reduce((s, i) => s + i.price * i.quantity, 0);
    const bar = document.getElementById('cartBar');
    if (!count) { bar.classList.remove('show'); return; }
    bar.classList.add('show');
    document.getElementById('cartCount').textContent = count + (count === 1 ? ' item' : ' items');
    document.getElementById('cartTotal').textContent = showPrices
        ? (currency + ' ' + total.toFixed(2))
        : 'Ready to send';
}

function filterCat(id, btn) {
    document.querySelectorAll('.cat').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.cat-block').forEach(block => {
        block.style.display = (id === 'all' || block.dataset.cat === String(id)) ? '' : 'none';
    });
}

function openCart() {
    const lines = document.getElementById('cartLines');
    const items = cartItems();
    if (!items.length) return;
    lines.innerHTML = items.map(i => `
        <div class="cart-line">
            <div>
                <strong>${i.name}</strong>
                <div style="color:var(--muted);font-size:.85rem;">× ${i.quantity}</div>
            </div>
            <div style="font-weight:800;color:var(--accent);">${showPrices ? (currency + ' ' + (i.price * i.quantity).toFixed(2)) : ''}</div>
        </div>
    `).join('');
    document.getElementById('submitBtn').style.display = '';
    document.getElementById('successBox').classList.remove('show');
    document.getElementById('submitBtn').textContent = needsApproval ? 'Send to waiter' : 'Send to kitchen';
    document.getElementById('cartSheet').classList.add('open');
}

function closeCart() {
    document.getElementById('cartSheet').classList.remove('open');
}

async function verifyCode() {
    const code = document.getElementById('codeInput').value.trim();
    const err = document.getElementById('gateErr');
    err.textContent = '';
    if (code.length < 4) { err.textContent = 'Enter the 4-digit code'; return; }
    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    try {
        const r = await fetch(`/qr-menu/${tableId}/verify`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ code }),
        });
        const data = await r.json();
        if (!data.success) { err.textContent = data.message || 'Wrong code'; btn.disabled = false; return; }
        document.getElementById('gate').style.display = 'none';
        document.getElementById('menuApp').style.display = '';
    } catch (e) {
        err.textContent = 'Network error — try again';
        btn.disabled = false;
    }
}

document.getElementById('codeInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') verifyCode();
});

async function submitOrder() {
    const items = cartItems().map(i => ({
        product_id: i.id,
        quantity: i.quantity,
    }));
    if (!items.length) return;
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Sending…';
    try {
        const r = await fetch(`/qr-menu/${tableId}/order`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                items,
                notes: document.getElementById('orderNotes').value || null,
                guest_name: document.getElementById('guestName').value || null,
            }),
        });
        const data = await r.json();
        if (!data.success) {
            alert(data.message || 'Could not place order');
            btn.disabled = false;
            btn.textContent = needsApproval ? 'Send to waiter' : 'Send to kitchen';
            return;
        }
        Object.keys(cart).forEach(k => cart[k] = 0);
        document.querySelectorAll('[id^=qty-]').forEach(el => el.textContent = '0');
        updateCartBar();
        btn.style.display = 'none';
        document.getElementById('successBox').classList.add('show');
        document.getElementById('successTitle').textContent = data.needs_approval ? 'Waiting for waiter' : 'Order placed';
        document.getElementById('successMsg').textContent = data.message + (data.order_number ? ' · ' + data.order_number : '');
    } catch (e) {
        alert('Network error');
        btn.disabled = false;
        btn.textContent = needsApproval ? 'Send to waiter' : 'Send to kitchen';
    }
}
</script>
</body>
</html>
