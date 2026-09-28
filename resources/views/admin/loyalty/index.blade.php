@extends('layouts.admin')
@section('title', 'Loyalty')
@section('page_title', 'Loyalty Stamp Cards')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="purch-toolbar">
    <div>
        <h3 class="purch-title">Loyalty stamp cards</h3>
        <p class="purch-sub mb-0">
            {{ $config['stamps_required'] }} stamps = {{ $config['reward_label'] }}
            · only joined customers · scan QR or select customer on POS
        </p>
    </div>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-primary"><i class="fas fa-user-plus me-1"></i>Customers</a>
</div>

@if(auth()->user()?->isSoftwareOwner() || auth()->user()?->can('settings.system'))
<div class="card mb-3">
    <div class="card-header respos-soft">
        <strong><i class="fas fa-cog me-1 text-warning"></i> Program settings</strong>
    </div>
    <form method="POST" action="{{ route('loyalty.config') }}">
        @csrf
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Stamps for free drink</label>
                <input type="number" min="1" max="50" name="loyalty_stamps_required" class="form-control" value="{{ $config['stamps_required'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Reward name</label>
                <input type="text" name="loyalty_reward_label" class="form-control" value="{{ $config['reward_label'] }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Card expiry (days)</label>
                <input type="number" min="0" max="3650" name="loyalty_card_expiry_days" class="form-control" value="{{ $config['card_expiry_days'] ?? 365 }}">
                <div class="form-text">0 = never expires</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Categories that earn 1 stamp each</label>
                <select name="loyalty_category_ids[]" class="form-select select2" multiple data-placeholder="e.g. Juice">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(in_array($cat->id, $config['category_ids'], true))>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Example: Juice — each juice bought = 1 stamp. At {{ $config['stamps_required'] }}th, free drink credit.</div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary" type="submit">Save program</button>
            <a href="{{ route('settings.index') }}" class="btn btn-link">Owner master switch is in Settings → Loyalty</a>
        </div>
    </form>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search members</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or phone">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table purch-table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Stamps</th>
                    <th>Free drinks</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                <tr>
                    <td class="fw-semibold">{{ $m->name }}</td>
                    <td>{{ $m->phone ?: '—' }}</td>
                    <td>{{ $m->loyalty_stamps }} / {{ $config['stamps_required'] }}</td>
                    <td>
                        @if($m->loyalty_free_drinks > 0)
                            <span class="badge bg-success">{{ $m->loyalty_free_drinks }} ready</span>
                        @else —
                        @endif
                    </td>
                    <td>{{ $m->loyalty_joined_at?->format('d M Y') ?: '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('loyalty.show', $m) }}" class="btn btn-sm btn-outline-secondary">Card</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state py-4">No joined members yet — open a customer and tap Join loyalty</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
    <div class="card-footer">{{ $members->links() }}</div>
    @endif
</div>
@endsection

@push('styles')
<style>
.purch-toolbar{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.purch-title{margin:0;font-size:1.2rem;font-weight:800;color:#1c1917}
.purch-sub{font-size:.85rem;color:#78716c}
.purch-table thead th{font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#78716c;background:#fafaf9}
.card-header.respos-soft{background:#fff7ed;border-bottom:1px solid #fed7aa}
</style>
@endpush
