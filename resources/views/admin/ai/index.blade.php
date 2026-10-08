@extends('layouts.admin')

@section('title', 'Avenque AI Agent')
@section('page_title', 'Avenque AI Agent')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Avenque AI</li>
@endsection

@section('content')
@php
    $ready = $status['ready'] ?? false;
    $available = $status['available'] ?? false;
    $enabled = $status['enabled'] ?? false;
    $hasKey = $status['has_api_key'] ?? false;
    $mode = $status['mode'] ?? 'off';
@endphp

<div class="ai-agent-page">
    <div class="ai-hero">
        <div class="ai-hero-text d-flex align-items-center gap-3">
            @include('partials.avenque-ai-logo', ['class' => 'ai-hero-logo', 'uid' => 'page', 'title' => 'Avenque AI'])
            <div>
                <div class="ai-brand">Avenque AI Agent</div>
                <p class="ai-sub mb-0">Dashboard chatbot for sales, stock, profit &amp; customers. Works without an API key (local mode); Gemini unlocks smarter chat.</p>
            </div>
        </div>
        <div class="ai-controls">
            @if($available)
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="aiEnableToggle" {{ $enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="aiEnableToggle">{{ $enabled ? 'Enabled' : 'Disabled' }}</label>
                </div>
            @endif
            @if($isOwner)
                <a href="{{ route('settings.index') }}#ai" class="btn btn-sm btn-outline-dark">
                    <i class="fas fa-key me-1"></i> Gemini settings
                </a>
            @endif
        </div>
    </div>

    @if(! $available)
        <div class="alert alert-warning">
            <strong>Not available yet.</strong>
            @if($isOwner)
                Open <a href="{{ route('settings.index') }}#ai">Settings → Avenque AI Agent</a> and turn on
                <em>Make Avenque AI Available</em>. Gemini API key is optional.
            @else
                Ask the software owner to unlock Avenque AI.
            @endif
        </div>
    @elseif(! $enabled)
        <div class="alert alert-info">Turn on the switch above to enable the chatbot (works without Gemini API key).</div>
    @elseif(! $hasKey)
        <div class="alert alert-success">
            <strong>Local mode active</strong> — common questions work without an API key.
            @if($isOwner)
                Optional: add a Gemini key in <a href="{{ route('settings.index') }}#ai">Settings</a> for smarter free-form answers.
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="ai-chat-card">
                <div class="ai-chat-messages" id="aiChatMessages">
                    <div class="ai-msg ai-msg-bot">
                        <div class="ai-bubble">
                            Hi! I’m Avenque Guide. Ask “how to billing” or “how to add product” — works without Gemini. Or ask sales / stock / profit for live data.
                        </div>
                    </div>
                </div>
                <form id="aiChatForm" class="ai-chat-form" autocomplete="off">
                    <input type="text" id="aiChatInput" class="form-control" placeholder="Ask in English, Tamil, or Sinhala…" {{ $ready ? '' : 'disabled' }} maxlength="2000">
                    <button type="submit" class="btn ai-send-btn" id="aiSendBtn" {{ $ready ? '' : 'disabled' }}>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="ai-side-card">
                <h6 class="mb-2">Quick asks</h6>
                <div class="d-flex flex-column gap-2" id="aiQuickAsks">
                    <button type="button" class="ai-chip" data-q="How to billing?">How to billing</button>
                    <button type="button" class="ai-chip" data-q="How to add product?">Add product</button>
                    <button type="button" class="ai-chip" id="aiBillingTourBtn">Billing tour</button>
                    <button type="button" class="ai-chip" data-q="How many sales today?">Sales today</button>
                    <button type="button" class="ai-chip" data-q="Which products are low stock?">Low stock</button>
                </div>
                <hr>
                <h6 class="mb-2">Coming soon</h6>
                <ul class="ai-soon small text-muted mb-0">
                    <li>Create quotation</li>
                    <li>Generate invoice from chat</li>
                    <li>Voice commands</li>
                    <li>Full UI language pack (EN / TA / SI)</li>
                </ul>
                <hr>
                <div class="small text-muted">
                    Mode:
                    @if($mode === 'gemini')
                        <span class="text-success">Gemini</span> (<code>{{ $status['model'] ?? 'gemini-2.0-flash' }}</code>)
                    @elseif($mode === 'local')
                        <span class="text-success">Local</span> (no API key)
                    @else
                        <span class="text-warning">Off</span>
                    @endif
                    <br>
                    Status:
                    @if($ready)
                        <span class="text-success">Ready</span>
                    @else
                        <span class="text-warning">Not ready</span>
                    @endif
                    <br>
                    <span class="text-muted">Floating chatbot appears bottom-right on all admin pages when available.</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.ai-agent-page { max-width: 1100px; }
.ai-hero {
    display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;
    margin-bottom: 1.25rem;
    padding: 1.25rem 1.4rem;
    border-radius: 18px;
    background:
        radial-gradient(1200px 200px at 10% -40%, rgba(245,158,11,.28), transparent 55%),
        linear-gradient(135deg, #1c1917 0%, #292524 55%, #44403c 100%);
    color: #fafaf9;
}
.ai-brand { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em; }
.ai-hero-logo {
    width: 72px; height: 72px; flex-shrink: 0;
    border-radius: 18px; object-fit: cover; object-position: 65% 15%;
    background: #fff; box-shadow: 0 0 0 2px rgba(255,255,255,.35);
}
.ai-sub { margin: .35rem 0 0; opacity: .78; font-size: .92rem; max-width: 36rem; }
.ai-controls { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.ai-controls .form-check-label { color: #fafaf9; }
.ai-controls .form-check-input:checked { background-color: #f59e0b; border-color: #f59e0b; }
.ai-controls .btn-outline-dark {
    border-color: rgba(255,255,255,.25); color: #fafaf9;
}
.ai-controls .btn-outline-dark:hover { background: rgba(255,255,255,.1); color: #fff; border-color: rgba(255,255,255,.4); }
.ai-chat-card, .ai-side-card {
    background: #fff; border: 1px solid #e7e5e4; border-radius: 16px;
    box-shadow: 0 1px 2px rgba(28,25,23,.04);
}
.ai-chat-card { display: flex; flex-direction: column; min-height: 520px; overflow: hidden; }
.ai-chat-messages {
    flex: 1; overflow-y: auto; padding: 1.1rem; display: flex; flex-direction: column; gap: .75rem;
    background: linear-gradient(180deg, #fafaf9 0%, #fff 40%);
    max-height: 520px; min-height: 420px;
}
.ai-msg { display: flex; }
.ai-msg-user { justify-content: flex-end; }
.ai-msg-bot { justify-content: flex-start; }
.ai-bubble {
    max-width: 88%; padding: .7rem .95rem; border-radius: 14px; font-size: .94rem; line-height: 1.45;
    white-space: pre-wrap; word-break: break-word;
}
.ai-msg-user .ai-bubble { background: #1c1917; color: #fafaf9; border-bottom-right-radius: 4px; }
.ai-msg-bot .ai-bubble { background: #fff7ed; color: #1c1917; border: 1px solid #fed7aa; border-bottom-left-radius: 4px; }
.ai-bubble.ai-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
.ai-chat-form {
    display: flex; gap: .5rem; padding: .85rem; border-top: 1px solid #e7e5e4; background: #fff;
}
.ai-send-btn {
    background: #f59e0b; color: #1c1917; border: none; width: 46px; border-radius: 12px; font-weight: 700;
}
.ai-send-btn:hover { background: #d97706; color: #fff; }
.ai-send-btn:disabled, #aiChatInput:disabled { opacity: .55; }
.ai-side-card { padding: 1.1rem 1.15rem; }
.ai-chip {
    text-align: left; border: 1px solid #e7e5e4; background: #fafaf9; border-radius: 10px;
    padding: .55rem .75rem; font-size: .88rem; color: #292524; transition: .15s ease;
}
.ai-chip:hover:not(:disabled) { border-color: #f59e0b; background: #fffbeb; }
.ai-chip:disabled { opacity: .5; cursor: not-allowed; }
.ai-soon { padding-left: 1.1rem; }
.ai-typing { opacity: .7; font-style: italic; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const ready = @json($ready);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const messagesEl = document.getElementById('aiChatMessages');
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiChatInput');
    const sendBtn = document.getElementById('aiSendBtn');
    const toggle = document.getElementById('aiEnableToggle');
    const history = [];

    function appendMsg(role, text, isError, tourMeta) {
        const wrap = document.createElement('div');
        wrap.className = 'ai-msg ' + (role === 'user' ? 'ai-msg-user' : 'ai-msg-bot');
        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble' + (isError ? ' ai-error' : '');
        bubble.textContent = text;
        if (tourMeta?.tour && role === 'bot' && !isError) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm mt-2';
            btn.style.cssText = 'background:#f59e0b;color:#1c1917;font-weight:800;border:none;border-radius:10px;';
            btn.textContent = tourMeta.tour_label || 'Take tour';
            btn.onclick = () => {
                if (window.AvenqueTour) window.AvenqueTour.launch(tourMeta.tour, tourMeta.tour_url);
                else if (tourMeta.tour_url) window.location.href = tourMeta.tour_url;
            };
            bubble.appendChild(document.createElement('br'));
            bubble.appendChild(btn);
        }
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return bubble;
    }

    async function sendMessage(text) {
        text = (text || '').trim();
        if (!text || !input || input.disabled) return;

        appendMsg('user', text);
        history.push({ role: 'user', content: text });
        input.value = '';
        sendBtn.disabled = true;
        const thinking = appendMsg('bot', 'Thinking…');
        thinking.classList.add('ai-typing');

        try {
            const r = await fetch(@json(route('ai.chat')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ message: text, history: history.slice(0, -1) }),
            });
            const data = await r.json();
            thinking.parentElement?.remove();
            if (data.success && data.reply) {
                appendMsg('bot', data.reply, false, {
                    tour: data.tour,
                    tour_url: data.tour_url,
                    tour_label: data.tour_label,
                });
                history.push({ role: 'assistant', content: data.reply });
            } else {
                appendMsg('bot', data.error || 'Something went wrong.', true);
            }
        } catch (e) {
            thinking.parentElement?.remove();
            appendMsg('bot', 'Network error. Please try again.', true);
        } finally {
            sendBtn.disabled = input.disabled;
        }
    }

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage(input.value);
    });

    document.querySelectorAll('.ai-chip').forEach((btn) => {
        btn.disabled = !ready;
        btn.addEventListener('click', () => {
            if (btn.id === 'aiBillingTourBtn') {
                if (window.AvenqueTour) {
                    window.AvenqueTour.launch('billing', @json(route('pos.index', ['tour' => 'billing'])));
                } else {
                    window.location.href = @json(route('pos.index', ['tour' => 'billing']));
                }
                return;
            }
            if (btn.dataset.q) sendMessage(btn.dataset.q);
        });
    });

    toggle?.addEventListener('change', async () => {
        const enabled = toggle.checked;
        toggle.disabled = true;
        try {
            const r = await fetch(@json(route('ai.toggle')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
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
@endpush
