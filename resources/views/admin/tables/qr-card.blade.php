<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $table->name }} — QR Card</title>
    <style>
        @page { size: A6; margin: 10mm; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            color: #1c1917;
            margin: 0;
            background: #f5f5f4;
        }
        .card {
            max-width: 360px;
            margin: 16px auto;
            background: #fff;
            border: 2px solid #f59e0b;
            border-radius: 18px;
            padding: 22px 18px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
        .brand { font-size: .8rem; letter-spacing: .14em; text-transform: uppercase; color: #a8a29e; font-weight: 700; }
        h1 { margin: .4rem 0 .15rem; font-size: 1.7rem; }
        .table { color: #d97706; font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; }
        img.qr { width: 200px; height: 200px; border-radius: 12px; border: 1px solid #e7e5e4; }
        .code-label { margin-top: 1rem; font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; color: #78716c; }
        .code {
            font-size: 2.6rem; font-weight: 800; letter-spacing: .28em;
            margin: .25rem 0 .75rem; color: #1c1917;
        }
        .hint { font-size: .85rem; color: #57534e; line-height: 1.45; }
        .link {
            margin-top: .75rem; font-size: .72rem; word-break: break-all;
            color: #78716c; background: #fafaf9; border-radius: 8px; padding: .55rem;
        }
        .actions { text-align: center; margin: 16px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .actions button, .actions a {
            background: #f59e0b; color: #1c1917; border: 0; border-radius: 10px;
            padding: 10px 16px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: .9rem;
        }
        .actions .ghost { background: #1c1917; color: #fff; }
        .send-box {
            max-width: 360px; margin: 0 auto 20px; background: #fff; border-radius: 14px;
            padding: 14px; border: 1px solid #e7e5e4;
        }
        .send-box label { display: block; font-size: .8rem; font-weight: 700; margin-bottom: .35rem; }
        .send-box input, .send-box select {
            width: 100%; padding: .55rem .7rem; border-radius: 8px; border: 1px solid #d6d3d1; margin-bottom: .65rem;
        }
        @media print {
            .actions, .send-box { display: none !important; }
            body { background: #fff; }
            .card { box-shadow: none; margin: 0; }
            .link { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Print table card</button>
        <button type="button" class="ghost" onclick="copyLink()">Copy menu link</button>
        <a class="ghost" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Share WhatsApp</a>
    </div>

    <div class="send-box">
        <form method="POST" action="{{ route('tables.send-qr', $table) }}">
            @csrf
            <label>Send QR menu link</label>
            <input type="text" name="phone" placeholder="07X XXX XXXX" required>
            <select name="channel">
                <option value="whatsapp">WhatsApp (API)</option>
                <option value="sms">SMS</option>
                <option value="both">WhatsApp + SMS</option>
            </select>
            <button type="submit" style="width:100%;background:#16a34a;color:#fff;border:0;border-radius:10px;padding:10px;font-weight:700;cursor:pointer;">Send link</button>
        </form>
        @if(session('success'))
            <p style="color:#16a34a;font-weight:700;margin:.65rem 0 0;">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p style="color:#dc2626;font-weight:700;margin:.65rem 0 0;">{{ session('error') }}</p>
        @endif
    </div>

    <div class="card">
        <div class="brand">{{ $companyName }}</div>
        <h1>Scan to order</h1>
        <div class="table">{{ $table->name }}@if($table->floor) · {{ $table->floor->name }}@endif</div>
        <img class="qr" src="{{ $qrImage }}" alt="QR">
        <div class="code-label">Table code</div>
        <div class="code">{{ $table->qr_code }}</div>
        <p class="hint">Scan the QR, then enter this code to unlock the menu.</p>
        <div class="link" id="menuLink">{{ $menuUrl }}</div>
    </div>
    <script>
        function copyLink() {
            const text = document.getElementById('menuLink').textContent.trim();
            navigator.clipboard.writeText(text).then(() => alert('Menu link copied'));
        }
    </script>
</body>
</html>
