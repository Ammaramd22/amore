<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Sign in — QRPOS By Avenque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <style>
        :root {
            --ink: #1c1410;
            --ink-soft: #3d2f26;
            --cream: #fff8f1;
            --muted: #8a7363;
            --line: rgba(28, 20, 16, 0.12);
            --amber: #e8890c;
            --amber-deep: #c45f08;
            --ember: #9a3412;
            --focus: #e8890c;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: Outfit, system-ui, sans-serif;
            color: var(--ink);
            background: var(--ink);
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
        }

        .hero {
            position: relative; overflow: hidden;
            padding: clamp(1.5rem, 4vw, 3.5rem);
            display: flex; flex-direction: column; justify-content: space-between;
            color: #fff8f1;
            background:
                radial-gradient(ellipse 80% 60% at 20% 80%, rgba(232, 137, 12, 0.35), transparent 55%),
                radial-gradient(ellipse 50% 40% at 90% 10%, rgba(154, 52, 18, 0.45), transparent 50%),
                linear-gradient(155deg, #1c1410 0%, #2a1a12 42%, #3d2314 100%);
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(-18deg, transparent, transparent 48px, rgba(255,248,241,0.02) 48px, rgba(255,248,241,0.02) 49px);
            pointer-events: none;
        }
        .brand { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: flex-start; gap: 1rem; max-width: 22rem; }
        .brand-mark {
            width: clamp(88px, 14vw, 120px); height: clamp(88px, 14vw, 120px);
            border-radius: 22px; display: grid; place-items: center;
            background: #0a0a0a;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
            overflow: hidden; border: 1px solid rgba(255, 248, 241, 0.12);
        }
        .brand-mark img { width: 100%; height: 100%; object-fit: contain; display: block; background: #0a0a0a; }
        .brand-mark .q-fallback { font-weight: 800; color: #fff; letter-spacing: 0.04em; font-size: 2rem; }
        .brand-text { display: flex; flex-direction: column; gap: 0.35rem; }
        .brand-name {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.55rem, 2.8vw, 2.05rem); font-weight: 700;
            letter-spacing: -0.02em; line-height: 1.1; margin: 0;
        }
        .brand-by {
            font-size: 0.78rem; font-weight: 700; letter-spacing: 0.16em;
            text-transform: uppercase; color: #60a5fa;
        }
        .brand-support {
            display: flex; flex-direction: column; gap: 0.35rem; margin-top: 0.35rem;
        }
        .brand-support a {
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: rgba(255, 248, 241, 0.82); text-decoration: none;
            font-size: 0.92rem; font-weight: 500;
        }
        .brand-support a:hover { color: #fbbf24; }
        .brand-support i { color: var(--amber); width: 1rem; text-align: center; font-size: 0.85rem; }

        .hero-copy { position: relative; z-index: 1; max-width: 28rem; margin-top: auto; padding-bottom: 0.5rem; }
        .hero-copy h1 {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(2rem, 4.2vw, 3.25rem); font-weight: 700;
            line-height: 1.08; letter-spacing: -0.03em; margin: 0 0 1rem;
        }
        .hero-copy p { margin: 0; font-size: 1.05rem; line-height: 1.55; color: rgba(255, 248, 241, 0.78); max-width: 26rem; }
        .roles { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1.75rem; }
        .role-chip {
            display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.85rem;
            border-radius: 999px; font-size: 0.8rem; font-weight: 600; color: #fff8f1;
            background: rgba(255, 248, 241, 0.08); border: 1px solid rgba(255, 248, 241, 0.14);
        }
        .role-chip i { color: var(--amber); font-size: 0.75rem; }

        .panel {
            display: flex; align-items: center; justify-content: center;
            padding: clamp(1.5rem, 4vw, 3rem);
            background: linear-gradient(180deg, #fff8f1 0%, #f3e6d8 100%);
        }
        .panel-inner { width: 100%; max-width: 400px; }
        .panel-brand { display: none; }
        .panel-logo {
            width: 72px; height: 72px; border-radius: 18px; overflow: hidden; margin: 0 auto 1rem;
            background: #0a0a0a; display: grid; place-items: center;
            box-shadow: 0 10px 24px rgba(28, 20, 16, 0.12);
            border: 1px solid rgba(28, 20, 16, 0.08);
        }
        .panel-logo img { width: 100%; height: 100%; object-fit: contain; }
        .panel-kicker {
            font-size: 0.75rem; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; color: var(--amber-deep); margin-bottom: 0.5rem;
            text-align: center;
        }
        .panel-title {
            font-family: Fraunces, Georgia, serif; font-size: 2rem; font-weight: 700;
            letter-spacing: -0.02em; margin: 0 0 0.35rem; color: var(--ink); text-align: center;
        }
        .panel-sub { margin: 0 0 1.25rem; color: var(--muted); font-size: 0.95rem; line-height: 1.5; text-align: center; }
        .alert {
            background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca;
            border-radius: 12px; padding: 0.85rem 1rem; font-size: 0.9rem; margin-bottom: 1.25rem;
        }
        .auth-tabs {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem;
            background: rgba(28, 20, 16, 0.06); border-radius: 14px; padding: 0.35rem; margin-bottom: 1.35rem;
        }
        .auth-tab {
            border: 0; background: transparent; border-radius: 11px; padding: 0.7rem 0.5rem;
            font: inherit; font-weight: 700; font-size: 0.9rem; color: var(--muted); cursor: pointer;
            min-height: 44px;
        }
        .auth-tab.active {
            background: #fff; color: var(--ink);
            box-shadow: 0 4px 14px rgba(28, 20, 16, 0.08);
        }
        .auth-pane { display: none; }
        .auth-pane.active { display: block; }
        .field { margin-bottom: 1.1rem; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--ink-soft); margin-bottom: 0.4rem; }
        .field-wrap {
            display: flex; align-items: center; gap: 0.65rem; background: #fff;
            border: 1.5px solid var(--line); border-radius: 12px; padding: 0 0.95rem;
        }
        .field-wrap:focus-within { border-color: var(--focus); box-shadow: 0 0 0 3px rgba(232, 137, 12, 0.2); }
        .field-wrap i { color: var(--muted); font-size: 0.9rem; width: 1rem; text-align: center; }
        .field-wrap input {
            flex: 1; border: 0; outline: 0; background: transparent; padding: 0.95rem 0;
            font: inherit; font-size: 1rem; color: var(--ink); min-width: 0;
        }
        .row-between { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 0.25rem 0 1.35rem; }
        .remember { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--ink-soft); cursor: pointer; user-select: none; }
        .remember input { width: 1.05rem; height: 1.05rem; accent-color: var(--amber-deep); cursor: pointer; }
        .btn-signin {
            width: 100%; border: 0; border-radius: 14px; padding: 1rem 1.25rem;
            font: inherit; font-size: 1.02rem; font-weight: 700; color: #fff; cursor: pointer;
            background: linear-gradient(135deg, var(--amber) 0%, var(--amber-deep) 55%, var(--ember) 100%);
            box-shadow: 0 10px 24px rgba(196, 95, 8, 0.28);
            min-height: 52px;
        }
        .btn-signin:hover { filter: brightness(1.05); }
        .btn-signin:disabled { opacity: 0.55; cursor: not-allowed; filter: none; }
        .pin-dots {
            display: flex; justify-content: center; gap: 0.75rem; margin: 0.15rem 0 1.25rem; min-height: 1.35rem;
        }
        .pin-step-label {
            text-align: center; font-size: 0.92rem; font-weight: 600;
            color: var(--ink-soft); margin: 0 0 0.2rem;
        }
        .pin-dot {
            width: 16px; height: 16px; border-radius: 50%;
            border: 2px solid rgba(28, 20, 16, 0.22); background: transparent;
            transition: 0.12s ease;
        }
        .pin-dot.filled { background: var(--amber-deep); border-color: var(--amber-deep); transform: scale(1.08); }
        .keypad {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.55rem; margin-bottom: 1rem;
        }
        .key {
            border: 0; border-radius: 16px; min-height: 64px; font: inherit; font-size: 1.45rem; font-weight: 700;
            color: var(--ink); background: #fff; cursor: pointer;
            box-shadow: 0 2px 0 rgba(28, 20, 16, 0.06); border: 1px solid var(--line);
            transition: transform 0.1s ease, background 0.1s ease;
            -webkit-tap-highlight-color: transparent; user-select: none;
        }
        .key:active { transform: scale(0.96); background: #fff7ed; }
        .key.muted { color: var(--muted); font-size: 1.05rem; }
        .key.action { color: var(--amber-deep); }
        .demo { margin-top: 1.5rem; padding-top: 1.15rem; border-top: 1px solid var(--line); }
        .demo-title { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.75rem; }
        .demo-list { display: grid; gap: 0.45rem; }
        .demo-item {
            display: flex; justify-content: space-between; gap: 0.75rem; align-items: baseline;
            font-size: 0.82rem; color: var(--ink-soft); background: rgba(255,255,255,0.55);
            border-radius: 10px; padding: 0.55rem 0.75rem;
        }
        .demo-item span:first-child { font-weight: 600; }
        .demo-item code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem; color: var(--muted); }
        .foot-note { margin-top: 1.25rem; text-align: center; font-size: 0.8rem; color: var(--muted); line-height: 1.55; }
        .foot-note a { color: var(--amber-deep); text-decoration: none; font-weight: 600; }
        .foot-note a:hover { text-decoration: underline; }
        .support-bar { display: none; }

        /* ===== Mobile login ===== */
        @media (max-width: 900px) {
            body {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                background: linear-gradient(180deg, #1c1410 0%, #2a1a12 28%, #fff8f1 28%);
            }

            .hero {
                min-height: 0;
                padding: calc(0.85rem + var(--safe-top)) 1.15rem 1.15rem;
                justify-content: flex-start;
                gap: 0;
            }
            .hero-copy { display: none; }

            .brand {
                width: 100%;
                max-width: none;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.75rem;
            }
            .brand-mark {
                width: 84px; height: 84px; border-radius: 20px;
                animation: logoIn 0.45s ease both;
            }
            .brand-text { align-items: center; gap: 0.25rem; }
            .brand-name {
                font-size: 1.65rem;
                animation: fadeUp 0.4s ease 0.08s both;
            }
            .brand-by { animation: fadeUp 0.4s ease 0.14s both; }
            .brand-support {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.35rem 1rem;
                margin-top: 0.55rem;
                animation: fadeUp 0.4s ease 0.2s both;
            }
            .brand-support a {
                font-size: 0.84rem;
                padding: 0.35rem 0.55rem;
                border-radius: 999px;
                background: rgba(255, 248, 241, 0.08);
                border: 1px solid rgba(255, 248, 241, 0.12);
            }

            .panel {
                align-items: stretch;
                justify-content: flex-start;
                padding: 0 0 calc(1rem + var(--safe-bottom));
                background: transparent;
            }
            .panel-inner {
                max-width: none;
                margin: 0 0.85rem;
                padding: 1.25rem 1.1rem 1.15rem;
                background: #fff8f1;
                border-radius: 22px 22px 18px 18px;
                box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.18);
                animation: sheetUp 0.4s ease 0.1s both;
            }
            .panel-logo,
            .desktop-only-logo { display: none !important; }
            .panel-brand { display: none; }
            .panel-kicker { margin-bottom: 0.25rem; font-size: 0.7rem; }
            .panel-title { font-size: 1.65rem; margin-bottom: 0.2rem; }
            .panel-sub { font-size: 0.88rem; margin-bottom: 1rem; }

            .auth-tabs { margin-bottom: 1rem; }
            .pin-dots { margin-bottom: 0.95rem; }
            .keypad { gap: 0.5rem; margin-bottom: 0.85rem; }
            .key {
                min-height: 58px;
                border-radius: 14px;
                font-size: 1.35rem;
                box-shadow: 0 1px 0 rgba(28, 20, 16, 0.05);
            }

            .demo { display: none; }
            .foot-note { display: none; }

            .support-bar {
                display: flex;
                justify-content: center;
                gap: 0.85rem;
                flex-wrap: wrap;
                margin-top: 1rem;
                padding-top: 0.85rem;
                border-top: 1px solid var(--line);
            }
            .support-bar a {
                display: inline-flex; align-items: center; gap: 0.4rem;
                color: var(--amber-deep); text-decoration: none;
                font-size: 0.82rem; font-weight: 600;
            }
            .support-bar i { font-size: 0.78rem; }
        }

        @media (max-width: 900px) and (max-height: 760px) {
            .brand-mark { width: 68px; height: 68px; border-radius: 16px; }
            .brand-name { font-size: 1.4rem; }
            .brand-support { margin-top: 0.35rem; }
            .panel-inner { padding-top: 1rem; }
            .panel-title { font-size: 1.45rem; }
            .panel-sub { display: none; }
            .key { min-height: 50px; font-size: 1.25rem; }
            .btn-signin { min-height: 48px; padding: 0.85rem 1rem; }
        }

        @keyframes logoIn {
            from { opacity: 0; transform: scale(0.88); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes sheetUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .brand-mark, .brand-name, .brand-by, .brand-support, .panel-inner { animation: none !important; }
        }
    </style>
</head>
<body>
    @php
        $posName = 'QRPOS';
        $posBy = 'By Avenque';
        $supportEmail = 'qrpos@avenque.io';
        $supportPhone = '076 822 2201';
        $supportPhoneTel = '+94768222201';
        $logoUrl = \App\Models\Setting::logoUrl();
    @endphp
    <aside class="hero">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $posName }}">
                @else
                    <span class="q-fallback">Q</span>
                @endif
            </div>
            <div class="brand-text">
                <div class="brand-name">{{ $posName }}</div>
                <div class="brand-by">{{ $posBy }}</div>
                <div class="brand-support">
                    <a href="mailto:{{ $supportEmail }}"><i class="fas fa-envelope" aria-hidden="true"></i>{{ $supportEmail }}</a>
                    <a href="tel:{{ $supportPhoneTel }}"><i class="fas fa-phone" aria-hidden="true"></i>{{ $supportPhone }}</a>
                </div>
            </div>
        </div>
        <div class="hero-copy">
            <h1>One login.<br>Your floor, your screen.</h1>
            <p>Use your 4-digit PIN on the keypad, or switch to email &amp; password.</p>
            <div class="roles">
                <span class="role-chip"><i class="fas fa-hashtag"></i> 4-digit PIN</span>
                <span class="role-chip"><i class="fas fa-cash-register"></i> Cashier / Admin</span>
                <span class="role-chip"><i class="fas fa-user-tie"></i> Waiter</span>
            </div>
        </div>
    </aside>

    <main class="panel">
        <div class="panel-inner">
            <div class="panel-logo desktop-only-logo" aria-hidden="true">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $posName }}">
                @else
                    <span class="q-fallback" style="color:#fff;font-weight:800;">Q</span>
                @endif
            </div>
            <div class="panel-kicker">{{ $posName }} · Staff access</div>
            <h2 class="panel-title">Sign in</h2>
            <p class="panel-sub">Staff ID + PIN, or email/username with password.</p>

            @if($errors->any())
                <div class="alert" role="alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                    @if($errors->has('pin'))
                        <div style="margin-top:8px;font-weight:700;">
                            Software owner / admin? Click <button type="button" onclick="switchAuthTab('account')" style="all:unset;cursor:pointer;color:#9a3412;text-decoration:underline;font-weight:800;">Account</button> and use email + password.
                        </div>
                    @endif
                </div>
            @endif

            @php
                $startAccount = $errors->has('login') || $errors->has('email');
            @endphp

            <div class="auth-tabs" role="tablist">
                <button type="button" class="auth-tab {{ $startAccount ? '' : 'active' }}" data-tab="pin" onclick="switchAuthTab('pin')">Staff ID</button>
                <button type="button" class="auth-tab {{ $startAccount ? 'active' : '' }}" data-tab="account" onclick="switchAuthTab('account')">Account</button>
            </div>

            <div id="pane-pin" class="auth-pane {{ $startAccount ? '' : 'active' }}">
                <form method="POST" action="{{ route('login.pin') }}" id="pinLoginForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="login_code" id="loginCodeValue" value="">
                    <input type="hidden" name="pin" id="pinValue" value="">

                    <div class="pin-step-label" id="pinStepLabel">Enter 4-digit Staff ID</div>
                    <div class="pin-dots" id="pinDots" aria-live="polite" aria-label="4 digits">
                        <span class="pin-dot"></span>
                        <span class="pin-dot"></span>
                        <span class="pin-dot"></span>
                        <span class="pin-dot"></span>
                    </div>
                    <div class="keypad" aria-label="PIN keypad">
                        <button type="button" class="key" data-key="1">1</button>
                        <button type="button" class="key" data-key="2">2</button>
                        <button type="button" class="key" data-key="3">3</button>
                        <button type="button" class="key" data-key="4">4</button>
                        <button type="button" class="key" data-key="5">5</button>
                        <button type="button" class="key" data-key="6">6</button>
                        <button type="button" class="key" data-key="7">7</button>
                        <button type="button" class="key" data-key="8">8</button>
                        <button type="button" class="key" data-key="9">9</button>
                        <button type="button" class="key muted" data-key="clear">C</button>
                        <button type="button" class="key" data-key="0">0</button>
                        <button type="button" class="key action" data-key="back"><i class="fas fa-backspace"></i></button>
                    </div>
                    <button type="button" class="btn-signin" id="pinNextBtn" style="display:none" onclick="pinGoToPinStep()">Continue to PIN</button>
                    <button type="submit" class="btn-signin" id="pinSubmitBtn" disabled>Sign in</button>
                    <button type="button" class="btn-signin" id="pinBackBtn" style="display:none;margin-top:.5rem;background:transparent;border:1px solid var(--line);color:var(--ink)" onclick="pinBackToId()">Change Staff ID</button>
                </form>
            </div>

            <div id="pane-account" class="auth-pane {{ $startAccount ? 'active' : '' }}">
                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="field">
                        <label for="login">Email or username</label>
                        <div class="field-wrap">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input id="login" type="text" name="login" value="{{ old('login', old('email')) }}" placeholder="you@restaurant.lk or username" autocomplete="username" {{ $startAccount ? 'autofocus' : '' }}>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <input id="password" type="password" name="password" placeholder="••••••••" autocomplete="current-password">
                        </div>
                    </div>
                    <div class="row-between">
                        <label class="remember" for="remember">
                            <input type="checkbox" name="remember" id="remember" value="1">
                            Remember me
                        </label>
                    </div>
                    <button type="submit" class="btn-signin">Sign in</button>
                </form>
            </div>

            <div class="support-bar">
                <a href="mailto:{{ $supportEmail }}"><i class="fas fa-envelope"></i>{{ $supportEmail }}</a>
                <a href="tel:{{ $supportPhoneTel }}"><i class="fas fa-phone"></i>{{ $supportPhone }}</a>
            </div>

            <div class="demo">
                <div class="demo-title">Login options</div>
                <div class="demo-list">
                    <div class="demo-item"><span>Staff ID</span><code>4-digit ID + 4-digit PIN</code></div>
                    <div class="demo-item"><span>Account</span><code>Email or username + password</code></div>
                </div>
            </div>
            <p class="foot-note">
                QRPOS By Avenque<br>
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                ·
                <a href="tel:{{ $supportPhoneTel }}">{{ $supportPhone }}</a>
            </p>
        </div>
    </main>

