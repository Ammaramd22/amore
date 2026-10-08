@php
    $appName = (string) (\App\Models\Setting::query()->where('key', 'pwa_app_name')->value('value') ?: 'QRPOS Waiter Panel');
    $shortName = (string) (\App\Models\Setting::query()->where('key', 'pwa_app_short_name')->value('value') ?: 'QRPOS Waiter');
    $theme = (string) (\App\Models\Setting::query()->where('key', 'pwa_theme_color')->value('value') ?: '#1c1410');
    $bg = (string) (\App\Models\Setting::query()->where('key', 'pwa_background_color')->value('value') ?: '#1c1410');
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $theme)) {
        $theme = '#1c1410';
    }
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $bg)) {
        $bg = '#1c1410';
    }
    $logoUrl = \App\Models\Setting::logoUrlFor('pwa_app_logo') ?? \App\Models\Setting::logoUrl() ?? asset('pwa/waiter/icon-192.png');
    $splashUrl = \App\Models\Setting::logoUrlFor('pwa_splash_image');
    $logoPath = (string) (\App\Models\Setting::query()->where('key', 'pwa_app_logo')->value('value') ?: '');
    $splashPath = (string) (\App\Models\Setting::query()->where('key', 'pwa_splash_image')->value('value') ?: '');
@endphp

<div class="pwa-studio">
    <div class="pwa-studio-preview">
        <div class="pwa-phone" id="pwaPhonePreview" style="--pwa-bg: {{ $bg }}; --pwa-theme: {{ $theme }};">
            <div class="pwa-phone-notch"></div>
            <div class="pwa-phone-screen" id="pwaSplashPreview">
                <div class="pwa-splash-art" id="pwaSplashArt">
                    <img id="pwaPreviewSplashImg" src="{{ $splashUrl ?: $logoUrl }}" alt="Splash" class="{{ $splashUrl ? 'cover' : 'contain' }}">
                </div>
                <div class="pwa-splash-copy">
                    <div class="pwa-splash-title" id="pwaPreviewName">{{ $appName }}</div>
                    <div class="pwa-splash-sub">QRPOS By Avenque</div>
                </div>
            </div>
            <div class="pwa-home-row">
                <div class="pwa-home-icon">
                    <img id="pwaPreviewLogoImg" src="{{ $logoUrl }}" alt="App icon">
                </div>
                <div class="pwa-home-label" id="pwaPreviewShort">{{ $shortName }}</div>
            </div>
        </div>
        <p class="pwa-preview-caption">Live preview — home icon &amp; splash</p>
    </div>

    <div class="pwa-studio-fields">
        <div class="pwa-field">
            <label class="settings-label" for="field-pwa_app_name">App Name</label>
            <input type="text" class="form-control" name="pwa_app_name" id="field-pwa_app_name"
                   value="{{ $appName }}" maxlength="40" autocomplete="off"
                   oninput="pwaPreviewSync()">
            <div class="form-text">Full name shown on splash and install prompts.</div>
        </div>

        <div class="pwa-field">
            <label class="settings-label" for="field-pwa_app_short_name">Short Name</label>
            <input type="text" class="form-control" name="pwa_app_short_name" id="field-pwa_app_short_name"
                   value="{{ $shortName }}" maxlength="16" autocomplete="off"
                   oninput="pwaPreviewSync()">
            <div class="form-text">Label under the home-screen icon (keep short).</div>
        </div>

        <div class="pwa-field">
            <label class="settings-label">App Logo</label>
            <div class="pwa-upload">
                <div class="pwa-upload-thumb">
                    <img id="pwaLogoThumb" src="{{ $logoUrl }}" alt="Logo">
                </div>
                <div class="pwa-upload-controls">
                    <input type="file" class="form-control" name="pwa_app_logo_file" id="field-pwa_app_logo_file"
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           onchange="pwaPreviewFile(this, 'logo')">
                    <input type="hidden" name="pwa_app_logo" value="{{ $logoPath }}">
                    @if($logoPath)
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="clear_pwa_app_logo" value="1" id="clear_pwa_app_logo">
                        <label class="form-check-label" for="clear_pwa_app_logo">Remove custom logo (use Software Logo)</label>
                    </div>
                    @endif
                    <div class="form-text">PNG with transparent background works best. Max 2MB.</div>
                </div>
            </div>
        </div>

        <div class="pwa-field">
            <label class="settings-label">Splash Screen</label>
            <div class="pwa-upload">
                <div class="pwa-upload-thumb tall">
                    @if($splashUrl)
                        <img id="pwaSplashThumb" src="{{ $splashUrl }}" alt="Splash">
                    @else
                        <img id="pwaSplashThumb" src="{{ $logoUrl }}" alt="Splash" style="opacity:.45;object-fit:contain;">
                    @endif
                </div>
                <div class="pwa-upload-controls">
                    <input type="file" class="form-control" name="pwa_splash_image_file" id="field-pwa_splash_image_file"
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           onchange="pwaPreviewFile(this, 'splash')">
                    <input type="hidden" name="pwa_splash_image" value="{{ $splashPath }}">
                    @if($splashPath)
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="clear_pwa_splash_image" value="1" id="clear_pwa_splash_image">
                        <label class="form-check-label" for="clear_pwa_splash_image">Remove splash image</label>
                    </div>
                    @endif
                    <div class="form-text">Optional full-screen image when the Waiter app opens. Portrait 1080×1920 recommended.</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <label class="settings-label" for="field-pwa_theme_color">Theme Color</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" id="picker-pwa_theme_color" class="form-control form-control-color" style="width:52px;height:42px;padding:4px;"
                           value="{{ $theme }}"
                           oninput="document.getElementById('field-pwa_theme_color').value=this.value; pwaPreviewSync()">
                    <input type="text" class="form-control" name="pwa_theme_color" id="field-pwa_theme_color"
                           value="{{ $theme }}" oninput="pwaPreviewSync()">
                </div>
                <div class="form-text">Status bar / browser chrome.</div>
            </div>
            <div class="col-sm-6">
                <label class="settings-label" for="field-pwa_background_color">Splash Background</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" id="picker-pwa_background_color" class="form-control form-control-color" style="width:52px;height:42px;padding:4px;"
                           value="{{ $bg }}"
                           oninput="document.getElementById('field-pwa_background_color').value=this.value; pwaPreviewSync()">
                    <input type="text" class="form-control" name="pwa_background_color" id="field-pwa_background_color"
                           value="{{ $bg }}" oninput="pwaPreviewSync()">
                </div>
                <div class="form-text">Behind splash &amp; icon padding.</div>
            </div>
        </div>

        <div class="pwa-howto">
            <strong><i class="fas fa-info-circle me-1"></i>How waiters install</strong>
            <ol class="mb-0 mt-2">
                <li>Open <code>/waiter/dashboard</code> on the phone (use <strong>HTTPS</strong> in production).</li>
                <li><strong>Android:</strong> browser menu → Install app / Add to Home screen.</li>
                <li><strong>iPhone:</strong> Share → Add to Home Screen.</li>
                <li>After changing logo/name, delete the old icon and reinstall.</li>
            </ol>
        </div>
    </div>
