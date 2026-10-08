<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="status-board-ver" content="big-anim-3">
    <title>Order Status Board — {{ $companyName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700;800&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0a09;
            --panel: #1c1917;
            --amber: #f59e0b;
            --amber-hot: #fb923c;
            --ready: #22c55e;
            --ready-glow: #4ade80;
            --muted: #a8a29e;
            --text: #fafaf9;
            --dine: #16a34a;
            --take: #d97706;
            --del: #0ea5e9;
            --ui: 1.35;
            --card-min: 15.5rem;
            --num-size: clamp(3.6rem, 6.5vmin + 1.4rem, 7.5rem);
            --pad: clamp(0.9rem, 1.7vmin, 1.45rem);
            --gap: clamp(0.9rem, 1.6vmin, 1.4rem);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', system-ui, sans-serif;
            overflow: hidden;
        }

        .board {
            display: grid;
            grid-template-rows: auto 1fr auto;
            height: 100vh;
            height: 100dvh;
            background:
                radial-gradient(ellipse 80% 50% at 20% 0%, rgba(245,158,11,.12), transparent 55%),
                radial-gradient(ellipse 70% 45% at 85% 10%, rgba(34,197,94,.1), transparent 50%),
                var(--bg);
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: calc(0.5rem * var(--ui)) calc(1rem * var(--ui));
            background: rgba(28,25,23,.95);
            border-bottom: 3px solid var(--amber);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: calc(0.65rem * var(--ui));
            font-weight: 800;
            font-size: calc(1.45rem * var(--ui));
            letter-spacing: .04em;
        }
        .brand img {
            width: calc(3.1rem * var(--ui));
            height: calc(3.1rem * var(--ui));
            object-fit: contain; border-radius: 12px;
            background: #fff;
        }
        .brand-mark {
            width: calc(3.1rem * var(--ui));
            height: calc(3.1rem * var(--ui));
            border-radius: 12px;
            background: linear-gradient(135deg, var(--amber), #ea580c);
            display: grid; place-items: center;
            font-weight: 800;
            font-size: calc(1.45rem * var(--ui));
            color: #1c1917;
        }
        .topbar-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: calc(1.85rem * var(--ui));
            letter-spacing: .12em;
            color: var(--amber);
            line-height: 1;
        }
        .topbar-right {
            display: flex; align-items: center; gap: calc(0.65rem * var(--ui));
        }
        .live {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: calc(0.72rem * var(--ui));
            font-weight: 800; letter-spacing: .1em;
            color: var(--ready);
        }
        .live::before {
            content: '';
            width: calc(0.55rem * var(--ui));
            height: calc(0.55rem * var(--ui));
            border-radius: 50%;
            background: var(--ready);
            box-shadow: 0 0 0 0 rgba(34,197,94,.6);
            animation: livePulse 1.6s infinite;
        }
        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(34,197,94,.55); }
            70% { box-shadow: 0 0 0 12px rgba(34,197,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
        }
        .clock {
            font-family: 'Bebas Neue', sans-serif;
            font-size: calc(1.9rem * var(--ui));
            letter-spacing: .06em;
        }
        .btn-fs {
            border: 1px solid rgba(245,158,11,.45);
            background: rgba(245,158,11,.12);
            color: var(--amber);
            border-radius: 8px;
            padding: calc(0.28rem * var(--ui)) calc(0.6rem * var(--ui));
            font: inherit; font-weight: 700;
            font-size: calc(0.72rem * var(--ui));
            cursor: pointer;
        }

        .split {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: var(--gap);
            padding: var(--pad) calc(0.75rem * var(--ui));
            min-height: 0;
            height: 100%;
        }

        .col {
            display: flex;
            flex-direction: column;
            min-height: 0;
            border-radius: calc(0.85rem * var(--ui));
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(28,25,23,.72);
            overflow: hidden;
        }
        .col-prep { box-shadow: inset 0 0 0 1px rgba(245,158,11,.12); }
        .col-ready { box-shadow: inset 0 0 0 1px rgba(34,197,94,.12); }

        .col-head {
            display: flex;
            align-items: center;
            gap: calc(0.65rem * var(--ui));
            padding: calc(0.55rem * var(--ui)) calc(0.85rem * var(--ui));
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .col-prep .col-head {
            background: linear-gradient(90deg, rgba(245,158,11,.18), transparent);
        }
        .col-ready .col-head {
            background: linear-gradient(90deg, rgba(34,197,94,.18), transparent);
        }
        .col-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.6rem, 4.8vmin, 3.8rem);
            letter-spacing: .1em;
            line-height: 1;
        }
        .col-prep .col-title { color: var(--amber); }
        .col-ready .col-title { color: var(--ready-glow); }
        .col-sub {
            font-size: calc(1rem * var(--ui));
            color: var(--muted);
            margin-top: .15rem;
            font-weight: 700;
        }
        .count {
            margin-left: auto;
            min-width: calc(3rem * var(--ui));
            text-align: center;
            font-family: 'Bebas Neue', sans-serif;
            font-size: calc(2.45rem * var(--ui));
            letter-spacing: .04em;
            padding: .15rem .7rem;
            border-radius: 12px;
            background: rgba(0,0,0,.35);
        }
        .col-prep .count { color: var(--amber); }
        .col-ready .count { color: var(--ready-glow); }

        .hero-anim {
            display: flex;
            align-items: center;
            gap: calc(0.45rem * var(--ui));
            margin-left: .25rem;
            flex-shrink: 0;
        }
        .hero-label {
            font-weight: 800;
            font-size: calc(1.05rem * var(--ui));
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            opacity: .9;
        }
        .col-prep .hero-label { color: var(--amber-hot); animation: cookBlink 1.2s ease-in-out infinite; }
        .col-ready .hero-label { color: var(--ready-glow); animation: cookBlink 1.3s ease-in-out infinite; }
        @keyframes cookBlink { 0%,100%{opacity:1} 50%{opacity:.55} }
        .kottu-scene {
            position: relative;
            width: clamp(5.8rem, 10vmin, 8.5rem);
            height: clamp(4.4rem, 7.5vmin, 6.4rem);
            flex-shrink: 0;
            overflow: visible;
        }
        .flame {
            position: absolute;
            bottom: 28%;
            left: 50%;
            width: 28%; height: 42%;
            margin-left: -14%;
            background: radial-gradient(circle at 50% 80%, #fbbf24, #ef4444 55%, transparent 70%);
            border-radius: 50% 50% 40% 40%;
            animation: flameFlicker .35s ease-in-out infinite alternate;
            filter: blur(.3px);
            z-index: 1;
        }
        .flame.f2 { left: 36%; width: 18%; height: 32%; animation-delay: .1s; opacity: .8; }
        .flame.f3 { left: 64%; width: 15%; height: 28%; animation-delay: .2s; opacity: .7; }
        @keyframes flameFlicker {
            from { transform: scaleY(1) scaleX(.95); opacity: .85; }
            to { transform: scaleY(1.15) scaleX(1.05); opacity: 1; }
        }
        .wok {
            position: absolute;
            bottom: 14%;
            left: 50%;
            width: 62%; height: 28%;
            margin-left: -31%;
            background: linear-gradient(180deg, #57534e, #1c1917);
            border-radius: 0 0 20px 20px;
            border: 1px solid #78716c;
            z-index: 2;
            transform-origin: 50% 80%;
            animation: wokToss 1.4s ease-in-out infinite;
        }
        .wok::before {
            content: '';
            position: absolute;
            top: -25%; left: -8%; right: -8%;
            height: 45%;
            background: #44403c;
            border-radius: 50%;
            border: 1px solid #a8a29e;
        }
        .wok-handle {
            position: absolute;
            right: -48%; top: 18%;
            width: 52%; height: 22%;
            background: linear-gradient(90deg, #78716c, #a8a29e);
            border-radius: 2px;
            transform: rotate(-8deg);
        }
        .food {
            position: absolute;
            bottom: 36%;
            left: 50%;
            width: 14%; height: 12%;
            margin-left: -7%;
            background: #ea580c;
            border-radius: 2px;
            z-index: 3;
            animation: foodToss 1.4s ease-in-out infinite;
        }
        .food.f2 { background: #fbbf24; margin-left: 10%; animation-delay: .08s; }
        .food.f3 { background: #22c55e; margin-left: -22%; animation-delay: .12s; width: 10%; }
        @keyframes wokToss {
            0%, 100% { transform: rotate(-6deg) translateY(0); }
            40% { transform: rotate(10deg) translateY(-3px); }
            55% { transform: rotate(-4deg) translateY(-1px); }
        }
        @keyframes foodToss {
            0%, 100% { transform: translateY(0) rotate(0); opacity: 1; }
            35% { transform: translateY(-20px) rotate(32deg); opacity: 1; }
            55% { transform: translateY(-6px) rotate(-14deg); }
        }
        .steam {
            position: absolute;
            top: 0;
            left: 50%;
            width: 10%; height: 32%;
            margin-left: -5%;
            background: linear-gradient(transparent, rgba(255,255,255,.45));
            border-radius: 50%;
            animation: steamUp 1.8s ease-out infinite;
            z-index: 4;
        }
        .steam.s2 { margin-left: -18%; animation-delay: .4s; }
        .steam.s3 { margin-left: 10%; animation-delay: .8s; }
        @keyframes steamUp {
            0% { transform: translateY(4px) scaleX(1); opacity: 0; }
            30% { opacity: .7; }
            100% { transform: translateY(-14px) scaleX(1.6); opacity: 0; }
        }

        .bike-scene {
            position: relative;
            width: clamp(7.5rem, 13vmin, 11rem);
            height: clamp(4.5rem, 7.5vmin, 6.5rem);
            overflow: hidden;
            flex-shrink: 0;
            border-radius: 14px;
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(74,222,128,.3);
        }
        .road {
            position: absolute;
            bottom: 18%; left: 0; right: 0;
            height: 3px;
            background: repeating-linear-gradient(90deg, #a8a29e 0 14px, transparent 14px 24px);
            animation: roadMove .4s linear infinite;
            opacity: .9;
        }
        @keyframes roadMove {
            from { background-position: 0 0; }
            to { background-position: -24px 0; }
        }
        .bike {
            position: absolute;
            bottom: 22%;
            left: 0;
            font-size: clamp(2rem, 3.5vmin, 2.9rem);
            color: var(--ready-glow);
            filter: drop-shadow(0 0 14px rgba(74,222,128,.7));
            animation: bikeRide 2.6s linear infinite;
            will-change: transform;
        }
        @keyframes bikeRide {
            0%   { transform: translateX(-130%) translateY(0); }
            20%  { transform: translateX(15%) translateY(-4px); }
            40%  { transform: translateX(40%) translateY(0); }
            60%  { transform: translateX(65%) translateY(-4px); }
            100% { transform: translateX(170%) translateY(0); }
        }
        .ready-burst {
            position: absolute;
            top: 8%; right: 8%;
            font-size: clamp(0.75rem, 1.4vmin, 1rem);
            font-weight: 800;
            letter-spacing: .06em;
            color: #052e16;
            background: var(--ready);
            padding: .25rem .5rem;
            border-radius: 999px;
            z-index: 2;
            animation: burstPop 1.1s ease-in-out infinite;
        }
        @keyframes burstPop {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.12); box-shadow: 0 0 14px rgba(34,197,94,.55); }
        }

        .orders {
            flex: 1;
            overflow-y: auto;
            padding: var(--pad);
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(var(--card-min), 1fr));
            gap: var(--gap);
            align-content: start;
            min-height: 0;
        }
        .orders > .empty { grid-column: 1 / -1; }
        @media (max-width: 700px) {
            .split { grid-template-columns: 1fr; }
            .orders { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .orders::-webkit-scrollbar { width: 8px; }
        .orders::-webkit-scrollbar-thumb { background: #44403c; border-radius: 8px; }

        .ticket {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.7rem;
            padding: 1.45rem 1.1rem 1.3rem;
            min-height: 14rem;
            border-radius: 1.4rem;
            background:
                linear-gradient(165deg, rgba(255,255,255,.06), transparent 42%),
                rgba(12, 10, 9, .88);
            border: 2px solid rgba(255,255,255,.1);
            box-shadow: 0 10px 28px rgba(0,0,0,.35);
            animation: ticketIn .35s ease;
            position: relative;
            overflow: hidden;
        }
        .ticket::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
        }
        .col-prep .ticket {
            border-color: rgba(245,158,11,.45);
            box-shadow: 0 10px 28px rgba(0,0,0,.35), 0 0 0 1px rgba(245,158,11,.12);
        }
        .col-prep .ticket::before {
            background: linear-gradient(90deg, var(--amber), var(--amber-hot));
        }
        .col-ready .ticket {
            border-color: rgba(34,197,94,.55);
            box-shadow: 0 10px 28px rgba(0,0,0,.35), 0 0 24px rgba(34,197,94,.18);
            animation: ticketIn .35s ease, readyGlow 2s ease-in-out infinite;
        }
        .col-ready .ticket::before {
            background: linear-gradient(90deg, var(--ready), var(--ready-glow));
        }
        @keyframes ticketIn {
            from { opacity: 0; transform: translateY(8px) scale(.96); }
            to { opacity: 1; transform: none; }
        }
        @keyframes readyGlow {
            0%, 100% { box-shadow: 0 10px 28px rgba(0,0,0,.35), 0 0 0 1px rgba(34,197,94,.2); }
            50% { box-shadow: 0 10px 28px rgba(0,0,0,.35), 0 0 28px 2px rgba(34,197,94,.35); }
        }

        .mini-anim {
            width: 3.6rem;
            height: 3.6rem;
            border-radius: 1rem;
            display: grid; place-items: center;
            font-size: 1.65rem;
            flex-shrink: 0;
        }
        .col-prep .mini-anim {
            background: rgba(245,158,11,.18);
            color: var(--amber);
            animation: pulseIcon 1.5s ease-in-out infinite;
        }
        .col-ready .mini-anim {
            background: rgba(34,197,94,.18);
            color: var(--ready-glow);
            animation: pulseIcon 1.2s ease-in-out infinite;
        }
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .ticket-body {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            min-width: 0;
        }
        .kot-num {
            font-family: 'Bebas Neue', sans-serif;
            font-size: var(--num-size);
            letter-spacing: .08em;
            color: #fffbeb;
            line-height: .9;
            text-align: center;
            text-shadow: 0 3px 20px rgba(245,158,11,.35);
            word-break: break-all;
        }
        .col-ready .kot-num {
            color: #ecfdf5;
            text-shadow: 0 3px 20px rgba(34,197,94,.35);
        }
        .inv-num { display: none; }
        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: clamp(1rem, 1.7vmin + 0.5rem, 1.35rem);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #fff;
        }
        .pill.type-dine_in { background: var(--dine); }
        .pill.type-takeaway { background: var(--take); color: #1c1917; }
        .pill.type-delivery { background: var(--del); }
        .pill.type-express { background: #a855f7; }
        .pill.table { background: rgba(255,255,255,.14); color: #fafaf9; font-weight: 800; }

        .status-tag {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.35rem, 2.4vmin + 0.55rem, 2.1rem);
            letter-spacing: .12em;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            white-space: nowrap;
            margin-top: 0.15rem;
        }
        .status-tag.prep {
            color: var(--amber);
            background: rgba(245,158,11,.16);
            border: 1px solid rgba(245,158,11,.4);
        }
        .status-tag.rdy {
            color: #052e16;
            background: var(--ready);
            border: 1px solid var(--ready-glow);
            animation: burstPop 1.4s ease-in-out infinite;
        }

        .empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #78716c;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.1rem;
            min-height: 16rem;
        }
        .empty .bike-scene {
            width: clamp(11rem, 20vmin, 17rem);
            height: clamp(5.5rem, 10vmin, 8rem);
        }
        .empty .kottu-scene {
            width: clamp(8rem, 14vmin, 11rem);
            height: clamp(5.5rem, 10vmin, 8rem);
        }
        .empty p {
            font-size: clamp(1.25rem, 2.4vmin, 1.8rem);
            font-weight: 700;
        }

        .footer {
            text-align: center;
            padding: calc(0.55rem * var(--ui)) 1rem;
            font-size: calc(1rem * var(--ui));
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #e7e5e4;
            border-top: 3px solid #f59e0b;
            background: #1c1917;
        }
        .footer span { color: #f59e0b; }

        /* Extra-large wall TVs — bigger cards + numbers */
        @media (min-width: 1400px) {
            :root {
                --card-min: 17rem;
                --num-size: clamp(4.2rem, 7vmin + 1.5rem, 8.5rem);
            }
            .ticket { min-height: 15.5rem; }
        }
        @media (min-width: 1800px) {
            :root {
                --card-min: 18.5rem;
                --num-size: clamp(4.8rem, 7.5vmin + 1.6rem, 9.5rem);
            }
            .ticket { min-height: 17rem; }
        }

        @media (max-width: 900px) {
            .split { grid-template-columns: 1fr; overflow-y: auto; }
            body { overflow: auto; }
            .board { height: auto; min-height: 100vh; }
            :root { --card-min: 10.5rem; }
        }
    </style>
    @include('partials.business-clock')
</head>
<body>
@php
    $brand = $companyName ?: 'ResPOS';
    $phone = $companyPhone ?? '';
@endphp

<div class="board">
    <header class="topbar">
        <div class="brand">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $brand }}">
            @else
                <div class="brand-mark">{{ strtoupper(substr($brand, 0, 1)) }}</div>
            @endif
            <div>
                <div>{{ $brand }}</div>
                <div class="topbar-title">ORDER STATUS</div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="live">LIVE</span>
            <span class="clock" id="clock">--:--</span>
            <button type="button" class="btn-fs" onclick="toggleFs()"><i class="fas fa-expand me-1"></i>Full Screen</button>
        </div>
    </header>

    <div class="split">
        <section class="col col-prep">
            <div class="col-head">
                <div>
                    <div class="col-title">PREPARING</div>
                    <div class="col-sub">Kitchen</div>
                </div>
                <div class="hero-anim" aria-hidden="true">
                    <div class="kottu-scene">
                        <div class="steam"></div>
                        <div class="steam s2"></div>
                        <div class="steam s3"></div>
                        <div class="flame"></div>
                        <div class="flame f2"></div>
                        <div class="flame f3"></div>
                        <div class="food"></div>
                        <div class="food f2"></div>
                        <div class="food f3"></div>
                        <div class="wok"><span class="wok-handle"></span></div>
                    </div>
                    <div class="hero-label">Cooking…</div>
                </div>
                <div class="count" id="preparingCount">0</div>
            </div>
            <div class="orders" id="preparingOrders"></div>
        </section>

        <section class="col col-ready">
            <div class="col-head">
                <div>
                    <div class="col-title">READY</div>
                    <div class="col-sub">Collect your order</div>
                </div>
                <div class="hero-anim" aria-hidden="true">
                    <div class="bike-scene">
                        <div class="road"></div>
                        <div class="bike"><i class="fas fa-motorcycle"></i></div>
                        <div class="ready-burst">GO</div>
                    </div>
                    <div class="hero-label">Ready!</div>
                </div>
                <div class="count" id="readyCount">0</div>
            </div>
            <div class="orders" id="readyOrders"></div>
        </section>
    </div>

    <div class="footer">QRPOS By Avenque (Pvt) Ltd | 076 822 2201</div>
</div>

<script>
    const TYPE_LABELS = {
        dine_in: 'Dine In',
        takeaway: 'Takeaway',
        delivery: 'Delivery',
        express: 'Express',
    };

    function updateClock() {
        document.getElementById('clock').textContent = (window.BusinessClock ? BusinessClock.formatTime(true) : new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
    }
    setInterval(updateClock, 1000);
    updateClock();

    function toggleFs() {
        if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
        else document.exitFullscreen?.();
    }

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function typeKey(o) {
        return String(o.type_raw || o.type || 'dine_in').toLowerCase().replace(/\s+/g, '_');
    }

    function typeLabel(o) {
        const k = typeKey(o);
        return TYPE_LABELS[k] || o.type || 'Dine In';
    }

    function formatTable(name) {
        if (!name) return '';
        let n = String(name).trim();
        while (/^table\s+/i.test(n)) n = n.replace(/^table\s+/i, '').trim();
        return n ? `Table ${n}` : '';
    }

    function ticketHtml(o, mode) {
        const key = typeKey(o);
        const table = key === 'dine_in' ? formatTable(o.table) : '';
        const num = o.display_number || o.order_number || '—';

        return `
            <article class="ticket">
                <div class="mini-anim">
                    <i class="fas ${mode === 'prep' ? 'fa-fire' : 'fa-bell'}"></i>
                </div>
                <div class="ticket-body">
                    <div class="kot-num">${esc(num)}</div>
                    <div class="meta-row">
                        <span class="pill type-${esc(key)}">${esc(typeLabel(o))}</span>
                        ${table ? `<span class="pill table"><i class="fas fa-chair"></i> ${esc(table)}</span>` : ''}
                    </div>
                </div>
                <div class="status-tag ${mode === 'prep' ? 'prep' : 'rdy'}">
                    ${mode === 'prep' ? 'PREP' : 'READY'}
                </div>
            </article>`;
    }

    function emptyHtml(mode, text) {
        if (mode === 'prep') {
            return `<div class="empty">
                <div class="kottu-scene" aria-hidden="true">
                    <div class="steam"></div><div class="steam s2"></div><div class="steam s3"></div>
                    <div class="flame"></div><div class="flame f2"></div><div class="flame f3"></div>
                    <div class="food"></div><div class="food f2"></div><div class="food f3"></div>
                    <div class="wok"><span class="wok-handle"></span></div>
                </div>
                <p>${text}</p>
            </div>`;
        }
        return `<div class="empty">
            <div class="bike-scene" aria-hidden="true">
                <div class="road"></div>
                <div class="bike"><i class="fas fa-motorcycle"></i></div>
                <div class="ready-burst">GO</div>
            </div>
            <p>${text}</p>
        </div>`;
    }

    function loadOrders() {
        fetch('/customer-display/orders', { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(data => {
                const preparing = data.preparing || [];
                const ready = data.ready || [];

                document.getElementById('preparingCount').textContent = preparing.length;
                document.getElementById('readyCount').textContent = ready.length;

                const prepEl = document.getElementById('preparingOrders');
                prepEl.innerHTML = preparing.length
                    ? preparing.map(o => ticketHtml(o, 'prep')).join('')
                    : emptyHtml('prep', 'No orders cooking right now');

                const readyEl = document.getElementById('readyOrders');
                readyEl.innerHTML = ready.length
                    ? ready.map(o => ticketHtml(o, 'rdy')).join('')
                    : emptyHtml('ready', 'No orders ready yet');
            })
            .catch(err => console.error('Order status load failed', err));
    }

    loadOrders();
    setInterval(loadOrders, 3000);
</script>
</body>
</html>
