@extends('layouts.admin')
@section('title', 'Settings')
@section('page_title', 'Business Settings')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('settings.update') }}" id="settingsForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="_settings_section" id="settingsActiveSection" value="">
    <div class="settings-shell">
        <aside class="settings-nav" aria-label="Settings sections">
            @foreach($sections as $slug => $section)
            <button type="button"
                    class="settings-nav-item {{ $loop->first ? 'active' : '' }}"
                    data-section="{{ $slug }}"
                    onclick="switchSettingsSection('{{ $slug }}', this)">
                <i class="fas {{ $section['icon'] }}"></i>
                <span>{{ $section['label'] }}</span>
                @if(!empty($section['owner_only']))
                    <span class="badge bg-dark ms-auto" style="font-size:0.65rem;">Owner</span>
                @endif
            </button>
            @endforeach
        </aside>

        <div class="settings-main">
            @foreach($sections as $slug => $section)
            <div class="settings-panel {{ $loop->first ? 'active' : '' }}" id="settings-panel-{{ $slug }}" data-section="{{ $slug }}">
                <div class="settings-panel-head">
                    <div>
                        <h3 class="settings-panel-title">
                            <i class="fas {{ $section['icon'] }} me-2"></i>{{ $section['label'] }}
                        </h3>
                        <p class="settings-panel-sub mb-0">
                            @if($slug === 'tax')
                                Enable or disable tax and service charge for POS bills
                            @elseif($slug === 'kitchen')
                                Kitchen alerts and kitchen display screen
                            @elseif($slug === 'bartender')
                                BarTender CSV export — fixed server URL + print-PC batch file
                            @elseif($slug === 'bakery')
                                Bakery POS — categories to show, direct billing, order types, header displays
                            @elseif($slug === 'pos')
                                Interface, printing, drawer, shortcuts, shifts and customer display
                            @elseif($slug === 'business')
                                Business identity, currency and receipt details
                            @elseif($slug === 'email')
                                SMTP + email shift / day-end reports to admin
                            @elseif($slug === 'pwa')
                                Waiter Panel home-screen app — name, logo &amp; splash
                            @else
                                Configure {{ strtolower($section['label']) }} options
                            @endif
                        </p>
                    </div>
                    @if($slug === 'pwa')
                    <a href="{{ route('waiter.dashboard') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-external-link-alt me-1"></i>Open Waiter Panel
                    </a>
                    @endif
                </div>

                @if($slug === 'pwa')
                    @include('admin.settings.partials.pwa-panel', ['items' => $section['items'], 'labels' => $labels, 'hints' => $hints])
                @elseif($slug === 'pos')
                    @include('admin.settings.partials.pos-panel', [
                        'items' => $section['items'],
                        'labels' => $labels,
                        'hints' => $hints,
                        'isOwner' => $isOwner ?? false,
                        'bakeryCategories' => $bakeryCategories ?? [],
                    ])
                @else
                    @include('admin.settings.partials.setting-fields', [
                        'items' => $section['items'],
                        'labels' => $labels,
                        'hints' => $hints,
                        'bakeryCategories' => $bakeryCategories ?? [],
                    ])
                @endif
            </div>
            @endforeach

            <div class="settings-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .settings-shell {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 0;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 20px rgba(0,0,0,0.03);
        min-height: 640px;
        border: 1px solid #e2e8f0;
    }
    .settings-nav {
        background: #0f172a;
        padding: 16px 10px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        border: 0;
        background: transparent;
        color: #94a3b8;
        text-align: left;
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 0.92rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .settings-nav-item i {
        width: 18px;
        text-align: center;
        color: #64748b;
    }
    .settings-nav-item:hover {
        background: rgba(255,255,255,0.06);
        color: #fff;
    }
    .settings-nav-item:hover i { color: #fff; }
    .settings-nav-item.active {
        background: #f59e0b;
        color: #fff;
        box-shadow: 0 6px 16px rgba(37,99,235,0.35);
    }
    .settings-nav-item.active i { color: #fff; }

    .settings-main {
        padding: 28px 28px 20px;
        background: #f8fafc;
    }
    .settings-panel { display: none; }
    .settings-panel.active { display: block; animation: settingsFade 0.2s ease; }
    @keyframes settingsFade {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: none; }
    }
    .settings-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 22px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .settings-panel-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }
    .settings-panel-sub {
        margin-top: 6px;
        color: #64748b;
        font-size: 0.88rem;
    }
    .settings-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }
    .settings-hint-icon {
        color: #3b82f6;
        font-size: 0.75rem;
        cursor: help;
    }
    .settings-fields .form-control,
    .settings-fields .form-select {
        background: #fff;
        border-radius: 10px;
        min-height: 44px;
    }
    .settings-switch-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 14px;
        min-height: 44px;
        display: flex;
        align-items: center;
    }
    .settings-switch-wrap .form-check-input {
        width: 2.4em;
        height: 1.25em;
        margin-top: 0;
    }
    .settings-switch-wrap .form-check-input:checked {
        background-color: #f59e0b;
        border-color: #f59e0b;
    }
    .settings-actions {
        margin-top: 28px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
    }
    .pos-shared-tabs { margin-top: 2px; }
    .pos-shared-tablist {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 18px;
        padding: 6px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .pos-shared-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 0;
        background: transparent;
        color: #64748b;
        padding: 9px 14px;
        border-radius: 9px;
        font-size: 0.86rem;
        font-weight: 600;
        transition: all 0.15s ease;
    }
    .pos-shared-tab i { font-size: 0.82rem; opacity: 0.85; }
    .pos-shared-tab:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .pos-shared-tab.active {
        background: #0f172a;
        color: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
    }
    .pos-shared-pane { display: none; }
    .pos-shared-pane.active { display: block; animation: settingsFade 0.18s ease; }

    @media (max-width: 991px) {
        .settings-shell {
            grid-template-columns: 1fr;
        }
        .settings-nav {
            flex-direction: row;
            overflow-x: auto;
            gap: 6px;
            padding: 10px;
        }
        .settings-nav-item {
            white-space: nowrap;
            flex: 0 0 auto;
        }
        .settings-main { padding: 18px; }
    }
