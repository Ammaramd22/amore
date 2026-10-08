@extends('layouts.admin')
@section('title', 'Cancelled Bills Report')
@section('page_title', 'Cancelled Bills Report')
@section('content')
@php $cur = $currency ?? 'LKR'; @endphp
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-ban"></i> Cancelled Bills</h3>
            <p class="rpt-sub">Voided and cancelled open bills with staff reasons</p>
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
            <div>
                <label for="void_type">Type</label>
                <select id="void_type" name="void_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="cancel" @selected(($typeFilter ?? '') === 'cancel')>Cancelled</option>
                    <option value="void" @selected(($typeFilter ?? '') === 'void')>Voided</option>
                </select>
            </div>
        </form>
    </div>

    <div class="rpt-kpis">
        <div class="rpt-kpi is-amber">
            <div class="rpt-kpi-value">{{ number_format($summary['total'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Total bills</div>
            <i class="fas fa-receipt rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-red">
            <div class="rpt-kpi-value">{{ number_format($summary['cancel_count'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Cancelled</div>
            <i class="fas fa-times-circle rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-blue">
            <div class="rpt-kpi-value">{{ number_format($summary['void_count'] ?? 0) }}</div>
            <div class="rpt-kpi-label">Voided</div>
            <i class="fas fa-ban rpt-kpi-icon"></i>
        </div>
        <div class="rpt-kpi is-green">
            <div class="rpt-kpi-value">{{ $cur }} {{ number_format($summary['amount'] ?? 0, 2) }}</div>
            <div class="rpt-kpi-label">Bill value</div>
            <i class="fas fa-coins rpt-kpi-icon"></i>
        </div>
    </div>

    @include('admin.reports.partials.insights', ['insights' => $insights ?? []])

    <div class="rpt-panel" style="margin-top:1rem;">
        <div class="rpt-panel-head">
            <h4><i class="fas fa-list"></i> Bill list</h4>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Invoice</th>
                        <th>Type</th>
                        <th>Table / guest</th>
                        <th>Amount</th>
                        <th>By</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $kind = $order->void_type ?: ($order->is_void ? 'void' : 'cancel');
                        @endphp
                        <tr>
                            <td class="text-nowrap text-muted small">{{ \App\Models\Setting::formatDateTime($order->updated_at, 'Y-m-d H:i') }}</td>
                            <td>
                                <strong>{{ $order->order_number }}</strong>
                                <div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $order->order_type) }}</div>
                            </td>
                            <td>
                                @if($kind === 'cancel')
                                    <span class="badge bg-danger">Cancelled</span>
                                @else
                                    <span class="badge bg-warning text-dark">Voided</span>
                                @endif
                            </td>
                            <td>
                                {{ $order->table?->name ?? '—' }}
                                <div class="small text-muted">{{ $order->waiter?->name ?? $order->cashier?->name ?? '—' }}</div>
                            </td>
                            <td class="text-nowrap fw-semibold">{{ $cur }} {{ number_format((float) $order->total_amount, 2) }}</td>
                            <td>{{ $order->voidedBy?->name ?? '—' }}</td>
                            <td style="max-width:320px;">{{ $order->void_reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No cancelled or voided bills in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($orders, 'links'))
            <div class="p-3">{{ $orders->links() }}</div>
        @endif
    </div>

    <div class="rpt-footer">QRPOS BY AVENQUE <span>|</span> 076 822 2201</div>
</div>
@endsection