<script>
let pinStep = 'id'; // id | pin
let idBuffer = '';
let pinBuffer = '';
const pinMax = 4;

function switchAuthTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });
    document.getElementById('pane-pin').classList.toggle('active', tab === 'pin');
    document.getElementById('pane-account').classList.toggle('active', tab === 'account');
}

function currentBuffer() {
    return pinStep === 'id' ? idBuffer : pinBuffer;
}

function setCurrentBuffer(v) {
    if (pinStep === 'id') idBuffer = v;
    else pinBuffer = v;
}

function renderPinDots() {
    const buf = currentBuffer();
    const dots = document.querySelectorAll('#pinDots .pin-dot');
    dots.forEach((dot, i) => dot.classList.toggle('filled', i < buf.length));
    document.getElementById('loginCodeValue').value = idBuffer;
    document.getElementById('pinValue').value = pinBuffer;
    document.getElementById('pinStepLabel').textContent = pinStep === 'id'
        ? 'Enter 4-digit Staff ID'
        : 'Enter 4-digit PIN';
    document.getElementById('pinSubmitBtn').disabled = !(idBuffer.length === 4 && pinBuffer.length === 4);
    document.getElementById('pinNextBtn').style.display = (pinStep === 'id' && idBuffer.length === 4) ? 'block' : 'none';
    document.getElementById('pinSubmitBtn').style.display = pinStep === 'pin' ? 'block' : 'none';
    document.getElementById('pinBackBtn').style.display = pinStep === 'pin' ? 'block' : 'none';
}

