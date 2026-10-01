<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('pwa_app_name', 'QRPOS Waiter Panel') }}</title>
    @include('partials.waiter-pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <style>
        :root {
            --ink: #1c1410;
            --amber: #e8890c;
            --amber-deep: #c45f08;
            --cream: #fff8f1;
            --muted: rgba(255, 248, 241, 0.72);
            --panel: rgba(255, 248, 241, 0.07);
            --line: rgba(255, 248, 241, 0.12);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Outfit, system-ui, sans-serif;
            color: var(--cream);
            background:
                radial-gradient(ellipse 70% 50% at 15% 90%, rgba(232, 137, 12, 0.28), transparent 55%),
                radial-gradient(ellipse 50% 40% at 95% 5%, rgba(154, 52, 18, 0.35), transparent 50%),
                linear-gradient(155deg, #1c1410 0%, #2a1a12 45%, #3d2314 100%);
            padding-bottom: 110px;
            font-size: 18px;
        }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1.15rem 1.5rem; }
        .brand { display: flex; align-items: center; gap: 0.75rem; font-family: Fraunces, Georgia, serif; font-size: 1.55rem; font-weight: 700; }
        .brand-mark { width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; background: #0a0a0a; overflow: hidden; border: 1px solid rgba(255,248,241,0.12); }
        .brand-mark img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .brand-sub { display: block; font-family: Outfit, system-ui, sans-serif; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #60a5fa; margin-top: 2px; }
        .btn-ghost { border: 1px solid var(--line); background: var(--panel); color: var(--cream); border-radius: 14px; padding: 0.85rem 1.1rem; font: inherit; font-weight: 600; font-size: 1.05rem; cursor: pointer; min-height: 52px; }
        .wrap { padding: 0 1.5rem 2rem; max-width: 920px; margin: 0 auto; }
        .hello { font-size: 0.9rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--amber); margin-bottom: 0.45rem; }
        h1 { font-family: Fraunces, Georgia, serif; font-size: clamp(2.1rem, 5vw, 2.75rem); margin: 0 0 0.45rem; letter-spacing: -0.02em; }
        .sub { color: var(--muted); margin: 0 0 1.5rem; line-height: 1.5; font-size: 1.1rem; }
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        @media (min-width: 700px) { .stats { grid-template-columns: repeat(4, 1fr); } }
        .stat { background: var(--panel); border: 1px solid var(--line); border-radius: 20px; padding: 1.35rem 1rem; text-align: center; min-height: 110px; display: flex; flex-direction: column; justify-content: center; }
        .stat .n { font-family: Fraunces, Georgia, serif; font-size: 2.6rem; font-weight: 700; line-height: 1; margin-bottom: 0.45rem; }
        .stat .l { font-size: 0.95rem; color: var(--muted); font-weight: 600; }
        .panel-btn { display: flex; align-items: center; gap: 1.15rem; width: 100%; padding: 1.45rem 1.4rem; border-radius: 20px; text-decoration: none; color: #fff; background: linear-gradient(135deg, var(--amber), var(--amber-deep) 60%, #9a3412); box-shadow: 0 14px 32px rgba(196, 95, 8, 0.35); margin-bottom: 1.5rem; min-height: 88px; }
        .panel-btn i { font-size: 2rem; }
        .panel-btn strong { display: block; font-size: 1.45rem; }
        .panel-btn span { font-size: 1.05rem; opacity: 0.92; }
        .section-title { font-size: 1rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--amber); margin: 0 0 0.9rem; }
        .tabs { display: flex; gap: 0.65rem; margin-bottom: 1rem; overflow-x: auto; }
        .tab { flex: 0 0 auto; border: 1px solid var(--line); background: var(--panel); color: var(--muted); border-radius: 999px; padding: 0.85rem 1.35rem; font: inherit; font-weight: 700; font-size: 1.05rem; cursor: pointer; min-height: 52px; }
        .tab.active { background: var(--amber); border-color: var(--amber); color: #fff; }
        .order-list { display: grid; gap: 1rem; }
        .order-card { background: var(--panel); border: 1px solid var(--line); border-radius: 20px; padding: 1.35rem 1.4rem; cursor: pointer; transition: border-color .15s ease, background .15s ease; }
        .order-card:active { border-color: rgba(232,137,12,.45); background: rgba(255,248,241,.1); }
        .order-card .row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
        .order-card .num { font-weight: 700; font-size: 1.45rem; }
        .order-card .meta { color: var(--muted); font-size: 1.05rem; margin-top: 0.35rem; }
        .order-card .meta.preview { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .badge { display: inline-block; padding: 0.4rem 0.85rem; border-radius: 999px; font-size: 0.9rem; font-weight: 700; background: rgba(232, 137, 12, 0.2); color: #fbbf24; }
        .badge.done { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .emoji-row { display: flex; justify-content: space-between; gap: 0.55rem; margin-top: 1.15rem; }
        .emoji-btn { flex: 1; border: 1px solid var(--line); background: rgba(0,0,0,0.25); border-radius: 18px; padding: 1.1rem 0.35rem; font-size: 2.75rem; cursor: pointer; line-height: 1; min-height: 96px; }
        .emoji-btn small { display: block; font-size: 0.85rem; color: var(--muted); margin-top: 0.45rem; font-family: Outfit, sans-serif; font-weight: 700; }
        .rated { margin-top: 0.85rem; font-size: 2.2rem; text-align: center; }
        .rate-share {
            display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.85rem;
        }
        .share-btn {
            display: inline-flex; align-items: center; gap: 0.35rem;
            border: 0; border-radius: 999px; padding: 0.45rem 0.85rem;
            background: rgba(255,255,255,0.08); color: #fef3c7; font-weight: 700; font-size: 0.82rem;
            cursor: pointer; text-decoration: none;
        }
        .share-btn.wa { background: rgba(37,211,102,0.2); color: #86efac; }
        .rate-qr-box { margin-top: 0.85rem; text-align: center; }
        .qr-section { margin-bottom: 1.35rem; }
        .qr-section:empty { display: none; }
        .qr-banner {
            background: linear-gradient(135deg, rgba(14,165,233,.22), rgba(2,132,199,.12));
            border: 1px solid rgba(56,189,248,.55);
            border-radius: 20px;
            padding: 1.15rem 1.2rem;
            margin-bottom: 0.85rem;
            animation: qrPulse 1.8s ease-in-out infinite;
        }
        @keyframes qrPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(56,189,248,.35); }
            50% { box-shadow: 0 0 0 12px rgba(56,189,248,0); }
        }
        .qr-banner .qr-head {
            display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .qr-banner .qr-head strong {
            color: #7dd3fc; font-size: 1.05rem; letter-spacing: .04em; text-transform: uppercase;
        }
        .qr-card {
            background: rgba(0,0,0,.28);
            border: 1px solid rgba(125,211,252,.25);
            border-radius: 16px;
            padding: 1rem 1.05rem;
            margin-bottom: 0.65rem;
        }
        .qr-card:last-child { margin-bottom: 0; }
        .qr-card .num { font-weight: 800; font-size: 1.25rem; }
        .qr-card .meta { color: #bae6fd; font-size: 0.95rem; margin-top: 0.25rem; }
        .qr-card .items { margin-top: 0.55rem; font-size: 1rem; color: #e0f2fe; }
        .qr-card .notes { margin-top: 0.4rem; font-size: 0.9rem; color: #fde68a; }
        .qr-actions { display: flex; gap: 0.55rem; margin-top: 0.85rem; }
        .qr-accept {
            flex: 1; border: 0; border-radius: 14px; padding: 0.95rem 1rem;
            font: inherit; font-weight: 800; font-size: 1.1rem; color: #052e16;
            background: linear-gradient(135deg, #34d399, #10b981); cursor: pointer; min-height: 52px;
        }
        .qr-reject {
            flex: 0 0 auto; border: 1px solid rgba(252,165,165,.45); border-radius: 14px;
            padding: 0.95rem 1rem; font: inherit; font-weight: 700; color: #fca5a5;
            background: rgba(239,68,68,.12); cursor: pointer; min-height: 52px;
        }
        .badge.qr {
            background: rgba(14,165,233,.25); color: #7dd3fc;
        }
        .empty { text-align: center; color: var(--muted); padding: 2.5rem 1rem; font-size: 1.2rem; }
        .bring-btn { margin-top: 1rem; width: 100%; border: 0; border-radius: 16px; padding: 1.15rem 1rem; font: inherit; font-weight: 800; font-size: 1.35rem; color: #fff; background: linear-gradient(135deg, #16a34a, #15803d); cursor: pointer; min-height: 64px; }
        .edit-btn { margin-top: 1rem; width: 100%; border: 0; border-radius: 16px; padding: 1.05rem 1rem; font: inherit; font-weight: 800; font-size: 1.2rem; color: #1c1410; background: linear-gradient(135deg, #f59e0b, #ea580c); cursor: pointer; min-height: 56px; }
        .edit-btn + .bring-btn, .edit-btn + .waiting-cashier { margin-top: 0.65rem; }
        .serve-btn { margin-top: 0.85rem; width: 100%; border: 0; border-radius: 16px; padding: 1.1rem 1rem; font: inherit; font-weight: 800; font-size: 1.25rem; color: #fff; background: linear-gradient(135deg, #059669, #047857); cursor: pointer; min-height: 60px; }
        .kot-status { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; }
        .kot-status.pending { background: rgba(239,68,68,.2); color: #fca5a5; }
        .kot-status.preparing { background: rgba(245,158,11,.2); color: #fbbf24; }
        .kot-status.ready { background: rgba(16,185,129,.25); color: #6ee7b7; animation: readyPulse 1.4s infinite; }
        @keyframes readyPulse { 0%,100%{ box-shadow: 0 0 0 0 rgba(16,185,129,.4);} 50%{ box-shadow: 0 0 0 10px rgba(16,185,129,0);} }
        .waiting-cashier { margin-top: 0.9rem; text-align: center; font-size: 1.1rem; font-weight: 700; color: #fbbf24; padding: 0.75rem; }
        .bottom-nav { position: fixed; left: 0; right: 0; bottom: 0; display: grid; grid-template-columns: repeat(var(--nav-cols, 4), 1fr); gap: 0.45rem; padding: 0.85rem 1rem calc(0.85rem + env(safe-area-inset-bottom)); background: rgba(28, 20, 16, 0.96); border-top: 1px solid var(--line); backdrop-filter: blur(10px); }
        .view { display: none; }
        .view.active { display: block; }
        .view-head { margin-bottom: 1.25rem; }
        .view-head h1 { margin-bottom: 0.35rem; }
        .nav-badge {
            position: absolute; top: 6px; right: calc(50% - 28px);
            min-width: 18px; height: 18px; padding: 0 5px;
            border-radius: 999px; background: #e8890c; color: #1c1410;
            font-size: 0.7rem; font-weight: 800; display: none; align-items: center; justify-content: center;
        }
        .nav-badge.show { display: inline-flex; }
        .bottom-nav a, .bottom-nav button { position: relative; }
        .bottom-nav a, .bottom-nav button { border: 0; background: transparent; color: var(--muted); text-decoration: none; font: inherit; font-size: 0.95rem; font-weight: 700; padding: 0.75rem; border-radius: 14px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 0.35rem; min-height: 64px; justify-content: center; }
        .bottom-nav a.active, .bottom-nav button.active { color: var(--amber); background: rgba(232,137,12,0.12); }
        .bottom-nav i { font-size: 1.45rem; }

        .profile-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 1.75rem 1.4rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .profile-avatar {
            width: 88px; height: 88px; border-radius: 28px; margin: 0 auto 1rem;
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--amber), var(--amber-deep));
            font-family: Fraunces, Georgia, serif; font-size: 2.4rem; font-weight: 700; color: #1c1410;
        }
        .profile-card h2 { font-family: Fraunces, Georgia, serif; margin: 0 0 0.35rem; font-size: 1.85rem; }
        .profile-card .role { color: var(--amber); font-weight: 700; letter-spacing: .08em; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 0.75rem; }
        .profile-meta { color: var(--muted); font-size: 1rem; line-height: 1.5; }
        .profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin: 1.25rem 0; }
        .profile-stats .ps {
            background: rgba(0,0,0,.25); border-radius: 16px; padding: 1rem 0.75rem;
        }
        .profile-stats .ps .n { font-family: Fraunces, Georgia, serif; font-size: 1.75rem; font-weight: 700; }
        .profile-stats .ps .l { color: var(--muted); font-size: 0.85rem; font-weight: 600; margin-top: 0.25rem; }
        .profile-logout {
            width: 100%; border: 1px solid rgba(252,165,165,.35); border-radius: 16px;
            padding: 1.1rem; font: inherit; font-weight: 800; font-size: 1.15rem;
            color: #fca5a5; background: rgba(239,68,68,.12); cursor: pointer; min-height: 56px;
        }
        .draft-float {
            display: none;
            position: fixed;
            left: 12px; right: 12px;
            bottom: calc(78px + env(safe-area-inset-bottom));
            z-index: 40;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #1c1917, #292524);
            border: 1px solid rgba(245,158,11,.55);
            box-shadow: 0 12px 40px rgba(0,0,0,.45);
            color: #fafaf9;
            text-decoration: none;
            max-width: 520px;
            margin: 0 auto;
        }
        .draft-float.show { display: flex; }
        .draft-float .df-icon {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            display: grid; place-items: center; position: relative;
            background: rgba(245,158,11,.18); color: #f59e0b; font-size: 1.15rem;
        }
        .draft-float .df-badge {
            position: absolute; top: -6px; right: -6px;
            min-width: 20px; height: 20px; padding: 0 5px;
            border-radius: 999px; background: #f59e0b; color: #1c1917;
            font-size: 0.7rem; font-weight: 800; display: grid; place-items: center;
        }
        .draft-float .df-body { flex: 1; min-width: 0; }
        .draft-float .df-title { font-weight: 800; font-size: 0.95rem; }
        .draft-float .df-meta { color: var(--muted); font-size: 0.78rem; margin-top: 2px; }
        .draft-float .df-go {
            border: 0; border-radius: 12px; padding: 10px 12px;
            background: #f59e0b; color: #1c1917; font-weight: 800; font-size: 0.8rem;
            text-decoration: none; flex-shrink: 0;
        }
        .draft-float .df-cancel {
            border: 0; background: transparent; color: var(--muted);
            width: 36px; height: 36px; border-radius: 10px; font-size: 1.2rem; cursor: pointer; flex-shrink: 0;
        }
        body.has-draft-float .wrap { padding-bottom: 160px; }
        .toast { position: fixed; top: 1rem; left: 50%; transform: translateX(-50%) translateY(-120%); background: #14532d; color: #fff; padding: 1rem 1.4rem; border-radius: 999px; font-weight: 700; font-size: 1.1rem; opacity: 0; transition: 0.25s ease; z-index: 90; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast.err { background: #7f1d1d; }
        .rate-overlay { position: fixed; inset: 0; background: rgba(12, 8, 6, 0.9); z-index: 80; display: none; align-items: center; justify-content: center; padding: 1.5rem; }
        .rate-overlay.show { display: flex; }
        .rate-card { width: 100%; max-width: 560px; background: linear-gradient(160deg, #2a1a12, #1c1410); border: 1px solid rgba(255,248,241,0.14); border-radius: 24px; padding: 2rem 1.5rem; text-align: center; }
        .rate-card h2 { font-family: Fraunces, Georgia, serif; margin: 0 0 0.5rem; font-size: 2rem; }
        .rate-card p { color: var(--muted); margin: 0 0 1.35rem; font-size: 1.15rem; }
        .rate-overlay .emoji-btn { font-size: 3.25rem; min-height: 110px; }

        /* Kitchen ticket detail popup */
        .kot-popup {
            position: fixed; inset: 0; z-index: 85;
            display: none; align-items: flex-end; justify-content: center;
            padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom));
            background: rgba(12, 8, 6, 0.72);
            backdrop-filter: blur(6px);
        }
        .kot-popup.show { display: flex; }
        @media (min-width: 640px) {
            .kot-popup { align-items: center; }
        }
        .kot-sheet {
            width: 100%; max-width: 420px;
            background: linear-gradient(165deg, #2f1d14 0%, #1c1410 70%);
            border: 1px solid rgba(255,248,241,0.14);
            border-radius: 24px;
            padding: 1.25rem 1.2rem 1.35rem;
            box-shadow: 0 24px 60px rgba(0,0,0,.45);
            animation: kotSheetIn .22s ease;
            max-height: min(78vh, 560px);
            display: flex; flex-direction: column;
        }
        @keyframes kotSheetIn {
            from { opacity: 0; transform: translateY(18px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .kot-sheet-top {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;
            margin-bottom: 0.35rem;
        }
        .kot-sheet-top .ks-label {
            font-size: 0.75rem; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: var(--amber); margin-bottom: 0.25rem;
        }
        .kot-sheet-top .ks-num {
            font-family: Fraunces, Georgia, serif; font-size: 1.55rem; font-weight: 700; line-height: 1.15;
        }
        .kot-sheet-close {
            border: 1px solid var(--line); background: var(--panel); color: var(--cream);
            width: 40px; height: 40px; border-radius: 12px; font-size: 1.15rem; cursor: pointer; flex-shrink: 0;
        }
        .kot-sheet-meta {
            color: var(--muted); font-size: 0.95rem; margin-bottom: 1rem;
        }
        .kot-sheet-items {
            flex: 1; overflow-y: auto; margin: 0 -0.15rem; padding: 0.15rem;
            display: grid; gap: 0.55rem;
        }
        .kot-item {
            display: flex; align-items: flex-start; gap: 0.75rem;
            background: rgba(0,0,0,.28);
            border: 1px solid rgba(255,248,241,0.08);
            border-radius: 14px;
            padding: 0.85rem 0.9rem;
        }
        .kot-item .qty {
            flex-shrink: 0; min-width: 36px; height: 36px; border-radius: 10px;
            display: grid; place-items: center;
            background: rgba(232,137,12,.2); color: #fbbf24;
            font-weight: 800; font-size: 0.95rem;
        }
        .kot-item .name { font-weight: 700; font-size: 1.05rem; line-height: 1.3; }
        .kot-item .note { margin-top: 0.25rem; font-size: 0.85rem; color: #fde68a; }
        .kot-sheet-foot { margin-top: 1rem; display: grid; gap: 0.55rem; }
        .kot-sheet-foot .serve-btn { margin-top: 0; }
        .kot-sheet-foot .ks-hint {
            text-align: center; font-size: 0.85rem; color: var(--muted); font-weight: 600;
        }
        @media (max-width: 480px) {
            body { font-size: 16px; padding-bottom: 96px; }
            .top { padding: 0.85rem 1rem; padding-top: calc(0.85rem + env(safe-area-inset-top)); }
            .brand { font-size: 1.25rem; }
            .brand-mark { width: 42px; height: 42px; border-radius: 12px; }
            .wrap { padding: 0 1rem 1.5rem; }
            h1 { font-size: 1.75rem; }
            .sub { font-size: 0.95rem; }
            .stat { min-height: 88px; padding: 1rem 0.75rem; border-radius: 16px; }
            .stat .n { font-size: 2rem; }
            .stat .l { font-size: 0.8rem; }
            .panel-btn { padding: 1.1rem 1rem; min-height: 72px; border-radius: 16px; }
            .panel-btn strong { font-size: 1.15rem; }
            .panel-btn span { font-size: 0.9rem; }
            .panel-btn i { font-size: 1.5rem; }
            .order-card { padding: 1.1rem; border-radius: 16px; }
            .order-card .num { font-size: 1.2rem; }
            .order-card .meta { font-size: 0.9rem; }
            .bring-btn { font-size: 1.15rem; min-height: 56px; padding: 0.95rem; }
            .edit-btn { font-size: 1.05rem; min-height: 52px; padding: 0.9rem; }
            .emoji-row { gap: 0.35rem; }
            .emoji-btn { font-size: 2rem; min-height: 72px; padding: 0.75rem 0.2rem; border-radius: 14px; }
            .emoji-btn small { font-size: 0.7rem; }
            .rate-overlay .emoji-btn { font-size: 2.25rem; min-height: 84px; }
            .bottom-nav { padding: 0.55rem 0.5rem calc(0.55rem + env(safe-area-inset-bottom)); gap: 0.25rem; }
            .bottom-nav a, .bottom-nav button { min-height: 56px; font-size: 0.8rem; padding: 0.5rem; }
            .bottom-nav i { font-size: 1.25rem; }
            .tab { padding: 0.65rem 1rem; font-size: 0.9rem; min-height: 44px; }
        }
    </style>
</head>
<body>
    @include('partials.waiter-pwa-splash')
    <div class="toast" id="toast"></div>

    <div class="kot-popup" id="kotPopup" onclick="if(event.target===this) closeKotPopup()">
        <div class="kot-sheet" role="dialog" aria-modal="true" aria-labelledby="kotPopupTitle">
            <div class="kot-sheet-top">
                <div>
                    <div class="ks-label" id="kotPopupLabel">Ticket</div>
                    <div class="ks-num" id="kotPopupTitle">—</div>
                </div>
                <button type="button" class="kot-sheet-close" onclick="closeKotPopup()" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div class="kot-sheet-meta" id="kotPopupMeta"></div>
            <div class="kot-sheet-items" id="kotPopupItems"></div>
            <div class="kot-sheet-foot" id="kotPopupFoot"></div>
        </div>
    </div>

    <div class="rate-overlay" id="rateOverlay">
        <div class="rate-card">
            <div class="hello">Payment received</div>
            <h2 id="rateOverlayTitle">How was the service?</h2>
            <p id="rateOverlaySub">Ask the guest to tap a face</p>
            <div class="emoji-row" id="rateOverlayEmojis"></div>
            <button type="button" class="btn-ghost" style="margin-top:1rem;" onclick="hideRateOverlay()">Later</button>
        </div>
    </div>

    <header class="top">
        <div class="brand">
            <div class="brand-mark">
                @php $pwaLogo = \App\Models\Setting::pwaLogoUrl(); @endphp
                <img src="{{ $pwaLogo }}" alt="{{ \App\Models\Setting::get('pwa_app_short_name', 'QRPOS Waiter') }}">
            </div>
            <div>
                {{ \App\Models\Setting::get('pwa_app_short_name', 'QRPOS Waiter') }}
                <span class="brand-sub">By Avenque</span>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn-ghost"><i class="fas fa-sign-out-alt"></i></button></form>
    </header>

    <div class="wrap">
        <div id="viewHome" class="view active">
            <div class="hello">Waiter dashboard</div>
            <h1>Hi, {{ $waiter->name }}</h1>
            <p class="sub">
                @if($kotConfirmationEnabled ?? true)
                    Track kitchen tickets here. When food is <strong>Ready</strong>, tap <strong>Serve</strong> after giving it to the guest.
                @else
                    KOT confirmation is off — tickets print only.
                @endif
                @if($bringBillEnabled ?? true)
                    <strong>Bring bill</strong> still goes to the cashier Pay Bills queue.
                @endif
            </p>

            <div class="stats">
                <div class="stat"><div class="n" id="statOpen">{{ $stats['open_today'] }}</div><div class="l">Open today</div></div>
                @if($kotConfirmationEnabled ?? true)
                <div class="stat"><div class="n" id="statReadyKot">0</div><div class="l">Ready to serve</div></div>
                @endif
                <div class="stat"><div class="n" id="statDoneToday">{{ $stats['completed_today'] }}</div><div class="l">Paid today</div></div>
                @if($ratingEnabled ?? true)
                <div class="stat"><div class="n" id="statAvg">{{ $stats['avg_rating'] !== null ? $stats['avg_rating'].' ★' : '—' }}</div><div class="l">Avg rating ({{ $stats['ratings_count'] }})</div></div>
                @endif
            </div>

            <a href="{{ route('waiter.index') }}" class="panel-btn">
                <i class="fas fa-tablet-screen-button"></i>
                <div><strong>Open Waiter Panel</strong><span>Tables · Menu · Send KOT</span></div>
            </a>

            <div class="section-title" id="qrSectionTitle" style="display:none;">Guest QR orders <span id="badgeQr" class="badge qr" style="display:none;">0</span></div>
            <div class="qr-section" id="qrPendingSection"></div>

            <div class="section-title">Open bills</div>
            <div class="tabs">
                <button type="button" class="tab active" data-tab="open" onclick="switchHomeTab('open', this)">Open bills</button>
                <button type="button" class="tab" data-tab="completed" onclick="switchHomeTab('completed', this)">Paid</button>
            </div>
            <div class="order-list" id="homeOrderList"><div class="empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div></div>
        </div>

        <div id="viewKitchen" class="view">
            <div class="view-head">
                <div class="hello">Kitchen</div>
                <h1>Tickets to serve</h1>
                <p class="sub">When food is <strong>Ready</strong>, tap <strong>Serve</strong> after giving it to the guest.</p>
            </div>
            <div class="section-title" id="qrSectionTitleKitchen" style="display:none;">Guest QR orders</div>
            <div class="qr-section" id="qrPendingSectionKitchen"></div>
            <div class="order-list" id="kitchenList"><div class="empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div></div>
        </div>

        <div id="viewRate" class="view">
            <div class="view-head">
                <div class="hello">Rate</div>
                <h1>Guest ratings</h1>
                <p class="sub">Ask the guest to rate service, or share the QR / WhatsApp link.</p>
            </div>
            <div class="order-list" id="rateList"><div class="empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div></div>
        </div>

        <div id="viewProfile" class="view">
            <div class="view-head">
                <div class="hello">Account</div>
                <h1>Your profile</h1>
                <p class="sub">Shift summary and account for {{ $waiter->name }}.</p>
            </div>
            <div class="profile-card">
                <div class="profile-avatar">{{ strtoupper(substr($waiter->name, 0, 1)) }}</div>
                <h2>{{ $waiter->name }}</h2>
                <div class="role">Waiter</div>
                <div class="profile-meta">
                    @if(!empty($waiter->employee_code))
                        Code {{ $waiter->employee_code }} ·
                    @endif
                    {{ $waiter->email ?? 'On floor' }}
                </div>
                <div class="profile-stats">
                    <div class="ps"><div class="n" id="profileOpen">{{ $stats['open_today'] }}</div><div class="l">Open today</div></div>
                    <div class="ps"><div class="n" id="profileDone">{{ $stats['completed_today'] }}</div><div class="l">Paid today</div></div>
                    @if($ratingEnabled ?? true)
                    <div class="ps"><div class="n" id="profileAvg">{{ $stats['avg_rating'] !== null ? $stats['avg_rating'].'★' : '—' }}</div><div class="l">Avg rating</div></div>
                    <div class="ps"><div class="n" id="profileRatings">{{ $stats['ratings_today'] ?? 0 }}</div><div class="l">Ratings today</div></div>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="profile-logout"><i class="fas fa-sign-out-alt me-2"></i>Sign out</button>
                </form>
            </div>
        </div>
    </div>

    @php
        $showKitchenNav = (bool) ($kotConfirmationEnabled ?? true);
        $showRateNav = (bool) ($ratingEnabled ?? true);
        $showProfileNav = ! $showKitchenNav; // replace Kitchen with Profile when confirmation off
        $navCols = 2 + ($showKitchenNav ? 1 : 0) + ($showProfileNav ? 1 : 0) + ($showRateNav ? 1 : 0);
    @endphp
    <nav class="bottom-nav" style="--nav-cols: {{ $navCols }};">
        <button type="button" class="nav-btn active" data-view="home" onclick="showView('home')"><i class="fas fa-home"></i>Home</button>
        @if($showKitchenNav)
        <button type="button" class="nav-btn" data-view="kitchen" onclick="showView('kitchen')">
            <span class="nav-badge" id="navBadgeKitchen">0</span>
            <i class="fas fa-bell"></i>Kitchen
        </button>
        @elseif($showProfileNav)
        <button type="button" class="nav-btn" data-view="profile" onclick="showView('profile')">
            <i class="fas fa-user"></i>Profile
        </button>
        @endif
        @if($showRateNav)
        <button type="button" class="nav-btn" data-view="rate" onclick="showView('rate')">
            <span class="nav-badge" id="navBadgeRate">{{ $stats['awaiting_rating'] > 0 ? $stats['awaiting_rating'] : '' }}</span>
            <i class="fas fa-face-smile"></i>Rate
        </button>
        @endif
        <a href="{{ route('waiter.index') }}" class="nav-btn"><i class="fas fa-utensils"></i>Panel</a>
    </nav>

    <div class="draft-float" id="draftFloat">
        <div class="df-icon">
            <i class="fas fa-shopping-bag"></i>
            <span class="df-badge" id="draftFloatCount">0</span>
        </div>
        <div class="df-body">
            <div class="df-title" id="draftFloatTitle">Draft order</div>
            <div class="df-meta" id="draftFloatMeta">Unsent items waiting</div>
        </div>
        <a class="df-go" href="{{ route('waiter.index') }}?resume=1">Continue</a>
        <button type="button" class="df-cancel" onclick="cancelDraftOrder()" title="Cancel draft">&times;</button>
    </div>

    <audio id="waiterBell" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3" type="audio/mpeg">
    </audio>
    <audio id="qrBell" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <script>
        function smartWaiterInterval(fn, ms) {
            return setInterval(function () { if (document.hidden) return; fn(); }, ms);
        }

        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const bringBillEnabled = @json((bool) ($bringBillEnabled ?? true));
        const kotConfirmationEnabled = @json((bool) ($kotConfirmationEnabled ?? true));
        const ratingEnabled = @json((bool) ($ratingEnabled ?? true));
        const emojis = @json($emojis);
        const labels = @json($labels);
        const SOUND_ENABLED = @json((bool) ($soundEnabled ?? true));
        let currentView = 'home';
        let homeTab = 'open';
        let ratePromptId = null;
        let dismissedPrompts = new Set(JSON.parse(sessionStorage.getItem('dismissedRatePrompts') || '[]'));
        let lastReadyKotIds = [];
        let lastKitchenMap = {}; // id -> status
        let kitchenOrdersById = {};
        let notifReady = false;
        let lastQrIds = [];
        let qrSoundPrimed = false;
        const DRAFT_KEY = 'waiter_order_draft_v1';

        function registerWaiterSw() {
            if (!('serviceWorker' in navigator)) return;
            navigator.serviceWorker.register('/sw-waiter.js', { scope: '/waiter/' }).catch(() => {});
        }
        registerWaiterSw();

        async function ensurePushPermission() {
            if (!('Notification' in window)) return false;
            if (Notification.permission === 'granted') {
                notifReady = true;
                return true;
            }
            if (Notification.permission === 'denied') return false;
            try {
                const p = await Notification.requestPermission();
                notifReady = p === 'granted';
                return notifReady;
            } catch (e) {
                return false;
            }
        }

        function pushNotify(title, body, tag) {
            if (!notifReady && Notification.permission !== 'granted') return;
            notifReady = true;
            const payload = {
                type: 'notify',
                title,
                body,
                tag: tag || 'waiter-kitchen',
                url: location.origin + '/waiter/dashboard?view=kitchen',
                icon: '/pwa/waiter/icon-192.png',
                badge: '/pwa/waiter/icon-96.png',
            };
            // Prefer service worker so alerts work when the tab is in the background
            if (navigator.serviceWorker?.controller) {
                navigator.serviceWorker.controller.postMessage(payload);
                return;
            }
            if (navigator.serviceWorker) {
                navigator.serviceWorker.ready.then((reg) => {
                    reg.showNotification(title, {
                        body,
                        tag: payload.tag,
                        renotify: true,
                        icon: payload.icon,
                        data: { url: payload.url },
                    }).catch(() => {
                        try { new Notification(title, { body, tag: payload.tag }); } catch (e) {}
                    });
                }).catch(() => {
                    try { new Notification(title, { body, tag: payload.tag }); } catch (e) {}
                });
                return;
            }
            try { new Notification(title, { body, tag: payload.tag }); } catch (e) {}
        }

        function isAwayFromApp() {
            return document.hidden || !document.hasFocus();
        }

        function handleKitchenStatusChanges(orders) {
            const map = {};
            (orders || []).forEach((o) => { map[o.id] = o.status; });
            const prev = lastKitchenMap;
            const hadPrev = Object.keys(prev).length > 0;

            if (hadPrev) {
                (orders || []).forEach((o) => {
                    const before = prev[o.id];
                    if (!before) {
                        // New ticket appeared
                        if (isAwayFromApp()) {
                            pushNotify(
                                'New kitchen ticket',
                                (o.order_number || o.kot_number) + (o.table_name ? ' · ' + o.table_name : '') + ' · ' + o.status,
                                'kot-new-' + o.id
                            );
                        }
                        return;
                    }
                    if (before !== o.status) {
                        const title = o.status === 'ready'
                            ? 'Food READY to serve'
                            : ('Kitchen update: ' + o.status);
                        const body = (o.order_number || o.kot_number)
                            + (o.table_name ? ' · ' + o.table_name : '');
                        if (isAwayFromApp() || o.status === 'ready') {
                            pushNotify(title, body, 'kot-' + o.id + '-' + o.status);
                        }
                        if (o.status === 'ready') playWaiterBell();
                    }
                });
            }

            lastKitchenMap = map;
        }

        function peekDraft() {
            try {
                const d = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null');
                if (!d || !Array.isArray(d.cart) || !d.cart.length || !d.table?.id) return null;
                return d;
            } catch (e) { return null; }
        }

        function updateDraftFloat() {
            const el = document.getElementById('draftFloat');
            if (!el) return;
            const draft = peekDraft();
            el.classList.toggle('show', !!draft);
            document.body.classList.toggle('has-draft-float', !!draft);
            if (!draft) return;
            const count = draft.cart.reduce((s, i) => s + Number(i.quantity || 0), 0);
            const total = draft.cart.reduce((s, i) => {
                const add = (i.addons || []).reduce((a, x) => a + Number(x.price || 0), 0);
                return s + (Number(i.price || 0) + add) * Number(i.quantity || 0);
            }, 0);
            document.getElementById('draftFloatCount').textContent = count;
            document.getElementById('draftFloatTitle').textContent = (draft.table.name || 'Table') + ' · draft';
            document.getElementById('draftFloatMeta').textContent =
                count + ' item' + (count === 1 ? '' : 's') + ' · LKR ' + total.toFixed(2);
        }

        function cancelDraftOrder() {
            if (!confirm('Cancel draft order? Unsent items will be removed.')) return;
            sessionStorage.removeItem(DRAFT_KEY);
            updateDraftFloat();
            toast('Draft cancelled');
        }

        updateDraftFloat();
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                updateDraftFloat();
                pollQrOrders();
            }
        });

        // Browsers block sound until a tap — unlock both alerts
        function primeSounds() {
            if (qrSoundPrimed) return;
            qrSoundPrimed = true;
            ['waiterBell', 'qrBell'].forEach(id => {
                const a = document.getElementById(id);
                if (!a) return;
                a.muted = true;
                a.play().then(() => { a.pause(); a.currentTime = 0; a.muted = false; }).catch(() => { a.muted = false; });
            });
            ensurePushPermission();
        }
        document.addEventListener('pointerdown', primeSounds, { once: true });
        document.addEventListener('keydown', primeSounds, { once: true });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeKotPopup();
        });
        // Ask for notifications shortly after load (mobile often needs a tap first)
        setTimeout(() => { ensurePushPermission(); }, 1200);

        function toast(msg, err = false) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.classList.toggle('err', !!err);
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 2200);
        }

        function playWaiterBell() {
            if (!SOUND_ENABLED) return;
            const a = document.getElementById('waiterBell');
            if (!a) return;
            a.currentTime = 0;
            a.volume = 0.65;
            a.play().catch(() => {});
        }

        function playQrBell() {
            if (!SOUND_ENABLED) return;
            const a = document.getElementById('qrBell') || document.getElementById('waiterBell');
            if (!a) return;
            a.currentTime = 0;
            a.volume = 0.85;
            a.play().catch(() => {});
            // Second chime so it stands out from ready-food beep
            setTimeout(() => {
                a.currentTime = 0;
                a.play().catch(() => {});
            }, 700);
        }

        function escHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
            }[c]));
        }

        function pollQrOrders() {
            fetch('/waiter/qr-orders')
                .then(r => r.json())
                .then(data => {
                    const orders = data.orders || [];
                    const ids = orders.map(o => o.id);
                    const newly = ids.filter(id => !lastQrIds.includes(id));
                    // Sound for brand-new QR orders (skip first empty→seed load if we start with existing)
                    if (newly.length && lastQrIds.length) {
                        playQrBell();
                        toast(newly.length + ' new guest QR order' + (newly.length > 1 ? 's' : ''));
                        if (isAwayFromApp()) {
                            pushNotify('Guest QR order', newly.length + ' order(s) waiting for accept', 'qr-new');
                        }
                    } else if (newly.length && lastQrIds.length === 0 && ids.length) {
                        // First poll found pending — alert once so waiter notices
                        playQrBell();
                        if (isAwayFromApp()) {
                            pushNotify('Guest QR order waiting', orders[0].table + ' · ' + orders[0].order_number, 'qr-pending');
                        }
                    }
                    lastQrIds = ids;
                    renderQrPending(orders);
                })
                .catch(() => {});
        }

        function renderQrPending(orders) {
            const html = !orders.length ? '' : `
                <div class="qr-banner">
                    <div class="qr-head">
                        <strong><i class="fas fa-qrcode me-1"></i> Waiting for any waiter</strong>
                        <span class="badge qr">${orders.length}</span>
                    </div>
                    ${orders.map(o => `
                        <div class="qr-card">
                            <div class="row">
                                <div>
                                    <div class="num">${escHtml(o.table || 'Table')} · ${escHtml(o.order_number)}</div>
                                    <div class="meta">Code ${escHtml(o.qr_code || '—')} · ${escHtml(o.created_at || '')} · LKR ${Number(o.total || 0).toFixed(0)}</div>
                                </div>
                            </div>
                            <div class="items">${(o.items || []).map(i => escHtml(i.name) + ' ×' + i.quantity).join(' · ') || '—'}</div>
                            ${o.notes ? `<div class="notes">${escHtml(o.notes)}</div>` : ''}
                            <div class="qr-actions">
                                <button type="button" class="qr-accept" onclick="acceptQrOrder(${o.id})"><i class="fas fa-check me-1"></i>Accept &amp; send kitchen</button>
                                <button type="button" class="qr-reject" onclick="rejectQrOrder(${o.id})">Reject</button>
                            </div>
                        </div>
                    `).join('')}
                </div>`;

            const section = document.getElementById('qrPendingSection');
            const title = document.getElementById('qrSectionTitle');
            const badge = document.getElementById('badgeQr');
            const kitchenSection = document.getElementById('qrPendingSectionKitchen');
            const kitchenTitle = document.getElementById('qrSectionTitleKitchen');

            if (section) section.innerHTML = html;
            if (kitchenSection) kitchenSection.innerHTML = html;

            if (title) title.style.display = orders.length ? '' : 'none';
            if (kitchenTitle) kitchenTitle.style.display = orders.length ? '' : 'none';
            if (badge) {
                badge.style.display = orders.length ? 'inline-block' : 'none';
                badge.textContent = orders.length;
            }
        }

        function acceptQrOrder(id) {
            fetch('/waiter/qr-orders/' + id + '/accept', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    toast(data.message || 'Already taken or failed', true);
                    pollQrOrders();
                    return;
                }
                toast(data.message || 'Accepted — kitchen notified');
                lastQrIds = lastQrIds.filter(x => x !== id);
                pollQrOrders();
                if (currentView === 'kitchen') loadKitchen();
            })
            .catch(() => {
                toast('Accept failed', true);
                pollQrOrders();
            });
        }

        function rejectQrOrder(id) {
            if (!confirm('Reject this guest QR order?')) return;
            fetch('/waiter/qr-orders/' + id + '/reject', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                toast(data.message || (data.success ? 'Rejected' : 'Failed'), !data.success);
                pollQrOrders();
            })
            .catch(() => toast('Reject failed', true));
        }

        function showView(view) {
            if (view === 'kitchen' && !kotConfirmationEnabled) view = 'profile';
            if (view === 'rate' && !ratingEnabled) view = 'home';
            if (view === 'profile' && kotConfirmationEnabled) {
                // Profile only replaces Kitchen when confirmation is off
            }

            currentView = view;
            document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
            const panel = document.getElementById('view' + view.charAt(0).toUpperCase() + view.slice(1));
            if (panel) panel.classList.add('active');

            document.querySelectorAll('.bottom-nav .nav-btn[data-view]').forEach(b => {
                b.classList.toggle('active', b.getAttribute('data-view') === view);
            });

            history.replaceState({}, '', '?view=' + view);

            if (view === 'home') {
                loadHomeOrders(homeTab);
                pollQrOrders();
            } else if (view === 'kitchen') {
                loadKitchen();
                pollQrOrders();
            } else if (view === 'rate') {
                loadOrders('rate');
            } else if (view === 'profile') {
                fetch('/waiter/stats').then(r => r.json()).then(data => {
                    if (data.stats) updateStats(data.stats);
                }).catch(() => {});
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function switchHomeTab(tab, btn) {
            homeTab = tab;
            document.querySelectorAll('#viewHome .tab').forEach(t => t.classList.remove('active'));
            btn?.classList.add('active');
            loadHomeOrders(tab);
        }

        function updateStats(stats) {
            if (!stats) return;
            document.getElementById('statOpen').textContent = stats.open_today;
            document.getElementById('statDoneToday').textContent = stats.completed_today;
            const avgEl = document.getElementById('statAvg');
            if (avgEl) avgEl.textContent = stats.avg_rating !== null ? (stats.avg_rating + ' ★') : '—';
            const pOpen = document.getElementById('profileOpen');
            const pDone = document.getElementById('profileDone');
            const pAvg = document.getElementById('profileAvg');
            const pRatings = document.getElementById('profileRatings');
            if (pOpen) pOpen.textContent = stats.open_today;
            if (pDone) pDone.textContent = stats.completed_today;
            if (pAvg) pAvg.textContent = stats.avg_rating !== null ? (stats.avg_rating + '★') : '—';
            if (pRatings) pRatings.textContent = stats.ratings_today ?? 0;
            if (ratingEnabled) setNavBadge('navBadgeRate', stats.awaiting_rating);
            if (ratingEnabled) maybeShowRatePrompt(stats.rate_prompt);
        }

        function setNavBadge(id, count) {
            const el = document.getElementById(id);
            if (!el) return;
            const n = Number(count || 0);
            el.textContent = n > 0 ? String(n) : '';
            el.classList.toggle('show', n > 0);
        }

        function maybeShowRatePrompt(prompt) {
            if (!prompt?.id || dismissedPrompts.has(String(prompt.id))) return;
            showRateOverlay(prompt);
        }

        function showRateOverlay(prompt) {
            ratePromptId = prompt.id;
            document.getElementById('rateOverlayTitle').textContent = 'Rate ' + (prompt.order_number || 'this bill');
            document.getElementById('rateOverlaySub').textContent = (prompt.table ? prompt.table + ' · ' : '') + 'Ask the guest to tap a face';
            document.getElementById('rateOverlayEmojis').innerHTML = [1,2,3,4,5].map(n => `
                <button type="button" class="emoji-btn" onclick="rateOrder(${prompt.id}, ${n}, true)">
                    ${emojis[n]}<small>${labels[n] || ''}</small>
                </button>`).join('');
            document.getElementById('rateOverlay').classList.add('show');
        }

        function hideRateOverlay() {
            if (ratePromptId) {
                dismissedPrompts.add(String(ratePromptId));
                sessionStorage.setItem('dismissedRatePrompts', JSON.stringify([...dismissedPrompts]));
            }
            document.getElementById('rateOverlay').classList.remove('show');
        }

        function applyKitchenBadge(data) {
            const readyCount = data.ready_count || 0;
            const preparingCount = data.preparing_count || 0;
            const activeCount = data.active_count != null
                ? data.active_count
                : (readyCount + preparingCount);
            const stat = document.getElementById('statReadyKot');
            if (stat) stat.textContent = readyCount;
            // Badge = preparing + ready (so “1 preparing” shows 1)
            setNavBadge('navBadgeKitchen', activeCount);
            return { readyCount, preparingCount, activeCount };
        }

        function loadKitchen() {
            const list = document.getElementById('kitchenList');
            if (!list) return;
            fetch('/waiter/kitchen-orders')
                .then(r => r.json())
                .then(data => {
                    const orders = data.orders || [];
                    kitchenOrdersById = {};
                    orders.forEach(o => { kitchenOrdersById[o.id] = o; });
                    applyKitchenBadge(data);
                    handleKitchenStatusChanges(orders);

                    const readyIds = orders.filter(o => o.status === 'ready').map(o => o.id);
                    const newly = readyIds.filter(id => !lastReadyKotIds.includes(id));
                    if (newly.length && lastReadyKotIds.length) playWaiterBell();
                    lastReadyKotIds = readyIds;

                    if (!orders.length) {
                        if (data.confirmation_enabled === false) {
                            list.innerHTML = '<div class="empty">KOT confirmation is <strong>off</strong><br><span style="font-size:.9rem;opacity:.8;">Tickets print only — no Accept / Ready / Serve. Turn it on in Admin → Settings → Kitchen if needed.</span></div>';
                        } else {
                            list.innerHTML = '<div class="empty">No kitchen tickets right now</div>';
                        }
                        return;
                    }

                    list.innerHTML = orders.map(o => `
                        <div class="order-card" onclick="openKotPopup(${o.id})" role="button" tabindex="0">
                            <div class="row">
                                <div>
                                    <div class="num">${o.order_number || o.kot_number}</div>
                                    <div class="meta">${o.kot_number}${o.table_name ? ' · ' + o.table_name : ''} · ${o.elapsed}</div>
                                </div>
                                <span class="kot-status ${o.status}">${o.status}</span>
                            </div>
                            <div class="meta preview" style="margin-top:0.75rem;">
                                ${(o.items || []).map(i => i.name + ' ×' + i.quantity).join(' · ')}
                            </div>
                            ${o.can_serve ? `<button type="button" class="serve-btn" onclick="event.stopPropagation(); serveKot(${o.id})"><i class="fas fa-utensils me-2"></i>Serve — food given</button>` : ''}
                        </div>
                    `).join('');
                })
                .catch(() => {
                    list.innerHTML = '<div class="empty">Could not load kitchen orders</div>';
                });
        }

        function openKotPopup(id) {
            const o = kitchenOrdersById[id];
            if (!o) return;
            const title = o.order_number || o.kot_number || 'Ticket';
            document.getElementById('kotPopupLabel').textContent = (o.kot_number || 'Ticket') + (o.status ? ' · ' + o.status : '');
            document.getElementById('kotPopupTitle').textContent = title;
            document.getElementById('kotPopupMeta').textContent = [
                o.table_name || null,
                o.elapsed ? o.elapsed + ' ago' : null,
                (o.items || []).length ? ((o.items || []).length + ' item' + ((o.items || []).length === 1 ? '' : 's')) : null,
            ].filter(Boolean).join(' · ');

            const items = o.items || [];
            document.getElementById('kotPopupItems').innerHTML = items.length
                ? items.map(i => `
                    <div class="kot-item">
                        <div class="qty">×${Number(i.quantity)}</div>
                        <div>
                            <div class="name">${escapeHtml(i.name || 'Item')}</div>
                            ${i.special_instructions ? `<div class="note"><i class="fas fa-comment-dots me-1"></i>${escapeHtml(i.special_instructions)}</div>` : ''}
                        </div>
                    </div>
                `).join('')
                : '<div class="empty" style="padding:1rem;">No items on this ticket</div>';

            const foot = document.getElementById('kotPopupFoot');
            if (o.can_serve) {
                foot.innerHTML = `<button type="button" class="serve-btn" onclick="serveKot(${o.id}, true)"><i class="fas fa-utensils me-2"></i>Serve — food given</button>`;
            } else {
                foot.innerHTML = `<div class="ks-hint">${o.status === 'preparing' || o.status === 'pending' ? 'Kitchen is still preparing — tap Serve when Ready' : 'Tap outside to close'}</div>`;
            }

            document.getElementById('kotPopup').classList.add('show');
        }

        function closeKotPopup() {
            document.getElementById('kotPopup')?.classList.remove('show');
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function serveKot(id, fromPopup) {
            fetch('/waiter/kitchen-orders/' + id + '/served', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { toast(data.message || 'Failed', true); return; }
                toast(data.message || 'Served');
                if (fromPopup) closeKotPopup();
                loadKitchen();
            })
            .catch(() => toast('Serve failed', true));
        }

        function loadHomeOrders(tab) {
            const list = document.getElementById('homeOrderList');
            if (!list) return;
            list.innerHTML = '<div class="empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div>';
            fetch('/waiter/my-orders?tab=' + encodeURIComponent(tab))
                .then(r => r.json())
                .then(data => {
                    if (data.stats) updateStats(data.stats);
                    renderOrderCards(list, data.orders || [], tab);
                })
                .catch(() => {
                    list.innerHTML = '<div class="empty">Could not load orders</div>';
                });
        }

        function loadOrders(tab) {
            const list = document.getElementById('rateList');
            if (!list) return;
            list.innerHTML = '<div class="empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div>';
            fetch('/waiter/my-orders?tab=' + encodeURIComponent(tab))
                .then(r => r.json())
                .then(data => {
                    if (data.stats) updateStats(data.stats);
                    renderOrderCards(list, data.orders || [], tab);
                })
                .catch(() => {
                    list.innerHTML = '<div class="empty">Could not load orders</div>';
                });
        }

        function renderOrderCards(list, orders, tab) {
            if (!orders.length) {
                list.innerHTML = '<div class="empty">Nothing here</div>';
                return;
            }
            list.innerHTML = orders.map(o => {
                let actions = '';
                if (tab === 'open') {
                    actions = `<button type="button" class="edit-btn" onclick="editOpenOrder(${o.id})"><i class="fas fa-pen"></i> Edit Order</button>`;
                    if (bringBillEnabled && o.can_request_bill) {
                        actions += `<button type="button" class="bring-btn" onclick="requestBill(${o.id})">Bring bill</button>`;
                    } else if (bringBillEnabled && o.bill_requested) {
                        actions += `<div class="waiting-cashier">Waiting for cashier</div>`;
                    }
                } else if (tab === 'rate' && o.can_rate) {
                    actions = `
                        <div class="emoji-row">${[1,2,3,4,5].map(n => `
                            <button type="button" class="emoji-btn" onclick="rateOrder(${o.id}, ${n})">${emojis[n]}<small>${labels[n]||''}</small></button>`).join('')}
                        </div>
                        <div class="rate-share">
                            <button type="button" class="share-btn" onclick="showRateQr(${o.id})"><i class="fas fa-qrcode"></i> Guest QR</button>
                            <button type="button" class="share-btn" onclick="copyRateLink(${o.id})"><i class="fas fa-link"></i> Copy link</button>
                            <button type="button" class="share-btn wa" onclick="shareRateWhatsApp(${o.id})"><i class="fab fa-whatsapp"></i> WhatsApp</button>
                        </div>
                        <div class="rate-qr-box" id="rateQr${o.id}" style="display:none;"></div>`;
                } else if (o.emoji) {
                    actions = `<div class="rated">${o.emoji}</div>`;
                }
                return `<div class="order-card">
                    <div class="row">
                        <div>
                            <div class="num">${o.order_number}</div>
                            <div class="meta">${o.table || '—'} · ${o.items_count || 0} items · ${o.created_at || o.completed_at || ''}</div>
                        </div>
                        <span class="badge ${o.payment_status==='paid'?'done':''}">${o.payment_status || o.status}</span>
                    </div>
                    ${actions}
                </div>`;
            }).join('');
        }

        function editOpenOrder(orderId) {
            window.location.href = @json(route('waiter.index')) + '?edit_order=' + encodeURIComponent(orderId);
        }

        function requestBill(orderId) {
            fetch('/waiter/request-bill/' + orderId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(r => r.json()).then(data => {
                toast(data.message || (data.success ? 'Sent' : 'Failed'), !data.success);
                if (data.stats) updateStats(data.stats);
                loadHomeOrders('open');
            });
        }

        function rateOrder(orderId, rating, fromOverlay) {
            fetch('/waiter/rate/' + orderId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ rating }),
            }).then(r => r.json()).then(data => {
                toast(data.message || (data.success ? 'Thanks!' : 'Failed'), !data.success);
                if (fromOverlay) hideRateOverlay();
                if (data.stats) updateStats(data.stats);
                if (currentView === 'rate') loadOrders('rate');
            });
        }

        async function fetchRateLink(orderId) {
            const r = await fetch('/waiter/rate-link/' + orderId, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (!data.success) throw new Error(data.message || 'Failed');
            if (data.stats) updateStats(data.stats);
            return data;
        }

        function showRateQr(orderId) {
            const box = document.getElementById('rateQr' + orderId);
            if (!box) return;
            box.style.display = 'block';
            box.innerHTML = '<div class="empty" style="padding:0.5rem;">Loading QR…</div>';
            fetchRateLink(orderId).then(data => {
                box.innerHTML = `
                    <img src="${data.qr_image}" alt="Rate QR" style="width:160px;height:160px;border-radius:12px;background:#fff;padding:6px;">
                    <div class="meta" style="margin-top:0.5rem;word-break:break-all;">${data.rate_url}</div>
                    <div class="rate-share" style="margin-top:0.55rem;">
                        <button type="button" class="share-btn" onclick="navigator.clipboard.writeText('${data.rate_url.replace(/'/g, "\\'")}').then(()=>toast('Link copied'))">Copy</button>
                        <a class="share-btn wa" href="${data.whatsapp_url}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> Send</a>
                    </div>`;
            }).catch(e => {
                box.innerHTML = '';
                toast(e.message || 'QR failed', true);
            });
        }

        function copyRateLink(orderId) {
            fetchRateLink(orderId).then(data => {
                navigator.clipboard.writeText(data.rate_url).then(() => toast('Rating link copied'));
            }).catch(e => toast(e.message || 'Failed', true));
        }

        function shareRateWhatsApp(orderId) {
            fetchRateLink(orderId).then(data => {
                window.open(data.whatsapp_url, '_blank');
            }).catch(e => toast(e.message || 'Failed', true));
        }

        function refreshStats() {
            fetch('/waiter/stats').then(r => r.json()).then(data => {
                if (data.stats) updateStats(data.stats);
            }).catch(() => {});
        }

        // Restore view from URL (?view=kitchen|rate|home)
        const startView = new URLSearchParams(location.search).get('view');
        const allowedViews = ['home', 'profile'];
        if (kotConfirmationEnabled) allowedViews.push('kitchen');
        if (ratingEnabled) allowedViews.push('rate');
        if (allowedViews.includes(startView)) {
            showView(startView);
        } else {
            showView('home');
        }
        if (ratingEnabled) setNavBadge('navBadgeRate', {{ (int) $stats['awaiting_rating'] }});

        smartWaiterInterval(() => {
            pollQrOrders();
            refreshStats();
            if (currentView === 'kitchen' && kotConfirmationEnabled) {
                loadKitchen();
            } else if (currentView === 'rate' && ratingEnabled) {
                loadOrders('rate');
            } else if (currentView === 'home') {
                loadHomeOrders(homeTab);
            }

            if (kotConfirmationEnabled && currentView !== 'kitchen') {
                fetch('/waiter/kitchen-orders').then(r => r.json()).then(data => {
                    applyKitchenBadge(data);
                    handleKitchenStatusChanges(data.orders || []);
                    const readyIds = (data.orders || []).filter(o => o.status === 'ready').map(o => o.id);
                    const newly = readyIds.filter(id => !lastReadyKotIds.includes(id));
                    if (newly.length && lastReadyKotIds.length) playWaiterBell();
                    lastReadyKotIds = readyIds;
                }).catch(() => {});
            }
        }, 4000);

        if (kotConfirmationEnabled) {
            fetch('/waiter/kitchen-orders').then(r => r.json()).then(data => {
                applyKitchenBadge(data);
                handleKitchenStatusChanges(data.orders || []);
                lastReadyKotIds = (data.orders || []).filter(o => o.status === 'ready').map(o => o.id);
            }).catch(() => {});
        }
    </script>
    @include('partials.waiter-pwa-install')
</body>
</html>
