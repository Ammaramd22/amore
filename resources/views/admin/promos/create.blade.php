@extends('layouts.admin')
@section('title', 'New Promo')
@section('page_title', 'Send Promo')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">Send Promo Campaign</h2>
    <div class="toolbar-actions">
        <a href="{{ route('admin.promos.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(!$whatsappReady && !$smsReady)
<div class="alert alert-warning">
    Neither WhatsApp nor SMS is configured. Go to
    <a href="{{ route('settings.index') }}">Settings → Notifications</a>
    and add your Meta WhatsApp Cloud API credentials and/or Notify.lk SMS credentials.
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Compose message</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.promos.store') }}" id="promoForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Campaign title</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required maxlength="120"
                               placeholder="Weekend special">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror"
                                  required maxlength="1000"
                                  placeholder="Enjoy 15% off dine-in this weekend. Show this message at the counter.">{{ old('message') }}</textarea>
                        <div class="form-text">Business name “{{ $companyName }}” is appended automatically if missing.</div>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Send via</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp"
                                       id="ch_wa" {{ in_array('whatsapp', old('channels', $whatsappReady ? ['whatsapp'] : [])) ? 'checked' : '' }}
                                       {{ $whatsappReady ? '' : 'disabled' }}>
                                <label class="form-check-label" for="ch_wa">
                                    WhatsApp (Meta Cloud)
                                    @unless($whatsappReady)<span class="text-muted small">— configure first</span>@endunless
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="channels[]" value="sms"
                                       id="ch_sms" {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}
                                       {{ $smsReady ? '' : 'disabled' }}>
                                <label class="form-check-label" for="ch_sms">
                                    SMS (Notify.lk)
                                    @unless($smsReady)<span class="text-muted small">— configure first</span>@endunless
                                </label>
                            </div>
                        </div>
                        @error('channels')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Audience</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience" id="aud_all" value="all"
                                   {{ old('audience', 'all') === 'all' ? 'checked' : '' }} onchange="toggleAudience()">
                            <label class="form-check-label" for="aud_all">
                                All active customers with a phone number ({{ $customers->count() }})
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience" id="aud_loyalty" value="loyalty"
                                   {{ old('audience') === 'loyalty' ? 'checked' : '' }} onchange="toggleAudience()">
                            <label class="form-check-label" for="aud_loyalty">
                                Loyalty stamp-card members ({{ $loyaltyCount }})
                                <span class="text-muted small d-block">Send offers / rewards to people who already joined</span>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience" id="aud_not_loyalty" value="not_loyalty"
                                   {{ old('audience') === 'not_loyalty' ? 'checked' : '' }} onchange="toggleAudience()">
                            <label class="form-check-label" for="aud_not_loyalty">
                                Not on loyalty yet ({{ $notLoyaltyCount }})
                                <span class="text-muted small d-block">Invite them to join the stamp card</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="audience" id="aud_sel" value="selected"
                                   {{ old('audience') === 'selected' ? 'checked' : '' }} onchange="toggleAudience()">
                            <label class="form-check-label" for="aud_sel">Select customers</label>
                        </div>
                        @unless($loyaltyEnabled)
                            <div class="form-text text-warning mt-2">Loyalty is currently off in Settings — enable stamp cards to grow this audience.</div>
                        @endunless
                    </div>

                    <div class="mb-3" id="loyaltyTips" style="display:none;">
                        <div class="alert alert-light border small mb-0" id="tipLoyalty" style="display:none;">
                            <strong>Idea for members:</strong>
                            <button type="button" class="btn btn-link btn-sm p-0 align-baseline" onclick="fillMessageTemplate('loyalty')">Use sample</button>
                            <div class="text-muted mt-1">Double stamps this weekend · redeem your {{ $rewardLabel }} · member-only offer</div>
                        </div>
                        <div class="alert alert-light border small mb-0" id="tipNotLoyalty" style="display:none;">
                            <strong>Idea to invite:</strong>
                            <button type="button" class="btn btn-link btn-sm p-0 align-baseline" onclick="fillMessageTemplate('not_loyalty')">Use sample</button>
                            <div class="text-muted mt-1">Ask them to join our free stamp card next visit — collect drinks → get a {{ $rewardLabel }}</div>
                        </div>
                    </div>

                    <div class="mb-3" id="customerPicker" style="display:none;">
                        <label class="form-label">Customers</label>
                        <select name="customer_ids[]" class="form-select" multiple size="10">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(in_array($c->id, old('customer_ids', [])))>
                                    {{ $c->name }} — {{ $c->phone }}{{ $c->loyalty_joined ? ' ★' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple. ★ = loyalty member.</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" {{ (!$whatsappReady && !$smsReady) ? 'disabled' : '' }}
                                onclick="return confirm('Send this promo now? This cannot be undone.');">
                            <i class="fas fa-paper-plane me-1"></i>Send now
                        </button>
                        <a href="{{ route('admin.promos.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">How it works</h3></div>
            <div class="card-body text-muted">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Configure <strong>Meta WhatsApp Cloud API</strong> and/or <strong>Notify.lk</strong> (SMS) under Settings → Notifications.</li>
                    <li class="mb-2">Write your offer message.</li>
                    <li class="mb-2">Choose WhatsApp, SMS, or both.</li>
                    <li class="mb-2">Pick audience: everyone, <strong>loyalty members</strong> (promos), <strong>not joined</strong> (invite), or hand-pick.</li>
                    <li>Send — cashiers can enroll non-members on POS when they visit.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
const rewardLabel = @json($rewardLabel);
const companyName = @json($companyName);

function toggleAudience() {
    const selected = document.getElementById('aud_sel').checked;
    const loyalty = document.getElementById('aud_loyalty').checked;
    const notLoyalty = document.getElementById('aud_not_loyalty').checked;
    document.getElementById('customerPicker').style.display = selected ? 'block' : 'none';
    const tips = document.getElementById('loyaltyTips');
    tips.style.display = (loyalty || notLoyalty) ? 'block' : 'none';
    document.getElementById('tipLoyalty').style.display = loyalty ? 'block' : 'none';
    document.getElementById('tipNotLoyalty').style.display = notLoyalty ? 'block' : 'none';
}

function fillMessageTemplate(kind) {
    const ta = document.querySelector('textarea[name="message"]');
    const title = document.querySelector('input[name="title"]');
    if (!ta) return;
    if (kind === 'loyalty') {
        if (title && !title.value) title.value = 'Loyalty member offer';
        ta.value = 'Hi! As a stamp-card member, enjoy a special offer on your next visit. Keep collecting stamps toward your ' + rewardLabel + '. See you soon at ' + companyName + '!';
    } else {
        if (title && !title.value) title.value = 'Join our stamp card';
        ta.value = 'Hi! Join our free stamp card next time you visit ' + companyName + '. Collect stamps on drinks and unlock a ' + rewardLabel + '. Ask any cashier to add you — it only takes a minute!';
    }
}
toggleAudience();
</script>
@endsection
