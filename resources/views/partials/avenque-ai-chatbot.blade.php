@php
    $aiShowWidget = false;
    $aiWidgetStatus = ['available' => false, 'enabled' => false, 'ready' => false, 'mode' => 'off', 'has_api_key' => false];
    try {
        $aiWidgetStatus = \App\Services\AiAgentService::status();
        // Only show floating guide when unlocked AND enabled (off = fully hidden)
        $aiShowWidget = \App\Services\AiAgentService::isEnabled();
    } catch (\Throwable $e) {
        $aiShowWidget = false;
    }
@endphp
@if($aiShowWidget)
<div id="avenqueAiWidget" class="avenque-ai-widget" data-ready="{{ $aiWidgetStatus['ready'] ? '1' : '0' }}" data-mode="{{ $aiWidgetStatus['mode'] }}">
    {{-- Floating Hi preview with character peeking --}}
    <div class="avenque-ai-preview" id="avenqueAiPreview" role="status">
        <button type="button" class="avenque-ai-preview-card" id="avenqueAiPreviewBtn">
            <span class="avenque-ai-preview-copy">
                <span class="avenque-hi-pill">Hi!</span>
                <strong>Need help with QRPOS?</strong>
                <small>Billing, products &amp; more — tap me</small>
            </span>
            <span class="avenque-ai-preview-x" id="avenqueAiPreviewDismiss" title="Dismiss">&times;</span>
        </button>
    </div>

    <div class="avenque-ai-panel" id="avenqueAiPanel" hidden>
        <div class="avenque-ai-panel-head">
            <div class="avenque-ai-panel-brand">
                <div class="avenque-ai-avatar-wrap">
                    @include('partials.avenque-ai-logo', ['class' => 'avenque-ai-logo-sm', 'title' => 'Avenque Guide'])
                </div>
                <div>
                    <div class="avenque-ai-name">Avenque Guide</div>
                    <div class="avenque-ai-mode" id="avenqueAiModeLabel">
                        @if(! $aiWidgetStatus['available'])
                            Locked — owner unlock needed
                        @elseif(! $aiWidgetStatus['enabled'])
                            Turn on to chat
                        @elseif($aiWidgetStatus['mode'] === 'gemini')
                            Gemini + software guide
                        @else
                            Software guide (no API key)
                        @endif
                    </div>
                </div>
            </div>
            <div class="avenque-ai-panel-actions">
                @if($aiWidgetStatus['available'])
                <label class="avenque-ai-mini-switch" title="Enable guide">
                    <input type="checkbox" id="avenqueAiWidgetToggle" {{ $aiWidgetStatus['enabled'] ? 'checked' : '' }}>
                </label>
                @endif
                <a href="{{ route('ai.index') }}" class="avenque-ai-icon-btn" title="Full page"><i class="fas fa-expand"></i></a>
                <button type="button" class="avenque-ai-icon-btn" id="avenqueAiClose" title="Close" aria-label="Close">&times;</button>
            </div>
        </div>
        <div class="avenque-ai-panel-msgs" id="avenqueAiMsgs">
            <div class="avenque-ai-bubble bot">
                Hi! I’m Avenque Guide. Ask “how to billing” or “how to add product” — I work without Gemini.
            </div>
        </div>
        <div class="avenque-ai-quick">
            <button type="button" data-q="How to billing?">How to billing</button>
            <button type="button" data-tour="billing" data-tour-url="{{ route('pos.index', ['tour' => 'billing']) }}">Billing tour</button>
            <button type="button" data-q="How to add product?">Add product</button>
            <button type="button" data-q="How many sales today?">Sales today</button>
        </div>
        <form class="avenque-ai-panel-form" id="avenqueAiForm" autocomplete="off">
            <input type="text" id="avenqueAiInput" placeholder="Ask: how to billing…" maxlength="2000" {{ $aiWidgetStatus['ready'] ? '' : 'disabled' }}>
            <button type="submit" id="avenqueAiSend" {{ $aiWidgetStatus['ready'] ? '' : 'disabled' }} aria-label="Send"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

    {{-- Character peeking from corner + Hi bubble --}}
    <button type="button" class="avenque-ai-fab" id="avenqueAiFab" aria-label="Open Avenque Guide" title="Avenque Guide">
        <span class="avenque-fab-hi" aria-hidden="true">Hi!</span>
        <span class="avenque-fab-char">
            @include('partials.avenque-ai-logo', ['class' => 'avenque-ai-logo-fab', 'title' => 'Avenque Guide'])
        </span>
    </button>
</div>