</style>
@endpush

@push('scripts')
<script>
function switchPosSharedTab(tab, btn) {
    const root = document.getElementById('posSharedTabs');
    if (!root) return;
    root.querySelectorAll('.pos-shared-tab').forEach(el => {
        el.classList.remove('active');
        el.setAttribute('aria-selected', 'false');
    });
    root.querySelectorAll('.pos-shared-pane').forEach(el => el.classList.remove('active'));
    const tabBtn = btn || root.querySelector(`.pos-shared-tab[data-pos-tab="${tab}"]`);
    if (tabBtn) {
        tabBtn.classList.add('active');
        tabBtn.setAttribute('aria-selected', 'true');
    }
    const pane = document.getElementById('pos-shared-pane-' + tab);
    if (pane) pane.classList.add('active');
    try {
        const section = document.getElementById('settingsActiveSection')?.value || 'pos';
        history.replaceState(null, '', '#' + section + '/' + tab);
    } catch (e) {}
}

function switchSettingsSection(slug, btn) {
    document.querySelectorAll('.settings-nav-item').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.settings-panel').forEach(el => el.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else {
        document.querySelector(`.settings-nav-item[data-section="${slug}"]`)?.classList.add('active');
    }
    const panel = document.getElementById('settings-panel-' + slug);
    if (panel) panel.classList.add('active');
    const sectionInput = document.getElementById('settingsActiveSection');
    if (sectionInput) sectionInput.value = slug;
    try {
        const hash = location.hash || '';
        const parts = hash.replace(/^#/, '').split('/');
        const keepTab = (slug === 'pos' && parts[0] === 'pos' && parts[1]) ? parts[1] : null;
        history.replaceState(null, '', keepTab ? '#' + slug + '/' + keepTab : '#' + slug);
        if (slug === 'pos' && keepTab) switchPosSharedTab(keepTab);
    } catch (e) {}
}

document.querySelectorAll('.settings-switch-wrap .form-check-input').forEach(input => {
    input.addEventListener('change', () => {
        const label = input.closest('.form-check')?.querySelector('.form-check-label');
        if (label) label.textContent = input.checked ? 'Enabled' : 'Disabled';
    });
});

(function openHashSection() {
    const raw = (location.hash || '').replace(/^#/, '');
    if (!raw) return;
    const [slug, tab] = raw.split('/');
    const btn = document.querySelector(`.settings-nav-item[data-section="${slug}"]`);
    if (btn) switchSettingsSection(slug, btn);
    if (slug === 'pos' && tab) switchPosSharedTab(tab);
})();

window.togglePrintBridgeCard = function togglePrintBridgeCard() {
    const mode = document.getElementById('field-receipt_print_mode')?.value || 'preview';
    const card = document.getElementById('printBridgeCard');
    if (!card) return;
    card.classList.toggle('d-none', mode !== 'direct');
};

document.getElementById('field-receipt_print_mode')?.addEventListener('change', () => togglePrintBridgeCard());

(function printBridgeHelpers() {
    const statusEl = document.getElementById('printBridgeStatus');
    const setStatus = (msg, ok) => {
        if (!statusEl) return;
        statusEl.textContent = msg || '';
        statusEl.className = 'small mt-2 ' + (ok === true ? 'text-success' : (ok === false ? 'text-danger' : 'text-muted'));
    };

    document.getElementById('btnCheckPrintBridge')?.addEventListener('click', async () => {
        const bridge = (document.getElementById('field-receipt_print_bridge_url')?.value || 'http://127.0.0.1:18181').replace(/\/$/, '');
        setStatus('Checking Local Print Bridge at ' + bridge + '…');
        try {
            const r = await fetch(bridge + '/health', { cache: 'no-store' });
            const data = await r.json().catch(() => ({}));
            if (r.ok && (data.success || data.service)) {
                setStatus('Bridge OK — ready for Direct print', true);
            } else {
                setStatus('Bridge responded but unexpected — restart Start-Print-Bridge.bat', false);
            }
        } catch (e) {
            setStatus('Bridge not running. Download Print Bridge ZIP and run Start-Print-Bridge.bat on this Windows PC', false);
        }
    });

    document.getElementById('btnProbePrinter')?.addEventListener('click', async () => {
        const bridge = (document.getElementById('field-receipt_print_bridge_url')?.value || 'http://127.0.0.1:18181').replace(/\/$/, '');
        const ip = document.getElementById('field-receipt_printer_ip')?.value?.trim() || '';
        const port = Number(document.getElementById('field-receipt_printer_port')?.value || 9100);
        if (!ip) {
            setStatus('Enter Receipt Printer IP first (or leave blank and use USB Windows queue)', false);
            return;
        }
        setStatus('Probing ' + ip + ':' + port + ' via bridge…');
        try {
            const r = await fetch(bridge + '/probe?ip=' + encodeURIComponent(ip) + '&port=' + port, { cache: 'no-store' });
            const data = await r.json().catch(() => ({}));
            setStatus(data.message || (data.success ? 'OK' : 'Probe failed'), !!data.success);
        } catch (e) {
            setStatus('Bridge not running — download and start Print Bridge first', false);
        }
    });
})();
</script>
@endpush
