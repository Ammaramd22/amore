<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#4A0E1A">
    <title>{{ $business }} · Stamp Card</title>
    <style>
        :root {
            --wine: #4A0E1A;
            --wine-deep: #2d0810;
            --cream: #f8f1e9;
            --gold: #e8c28a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh;
            font-family: "Avenir Next", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, #1a0a0e 0%, #0c0608 100%);
            color: #fff; padding: 20px 14px 36px;
        }
        .wallet {
            max-width: 390px; margin: 0 auto;
            border-radius: 22px; overflow: hidden;
            background: var(--wine);
            box-shadow: 0 28px 70px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.06);
        }
        .brand-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 18px 10px;
        }
        .brand {
            font-size: .78rem; letter-spacing: .18em; text-transform: uppercase;
            font-weight: 700; color: #fff;
        }
        .brand small { display: block; letter-spacing: .08em; opacity: .55; font-size: .65rem; margin-top: 2px; }
        .reward-chip {
            background: rgba(255,255,255,.12); border: 1px solid rgba(232,194,138,.35);
            color: var(--gold); font-size: .72rem; font-weight: 700;
            padding: 6px 10px; border-radius: 999px;
        }
        .hero {
            margin: 0 14px; border-radius: 14px; overflow: hidden;
            height: 110px; position: relative;
            background:
                radial-gradient(circle at 70% 40%, rgba(255,255,255,.18), transparent 45%),
                linear-gradient(120deg, #6b1a2c, #3a0c16 55%, #1f070d);
            display: grid; place-items: center;
            border: 1px solid rgba(255,255,255,.08);
        }
        .hero-title {
            text-align: center; z-index: 1;
        }
        .hero-title strong { display: block; font-size: 1.35rem; letter-spacing: .04em; }
        .hero-title span { font-size: .78rem; opacity: .75; }
        .body { padding: 18px 18px 22px; }
        .who { font-size: 1.05rem; font-weight: 700; }
        .phone { opacity: .65; font-size: .88rem; margin-top: 2px; }
        .meta { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; font-size: .75rem; opacity: .7; }
        .free-banner {
            margin-top: 14px; padding: 12px 14px; border-radius: 12px;
            background: rgba(16,185,129,.15); border: 1px solid rgba(52,211,153,.35);
            color: #6ee7b7; font-weight: 700; text-align: center; font-size: .9rem;
        }
        .expired-banner {
            margin-top: 14px; padding: 12px 14px; border-radius: 12px;
            background: rgba(239,68,68,.15); border: 1px solid rgba(252,165,165,.35);
            color: #fca5a5; font-weight: 700; text-align: center;
        }
        .stamps-label {
            margin: 18px 0 10px; font-size: .72rem; letter-spacing: .12em;
            text-transform: uppercase; opacity: .55; font-weight: 700;
        }
        .stamps {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;
        }
        .stamp {
            aspect-ratio: 1; border-radius: 50%;
            border: 2px dashed rgba(255,255,255,.22);
            display: grid; place-items: center;
            font-size: .72rem; font-weight: 800; color: rgba(255,255,255,.35);
            background: rgba(0,0,0,.18);
        }
        .stamp.on {
            border-style: solid; border-color: var(--gold);
            background: radial-gradient(circle at 35% 30%, #fff7ed, var(--gold));
            color: var(--wine-deep);
            box-shadow: 0 4px 12px rgba(0,0,0,.25);
        }
        .progress {
            text-align: center; margin-top: 12px; font-size: .85rem; opacity: .8;
        }
        .qr-wrap {
            margin-top: 18px; background: #fff; border-radius: 16px;
            padding: 16px; text-align: center; color: #1c1917;
        }
        .qr-wrap img {
            width: 200px; height: 200px; border-radius: 8px;
        }
        .qr-wrap .hint { margin-top: 8px; font-size: .78rem; color: #78716c; line-height: 1.35; }
        .actions { display: grid; gap: 10px; margin-top: 16px; }
        .btn {
            display: block; text-align: center; text-decoration: none; font-weight: 700;
            padding: 14px 16px; border-radius: 14px; font-size: .95rem;
        }
        .btn-primary { background: linear-gradient(135deg, #e8c28a, #c9a06a); color: var(--wine-deep); }
        .btn-ghost { background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.12); }
        .foot {
            text-align: center; margin-top: 18px; font-size: .72rem; opacity: .4;
        }
    </style>
</head>
<body>
<div class="wallet">
    <div class="brand-bar">
        <div class="brand">{{ $business }}<small>Loyalty rewards</small></div>
        <div class="reward-chip">{{ $payload['stamps_required'] }} → {{ $payload['reward_label'] }}</div>
    </div>

    <div class="hero">
        <div class="hero-title">
            <strong>Stamp Card</strong>
            <span>Collect · Sip · Enjoy</span>
        </div>
    </div>

    <div class="body">
        <div class="who">{{ $customer->name }}</div>
        <div class="phone">{{ $customer->phone ?: 'Member' }}</div>
        <div class="meta">
            @if(!empty($payload['expires_at']))
                <span>Expires {{ \Illuminate\Support\Carbon::parse($payload['expires_at'])->format('d M Y') }}</span>
            @else
                <span>No expiry</span>
            @endif
            <span>{{ $payload['stamps'] }}/{{ $payload['stamps_required'] }} stamps</span>
        </div>

        @if(!empty($payload['expired']))
        <div class="expired-banner">Card expired — ask staff to renew</div>
        @elseif($payload['free_drinks'] > 0)
        <div class="free-banner">{{ $payload['free_drinks'] }} × {{ $payload['reward_label'] }} ready — show QR at counter</div>
        @endif

        <div class="stamps-label">Your stamps</div>
        <div class="stamps">
            @for($i = 1; $i <= $payload['stamps_required']; $i++)
                <div class="stamp {{ $i <= $payload['stamps'] ? 'on' : '' }}">{{ $i <= $payload['stamps'] ? '✓' : $i }}</div>
            @endfor
        </div>
        <div class="progress">{{ $payload['stamps'] }} of {{ $payload['stamps_required'] }} · {{ $payload['reward_label'] }} when full</div>

        <div class="qr-wrap">
            <img src="{{ $qrImage }}" alt="Scan me">
            <div class="hint">Staff scans this QR each visit to add stamps or redeem</div>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="{{ route('loyalty.wallet', $customer->loyalty_token) }}">Add to Apple / Google Wallet</a>
            <a class="btn btn-ghost" href="{{ $cardUrl }}">Save / share this card</a>
        </div>
    </div>
</div>
<div class="foot">Show this card every visit · Linked to your phone number</div>
</body>
</html>