<style>
.avenque-ai-widget {
    position: fixed; right: 8px; bottom: 0; z-index: 1080;
    font-family: Inter, system-ui, sans-serif;
    display: flex; flex-direction: column; align-items: flex-end; gap: 8px;
    pointer-events: none;
}
.avenque-ai-widget > * { pointer-events: auto; }

.avenque-ai-preview { max-width: min(240px, calc(100vw - 110px)); margin-right: 72px; margin-bottom: 48px; }
.avenque-ai-preview.is-hidden { display: none !important; }
.avenque-ai-preview-card {
    display: block; text-align: left; width: 100%;
    border: none; background: #fff;
    border-radius: 16px; padding: .85rem 1rem .85rem .95rem;
    box-shadow: 0 12px 40px rgba(28,25,23,.18), 0 0 0 1px rgba(245,158,11,.25);
    cursor: pointer; animation: avenquePreviewIn .5s ease-out;
    position: relative;
}
.avenque-ai-preview-card:hover { box-shadow: 0 16px 44px rgba(234,88,12,.22), 0 0 0 1px #f59e0b; }
.avenque-ai-preview-copy { display: flex; flex-direction: column; gap: 3px; padding-right: 18px; }
.avenque-hi-pill {
    display: inline-flex; align-self: flex-start;
    background: #f59e0b; color: #fff; font-weight: 800; font-size: .72rem;
    padding: .15rem .55rem; border-radius: 999px; margin-bottom: 2px;
}
.avenque-ai-preview-copy strong { font-size: .9rem; color: #1c1917; font-weight: 800; }
.avenque-ai-preview-copy small { font-size: .74rem; color: #78716c; line-height: 1.3; }
.avenque-ai-preview-x {
    position: absolute; top: 6px; right: 8px; width: 22px; height: 22px;
    display: grid; place-items: center; border-radius: 50%; color: #a8a29e; font-size: 1rem;
}
.avenque-ai-preview-x:hover { background: #f5f5f4; color: #1c1917; }
@keyframes avenquePreviewIn {
    from { opacity: 0; transform: translateX(18px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Peeking character FAB — transparent, Hi by head */
.avenque-ai-fab {
    width: 88px; height: 108px; border: none; padding: 0;
    background: transparent !important; cursor: pointer; position: relative;
    overflow: visible;
    box-shadow: none !important;
}
.avenque-fab-char {
    position: absolute; right: 0; bottom: 0;
    width: 88px; height: 108px;
    overflow: visible;
    background: transparent;
    filter: drop-shadow(0 6px 12px rgba(28,25,23,.25));
    animation: avenqueWave 2.8s ease-in-out infinite;
}
.avenque-ai-logo-fab {
    width: 92px; height: auto;
    position: absolute; right: -4px; bottom: -4px;
    object-fit: contain; object-position: right bottom;
    pointer-events: none; user-select: none;
    background: transparent !important;
}
.avenque-fab-hi {
    position: absolute; right: 62px; top: 6px; z-index: 2;
    left: auto;
    background: #fff; color: #ea580c;
    font-weight: 800; font-size: .68rem;
    padding: .2rem .42rem; border-radius: 10px 10px 4px 10px;
    box-shadow: 0 4px 12px rgba(28,25,23,.18);
    animation: avenqueHiBob 2s ease-in-out infinite;
    white-space: nowrap;
}
.avenque-fab-hi::after {
    content: '';
    position: absolute; right: -3px; bottom: 5px;
    width: 6px; height: 6px; background: #fff;
    transform: rotate(45deg);
}
.avenque-ai-fab-pulse {
    display: none; /* remove orange ring / boxed look */
}
.avenque-ai-fab::-moz-focus-inner { border: 0; }
.avenque-ai-fab:focus { outline: none; }
.avenque-ai-fab:focus-visible .avenque-fab-hi {
    box-shadow: 0 0 0 3px rgba(245,158,11,.45), 0 6px 16px rgba(28,25,23,.2);
}
@keyframes avenqueWave {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
@keyframes avenqueHiBob {
    0%, 100% { transform: translateY(0) rotate(-2deg); }
    50% { transform: translateY(-3px) rotate(2deg); }
}
@keyframes avenquePulse {
    0% { transform: scale(.85); opacity: .7; }
    100% { transform: scale(1.45); opacity: 0; }
}

.avenque-ai-panel {
    width: min(400px, calc(100vw - 28px));
    height: min(520px, calc(100vh - 160px));
    margin-right: 12px; margin-bottom: 8px;
    background: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 20px;
    box-shadow: 0 24px 56px rgba(28,25,23,.24);
    display: flex; flex-direction: column; overflow: hidden;
    transform-origin: bottom right;
    animation: avenquePanelIn .28s ease-out;
}
.avenque-ai-panel[hidden] { display: none !important; animation: none; }
@keyframes avenquePanelIn {
    from { opacity: 0; transform: translateY(16px) scale(.94); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.avenque-ai-panel-head {
    display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    padding: .75rem .85rem;
    background: linear-gradient(135deg, #1c1917, #44403c);
    color: #fafaf9;
}
.avenque-ai-panel-brand { display: flex; align-items: center; gap: .65rem; min-width: 0; }
.avenque-ai-avatar-wrap {
    width: 44px; height: 44px; border-radius: 12px; overflow: hidden;
    background: #fff; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(245,158,11,.5);
}
.avenque-ai-logo-sm {
    width: 72px; height: 44px; object-fit: cover; object-position: 70% 20%;
    display: block;
}
.avenque-ai-name { font-weight: 800; font-size: .98rem; line-height: 1.1; }
.avenque-ai-mode { font-size: .72rem; opacity: .75; margin-top: 2px; }
.avenque-ai-panel-actions { display: flex; align-items: center; gap: .25rem; }
.avenque-ai-icon-btn {
    width: 32px; height: 32px; border: none; border-radius: 8px;
    background: transparent; color: #fafaf9; font-size: 1.15rem; line-height: 1;
    display: grid; place-items: center; text-decoration: none;
}
.avenque-ai-icon-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
.avenque-ai-mini-switch { margin: 0 .25rem 0 0; }
.avenque-ai-mini-switch input { width: 2.2em; height: 1.15em; cursor: pointer; }
.avenque-ai-panel-msgs {
    flex: 1; overflow-y: auto; padding: .9rem; background: linear-gradient(180deg,#fffbeb 0%,#fafaf9 28%);
    display: flex; flex-direction: column; gap: .55rem;
}
.avenque-ai-bubble {
    max-width: 92%; padding: .6rem .8rem; border-radius: 14px;
    font-size: .86rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word;
}
.avenque-ai-bubble.bot { align-self: flex-start; background: #fff; border: 1px solid #fed7aa; color: #1c1917; border-bottom-left-radius: 4px; }
.avenque-ai-bubble.user { align-self: flex-end; background: #1c1917; color: #fafaf9; border-bottom-right-radius: 4px; }
.avenque-ai-bubble.err { align-self: flex-start; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.avenque-tour-cta {
    margin-top: .55rem; border: none; border-radius: 10px;
    background: #f59e0b; color: #1c1917; font-weight: 800; font-size: .8rem;
    padding: .45rem .8rem; cursor: pointer;
}
.avenque-tour-cta:hover { background: #d97706; color: #fff; }
.avenque-ai-quick {
    display: flex; flex-wrap: wrap; gap: .4rem; padding: .5rem .75rem; border-top: 1px solid #f5f5f4; background: #fff;
}
.avenque-ai-quick button {
    border: 1px solid #e7e5e4; background: #fff7ed; border-radius: 999px;
    padding: .32rem .7rem; font-size: .74rem; color: #9a3412; font-weight: 600; cursor: pointer;
}
.avenque-ai-quick button:hover:not(:disabled) { border-color: #f59e0b; background: #ffedd5; }
.avenque-ai-quick button:disabled { opacity: .45; cursor: not-allowed; }
.avenque-ai-panel-form {
    display: flex; gap: .4rem; padding: .7rem .75rem; border-top: 1px solid #e7e5e4; background: #fff;
}
.avenque-ai-panel-form input {
    flex: 1; border: 1px solid #e7e5e4; border-radius: 12px; padding: .6rem .75rem; font-size: .88rem;
}
.avenque-ai-panel-form button {
    width: 42px; border: none; border-radius: 12px; background: #f59e0b; color: #1c1917; cursor: pointer;
}
.avenque-ai-panel-form button:disabled, .avenque-ai-panel-form input:disabled { opacity: .5; }
@media (max-width: 575.98px) {
    .avenque-ai-preview { margin-right: 56px; max-width: min(200px, calc(100vw - 90px)); margin-bottom: 40px; }
    .avenque-ai-fab { width: 72px; height: 90px; }
    .avenque-ai-logo-fab { width: 76px; right: -2px; }
    .avenque-fab-hi { right: 48px; top: 4px; font-size: .62rem; }
}
</style>

<script>
(function () {
    const root = document.getElementById('avenqueAiWidget');
    if (!root) return;
    const fab = document.getElementById('avenqueAiFab');
    const panel = document.getElementById('avenqueAiPanel');
    const preview = document.getElementById('avenqueAiPreview');
    const previewBtn = document.getElementById('avenqueAiPreviewBtn');
    const previewDismiss = document.getElementById('avenqueAiPreviewDismiss');
    const closeBtn = document.getElementById('avenqueAiClose');
    const form = document.getElementById('avenqueAiForm');
    const input = document.getElementById('avenqueAiInput');
    const sendBtn = document.getElementById('avenqueAiSend');
    const msgs = document.getElementById('avenqueAiMsgs');
    const toggle = document.getElementById('avenqueAiWidgetToggle');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const history = [];
    let ready = root.dataset.ready === '1';
    const previewKey = 'avenque_guide_preview_dismissed';

    function setReady(on) {
        ready = !!on;
        root.dataset.ready = on ? '1' : '0';
        if (input) input.disabled = !on;
        if (sendBtn) sendBtn.disabled = !on;
        document.querySelectorAll('.avenque-ai-quick button').forEach(b => b.disabled = !on);
    }
    setReady(ready);

    function hidePreview() { preview?.classList.add('is-hidden'); }
    function showPreview() {
        if (sessionStorage.getItem(previewKey) === '1') { hidePreview(); return; }
        preview?.classList.remove('is-hidden');
    }

    function openPanel() {
        hidePreview();
        panel.hidden = false;
        panel.style.animation = 'none';
        void panel.offsetHeight;
        panel.style.animation = '';
        input?.focus();
    }
    function closePanel() {
        panel.hidden = true;
        showPreview();
    }

    setTimeout(showPreview, 700);

    previewBtn?.addEventListener('click', (e) => {
        if (e.target === previewDismiss || previewDismiss?.contains(e.target)) return;
        openPanel();
    });
    previewDismiss?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        sessionStorage.setItem(previewKey, '1');
        hidePreview();
    });

    fab?.addEventListener('click', () => {
        if (panel.hidden) openPanel();
        else closePanel();
    });
    closeBtn?.addEventListener('click', closePanel);

    function append(role, text, isErr, tourMeta) {
        const el = document.createElement('div');
        el.className = 'avenque-ai-bubble ' + (isErr ? 'err' : role);
        el.textContent = text;
        if (tourMeta && tourMeta.tour && role === 'bot' && !isErr) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'avenque-tour-cta';
            btn.textContent = tourMeta.tour_label || 'Take tour';
            btn.addEventListener('click', () => {
                if (window.AvenqueTour) {
                    window.AvenqueTour.launch(tourMeta.tour, tourMeta.tour_url);
                } else if (tourMeta.tour_url) {
                    window.location.href = tourMeta.tour_url;
                }
            });
            el.appendChild(document.createElement('br'));
            el.appendChild(document.createElement('br'));
            el.appendChild(btn);
        }
        msgs.appendChild(el);
        msgs.scrollTop = msgs.scrollHeight;
        return el;
    }

    async function ask(text) {
        text = (text || '').trim();
        if (!text || !ready) {
            if (!ready) alert('Turn on the switch in the chat header to enable Avenque Guide.');
            return;
        }
        append('user', text);
        history.push({ role: 'user', content: text });
        input.value = '';
        sendBtn.disabled = true;
        const thinking = append('bot', '…');
        try {
            const r = await fetch(@json(route('ai.chat')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message: text, history: history.slice(0, -1) }),
            });
            const data = await r.json();
            thinking.remove();
            if (data.success && data.reply) {
                append('bot', data.reply, false, {
                    tour: data.tour,
                    tour_url: data.tour_url,
                    tour_label: data.tour_label,
                });
                history.push({ role: 'assistant', content: data.reply });
            } else {
                append('bot', data.error || 'Something went wrong.', true);
            }
        } catch (e) {
            thinking.remove();
            append('bot', 'Network error.', true);
        } finally {
            sendBtn.disabled = !ready;
        }
    }

    form?.addEventListener('submit', (e) => { e.preventDefault(); ask(input.value); });
    document.querySelectorAll('.avenque-ai-quick button').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.dataset.tour) {
                if (window.AvenqueTour) {
                    window.AvenqueTour.launch(btn.dataset.tour, btn.dataset.tourUrl);
                } else if (btn.dataset.tourUrl) {
                    window.location.href = btn.dataset.tourUrl;
                }
                return;
            }
            ask(btn.dataset.q);
        });
    });

    toggle?.addEventListener('change', async () => {
        const enabled = toggle.checked;
        toggle.disabled = true;
        try {
            const r = await fetch(@json(route('ai.toggle')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ enabled }),
            });
            const data = await r.json();
            if (!data.success) {
                toggle.checked = !enabled;
                alert(data.message || 'Could not update');
                return;
            }
            location.reload();
        } catch (e) {
            toggle.checked = !enabled;
            alert('Network error');
        } finally {
            toggle.disabled = false;
        }
    });
})();
</script>
@endif
