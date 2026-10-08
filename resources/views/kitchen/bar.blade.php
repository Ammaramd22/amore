<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bar Display — ResPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0a09; --cream: #fff7ed; --muted: #a8a29e;
            --amber: #f59e0b; --amber2: #ea580c;
        }
        body {
            margin: 0; min-height: 100vh; color: var(--cream); font-family: Outfit, system-ui, sans-serif;
            background: radial-gradient(ellipse 50% 35% at 100% 0%, rgba(245,158,11,0.14), transparent 50%), var(--bg);
        }
        .top {
            padding: 1rem 1.25rem; border-bottom: 2px solid var(--amber);
            background: linear-gradient(135deg, #1c1917, #292524);
            display: flex; justify-content: space-between; align-items: center;
        }
        .top h1 { margin: 0; font-size: 1.25rem; font-weight: 800; }
        .wrap { padding: 1.15rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.9rem; }
        .card {
            background: linear-gradient(165deg, #292524, #1c1917);
            border: 1px solid rgba(255,247,237,0.1); border-radius: 16px; padding: 1rem;
            border-left: 5px solid #38bdf8;
        }
        .kot { font-weight: 800; font-size: 1.1rem; color: #7dd3fc; }
        .timer { font-weight: 800; color: #fb7185; font-variant-numeric: tabular-nums; }
        .meta { color: var(--muted); margin: 0.35rem 0 0.65rem; font-size: 0.9rem; }
        ul { list-style: none; padding: 0; margin: 0 0 0.75rem; }
        li { padding: 0.35rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-weight: 650; }
        li:last-child { border-bottom: 0; }
        .btn-row { display: flex; gap: 0.45rem; }
        .act {
            flex: 1; border: 0; border-radius: 12px; padding: 0.65rem; font: inherit; font-weight: 800;
            min-height: 44px; cursor: pointer;
        }
        .act.warn { background: linear-gradient(135deg, #fbbf24, var(--amber)); color: #1c1917; }
        .act.ok { background: linear-gradient(135deg, #34d399, #059669); color: #fff; }
        .act.done { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff; }
        .empty { text-align: center; color: var(--muted); padding: 3rem 1rem; grid-column: 1 / -1; }
    </style>
</head>
<body>
<header class="top">
    <h1><i class="fas fa-glass-martini-alt me-2" style="color:#7dd3fc"></i>Bar Orders</h1>
</header>
<div class="wrap">
    <div id="ordersContainer" class="grid"></div>
</div>
<script>
function loadOrders() {
    fetch('{{ route('bar.orders') }}')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('ordersContainer');
            if (!data.length) {
                container.innerHTML = '<div class="empty"><h3>No pending orders</h3></div>';
                return;
            }
            container.innerHTML = data.map(o => `
                <div class="card">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="kot">${o.order_number || o.kot_number}</span>
                        <span class="timer">${formatTime(o.elapsed_seconds)}</span>
                    </div>
                    <div class="meta">${o.kot_number || ''} · ${o.table || ''}</div>
                    <ul>${(o.items || []).map(i => `<li>${i.quantity}× ${i.name}</li>`).join('')}</ul>
                    <div class="btn-row">
                        ${o.status === 'pending' ? `<button class="act warn" onclick="updateStatus(${o.id}, 'preparing')">Start</button>` : ''}
                        ${o.status === 'preparing' ? `<button class="act ok" onclick="updateStatus(${o.id}, 'ready')">Ready</button>` : ''}
                        ${o.status === 'ready' ? `<button class="act done" onclick="updateStatus(${o.id}, 'served')">Served</button>` : ''}
                    </div>
                </div>
            `).join('');
        });
}
function formatTime(s) {
    const total = Math.max(0, Math.floor(Number(s) || 0));
    const m = Math.floor(total / 60);
    const sec = total % 60;
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
}
function updateStatus(id, status) {
    fetch('{{ route('bar.update-status') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ kot_id: id, status: status })
    }).then(() => loadOrders());
}
setInterval(loadOrders, 8000);
loadOrders();
</script>
</body>
</html>
