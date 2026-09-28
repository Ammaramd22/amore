@extends('layouts.admin')
@section('title', 'Billiards Bookings')
@section('page_title', 'Bookings')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['booked','active','completed','cancelled','no_show'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Payment</label>
                <select name="payment_status" class="form-select">
                    <option value="">All</option>
                    @foreach(['unpaid','partial','paid'] as $s)
                        <option value="{{ $s }}" @selected(request('payment_status')===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label">Search</label>
                <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Number, name, phone">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-dark flex-fill">Filter</button>
                <a href="{{ route('billiards.bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">All bookings</h3>
            <small class="text-muted">Open a row to collect, print, or complete</small>
        </div>
        <a href="{{ route('billiards.pos') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Create booking
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th>Booking</th>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>When</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                    <tr>
                        <td class="col-num">{{ (($bookings->currentPage() - 1) * $bookings->perPage() + $loop->iteration) }}</td>
                        <td class="fw-semibold">{{ $b->booking_number }}</td>
                        <td>{{ $b->table?->name ?? '—' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $b->displayName() }}</div>
                            @if($b->displayPhone())
                                <small class="text-muted">{{ $b->displayPhone() }}</small>
                            @endif
                        </td>
                        <td>
                            <div>{{ $b->scheduled_start?->format('d M Y') }}</div>
                            <small class="text-muted">{{ $b->scheduled_start?->format('H:i') }}–{{ $b->scheduled_end?->format('H:i') }} · {{ rtrim(rtrim(number_format((float)$b->hours,2),'0'),'.') }}h</small>
                        </td>
                        <td class="fw-semibold">{{ $currency }} {{ number_format((float)$b->amount, 2) }}</td>
                        <td><span class="badge rounded-pill text-bg-{{ $b->statusBadgeClass() }}">{{ $b->statusLabel() }}</span></td>
                        <td><span class="badge rounded-pill text-bg-{{ $b->paymentBadgeClass() }}">{{ ucfirst($b->payment_status) }}</span></td>
                        <td class="text-end">
                            <x-row-actions>
                                <li>
                                    <a class="dropdown-item" href="{{ route('billiards.bookings.show', $b) }}">
                                        <i class="fas fa-eye"></i> Open / Pay
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('billiards.bookings.print', $b) }}?format=html&autoprint=1" target="_blank">
                                        <i class="fas fa-print"></i> Print 80mm
                                    </a>
                                </li>
                                @if($b->status === 'booked')
                                <li>
                                    <form method="post" action="{{ route('billiards.bookings.start', $b) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="fas fa-play"></i> Start</button>
                                    </form>
                                </li>
                                @endif
                                @if($b->status === 'active')
                                <li>
                                    <form method="post" action="{{ route('billiards.bookings.complete', $b) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="fas fa-flag-checkered"></i> Complete</button>
                                    </form>
                                </li>
                                @endif
                                @if(in_array($b->status, ['booked','active'], true))
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" action="{{ route('billiards.bookings.cancel', $b) }}" onsubmit="return confirm('Cancel this booking?')">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-ban"></i> Cancel</button>
                                    </form>
                                </li>
                                @endif
                            </x-row-actions>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state py-4">
                                <i class="fas fa-calendar-check"></i>
                                No bookings match — try clearing filters or create a booking
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-footer">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
