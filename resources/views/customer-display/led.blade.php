<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $companyName }} — LED Display</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --led-bg: #050806;
            --led-panel: #0a120c;
            --led-bezel: #1a1f1c;
            --led-green: #39ff14;
            --led-green-dim: rgba(57, 255, 20, 0.18);
            --led-amber: #ffb000;
            --led-red: #ff3b3b;
            --currency: {{ json_encode($currency ?? 'LKR') }};
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: #000;
            color: var(--led-green);
            font-family: 'Share Tech Mono', ui-monospace, monospace;
            overflow: hidden;
            user-select: none;
        }

        body {
            display: grid;
            place-items: center;
            padding: 1.5vh 1.5vw;
            background:
                radial-gradient(ellipse at center, #0d1510 0%, #020403 70%),
                #000;
        }

        .pole {
            width: min(1100px, 96vw);
            background: linear-gradient(180deg, #222826, #0d100e 40%, #151a17);
            border-radius: 18px;
            padding: 14px;
            box-shadow:
                0 0 0 2px #2a322e,
                0 20px 50px rgba(0,0,0,0.65),
                inset 0 1px 0 rgba(255,255,255,0.08);
        }

        .pole-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 10px 12px;
            color: #8fa396;
            font-family: Orbitron, sans-serif;
            font-size: clamp(0.7rem, 1.4vw, 0.95rem);
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .pole-top .brand {
            color: #c9d6cc;
            font-weight: 700;
        }

        .pole-screen {
            background: var(--led-bg);
            border-radius: 10px;
            border: 3px solid #050705;
            box-shadow:
                inset 0 0 40px rgba(0,0,0,0.85),
                inset 0 0 80px rgba(57, 255, 20, 0.04);
            padding: clamp(16px, 3vh, 28px) clamp(18px, 3vw, 36px);
            position: relative;
            min-height: min(72vh, 520px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: clamp(10px, 2.2vh, 22px);
        }

        .pole-screen::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 2px,
                    rgba(0,0,0,0.12) 2px,
                    rgba(0,0,0,0.12) 3px
                );
            pointer-events: none;
            border-radius: 8px;
            opacity: 0.45;
        }

        .led-row {
            display: grid;
            grid-template-columns: minmax(90px, 1.1fr) minmax(140px, 1.6fr);
            align-items: baseline;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .led-label {
            font-family: Orbitron, sans-serif;
            font-size: clamp(0.85rem, 2.2vw, 1.35rem);
            letter-spacing: 0.18em;
            color: var(--led-green);
            text-shadow: 0 0 8px rgba(57, 255, 20, 0.55);
            opacity: 0.9;
        }

        .led-value {
            text-align: right;
            font-size: clamp(1.8rem, 6.5vw, 4.2rem);
            line-height: 1;
            letter-spacing: 0.04em;
            color: var(--led-green);
            text-shadow:
                0 0 6px rgba(57, 255, 20, 0.85),
                0 0 18px rgba(57, 255, 20, 0.35);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .led-value.amber {
            color: var(--led-amber);
            text-shadow:
                0 0 6px rgba(255, 176, 0, 0.85),
                0 0 18px rgba(255, 176, 0, 0.35);
        }

        .led-value.red {
            color: var(--led-red);
            text-shadow:
                0 0 6px rgba(255, 59, 59, 0.85),
                0 0 18px rgba(255, 59, 59, 0.3);
        }

        .led-item {
            position: relative;
            z-index: 1;
            min-height: 1.4em;
            font-size: clamp(0.95rem, 2.4vw, 1.45rem);
            letter-spacing: 0.08em;
            color: rgba(57, 255, 20, 0.72);
            text-shadow: 0 0 6px rgba(57, 255, 20, 0.35);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        .led-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(57,255,20,0.35), transparent);
            position: relative;
            z-index: 1;
            margin: 4px 0;
        }

        .idle-msg {
            position: relative;
            z-index: 1;
            text-align: center;
            display: none;
            flex-direction: column;
            gap: 18px;
            padding: 8vh 0;
        }

        .idle-msg.show { display: flex; }
        .active-panel { display: none; position: relative; z-index: 1; }
        .active-panel.show { display: flex; flex-direction: column; gap: clamp(10px, 2.2vh, 22px); }

        .idle-line {
            font-family: Orbitron, sans-serif;
            font-size: clamp(1.4rem, 5vw, 3rem);
            letter-spacing: 0.28em;
            color: var(--led-green);
            text-shadow: 0 0 12px rgba(57, 255, 20, 0.55);
            animation: blinkSoft 2.8s ease-in-out infinite;
        }

        .idle-sub {
            font-size: clamp(0.9rem, 2vw, 1.25rem);
            letter-spacing: 0.2em;
            color: rgba(57, 255, 20, 0.55);
        }

        @keyframes blinkSoft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.55; }
        }

        .pay-block { display: none; }
        .pay-block.show { display: contents; }

        .toolbar {
            position: fixed;
            top: 10px;
            right: 12px;
            display: flex;
            gap: 8px;
            opacity: 0.35;
            transition: opacity 0.2s;
            z-index: 20;
        }
        .toolbar:hover { opacity: 1; }
        .toolbar button {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #cfe0d4;
            border-radius: 8px;
            padding: 6px 10px;
            cursor: pointer;
            font: inherit;
            font-size: 0.8rem;
        }

        @media (max-width: 640px) {
            .led-row { grid-template-columns: 1fr; gap: 2px; }
            .led-value { text-align: left; font-size: clamp(1.6rem, 10vw, 2.6rem); }
            .led-label { font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="toggleFullscreen()" title="Fullscreen">Fullscreen</button>
    </div>

    <div class="pole" aria-live="polite">
        <div class="pole-top">
            <div class="brand">{{ $companyName }}</div>
            <div id="clock">--:--</div>
        </div>
        <div class="pole-screen">
            <div class="idle-msg show" id="idlePanel">
                <div class="idle-line">WELCOME</div>
                <div class="idle-sub">{{ strtoupper($companyName) }}</div>
            </div>

            <div class="active-panel" id="activePanel">
                <div class="led-item" id="itemName">—</div>
                <div class="led-row">
                    <div class="led-label">PRICE</div>
                    <div class="led-value" id="priceVal">0.00</div>
                </div>
                <div class="led-divider"></div>
                <div class="led-row">
                    <div class="led-label">TOTAL</div>
                    <div class="led-value amber" id="totalVal">0.00</div>
                </div>

                <div class="pay-block" id="payBlock">
                    <div class="led-divider"></div>
                    <div class="led-row">
                        <div class="led-label">TENDER</div>
                        <div class="led-value" id="tenderVal">0.00</div>
                    </div>
                    <div class="led-row">
                        <div class="led-label" id="changeLabel">CHANGE</div>
                        <div class="led-value red" id="changeVal">0.00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currency = @json($currency ?? 'LKR');
        let lastSeenSeq = 0;
        let lastKey = '';
        let thankYouUntil = 0;

        function money(n) {
            const v = Number(n || 0);
            return v.toFixed(2);
        }

        function tickClock() {
            const d = new Date();
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            document.getElementById('clock').textContent = hh + ':' + mm;
        }

        function showIdle(message) {
            document.getElementById('idlePanel').classList.add('show');
            document.getElementById('activePanel').classList.remove('show');
            const line = document.querySelector('#idlePanel .idle-line');
            if (line) line.textContent = message || 'WELCOME';
        }

        function showActive() {
            document.getElementById('idlePanel').classList.remove('show');
            document.getElementById('activePanel').classList.add('show');
        }

        function renderCart(payload) {
            const cart = payload.cart || payload || {};
            const items = cart.items || [];
            const totals = cart.totals || {};
            const payment = cart.payment || {};

            if (!items.length) {
                if (Date.now() < thankYouUntil) {
                    showIdle('THANK YOU');
                } else {
                    showIdle('WELCOME');
                }
                return;
            }

            showActive();

            const last = items[items.length - 1];
            const qty = Number(last.qty || 1);
            const name = String(last.name || '').toUpperCase();
            document.getElementById('itemName').textContent =
                (qty > 1 ? qty + ' x ' : '') + name;

            document.getElementById('priceVal').textContent = money(last.lineTotal ?? last.unitPrice);
            document.getElementById('totalVal').textContent = money(totals.total);

            const paying = Number(payment.paying || 0);
            const change = Number(payment.change || 0);
            const balance = payment.balance != null ? Number(payment.balance) : null;
            const payBlock = document.getElementById('payBlock');

            if (paying > 0 || change > 0 || (balance != null && balance < Number(totals.total))) {
                payBlock.classList.add('show');
                document.getElementById('tenderVal').textContent = money(paying);
                if (change > 0) {
                    document.getElementById('changeLabel').textContent = 'CHANGE';
                    document.getElementById('changeVal').textContent = money(change);
                    document.getElementById('changeVal').classList.add('red');
                    document.getElementById('changeVal').classList.remove('amber');
                } else if (balance != null && balance > 0) {
                    document.getElementById('changeLabel').textContent = 'BALANCE';
                    document.getElementById('changeVal').textContent = money(balance);
                    document.getElementById('changeVal').classList.remove('red');
                    document.getElementById('changeVal').classList.add('amber');
                } else {
                    document.getElementById('changeLabel').textContent = 'CHANGE';
                    document.getElementById('changeVal').textContent = money(0);
                }
            } else {
                payBlock.classList.remove('show');
            }
        }

        function pollCart() {
            fetch('/customer-display/cart')
                .then(r => r.json())
                .then(data => {
                    const seq = Number(data.seq || 0);
                    if (seq > 0 && seq < lastSeenSeq) return;
                    if (seq > lastSeenSeq) lastSeenSeq = seq;

                    const key = JSON.stringify(data.cart || {}) + '|' + (data.active ? '1' : '0') + '|' + (data.cleared ? '1' : '0');
                    if (key === lastKey) return;
                    lastKey = key;

                    if (data.cleared && !data.active) {
                        thankYouUntil = Date.now() + 5000;
                        showIdle('THANK YOU');
                        return;
                    }

                    if (!data.active || !data.cart) {
                        if (Date.now() < thankYouUntil) {
                            showIdle('THANK YOU');
                        } else {
                            showIdle('WELCOME');
                        }
                        return;
                    }

                    thankYouUntil = 0;
                    renderCart(data);
                })
                .catch(() => {});
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        }

        tickClock();
        setInterval(tickClock, 1000);
        setInterval(pollCart, 700);
        pollCart();
    </script>
</body>
</html>
