<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Branch — QRPOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --felt: #0f4a37;
            --ink: #14110f;
            --sand: #f6f1e8;
            --accent: #c45c12;
        }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, rgba(196,92,18,.18), transparent 55%),
                radial-gradient(ellipse 70% 50% at 100% 100%, rgba(15,74,55,.22), transparent 50%),
                linear-gradient(160deg, #1a1512, #0c0a09 55%, #12261e);
            color: #fff;
            font-family: "Segoe UI", system-ui, sans-serif;
        }
        .wrap { width: min(560px, 100%); }
        .eyebrow {
            letter-spacing: .16em; text-transform: uppercase; font-size: .72rem;
            color: #f0d078; font-weight: 700; margin-bottom: .5rem;
        }
        h1 { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 800; margin: 0 0 .4rem; }
        .sub { opacity: .75; margin-bottom: 1.25rem; }
        .branch-card {
            display: block; width: 100%; text-align: left;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06);
            color: #fff; border-radius: 16px; padding: 1rem 1.15rem;
            margin-bottom: .75rem; transition: .15s ease;
            backdrop-filter: blur(8px);
        }
        .branch-card:hover, .branch-card:focus {
            border-color: #f0d078; background: rgba(240,208,120,.12);
            transform: translateY(-1px); color: #fff;
        }
        .branch-card .code {
            font-size: .75rem; font-weight: 700; letter-spacing: .08em;
            color: #f0d078; text-transform: uppercase;
        }
        .branch-card .name { font-size: 1.1rem; font-weight: 750; margin: .15rem 0; }
        .branch-card .meta { font-size: .85rem; opacity: .7; }
        .logout { color: rgba(255,255,255,.55); font-size: .9rem; }
        .logout:hover { color: #fff; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="eyebrow">Multi branch</div>
    <h1>Which branch?</h1>
    <p class="sub">Choose where you want to work this session.</p>

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    @foreach($branches as $b)
        <form method="post" action="{{ route('branches.select.store') }}" class="m-0">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $b->id }}">
            <button type="submit" class="branch-card">
                <div class="code">{{ $b->code }}@if($b->is_main) · Main @endif</div>
                <div class="name">{{ $b->name }}</div>
                <div class="meta">
                    {{ $b->company_name ?: 'Uses business invoice name' }}
                    @if($b->address) · {{ $b->address }}@endif
                </div>
            </button>
        </form>
    @endforeach

    <form method="post" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link logout">Sign out</button>
    </form>
</div>
</body>
</html>
