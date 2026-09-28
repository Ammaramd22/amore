{{-- Install / Add to Home Screen banner for QRPOS Waiter Panel --}}
@php $pwaShort = \App\Models\Setting::get('pwa_app_short_name', 'QRPOS Waiter'); @endphp
<style>
    #waiterPwaInstall {
        display: none;
        position: fixed; left: 12px; right: 12px;
        bottom: calc(88px + env(safe-area-inset-bottom, 0px));
        z-index: 1200; padding: 12px 14px; border-radius: 16px;
        background: linear-gradient(135deg, #292524, #1c1917);
        border: 1px solid rgba(245, 158, 11, 0.45);
        box-shadow: 0 12px 36px rgba(0,0,0,0.45);
        color: #fff8f1; gap: 12px; align-items: center;
    }
    #waiterPwaInstall.show { display: flex; }
    #waiterPwaInstall .copy { flex: 1; min-width: 0; }
    #waiterPwaInstall strong { display: block; font-size: 0.95rem; }
    #waiterPwaInstall span { display: block; font-size: 0.78rem; opacity: 0.78; margin-top: 2px; line-height: 1.35; }
    #waiterPwaInstall .actions { display: flex; gap: 8px; flex-shrink: 0; }
    #waiterPwaInstall button {
        border: 0; border-radius: 10px; padding: 0.55rem 0.85rem;
        font: inherit; font-weight: 700; font-size: 0.82rem; cursor: pointer; min-height: 44px;
    }
    #waiterPwaInstall .btn-install { background: #e8890c; color: #fff; }
    #waiterPwaInstall .btn-dismiss { background: rgba(255,255,255,0.08); color: #fff8f1; }
    @media (max-width: 480px) {
        #waiterPwaInstall {
            flex-direction: column; align-items: stretch; text-align: center;
            bottom: calc(76px + env(safe-area-inset-bottom, 0px));
        }
        #waiterPwaInstall .actions { justify-content: stretch; }
        #waiterPwaInstall .actions button { flex: 1; }
    }
    @media (min-width: 900px) {
        #waiterPwaInstall { left: auto; right: 20px; width: 360px; bottom: 24px; flex-direction: row; text-align: left; }
    }
</style>
<div id="waiterPwaInstall" role="dialog" aria-label="Install {{ $pwaShort }}">
    <div class="copy">
        <strong>Install {{ $pwaShort }}</strong>
        <span id="waiterPwaInstallHint">Add to your home screen for a full-screen app.</span>
    </div>
    <div class="actions">
        <button type="button" class="btn-dismiss" id="waiterPwaDismiss">Later</button>
        <button type="button" class="btn-install" id="waiterPwaInstallBtn">Install</button>
    </div>
</div>
<script>
(function () {
    const KEY = 'qrpos_waiter_pwa_dismissed';
    const banner = document.getElementById('waiterPwaInstall');
    const installBtn = document.getElementById('waiterPwaInstallBtn');
    const dismissBtn = document.getElementById('waiterPwaDismiss');
    const hint = document.getElementById('waiterPwaInstallHint');
    if (!banner) return;

    let deferredPrompt = null;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isAndroid = /android/i.test(navigator.userAgent);
    const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost';

    function dismissed() {
        try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; }
    }

    function showBanner() {
        if (isStandalone || dismissed()) return;
        banner.classList.add('show');
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        if (isStandalone || dismissed()) return;
        e.preventDefault();
        deferredPrompt = e;
        if (hint) hint.textContent = 'Add to your home screen for a full-screen app.';
        if (installBtn) installBtn.textContent = 'Install';
        showBanner();
    });

    if (!isStandalone && !dismissed()) {
        if (isIos) {
            if (hint) hint.textContent = 'Safari → Share → Add to Home Screen';
            if (installBtn) installBtn.textContent = 'How';
            setTimeout(showBanner, 1600);
        } else if (isAndroid && !isSecure) {
            // Chrome install prompt needs HTTPS (or localhost)
            if (hint) hint.textContent = 'Open this site on HTTPS to Install as an app.';
            if (installBtn) installBtn.textContent = 'OK';
            setTimeout(showBanner, 2000);
        }
    }

    installBtn?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            try { await deferredPrompt.userChoice; } catch (e) {}
            deferredPrompt = null;
            banner.classList.remove('show');
            return;
        }
        if (isIos) {
            alert('On iPhone/iPad:\n1. Tap Share\n2. Add to Home Screen\n3. Open {{ $pwaShort }} from your home screen');
            return;
        }
        banner.classList.remove('show');
    });

    dismissBtn?.addEventListener('click', () => {
        try { localStorage.setItem(KEY, '1'); } catch (e) {}
        banner.classList.remove('show');
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw-waiter.js', { scope: '/waiter/' }).catch(() => {});
    }
})();
</script>
