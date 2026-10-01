<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Display — ResPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0a09; --cream: #fff7ed; --muted: #a8a29e;
            --amber: #f59e0b; --amber2: #ea580c; --green: #10b981;
        }
        body {
            margin: 0; min-height: 100vh; color: var(--cream); font-family: Outfit, system-ui, sans-serif;
            background: radial-gradient(ellipse 50% 40% at 50% 0%, rgba(245,158,11,0.14), transparent 55%), var(--bg);
        }
        .top {
            padding: 1.25rem 1.5rem; border-bottom: 2px solid var(--amber);
            background: linear-gradient(135deg, #1c1917, #292524);
            display: flex; justify-content: space-between; align-items: center;
        }
        .top h1 { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: 0.02em; }
        .clock { font-variant-numeric: tabular-nums; font-size: 1.35rem; font-weight: 700; color: #fbbf24; }
        .wrap { padding: 1.5rem; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; min-height: calc(100vh - 100px); }
        .panel {
            border-radius: 20px; padding: 1.25rem;
            background: linear-gradient(165deg, #1c1917, #0c0a09);
            border: 1px solid rgba(255,247,237,0.08);
        }
        .panel h2 {
            margin: 0 0 1rem; font-size: 1.15rem; font-weight: 800;
            display: flex; align-items: center; gap: 0.5rem;
            padding-bottom: 0.75rem; border-bottom: 2px solid;
        }
        .panel.prep h2 { color: #fbbf24; border-color: rgba(245,158,11,0.35); }
        .panel.ready h2 { color: #34d399; border-color: rgba(16,185,129,0.35); }
        .nums { display: flex; flex-wrap: wrap; gap: 0.85rem; justify-content: center; align-content: flex-start; }
        .num {
            min-width: 120px; padding: 1.1rem 1.25rem; border-radius: 16px;
            font-size: 2.4rem; font-weight: 800; text-align: center;
            letter-spacing: 0.04em; font-variant-numeric: tabular-nums;
            background: #292524; border: 1px solid rgba(255,247,237,0.08);
        }
        .panel.prep .num { color: #fbbf24; border-color: rgba(245,158,11,0.25); }
        .panel.ready .num {
            color: #34d399; border-color: rgba(16,185,129,0.3);
            animation: pulse 1.6s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.35); }
            50% { box-shadow: 0 0 0 12px rgba(16,185,129,0); }
        }
        .empty { color: var(--muted); padding: 2.5rem 1rem; text-align: center; width: 100%; }
        @media (max-width: 768px) {
            .cols { grid-template-columns: 1fr; }
            .num { font-size: 1.8rem; min-width: 90px; }
        }
    </style>
    @include('partials.business-clock')
</head>
<body>
<header class="top">
    <h1><i class="fas fa-utensils me-2" style="color:#f59e0b"></i>Order Status</h1>
    <div class="clock" id="clock"></div>
</header>
<div class="wrap">
    <div class="cols">
        <section class="panel prep">
            <h2><i class="fas fa-fire"></i> Preparing</h2>
            <div id="preparingList" class="nums"></div>
        </section>
        <section class="panel ready">
            <h2><i class="fas fa-bell"></i> Ready</h2>
            <div id="readyList" class="nums"></div>
        </section>
    </div>
</div>
<script>
function tick() {
    document.getElementById('clock').textContent = (window.BusinessClock ? BusinessClock.formatTime(true) : new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}));
}
function loadOrders() {
    fetch('{{ route('display.orders') }}')
        .then(r => r.json())
        .then(data => {
            document.getElementById('preparingList').innerHTML = (data.preparing || []).length
                ? data.preparing.map(o => `<div class="num">${o.order_number}</div>`).join('')
                : '<div class="empty">No orders preparing</div>';
            document.getElementById('readyList').innerHTML = (data.ready || []).length
                ? data.ready.map(o => `<div class="num">${o.order_number}</div>`).join('')
                : '<div class="empty">Waiting for ready orders</div>';
        });
}
tick();
setInterval(tick, 1000);
setInterval(loadOrders, 10000);
loadOrders();
</script>
</body>
</html>
