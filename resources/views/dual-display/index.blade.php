<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Display — {{ $companyName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #050505;
            --panel: #0a0a0a;
            --card: #111111;
            --border: #222;
            --amber: #f5b800;
            --amber-dim: rgba(245, 184, 0, 0.18);
            --green: #22c55e;
            --red: #ef4444;
            --muted: #737373;
            --text: #f5f5f5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', system-ui, sans-serif;
            overflow: hidden;
        }

        .shell {
            display: grid;
            grid-template-rows: auto 1fr auto;
            height: 100vh;
        }

        /* Header */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.7rem 1.25rem;
            background: #000;
            border-bottom: 1px solid #1a1a1a;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 0.04em;
        }
        .brand img {
            width: 34px; height: 34px;
            object-fit: contain;
            border-radius: 8px;
        }
        .brand-mark {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            display: grid; place-items: center;
            font-weight: 800; color: #fff; font-size: 1rem;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--green);
        }
        .live-pill::before {
            content: '';
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6);
            animation: pulse 1.6s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .clock {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            font-size: 0.95rem;
            color: #d4d4d4;
        }
        .btn-fs {
            border: 1px solid var(--amber);
            color: var(--amber);
            background: transparent;
            border-radius: 6px;
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-fs:hover { background: var(--amber-dim); }

        /* Main split */
        .main {
            display: grid;
            grid-template-columns: 1.35fr 0.9fr;
            min-height: 0;
        }
        .left {
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            min-height: 0;
            border-right: 1px solid #161616;
            background: var(--panel);
        }
        .right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #000;
            position: relative;
            overflow: hidden;
        }

        .customer-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #161616;
            background: #0d0d0d;
        }
        .customer-bar .who {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .customer-bar .who i {
            color: var(--muted);
        }
        .meta-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: flex-end;
        }
        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid #333;
            color: #d4d4d4;
            background: #151515;
        }
        .meta-badge.type { border-color: rgba(245,184,0,.35); color: var(--amber); }
        .meta-badge.table { border-color: rgba(59,130,246,.4); color: #60a5fa; }
        .meta-badge.invoice { border-color: #333; color: #a3a3a3; }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1.25fr;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid #161616;
        }
        .sum-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            min-height: 84px;
        }
        .sum-card .label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.35rem;
        }
        .sum-card .sub {
            font-size: 0.78rem;
            color: #737373;
            margin-bottom: 0.25rem;
        }
        .sum-card .val {
            font-size: 1.35rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .sum-card.savings .val { color: var(--green); }
        .sum-card.total {
            border-color: var(--amber);
            box-shadow: inset 0 0 0 1px rgba(245,184,0,.25);
        }
        .sum-card.total .label { color: var(--amber); }
        .sum-card.total .val { color: var(--amber); font-size: 1.55rem; }

        .items-wrap {
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: 0.75rem 1.1rem 0.5rem;
        }
        .items-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.65rem;
        }
        .items-list {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
        .items-list::-webkit-scrollbar { width: 5px; }
        .items-list::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 4px; }

        .item-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 0.85rem;
            align-items: center;
            padding: 0.75rem 0.85rem;
            margin-bottom: 0.45rem;
            background: #0f0f0f;
            border: 1px solid #1c1c1c;
            border-radius: 10px;
            animation: in 0.35s ease;
        }
        @keyframes in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: none; }
        }
        .item-qty {
            width: 36px; height: 36px;
            border-radius: 9px;
            background: var(--amber-dim);
            color: var(--amber);
            display: grid; place-items: center;
            font-weight: 800;
            font-size: 0.9rem;
        }
        .item-name { font-weight: 600; font-size: 1.02rem; }
        .item-unit { font-size: 0.78rem; color: var(--muted); margin-top: 0.1rem; }
        .item-total {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #e5e5e5;
            white-space: nowrap;
        }
        .items-empty {
            flex: 1;
            display: grid;
            place-items: center;
            color: #3f3f3f;
            text-align: center;
            min-height: 180px;
        }
        .items-empty i { font-size: 2.5rem; margin-bottom: 0.75rem; display: block; }

        .pay-bar {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.5rem;
            padding: 0.85rem 1.1rem 1rem;
            border-top: 1px solid #161616;
            background: #0a0a0a;
        }
        .pay-cell .k {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 0.2rem;
        }
        .pay-cell .v {
            font-size: 1.05rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        .pay-cell.balance .v { color: var(--red); }
        .pay-cell.change .v { color: var(--green); }

        /* Brand panel */
        .brand-panel {
            text-align: center;
            padding: 2rem;
            z-index: 1;
        }
        .brand-panel .logo-wrap {
            width: min(42vw, 280px);
            height: min(42vw, 280px);
            margin: 0 auto 1.25rem;
            display: grid;
            place-items: center;
        }
        .brand-panel .logo-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 12px 40px rgba(59,130,246,.25));
        }
        .brand-panel .logo-fallback {
            width: 180px; height: 180px;
            border-radius: 40px;
            background: radial-gradient(circle at 30% 30%, #3b82f6, #1e3a8a 70%);
            display: grid; place-items: center;
            font-size: 5rem;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 20px 60px rgba(37,99,235,.35);
        }
        .brand-panel h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800;
            letter-spacing: 0.06em;
            margin-bottom: 0.35rem;
        }
        .brand-panel .by {
            color: #3b82f6;
            font-weight: 700;
            letter-spacing: 0.18em;
            font-size: 0.85rem;
        }
        .brand-glow {
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,235,.18), transparent 70%);
            pointer-events: none;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            padding: 0.7rem 1.25rem;
            background: #000;
            border-top: 1px solid #1a1a1a;
            color: #a3a3a3;
            font-size: clamp(0.85rem, 1.4vw, 1.05rem);
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .idle-overlay .footer {
            font-size: clamp(0.9rem, 1.5vw, 1.1rem);
            padding: 0.85rem 1.25rem;
            color: #d4d4d4;
        }

        .idle-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: #000;
            display: flex;
            flex-direction: column;
        }
        .idle-overlay.hidden { display: none; }
        .idle-overlay .topbar,
        .idle-overlay .footer { flex: 0 0 auto; }
        .idle-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            min-height: 0;
        }

        /* Idle offers — balanced poster + message + footer */
        .offers-stage {
            display: none;
            flex: 1;
            flex-direction: column;
            min-height: 0;
            background:
                radial-gradient(ellipse 70% 50% at 50% 18%, rgba(245,184,0,.14), transparent 55%),
                radial-gradient(ellipse 45% 35% at 85% 85%, rgba(37,99,235,.12), transparent 50%),
                linear-gradient(165deg, #050505, #111 50%, #0a0a0a);
        }
        .offers-stage.show { display: flex; }
        .offers-media {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
            position: relative;
            display: grid;
            place-items: center;
            justify-items: center;
            align-content: center;
            padding: clamp(1rem, 3vh, 2rem) clamp(1.25rem, 4vw, 3rem);
        }
        .offers-stage.has-poster .offers-media {
            flex: 1 1 auto;
            padding: clamp(1rem, 3vh, 2rem) clamp(1.5rem, 5vw, 3.5rem);
        }
        .poster-slider {
            position: relative;
            width: min(440px, 48vw);
            height: min(440px, 42vh);
            max-width: 100%;
            margin: 0 auto;
        }
        .poster-slider .slides {
            position: absolute;
            inset: 0;
        }
        .poster-slider .slide {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            opacity: 0;
            transform: translateX(12px);
            transition: opacity .55s ease, transform .55s ease;
            pointer-events: none;
        }
        .poster-slider .slide.active {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
            z-index: 1;
        }
        .poster-slider .slide img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,.4);
        }
        .poster-dots {
            position: absolute;
            left: 50%;
            bottom: -1.15rem;
            transform: translateX(-50%);
            display: flex;
            gap: 0.45rem;
            z-index: 2;
        }
        .poster-dots button {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            border: 0;
            padding: 0;
            background: rgba(255,255,255,.28);
            cursor: pointer;
            transition: background .2s, width .2s;
        }
        .poster-dots button.active {
            background: var(--amber);
            width: 18px;
        }
        .offers-copy {
            flex: 0 0 auto;
            position: relative;
            z-index: 2;
            padding: clamp(1.1rem, 2.2vh, 1.6rem) clamp(1.25rem, 3vw, 2rem);
            text-align: center;
            background: rgba(8,8,8,.96);
            border-top: 1px solid rgba(245,184,0,.3);
        }
        .offers-stage.has-poster .offers-copy {
            background: rgba(8,8,8,.96);
        }
        .offers-copy .offer-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: rgba(245,184,0,.2);
            border: 1px solid rgba(245,184,0,.45);
            color: var(--amber);
            font-size: clamp(0.7rem, 1.1vw, 0.8rem);
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 0.7rem;
        }
        .offers-copy .offer-text {
            font-size: clamp(1.35rem, 2.6vw, 2.15rem);
            font-weight: 800;
            line-height: 1.25;
            max-width: 32ch;
            margin: 0 auto;
        }
        .offers-copy .offer-hint {
            margin-top: 0.55rem;
            color: #a3a3a3;
            font-size: clamp(0.9rem, 1.4vw, 1.1rem);
            font-weight: 600;
        }
        .idle-brand-fallback {
            flex: 1;
            display: grid;
            place-items: center;
            position: relative;
            z-index: 1;
            min-height: 0;
            padding: 1.5rem;
        }
        .idle-brand-fallback.hide { display: none !important; }
        .idle-brand-fallback.brand-panel .logo-wrap {
            width: min(36vw, 220px);
            height: min(36vw, 220px);
        }
        .idle-brand-fallback.brand-panel h1 {
            font-size: clamp(1.6rem, 3.5vw, 2.6rem);
        }

        .right-offer {
            position: absolute;
            left: 1.25rem; right: 1.25rem; bottom: 1.25rem;
            z-index: 2;
            display: none;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(245,184,0,.35);
            background: rgba(0,0,0,.55);
            backdrop-filter: blur(8px);
            text-align: left;
        }
        .right-offer.show { display: grid; grid-template-columns: 88px 1fr; min-height: 88px; }
        .right-offer img {
            width: 88px; height: 100%; min-height: 88px;
            object-fit: cover;
            background: #111;
        }
        .right-offer .ro-body { padding: 0.85rem 1rem; display: flex; flex-direction: column; justify-content: center; }
        .right-offer .ro-label {
            font-size: 0.65rem; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: var(--amber); margin-bottom: 0.25rem;
        }
        .right-offer .ro-text {
            font-size: 0.95rem; font-weight: 700; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        @media (max-width: 900px) {
            .main { grid-template-columns: 1fr; }
            .right { display: none; }
            .summary-row { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.business-clock')
</head>
<body>
@php
    $brand = $companyName ?: 'ResPOS';
    $phone = $companyPhone ?: '';
    $cur = $currency ?: 'Rs';
@endphp

<div class="idle-overlay" id="idleOverlay">
    <div class="topbar">
        <div class="brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brand }}">
            @else
                <div class="brand-mark">{{ strtoupper(substr($brand, 0, 1)) }}</div>
            @endif
            <span>{{ $brand }}</span>
        </div>
        <div class="topbar-right">
            <span class="live-pill">LIVE</span>
            <span class="clock" id="clockIdle">--:--</span>
            <button type="button" class="btn-fs" onclick="toggleFs()"><i class="fas fa-expand me-1"></i>Full Screen</button>
        </div>
    </div>
    <div class="idle-body">
        <div class="offers-stage" id="offersStage">
            <div class="offers-media">
                <div class="poster-slider" id="posterSlider" hidden>
                    <div class="slides" id="posterSlides"></div>
                    <div class="poster-dots" id="posterDots"></div>
                </div>
            </div>
            <div class="offers-copy">
                <div class="offer-badge"><i class="fas fa-tags me-1"></i>Offers</div>
                <div class="offer-text" id="offerWelcome">Welcome! Order at the counter</div>
                <div class="offer-hint">Your bill will appear here when items are scanned</div>
            </div>
        </div>
        <div class="brand-glow idle-brand-fallback" id="idleBrandGlow"></div>
        <div class="brand-panel idle-brand-fallback" id="idleBrandPanel">
            <div class="logo-wrap">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brand }}">
                @else
                    <div class="logo-fallback">{{ strtoupper(substr($brand, 0, 1)) }}</div>
                @endif
            </div>
            <h1>{{ strtoupper($brand) }}</h1>
            <div class="by">BY AVENQUE</div>
            <p style="margin-top:1.5rem;color:#525252;font-size:1.05rem;">Your order will appear here</p>
        </div>
    </div>
    <div class="footer">QRPOS By Avenque (Pvt) Ltd | 076 822 2201</div>
</div>

<div class="shell" id="activeShell" style="display:none;">
    <div class="topbar">
        <div class="brand">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brand }}">
            @else
                <div class="brand-mark">{{ strtoupper(substr($brand, 0, 1)) }}</div>
            @endif
            <span>{{ $brand }}</span>
        </div>
        <div class="topbar-right">
            <span class="live-pill">LIVE</span>
            <span class="clock" id="clock">--:--</span>
            <button type="button" class="btn-fs" onclick="toggleFs()"><i class="fas fa-expand me-1"></i>Full Screen</button>
        </div>
    </div>

    <div class="main">
        <section class="left">
            <div class="customer-bar">
                <div class="who">
                    <i class="fas fa-user-circle"></i>
                    <span id="customerName">Walk-in Customer</span>
                </div>
                <div class="meta-badges" id="metaBadges"></div>
            </div>

            <div class="summary-row">
                <div class="sum-card">
                    <div class="label">Subtotal</div>
                    <div class="sub" id="itemCountLabel">0 Items</div>
                    <div class="val" id="subtotalVal">{{ $cur }} 0.00</div>
                </div>
                <div class="sum-card savings">
                    <div class="label">Savings</div>
                    <div class="sub">Discounts & offers</div>
                    <div class="val" id="savingsVal">{{ $cur }} 0.00</div>
                </div>
                <div class="sum-card total">
                    <div class="label">Total Amount Payable</div>
                    <div class="sub">&nbsp;</div>
                    <div class="val" id="totalVal">{{ $cur }} 0.00</div>
                </div>
            </div>

            <div class="items-wrap">
                <div class="items-title">Scanned Items</div>
                <div class="items-list" id="itemsList">
                    <div class="items-empty">
                        <div>
                            <i class="fas fa-barcode"></i>
                            <div>Waiting for items…</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pay-bar">
                <div class="pay-cell">
                    <div class="k">Paying</div>
                    <div class="v" id="payingVal">{{ $cur }} 0.00</div>
                </div>
                <div class="pay-cell change">
                    <div class="k">Change Return</div>
                    <div class="v" id="changeVal">{{ $cur }} 0.00</div>
                </div>
                <div class="pay-cell balance">
                    <div class="k">Balance</div>
                    <div class="v" id="balanceVal">{{ $cur }} 0.00</div>
                </div>
            </div>
        </section>

        <aside class="right">
            <div class="brand-glow"></div>
            <div class="brand-panel">
                <div class="logo-wrap">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brand }}">
                    @else
                        <div class="logo-fallback">{{ strtoupper(substr($brand, 0, 1)) }}</div>
                    @endif
                </div>
                <h1>{{ strtoupper($brand) }}</h1>
                <div class="by">BY AVENQUE</div>
            </div>
            <div class="right-offer" id="rightOffer">
                <img id="rightOfferImg" src="" alt="" onerror="this.style.display='none'">
                <div class="ro-body">
                    <div class="ro-label">Special offer</div>
                    <div class="ro-text" id="rightOfferText">—</div>
                </div>
            </div>
        </aside>
    </div>

    <div class="footer">QRPOS By Avenque (Pvt) Ltd | 076 822 2201</div>
