<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KDS — ResPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0a09; --card: #1c1917; --line: rgba(255,247,237,0.1);
            --amber: #f59e0b; --amber2: #ea580c; --cream: #fff7ed; --muted: #a8a29e;
        }
        body {
            margin: 0; min-height: 100vh; color: var(--cream); font-family: Outfit, system-ui, sans-serif;
            background: radial-gradient(ellipse 55% 40% at 0% 0%, rgba(245,158,11,0.16), transparent 50%), var(--bg);
        }
        .top {
            display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
            padding: 1rem 1.25rem; border-bottom: 2px solid var(--amber);
            background: linear-gradient(135deg, #1c1917, #292524);
        }
        .brand { display: flex; align-items: center; gap: 0.7rem; }
        .mark {
            width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center;
            background: linear-gradient(135deg, var(--amber), var(--amber2)); color: #fff;
        }
        h1 { margin: 0; font-size: 1.25rem; font-weight: 800; }
        .sub { margin: 0; font-size: 0.78rem; color: var(--muted); }
        .link-btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.55rem 0.9rem; border-radius: 999px; text-decoration: none !important;
            background: rgba(245,158,11,0.15); color: #fbbf24 !important; font-weight: 700; font-size: 0.85rem;
            border: 1px solid rgba(245,158,11,0.3);
        }
        .wrap { padding: 1.15rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.9rem; }
        .card {
            background: linear-gradient(165deg, #292524, #1c1917);
            border: 1px solid var(--line); border-radius: 16px; padding: 1rem;
            border-left: 5px solid var(--amber);
        }
        .card.status-pending { border-left-color: #f87171; }
        .card.status-preparing { border-left-color: var(--amber); }
        .card.status-ready { border-left-color: #10b981; }
        .head { display: flex; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.55rem; }
        .kot { font-weight: 800; font-size: 1.15rem; color: var(--amber); }
        .timer { font-variant-numeric: tabular-nums; font-weight: 800; color: #fb7185; }
        .meta { color: var(--muted); font-size: 0.85rem; margin-bottom: 0.65rem; }
        .pill {
            display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px;
            background: rgba(245,158,11,0.15); color: #fbbf24; font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase;
        }
        ul { list-style: none; padding: 0; margin: 0 0 0.75rem; }
        li {
            display: flex; justify-content: space-between; gap: 0.5rem;
            padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-weight: 650;
        }
        li:last-child { border-bottom: 0; }
        .note { display: block; font-size: 0.75rem; color: #fbbf24; font-weight: 500; }
        .btn-row { display: flex; gap: 0.45rem; }
        .act {
            flex: 1; border: 0; border-radius: 12px; padding: 0.7rem; font: inherit; font-weight: 800;
            min-height: 46px; cursor: pointer;
        }
        .act.warn { background: linear-gradient(135deg, #fbbf24, var(--amber)); color: #1c1917; }
        .act.ok { background: linear-gradient(135deg, #34d399, #059669); color: #fff; }
        .act.done { background: linear-gradient(135deg, var(--amber), var(--amber2)); color: #fff; }
        .empty { text-align: center; color: var(--muted); padding: 3rem 1rem; grid-column: 1 / -1; }
    </style>
</head>
<body>
    <header class="top">
        <div class="brand">
            <div class="mark"><i class="fas fa-fire"></i></div>
            <div>
                <h1>Kitchen Display System</h1>
                <p class="sub">Touch tickets · Start → Ready → Served</p>
            </div>
        </div>
        <a class="link-btn" href="{{ route('kitchen.display') }}" target="_blank">
            <i class="fas fa-expand"></i> Full kitchen screen
        </a>
    </header>

    <div class="wrap">
        <div id="ordersContainer" class="grid"></div>
    </div>

<script>
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function loadOrders() {
    fetch('{{ route('kds.orders') }}')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('ordersContainer');
            if (!data.length) {
                container.innerHTML = '<div class="empty"><i class="fas fa-utensils fa-2x mb-3 d-block opacity-50"></i><h3>No orders</h3></div>';
                return;
            }
            container.innerHTML = data.map(o => `
                <div class="card status-${esc(o.status)}">
                    <div class="head">
                        <strong class="kot">${esc(o.order_number || o.kot_number)}</strong>
                        <span class="timer">${formatTime(o.elapsed_seconds)}</span>
                    </div>
                    <div class="meta">${esc(o.kot_number)} · ${esc(o.table)} <span class="pill">${esc((o.order_type || '').replace('_',' '))}</span></div>
                    <ul>
                        ${(o.items || []).map(i => `
                            <li>
                                <span>${esc(i.quantity)}× ${esc(i.name)}${i.instructions ? `<span class="note">${esc(i.instructions)}</span>` : ''}</span>
                            </li>
                        `).join('')}
                    </ul>
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
    fetch('{{ route('kds.update-status') }}', {
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
