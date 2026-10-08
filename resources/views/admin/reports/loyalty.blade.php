@extends('layouts.admin')
@section('title', 'Loyalty Report')
@section('page_title', 'Loyalty Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-stamp"></i> Loyalty stamp cards</h3>
            <p class="rpt-sub">{{ $config['stamps_required'] }} stamps = {{ $config['reward_label'] }} · members & free drinks</p>
        </div>
        <form method="GET" class="rpt-filters">
            @include('partials.branch-filter', ['branchFilter' => $branchFilter ?? ['enabled'=>false], 'branchFilterAutoSubmit' => true])

            <div>
                <label for="from">From</label>
                <input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $from }}" onchange="this.form.submit()">
            </div>
            <div>
                <label for="to">To</label>
                <input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ $to }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="rpt-kpis">
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($summary['members'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Joined members</div>
            <i class="fas fa-id-card rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['stamps'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Stamps earned</div>
            <i class="fas fa-stamp rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ number_format($summary['free_issued'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Free drinks issued</div>
            <i class="fas fa-gift rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-value">{{ number_format($summary['free_ready'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Credits ready</div>
            <i class="fas fa-mug-hot rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-charts">
        <div class="rpt-chart-box">
            <h4><i class="fas fa-gift"></i> Free drinks by member</h4>
            <div class="rpt-chart-wrap is-tall"><canvas id="loyRedeemChart"></canvas></div>
        </div>
        <div class="rpt-chart-box">
            <h4><i class="fas fa-stamp"></i> Stamps earned</h4>
            <div class="rpt-chart-wrap"><canvas id="loyStampChart"></canvas></div>
        </div>
    </div>

    <div class="rpt-panel mb-3">
        <div class="rpt-panel-head"><h4>Loyal customers</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th class="text-end">Card stamps</th>
                        <th class="text-end">Free ready</th>
                        <th class="text-end">Stamps (period)</th>
                        <th class="text-end">Free issued (period)</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $i => $m)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $m->name }}</td>
                        <td>{{ $m->phone ?: '—' }}</td>
                        <td class="text-end">{{ $m->loyalty_stamps }} / {{ $config['stamps_required'] }}</td>
                        <td class="text-end">
                            @if($m->loyalty_free_drinks > 0)
                                <span class="badge bg-success">{{ $m->loyalty_free_drinks }}</span>
                            @else —
                            @endif
                        </td>
                        <td class="text-end">{{ $m->stamps_earned ?? 0 }}</td>
                        <td class="text-end fw-bold" style="color:#059669;">{{ $m->free_redeemed ?? 0 }}</td>
                        <td>
                            @if($m->loyalty_expires_at)
                                @if($m->loyalty_expires_at->isPast())
                                    <span class="badge bg-danger">Expired</span>
                                @else
                                    {{ $m->loyalty_expires_at->format('d M Y') }}
                                @endif
                            @else Never
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="rpt-empty">No loyalty members yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-panel">
        <div class="rpt-panel-head"><h4>Free drinks issued (period)</h4></div>
        <div class="table-responsive">
            <table class="table rpt-table mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Customer</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redeems as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td class="fw-semibold">{{ $log->customer?->name ?? '—' }}</td>
                        <td>{{ $log->note ?: $config['reward_label'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="rpt-empty">No free drinks redeemed in this period</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rpt-footer">QRPOS BY AVENQUE <span>|</span> 076 822 2201</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const redeemChart = @json($charts['redeems'] ?? ['labels'=>[], 'data'=>[]]);
const stampChart = @json($charts['stamps'] ?? ['labels'=>[], 'data'=>[]]);
new Chart(document.getElementById('loyRedeemChart'), {
    type: 'bar',
    data: { labels: redeemChart.labels, datasets: [{ label: 'Free drinks', data: redeemChart.data, backgroundColor: '#10b981' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
new Chart(document.getElementById('loyStampChart'), {
    type: 'bar',
    data: { labels: stampChart.labels, datasets: [{ label: 'Stamps', data: stampChart.data, backgroundColor: '#f59e0b' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
</script>
@endpush