</div>

<script>
    const CUR = @json($cur);
    let marketing = {
        enabled: true,
        welcome_text: 'Welcome! Order at the counter',
        posters: [],
        slide_interval: 6,
    };
    let slideIndex = 0;
    let slideTimer = null;

    function money(n) {
        const v = Number(n || 0);
        return CUR + ' ' + v.toFixed(2);
    }

    function updateClock() {
        const t = window.BusinessClock
            ? BusinessClock.formatTime(false)
            : new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        document.querySelectorAll('.clock').forEach(el => el.textContent = t);
    }
    setInterval(updateClock, 1000);
    updateClock();

    function toggleFs() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    }

    function stopPosterSlider() {
        if (slideTimer) {
            clearInterval(slideTimer);
            slideTimer = null;
        }
    }

    function goToSlide(i) {
        const slides = document.querySelectorAll('#posterSlides .slide');
        const dots = document.querySelectorAll('#posterDots button');
        if (!slides.length) return;
        slideIndex = ((i % slides.length) + slides.length) % slides.length;
        slides.forEach((el, idx) => el.classList.toggle('active', idx === slideIndex));
        dots.forEach((el, idx) => el.classList.toggle('active', idx === slideIndex));

        const rightImg = document.getElementById('rightOfferImg');
        const url = marketing.posters[slideIndex];
        if (rightImg && url) {
            rightImg.style.display = '';
            rightImg.src = url;
        }
    }

    function startPosterSlider() {
        stopPosterSlider();
        const n = marketing.posters.length;
        if (n < 2) return;
        const sec = Math.max(3, Number(marketing.slide_interval) || 6);
        slideTimer = setInterval(() => goToSlide(slideIndex + 1), sec * 1000);
    }

    function buildPosterSlider(urls) {
        const slider = document.getElementById('posterSlider');
        const track = document.getElementById('posterSlides');
        const dots = document.getElementById('posterDots');
        track.innerHTML = '';
        dots.innerHTML = '';
        stopPosterSlider();

        if (!urls.length) {
            slider.hidden = true;
            return;
        }

        urls.forEach((url, idx) => {
            const slide = document.createElement('div');
            slide.className = 'slide' + (idx === 0 ? ' active' : '');
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Offer ' + (idx + 1);
            img.onerror = () => {
                slide.remove();
                const remaining = [...track.querySelectorAll('.slide')];
                if (!remaining.length) {
                    slider.hidden = true;
                    document.getElementById('offersStage')?.classList.remove('has-poster');
                } else {
                    rebuildDots();
                    goToSlide(0);
                    startPosterSlider();
                }
            };
            slide.appendChild(img);
            track.appendChild(slide);

            if (urls.length > 1) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = idx === 0 ? 'active' : '';
                dot.setAttribute('aria-label', 'Poster ' + (idx + 1));
                dot.addEventListener('click', () => {
                    goToSlide(idx);
                    startPosterSlider();
                });
                dots.appendChild(dot);
            }
        });

        function rebuildDots() {
            dots.innerHTML = '';
            const remaining = [...track.querySelectorAll('.slide')];
            if (remaining.length < 2) return;
            remaining.forEach((_, idx) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = idx === 0 ? 'active' : '';
                dot.addEventListener('click', () => {
                    goToSlide(idx);
                    startPosterSlider();
                });
                dots.appendChild(dot);
            });
        }

        slider.hidden = false;
        slideIndex = 0;
        goToSlide(0);
        startPosterSlider();
    }

    function applyMarketingUi() {
        const stage = document.getElementById('offersStage');
        const welcome = document.getElementById('offerWelcome');
        const brandPanel = document.getElementById('idleBrandPanel');
        const brandGlow = document.getElementById('idleBrandGlow');
        const rightOffer = document.getElementById('rightOffer');
        const rightImg = document.getElementById('rightOfferImg');
        const rightText = document.getElementById('rightOfferText');

        const text = marketing.welcome_text || 'Welcome! Order at the counter';
        welcome.textContent = text;
        rightText.textContent = text;

        const posters = Array.isArray(marketing.posters) ? marketing.posters.filter(Boolean) : [];
        const hasPoster = !!(marketing.enabled && posters.length);
        const showOffers = !!marketing.enabled && (hasPoster || text);

        if (showOffers) {
            stage.classList.add('show');
            brandPanel?.classList.add('hide');
            brandGlow?.classList.add('hide');
            if (hasPoster) {
                stage.classList.add('has-poster');
                buildPosterSlider(posters);
                rightImg.style.display = '';
                rightImg.onerror = () => { rightImg.style.display = 'none'; };
                rightImg.src = posters[0];
                rightOffer.classList.add('show');
            } else {
                stage.classList.remove('has-poster');
                buildPosterSlider([]);
                rightOffer.classList.remove('show');
                rightImg.removeAttribute('src');
            }
        } else {
            stage.classList.remove('show', 'has-poster');
            brandPanel?.classList.remove('hide');
            brandGlow?.classList.remove('hide');
            buildPosterSlider([]);
            rightOffer.classList.remove('show');
        }
    }

    function loadMarketing() {
        fetch('/api/marketing-settings')
            .then(r => r.json())
            .then(data => {
                const posters = Array.isArray(data.posters) && data.posters.length
                    ? data.posters
                    : (data.poster_url ? [data.poster_url] : []);
                marketing = {
                    enabled: data.marketing_enabled !== false,
                    welcome_text: data.welcome_text || 'Welcome! Order at the counter',
                    posters,
                    slide_interval: data.slide_interval || 6,
                };
                applyMarketingUi();
            })
            .catch(() => {});
    }

    function typeLabel(raw) {
        const map = {
            dine_in: 'Dine In',
            takeaway: 'Takeaway',
            delivery: 'Delivery',
            express: 'Express',
        };
        return map[raw] || (raw ? String(raw).replace(/_/g, ' ') : '');
    }

    function renderMeta(meta = {}) {
        const badges = [];
        if (meta.invoice) {
            badges.push(`<span class="meta-badge invoice"><i class="fas fa-hashtag"></i>${escapeHtml(meta.invoice)}</span>`);
        }
        if (meta.order_type) {
            badges.push(`<span class="meta-badge type"><i class="fas fa-tag"></i>${escapeHtml(typeLabel(meta.order_type))}</span>`);
        }
        if (meta.order_type === 'dine_in' && meta.table) {
            const table = String(meta.table).replace(/^table\s+/i, '');
            badges.push(`<span class="meta-badge table"><i class="fas fa-chair"></i>Table ${escapeHtml(table)}</span>`);
        }
        document.getElementById('metaBadges').innerHTML = badges.join('');
        document.getElementById('customerName').textContent = meta.customer || 'Walk-in Customer';
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showIdle() {
        document.getElementById('idleOverlay').classList.remove('hidden');
        document.getElementById('activeShell').style.display = 'none';
        applyMarketingUi();
    }

    function showActive() {
        document.getElementById('idleOverlay').classList.add('hidden');
        document.getElementById('activeShell').style.display = 'grid';
    }

    let lastCartKey = '';
    let displayMode = null; // 'idle' | 'active'
    let lastSeenSeq = 0;

    function cartKey(payload) {
        try {
            return JSON.stringify(payload?.cart || payload || {});
        } catch (e) {
            return String(Date.now());
        }
    }

    function goPromoIdle() {
        lastCartKey = '';
        const list = document.getElementById('itemsList');
        if (list) {
            list.innerHTML = '';
            list.dataset.rowsKey = '';
        }
        displayMode = 'idle';
        showIdle();
    }

    function renderCart(payload) {
        const cart = payload.cart || payload;
        const items = cart.items || [];
        const totals = cart.totals || {};
        const meta = cart.meta || {};
        const payment = cart.payment || {};

        if (!items.length) {
            goPromoIdle();
            return;
        }

        if (displayMode !== 'active') {
            displayMode = 'active';
            showActive();
        }

        renderMeta(meta);

        const count = items.reduce((s, i) => s + Number(i.qty || 0), 0);
        document.getElementById('itemCountLabel').textContent = count + (count === 1 ? ' Item' : ' Items');
        document.getElementById('subtotalVal').textContent = money(totals.subtotal);
        document.getElementById('savingsVal').textContent = money(totals.discount || totals.savings || 0);
        document.getElementById('totalVal').textContent = money(totals.total);

        document.getElementById('payingVal').textContent = money(payment.paying || 0);
        document.getElementById('changeVal').textContent = money(payment.change || 0);
        document.getElementById('balanceVal').textContent = money(
            payment.balance != null ? payment.balance : totals.total
        );

        const list = document.getElementById('itemsList');
        const nextHtml = items.map(item => `
            <div class="item-row">
                <div class="item-qty">${Number(item.qty)}</div>
                <div>
                    <div class="item-name">${escapeHtml(item.name)}</div>
                    <div class="item-unit">${money(item.unitPrice)} each</div>
                    ${Number(item.discount || 0) > 0 ? `<div class="item-unit" style="color:#dc2626;font-weight:700;">Discount −${money(item.discount)}</div>` : ''}
                </div>
                <div class="item-total">${money(item.lineTotal)}</div>
            </div>
        `).join('');

        // Only rewrite list when rows actually change (avoids flicker)
        if (list.dataset.rowsKey !== lastCartKey) {
            list.innerHTML = nextHtml;
            list.dataset.rowsKey = lastCartKey;
        }
    }

    function pollCart() {
        fetch('/customer-display/cart')
            .then(r => r.json())
            .then(data => {
                const seq = Number(data.seq || 0);
                if (seq > 0 && seq < lastSeenSeq) {
                    return; // stale poll response
                }
                if (seq > lastSeenSeq) lastSeenSeq = seq;

                const hasItems = !!(data.active && data.cart && (data.cart.items || []).length);
                if (!hasItems) {
                    if (displayMode !== 'idle') {
                        goPromoIdle();
                    }
                    return;
                }

                const key = cartKey(data);
                if (key === lastCartKey && displayMode === 'active') {
                    return; // same cart — no DOM refresh
                }
                lastCartKey = key;
                renderCart(data);
            })
            .catch(() => {});
    }

    loadMarketing();
    setInterval(loadMarketing, 60000);
    setInterval(pollCart, 800);
    pollCart();
</script>
</body>
</html>
