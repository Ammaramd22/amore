@php
    $pwa = \App\Models\Setting::pwaConfig();
@endphp
<style>
    #waiterPwaSplash {
        position: fixed; inset: 0; z-index: 99999;
        display: none; flex-direction: column; align-items: center; justify-content: center;
        background: {{ $pwa['background_color'] }};
        color: #fff8f1; text-align: center;
        padding: calc(2rem + env(safe-area-inset-top, 0px)) 1.5rem calc(2rem + env(safe-area-inset-bottom, 0px));
        transition: opacity 0.35s ease, visibility 0.35s ease;
    }
    #waiterPwaSplash.show { display: flex; }
    #waiterPwaSplash.hide { opacity: 0; visibility: hidden; pointer-events: none; }
    #waiterPwaSplash .splash-media {
        width: min(68vw, 240px); aspect-ratio: 1; max-height: 42vh; border-radius: 22px; overflow: hidden;
        background: rgba(0,0,0,0.25); display: grid; place-items: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.35); margin-bottom: 1.1rem;
    }
    #waiterPwaSplash .splash-media.is-splash {
        width: min(86vw, 320px); aspect-ratio: 9 / 16; max-height: 58vh; border-radius: 18px;
    }
    #waiterPwaSplash .splash-media img {
        width: 100%; height: 100%; object-fit: {{ $pwa['splash'] ? 'cover' : 'contain' }};
        display: block;
    }
    #waiterPwaSplash .splash-name {
        font-family: Fraunces, Georgia, ui-serif, serif; font-size: clamp(1.25rem, 5vw, 1.55rem); font-weight: 700;
        letter-spacing: -0.02em; margin: 0 0 0.35rem; padding: 0 0.5rem;
    }
    #waiterPwaSplash .splash-by {
        font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
        text-transform: uppercase; color: #60a5fa; margin: 0;
    }
</style>
<div id="waiterPwaSplash" aria-hidden="true">
    <div class="splash-media {{ $pwa['splash'] ? 'is-splash' : '' }}">
        <img src="{{ $pwa['splash'] ?: $pwa['logo'] }}" alt="{{ $pwa['name'] }}">
    </div>
    <p class="splash-name">{{ $pwa['name'] }}</p>
    <p class="splash-by">QRPOS By Avenque</p>
</div>
<script>
(function () {
    const el = document.getElementById('waiterPwaSplash');
    if (!el) return;

    const params = new URLSearchParams(location.search);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const fromPwa = params.get('source') === 'pwa';
    // Only splash when launched as installed app (or explicit PWA start URL) — not every mobile browser tap
    const shouldSplash = isStandalone || fromPwa;

    if (!shouldSplash) {
        el.remove();
        return;
    }

    const onceKey = 'qrpos_waiter_splash_once';
    try {
        if (sessionStorage.getItem(onceKey) === '1') {
            el.remove();
            return;
        }
    } catch (e) {}

    el.classList.add('show');
    const hide = () => {
        el.classList.add('hide');
        try { sessionStorage.setItem(onceKey, '1'); } catch (e) {}
        setTimeout(() => el.remove(), 400);
    };
    window.addEventListener('load', () => setTimeout(hide, 1100));
    setTimeout(hide, 2200);
})();
</script>
