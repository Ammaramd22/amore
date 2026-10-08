<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Shop UI — ResPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, rgba(219,39,119,.2), transparent 55%),
                radial-gradient(ellipse 70% 50% at 100% 100%, rgba(245,158,11,.18), transparent 50%),
                linear-gradient(160deg, #1a1512, #0c0a09 55%, #1a0f18);
            color: #fff;
            font-family: "Segoe UI", system-ui, sans-serif;
        }
        .wrap { width: min(920px, 100%); }
        .eyebrow {
            letter-spacing: .16em; text-transform: uppercase; font-size: .72rem;
            color: #f9a8d4; font-weight: 700; margin-bottom: .5rem;
        }
        h1 { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 800; margin: 0 0 .4rem; }
        .sub { opacity: .75; margin-bottom: 1.5rem; }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }
        @media (max-width: 767.98px) {
            .grid { grid-template-columns: 1fr; }
        }
        .shop-card {
            display: flex; flex-direction: column; align-items: flex-start;
            width: 100%; text-align: left; min-height: 190px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            color: #fff; border-radius: 18px; padding: 1.25rem 1.2rem;
            transition: .15s ease; backdrop-filter: blur(8px);
        }
        .shop-card:hover, .shop-card:focus {
            transform: translateY(-2px); color: #fff;
            border-color: var(--accent, #f9a8d4);
            background: rgba(255,255,255,.1);
            box-shadow: 0 12px 28px rgba(0,0,0,.28);
        }
        .shop-card.is-current {
            border-color: var(--accent, #f9a8d4);
            box-shadow: 0 0 0 2px rgba(249,168,212,.35);
        }
        .shop-card .icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: grid; place-items: center;
            background: color-mix(in srgb, var(--accent) 22%, transparent);
            color: var(--accent); font-size: 1.35rem; margin-bottom: .9rem;
        }
        .shop-card .title { font-size: 1.2rem; font-weight: 800; margin-bottom: .25rem; }
        .shop-card .desc { font-size: .9rem; opacity: .72; line-height: 1.35; flex: 1; }
        .shop-card .cta {
            margin-top: 1rem; font-size: .78rem; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase; color: var(--accent);
        }
        .logout { color: rgba(255,255,255,.55); font-size: .9rem; }
        .logout:hover { color: #fff; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="eyebrow">Demo · Shop type</div>
    <h1>Which shop UI?</h1>
    <p class="sub">Pick the front screen for this session. You can change it again anytime from POS.</p>

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <div class="grid">
        @foreach($modes as $mode)
            <form method="post" action="{{ route('shop-ui.select.store') }}" class="m-0">
                @csrf
                <input type="hidden" name="pos_ui_mode" value="{{ $mode['key'] }}">
                <button type="submit" class="shop-card {{ ($current ?? '') === $mode['key'] ? 'is-current' : '' }}"
                        style="--accent: {{ $mode['accent'] }}">
                    <div class="icon"><i class="fas {{ $mode['icon'] }}"></i></div>
                    <div class="title">{{ $mode['title'] }}</div>
                    <div class="desc">{{ $mode['subtitle'] }}</div>
                    <div class="cta">{{ ($current ?? '') === $mode['key'] ? 'Selected · Continue' : 'Use this UI' }}</div>
                </button>
            </form>
        @endforeach
    </div>

    <form method="post" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="btn btn-link logout">Sign out</button>
    </form>
</div>
</body>
</html>
