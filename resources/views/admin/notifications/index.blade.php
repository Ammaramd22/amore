@extends('layouts.admin')
@section('title', 'Notifications')
@section('page_title', 'Notifications')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">Notifications &amp; Reminders</h2>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave me-2" style="color:#d97706;"></i>Payment reminder</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('notifications.send') }}">
                    @csrf
                    <input type="hidden" name="kind" value="payment_reminder">
                    <p class="text-muted small mb-3">In-app goes to the user whose <strong>email</strong> matches (restaurant admin). Software owner will not see it on their own bell.</p>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', 'Payment reminder — QRPOS') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" name="email" class="form-control" value="{{ old('email', $config['payment_email']) }}" placeholder="client@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone (SMS / WhatsApp)</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $config['payment_phone']) }}" placeholder="07XXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Channels</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="pay_email" checked>
                            <label class="form-check-label" for="pay_email">Email</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="pay_sms">
                            <label class="form-check-label" for="pay_sms">SMS</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="pay_wa">
                            <label class="form-check-label" for="pay_wa">WhatsApp</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="inapp" id="pay_inapp" checked>
                            <label class="form-check-label" for="pay_inapp">In-app (header bell)</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="7" required>{{ old('message', $defaults['payment']) }}</textarea>
                    </div>
                    @can('notifications.send')
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Send payment reminder</button>
                    @endcan
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server me-2" style="color:#d97706;"></i>Hosting reminder</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('notifications.send') }}">
                    @csrf
                    <input type="hidden" name="kind" value="hosting_reminder">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="Hosting renewal reminder — QRPOS">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" name="email" class="form-control" value="{{ old('email', $config['hosting_email']) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone (SMS / WhatsApp)</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $config['hosting_phone']) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-block">Channels</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="host_email" checked>
                            <label class="form-check-label" for="host_email">Email</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="host_sms">
                            <label class="form-check-label" for="host_sms">SMS</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="host_wa">
                            <label class="form-check-label" for="host_wa">WhatsApp</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="inapp" id="host_inapp" checked>
                            <label class="form-check-label" for="host_inapp">In-app (header bell)</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="7" required>{{ old('kind') === 'hosting_reminder' ? old('message') : $defaults['hosting'] }}</textarea>
                    </div>
                    @can('notifications.send')
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Send hosting reminder</button>
                    @endcan
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-robot me-2" style="color:#d97706;"></i>Automated hosting reminder</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">When enabled, QRPOS sends a reminder once per day while today is within <strong>days before</strong> the renewal date (inclusive). Requires a daily cron: <code>php artisan schedule:run</code>.</p>
        <form method="POST" action="{{ route('notifications.hosting-auto') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label d-block">Status</label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="hosting_reminder_enabled" value="1" id="host_auto" {{ $config['hosting_enabled'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="host_auto">Enable auto reminder</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Renewal due date</label>
                    <input type="date" name="hosting_reminder_due_date" class="form-control" value="{{ $config['hosting_due_date'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Days before</label>
                    <input type="number" name="hosting_reminder_days_before" class="form-control" min="0" max="90" value="{{ $config['hosting_days_before'] }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email recipients</label>
                    <input type="text" name="hosting_reminder_email" class="form-control" value="{{ $config['hosting_email'] }}" placeholder="comma-separated">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="hosting_reminder_phone" class="form-control" value="{{ $config['hosting_phone'] }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Auto channels</label>
                    @foreach(['email','sms','whatsapp','inapp'] as $ch)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="hosting_reminder_channels[]" value="{{ $ch }}" id="auto_{{ $ch }}"
                            {{ in_array($ch, $config['hosting_channels'], true) || ($ch === 'inapp' && empty($config['hosting_channels'])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_{{ $ch }}">{{ $ch === 'inapp' ? 'In-app' : ucfirst($ch) }}</label>
                    </div>
                    @endforeach
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="text-muted small">
                        Last auto send: <strong>{{ $config['hosting_last_sent'] ?: '—' }}</strong>
                    </div>
                </div>
            </div>
            @can('notifications.send')
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save auto hosting</button>
            </div>
            @endcan
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Reminder history</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Channel</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td><span class="badge-soft">{{ str_replace('_', ' ', $log->reference_type ?? '—') }}</span></td>
                        <td>{{ strtoupper($log->type) }} <span class="text-muted small">({{ $log->provider }})</span></td>
                        <td>{{ $log->to }}</td>
                        <td>
                            <span class="badge-soft" style="{{ $log->status === 'sent' ? 'color:#15803d;background:rgba(34,197,94,.12)' : 'color:#b91c1c;background:rgba(239,68,68,.12)' }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $log->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No reminders sent yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
