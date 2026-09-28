@extends('layouts.admin')
@section('title', 'Promo Campaigns')
@section('page_title', 'Promo Campaigns')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">Promo Campaigns</h2>
    <div class="toolbar-actions">
        <a href="{{ route('admin.promos.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-paper-plane me-1"></i>New Promo
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="report-card" style="padding:1rem 1.15rem;">
            <div class="d-flex align-items-center gap-3">
                <span class="badge-soft" style="background:{{ $whatsappReady ? 'rgba(37,211,102,.15)' : 'rgba(120,113,108,.12)' }};color:{{ $whatsappReady ? '#15803d' : '#78716c' }};">
                    <i class="fab fa-whatsapp me-1"></i>WhatsApp
                </span>
                <div>
                    <div class="fw-semibold">{{ $whatsappReady ? 'Ready (Meta Cloud)' : 'Not configured' }}</div>
                    <div class="text-muted small">Settings → Notifications → Meta WhatsApp</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="report-card" style="padding:1rem 1.15rem;">
            <div class="d-flex align-items-center gap-3">
                <span class="badge-soft" style="background:{{ $smsReady ? 'rgba(37,99,235,.12)' : 'rgba(120,113,108,.12)' }};color:{{ $smsReady ? '#1d4ed8' : '#78716c' }};">
                    <i class="fas fa-sms me-1"></i>SMS
                </span>
                <div>
                    <div class="fw-semibold">{{ $smsReady ? 'Ready (Notify.lk)' : 'Not configured' }}</div>
                    <div class="text-muted small">Settings → Notifications → SMS / Notify.lk</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Campaign history</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="bulk-table table table-hover mb-0" data-export="promos" data-can-delete="0">
                <thead>
                    <tr><th class="col-check"><input type="checkbox" class="form-check-input bulk-check-all" aria-label="Select all"></th><th class="col-num">#</th>
                        <th>Title</th>
                        <th>Channels</th>
                        <th>Audience</th>
                        <th>Results</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $c)
                    <tr>
                        <td class="col-check"><input type="checkbox" class="form-check-input bulk-row-check" value="{{ $c->id }}" aria-label="Select row"></td>
                        <td class="col-num">{{ (($campaigns->currentPage() - 1) * $campaigns->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $c->title }}</td>
                        <td>
                            @foreach($c->channels ?? [] as $ch)
                                <span class="badge-soft me-1">{{ strtoupper($ch) }}</span>
                            @endforeach
                        </td>
                        <td class="text-muted">{{ $c->audienceLabel() }}</td>
                        <td>
                            <span class="text-success">{{ $c->sent_count }} ok</span>
                            @if($c->failed_count)
                                <span class="text-danger ms-1">{{ $c->failed_count }} fail</span>
                            @endif
                            <span class="text-muted small">/ {{ $c->recipient_count }}</span>
                        </td>
                        <td>
                            <span class="badge-soft">{{ ucfirst($c->status) }}</span>
                        </td>
                        <td class="text-muted small">{{ $c->sent_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.promos.show', $c) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No promos yet. Create one to send offers via WhatsApp or SMS.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())
    <div class="card-footer">{{ $campaigns->links() }}</div>
    @endif
</div>
@endsection
