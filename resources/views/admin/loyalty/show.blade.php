@extends('layouts.admin')
@section('title', 'Loyalty · '.$customer->name)
@section('page_title', 'Loyalty card')

@section('content')
<div class="purch-toolbar">
    <div>
        <h3 class="purch-title">{{ $customer->name }}</h3>
        <p class="purch-sub mb-0">{{ $customer->phone }} · {{ $payload['stamps'] }}/{{ $payload['stamps_required'] }} stamps · {{ $payload['free_drinks'] }} free</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('loyalty.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ $cardUrl }}" target="_blank" class="btn btn-primary"><i class="fas fa-external-link-alt me-1"></i>Open digital card</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card text-center p-4">
            <img src="{{ $qrImage }}" alt="Loyalty QR" class="mx-auto mb-3" style="width:220px;height:220px;border-radius:16px;border:1px solid #e7e5e4;">
            <div class="fw-bold mb-1">Scan on POS each visit</div>
            <div class="text-muted small mb-3">Or select customer by phone</div>
            <a href="{{ route('loyalty.wallet', $customer->loyalty_token) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                Apple / Google Wallet tips
            </a>
            <div class="mt-3">
                <input class="form-control form-control-sm" readonly value="{{ $cardUrl }}" onclick="this.select()">
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Stamp history</strong></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>When</th><th>Type</th><th>Δ</th><th>After</th><th>Note</th></tr></thead>
                    <tbody>
                        @forelse($customer->loyaltyLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M H:i') }}</td>
                            <td>{{ ucfirst($log->type) }}</td>
                            <td>{{ $log->stamps_delta >= 0 ? '+'.$log->stamps_delta : $log->stamps_delta }} stamp / {{ $log->free_delta }} free</td>
                            <td>{{ $log->stamps_after }} / free {{ $log->free_after }}</td>
                            <td class="small text-muted">{{ $log->note }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-4">No activity yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.purch-toolbar{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.purch-title{margin:0;font-size:1.2rem;font-weight:800}
.purch-sub{font-size:.85rem;color:#78716c}
</style>
@endpush
