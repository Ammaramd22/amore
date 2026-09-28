@extends('layouts.admin')
@section('title', 'Billiards Settings')
@section('page_title', 'Billiards Settings')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card" style="max-width:720px">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Module settings</h3>
            <small class="text-muted">Display, alerts, print &amp; SMS</small>
        </div>
        <a href="{{ route('billiards.desk') }}" class="btn btn-outline-secondary btn-sm">Live Desk</a>
    </div>
    <form method="post" action="{{ route('billiards.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">End alert (minutes before)</label>
                <input type="number" min="1" max="120" name="billiards_end_alert_minutes" class="form-control"
                       value="{{ old('billiards_end_alert_minutes', $endAlertMinutes) }}" required>
                <div class="form-text">Desk + Display highlight tables when this many minutes remain.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Display public token (optional)</label>
                <input type="text" name="billiards_display_token" class="form-control"
                       value="{{ old('billiards_display_token', $displayToken) }}" placeholder="Leave empty for open TV URL">
                <div class="form-text">
                    TV URL:
                    <code>{{ url('/billiards/display') }}@if($displayToken)?token=…@endif</code>
                </div>
            </div>

            <hr class="my-4">

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="autoStart"
                       name="billiards_auto_start_on_pay" value="1" @checked(old('billiards_auto_start_on_pay', $autoStartOnPay))>
                <label class="form-check-label fw-semibold" for="autoStart">Auto-start session on Book &amp; Pay</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="printAsk"
                       name="billiards_print_ask" value="1" @checked(old('billiards_print_ask', $printAsk))>
                <label class="form-check-label fw-semibold" for="printAsk">Ask to print 80mm after payment</label>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" for="receiptFooter">80mm receipt footer</label>
                <input type="text" id="receiptFooter" name="billiards_receipt_footer" class="form-control"
                       maxlength="200"
                       value="{{ old('billiards_receipt_footer', $receiptFooter) }}"
                       placeholder="Thanks for playing! See you at the tables.">
                <div class="form-text">Shown on billiards receipts only (not restaurant bills).</div>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="smsOnPay"
                       name="billiards_sms_on_pay" value="1" @checked(old('billiards_sms_on_pay', $smsOnPay))>
                <label class="form-check-label fw-semibold" for="smsOnPay">Offer eBill SMS after payment</label>
            </div>

            <div class="p-3 mb-0 rounded-3 bg-light">
                <div class="fw-bold mb-1">SMS status</div>
                @if($smsReady)
                    <div class="text-success fw-semibold">Configured · provider {{ strtoupper(str_replace('_',' ', $smsProvider)) }}</div>
                    <div class="form-text mb-0">SMS keys live under Admin → Settings → Integrations (software owner).</div>
                @else
                    <div class="text-warning fw-semibold">Not configured</div>
                    <div class="form-text mb-0">Enable Notify.lk or SMSLenz in Integrations to send eBill / reminders.</div>
                @endif
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Save settings
            </button>
        </div>
    </form>
</div>
@endsection
