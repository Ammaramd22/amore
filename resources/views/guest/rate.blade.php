<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate service — {{ $companyName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --amber:#f59e0b; --bg:#0c0a09; --card:#1c1917; --text:#fafaf9; --muted:#a8a29e; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; font-family: 'DM Sans', system-ui, sans-serif;
            background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(245,158,11,.2), transparent 55%), var(--bg);
            color: var(--text); display: grid; place-items: center; padding: 1.25rem;
        }
        .card {
            width: 100%; max-width: 420px; background: linear-gradient(165deg, #292524, var(--card));
            border: 1px solid rgba(255,255,255,.08); border-radius: 22px; padding: 1.75rem 1.35rem; text-align: center;
        }
        .brand { font-size: .75rem; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); font-weight: 800; }
        h1 { font-family: Fraunces, Georgia, serif; font-size: 1.7rem; margin: .55rem 0 .35rem; }
        .meta { color: var(--muted); margin-bottom: 1.35rem; font-size: .95rem; }
        .row { display: grid; grid-template-columns: repeat(5, 1fr); gap: .55rem; }
        button {
            border: 1px solid rgba(255,255,255,.1); background: rgba(0,0,0,.35); border-radius: 16px;
            padding: .85rem .25rem; cursor: pointer; color: var(--text); font: inherit;
        }
        button:active { transform: scale(.96); }
        button .e { font-size: 1.85rem; display: block; line-height: 1; }
        button small { display: block; margin-top: .35rem; font-size: .65rem; color: var(--muted); font-weight: 700; }
        .msg { margin-top: 1rem; font-weight: 700; color: var(--amber); min-height: 1.4rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">{{ $companyName }}</div>
        <h1>How was the service?</h1>
        <div class="meta">
            {{ $order->order_number }}
            @if($order->table) · {{ $order->table->name }}@endif
            @if($order->waiter) · {{ $order->waiter->name }}@endif
        </div>
        <div class="row" id="faces">
            @foreach($emojis as $n => $emoji)
            <button type="button" onclick="submitRate({{ $n }})">
                <span class="e">{{ $emoji }}</span>
                <small>{{ $labels[$n] ?? '' }}</small>
            </button>
            @endforeach
        </div>
        <div class="msg" id="msg"></div>
    </div>
    <script>
        const submitUrl = @json($submitUrl);
        function submitRate(rating) {
            document.getElementById('msg').textContent = 'Sending…';
            fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ rating }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('msg').textContent = data.message || 'Could not save';
                    return;
                }
                document.getElementById('faces').innerHTML = `<div style="grid-column:1/-1;font-size:3rem;">${data.emoji || '🙏'}</div>`;
                document.getElementById('msg').textContent = data.message || 'Thank you!';
            })
            .catch(() => { document.getElementById('msg').textContent = 'Network error'; });
        }
    </script>
</body>
</html>
