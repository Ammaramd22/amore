@extends('layouts.admin')
@section('title', 'Promo Details')
@section('page_title', 'Promo Details')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">{{ $campaign->title }}</h2>
    <div class="toolbar-actions">
        <a href="{{ route('admin.promos.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Campaign</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4 text-muted">Status</dt>
                    <dd class="col-8">{{ ucfirst($campaign->status) }}</dd>
                    <dt class="col-4 text-muted">Channels</dt>
                    <dd class="col-8">{{ implode(', ', $campaign->channels ?? []) }}</dd>
                    <dt class="col-4 text-muted">Audience</dt>
                    <dd class="col-8">{{ $campaign->audienceLabel() }}</dd>
                    <dt class="col-4 text-muted">Results</dt>
                    <dd class="col-8">{{ $campaign->sent_count }} sent / {{ $campaign->failed_count }} failed / {{ $campaign->recipient_count }} total</dd>
                    <dt class="col-4 text-muted">Sent at</dt>
                    <dd class="col-8">{{ $campaign->sent_at?->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-4 text-muted">By</dt>
                    <dd class="col-8">{{ $campaign->creator?->name ?? '—' }}</dd>
                </dl>
                <hr>
                <div class="text-muted small mb-1">Message</div>
                <div style="white-space:pre-wrap;">{{ $campaign->message }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Delivery log</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->type }} / {{ $log->provider }}</td>
                                <td>{{ $log->to }}</td>
                                <td>
                                    <span class="{{ $log->status === 'sent' ? 'text-success' : 'text-danger' }}">
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d M H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No delivery logs.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