</div>

<style>
    .pwa-studio {
        display: grid;
        grid-template-columns: minmax(220px, 280px) 1fr;
        gap: 1.5rem;
        align-items: start;
    }
    @media (max-width: 900px) {
        .pwa-studio { grid-template-columns: 1fr; }
        .pwa-studio-preview { order: -1; justify-self: center; }
    }
    .pwa-phone {
        width: 220px;
        background: #111;
        border-radius: 28px;
        padding: 10px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.28);
        border: 2px solid #292524;
        position: relative;
    }
    .pwa-phone-notch {
        width: 72px; height: 8px; border-radius: 999px; background: #292524;
        margin: 4px auto 10px;
    }
    .pwa-phone-screen {
        background: var(--pwa-bg, #1c1410);
        border-radius: 18px;
        min-height: 280px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 1rem 0.85rem 1.25rem; text-align: center; color: #fff8f1;
        overflow: hidden;
    }
    .pwa-splash-art {
        width: 112px; height: 112px; border-radius: 24px; overflow: hidden;
        background: rgba(0,0,0,0.25); margin-bottom: 0.85rem;
        display: grid; place-items: center;
    }
    .pwa-splash-art.has-full {
        width: 100%; height: 180px; border-radius: 14px;
    }
    .pwa-splash-art img { width: 100%; height: 100%; display: block; }
    .pwa-splash-art img.contain { object-fit: contain; }
    .pwa-splash-art img.cover { object-fit: cover; }
    .pwa-splash-title { font-weight: 800; font-size: 0.95rem; line-height: 1.25; }
    .pwa-splash-sub { font-size: 0.65rem; letter-spacing: 0.12em; text-transform: uppercase; color: #60a5fa; margin-top: 0.35rem; font-weight: 700; }
    .pwa-home-row {
        margin-top: 12px; display: flex; flex-direction: column; align-items: center; gap: 6px;
        padding-bottom: 6px;
    }
    .pwa-home-icon {
        width: 48px; height: 48px; border-radius: 12px; overflow: hidden;
        background: #0a0a0a; border: 1px solid rgba(255,255,255,0.08);
    }
    .pwa-home-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .pwa-home-label { color: #e7e5e4; font-size: 0.72rem; font-weight: 600; text-align: center; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pwa-preview-caption { text-align: center; color: #64748b; font-size: 0.8rem; margin: 0.75rem 0 0; }
    .pwa-field { margin-bottom: 1.15rem; }
    .pwa-upload { display: flex; gap: 14px; align-items: flex-start; }
    .pwa-upload-thumb {
        width: 72px; height: 72px; border-radius: 16px; overflow: hidden; flex-shrink: 0;
        background: #1c1917; border: 1px solid #e7e5e4; display: grid; place-items: center;
    }
    .pwa-upload-thumb.tall { width: 64px; height: 104px; border-radius: 12px; }
    .pwa-upload-thumb img { width: 100%; height: 100%; object-fit: contain; }
    .pwa-upload-thumb.tall img { object-fit: cover; }
    .pwa-upload-controls { flex: 1; min-width: 0; }
    .pwa-howto {
        background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px;
        padding: 14px 16px; color: #9a3412; font-size: 0.88rem; margin-top: 0.5rem;
    }
    .pwa-howto ol { padding-left: 1.15rem; color: #7c2d12; }
    .pwa-howto code { background: #fff; padding: 1px 6px; border-radius: 6px; font-size: 0.8rem; }
</style>

<script>
function pwaPreviewSync() {
    const name = document.getElementById('field-pwa_app_name')?.value || 'QRPOS Waiter Panel';
    const shortName = document.getElementById('field-pwa_app_short_name')?.value || 'QRPOS Waiter';
    const theme = document.getElementById('field-pwa_theme_color')?.value || '#1c1410';
    const bg = document.getElementById('field-pwa_background_color')?.value || '#1c1410';
    const phone = document.getElementById('pwaPhonePreview');
    if (phone) {
        phone.style.setProperty('--pwa-bg', bg);
        phone.style.setProperty('--pwa-theme', theme);
    }
    const n = document.getElementById('pwaPreviewName');
    const s = document.getElementById('pwaPreviewShort');
    if (n) n.textContent = name;
    if (s) s.textContent = shortName;
    if (/^#[0-9A-Fa-f]{6}$/.test(theme)) {
        const p = document.getElementById('picker-pwa_theme_color');
        if (p) p.value = theme;
    }
    if (/^#[0-9A-Fa-f]{6}$/.test(bg)) {
        const p = document.getElementById('picker-pwa_background_color');
        if (p) p.value = bg;
    }
}

function pwaPreviewFile(input, kind) {
    const file = input.files && input.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    if (kind === 'logo') {
        const thumb = document.getElementById('pwaLogoThumb');
        const icon = document.getElementById('pwaPreviewLogoImg');
        const splashImg = document.getElementById('pwaPreviewSplashImg');
        if (thumb) thumb.src = url;
        if (icon) icon.src = url;
        // If no dedicated splash, mirror logo in splash art
        const splashInput = document.getElementById('field-pwa_splash_image_file');
        if (splashImg && (!splashInput?.files?.length)) {
            splashImg.src = url;
            splashImg.classList.remove('cover');
            splashImg.classList.add('contain');
            document.getElementById('pwaSplashArt')?.classList.remove('has-full');
        }
    }
    if (kind === 'splash') {
        const thumb = document.getElementById('pwaSplashThumb');
        const splashImg = document.getElementById('pwaPreviewSplashImg');
        if (thumb) {
            thumb.src = url;
            thumb.style.opacity = '1';
            thumb.style.objectFit = 'cover';
        }
        if (splashImg) {
            splashImg.src = url;
            splashImg.classList.remove('contain');
            splashImg.classList.add('cover');
        }
        document.getElementById('pwaSplashArt')?.classList.add('has-full');
    }
}

@if($splashUrl)
document.getElementById('pwaSplashArt')?.classList.add('has-full');
@endif
</script>