function pinGoToPinStep() {
    if (idBuffer.length !== 4) return;
    pinStep = 'pin';
    pinBuffer = '';
    renderPinDots();
}

function pinBackToId() {
    pinStep = 'id';
    pinBuffer = '';
    renderPinDots();
}

function pushPin(digit) {
    let buf = currentBuffer();
    if (buf.length >= pinMax) return;
    buf += digit;
    setCurrentBuffer(buf);
    renderPinDots();
    if (pinStep === 'id' && buf.length === pinMax) {
        setTimeout(pinGoToPinStep, 120);
    } else if (pinStep === 'pin' && buf.length === pinMax && idBuffer.length === pinMax) {
        document.getElementById('pinLoginForm').requestSubmit();
    }
}

function clearPin() {
    setCurrentBuffer('');
    renderPinDots();
}

function backPin() {
    setCurrentBuffer(currentBuffer().slice(0, -1));
    renderPinDots();
}

document.querySelectorAll('.keypad .key').forEach(btn => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.key;
        if (key === 'clear') clearPin();
        else if (key === 'back') backPin();
        else pushPin(key);
    });
});

document.addEventListener('keydown', (e) => {
    if (!document.getElementById('pane-pin').classList.contains('active')) return;
    if (e.key >= '0' && e.key <= '9') {
        e.preventDefault();
        pushPin(e.key);
    } else if (e.key === 'Backspace') {
        e.preventDefault();
        backPin();
    } else if (e.key === 'Escape') {
        clearPin();
    } else if (e.key === 'Enter' && idBuffer.length === 4 && pinBuffer.length === 4) {
        e.preventDefault();
        document.getElementById('pinLoginForm').requestSubmit();
    }
});

renderPinDots();
</script>
</body>
</html>
