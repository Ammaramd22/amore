<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Billiards') — {{ \App\Models\Setting::get('company_name', 'QRPOS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    @stack('styles')
    <style>
        :root {
            --bil-bg: #f3f0eb;
            --bil-ink: #14110f;
            --bil-muted: #6f675f;
            --bil-amber: #e8a317;
            --bil-amber2: #c45c12;
            --bil-green: #0d9f6e;
            --bil-felt: #0a3326;
            --bil-felt-2: #0f4a37;
            --bil-line: rgba(20,17,15,.1);
            --bil-card: #fffcf8;
            --bil-wood: #5c3a1c;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; padding: 0; overflow-x: hidden;
            background:
                radial-gradient(ellipse 70% 50% at 100% -10%, rgba(232,163,23,.12), transparent 50%),
                radial-gradient(ellipse 50% 40% at -5% 20%, rgba(10,51,38,.07), transparent 45%),
                var(--bil-bg);
            color: var(--bil-ink);
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .bil-topbar {
            background:
                linear-gradient(180deg, rgba(255,255,255,.04), transparent),
                linear-gradient(135deg, #0c1f18 0%, #14110f 55%, #1c1917 100%);
            color: #fff;
            padding: 10px 16px;
            display: flex; justify-content: space-between; align-items: center; gap: 14px;
            border-bottom: 1px solid rgba(232,163,23,.35);
            box-shadow: 0 12px 28px rgba(10,20,16,.28);
            position: sticky; top: 0; z-index: 100;
        }
        .bil-topbar .brand {
            display: flex; align-items: center; gap: 12px;
            font-size: 1.2rem; font-weight: 800; color: #f0d078;
            letter-spacing: -0.03em; text-decoration: none; white-space: nowrap;
        }
        .bil-mark {
            width: 40px; height: 40px; border-radius: 11px;
            background: url('{{ asset('images/billiards-icon.png') }}') center / cover no-repeat;
            border: 1px solid rgba(201,162,39,.45);
            box-shadow: 0 6px 16px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.06);
            animation: bilMarkIdle 4s ease-in-out infinite;
        }
        @keyframes bilMarkIdle {
            0%, 100% { transform: translateY(0); filter: brightness(1); }
            50% { transform: translateY(-2px); filter: brightness(1.08); }
        }
        @media (prefers-reduced-motion: reduce) {
            .bil-mark { animation: none !important; }
        }
        .bil-topbar-actions {
            display: flex; align-items: center; gap: 6px;
            overflow-x: auto; scrollbar-width: none; padding: 2px 0;
        }
        .bil-topbar-actions::-webkit-scrollbar { display: none; }
        .bil-topbar .btn-bar {
            white-space: nowrap; flex-shrink: 0;
            min-height: 42px;
            padding: 8px 12px; font-size: .8rem; font-weight: 650;
            border-radius: 12px; border: 1px solid rgba(255,255,255,.1);
            background: rgba(255,255,255,.05); color: rgba(255,247,237,.88); text-decoration: none;
            display: inline-flex; align-items: center; gap: .4rem;
            transition: background .15s ease, border-color .15s ease, transform .15s ease;
        }
        .bil-topbar .btn-bar i { opacity: .9; }
        .bil-topbar .btn-bar:hover {
            background: rgba(232,163,23,.22); border-color: rgba(232,163,23,.45); color: #fff;
            transform: translateY(-1px);
        }
        .bil-topbar .btn-bar.primary {
            background: linear-gradient(135deg, #e8a317, #c45c12);
            border-color: transparent; color: #fff;
            box-shadow: 0 8px 18px rgba(196,92,18,.35);
        }
        .bil-topbar .btn-bar.is-active {
            background: rgba(15,74,55,.55);
            border-color: rgba(232,163,23,.4);
            color: #fff;
        }
        .bil-page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 16px 16px 28px;
        }
        .bil-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(340px, 420px);
            gap: 16px;
            min-height: calc(100vh - 78px);
        }
        @media (max-width: 991.98px) {
            .bil-shell { grid-template-columns: 1fr; }
            .bil-topbar { padding: 10px 12px; }
            .bil-topbar .brand span { display: none; }
            .bil-topbar .btn-bar span { display: none; }
            .bil-topbar .btn-bar { padding: 10px 12px; }
            .bil-page { padding: 12px 10px 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .bil-topbar .btn-bar, .btile, .pay-tile, .pos-btn { transition: none !important; }
        }
    </style>
</head>
<body>
    <header class="bil-topbar">
        <a class="brand" href="{{ route('billiards.desk') }}">
            <span class="bil-mark" aria-hidden="true"></span>
            <span>Billiards</span>
        </a>
        <nav class="bil-topbar-actions" aria-label="Billiards">
            @yield('topbar_actions')
            <a class="btn-bar {{ request()->routeIs('billiards.desk') ? 'is-active' : '' }}" href="{{ route('billiards.desk') }}"><i class="fas fa-th-large"></i> <span>Desk</span></a>
            <a class="btn-bar primary {{ request()->routeIs('billiards.pos') || request()->routeIs('billiards.bookings.create') ? 'is-active' : '' }}" href="{{ route('billiards.pos') }}"><i class="fas fa-plus"></i> <span>Create</span></a>
            <a class="btn-bar {{ request()->routeIs('billiards.bookings.*') && !request()->routeIs('billiards.bookings.create') ? 'is-active' : '' }}" href="{{ route('billiards.bookings.index') }}"><i class="fas fa-calendar-check"></i> <span>Bookings</span></a>
            <a class="btn-bar" href="{{ route('billiards.display') }}" target="_blank"><i class="fas fa-tv"></i> <span>Display</span></a>
            <a class="btn-bar {{ request()->routeIs('billiards.tables.*') ? 'is-active' : '' }}" href="{{ route('billiards.tables.index') }}"><i class="fas fa-border-all"></i> <span>Tables</span></a>
            <a class="btn-bar {{ request()->routeIs('billiards.reports') ? 'is-active' : '' }}" href="{{ route('billiards.reports') }}"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
            <a class="btn-bar {{ request()->routeIs('billiards.settings*') ? 'is-active' : '' }}" href="{{ route('billiards.settings') }}"><i class="fas fa-cog"></i> <span>Settings</span></a>
            @unless(auth()->user()?->isBilliardsStaff())
                <a class="btn-bar" href="{{ route('dashboard') }}"><i class="fas fa-home"></i> <span>Admin</span></a>
            @endunless
            <form method="post" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn-bar" style="cursor:pointer"><i class="fas fa-sign-out-alt"></i> <span>Out</span></button>
            </form>
        </nav>
    </header>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
    @stack('scripts')
</body>
</html>
