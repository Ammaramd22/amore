@extends('layouts.admin')
@section('title', 'Billiards Reports')
@section('page_title', 'Billiards Reports')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-0">Reports</h3>
            <small class="text-muted">{{ $from }} → {{ $to }}</small>
        </div>
        <a href="{{ route('billiards.desk') }}" class="btn btn-outline-secondary btn-sm">Live Desk</a>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-dark w-100">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Bookings', $totalBookings, 'fa-calendar-check'],
        ['Hours', $totalHours, 'fa-clock'],
        ['Collected', $currency.' '.number_format($totalCollected,0), 'fa-coins'],
        ['Unpaid', $currency.' '.number_format($unpaidTotal,0), 'fa-receipt'],
    ] as [$label,$val,$icon])
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase mb-1">
                    <i class="fas {{ $icon }} me-1"></i>{{ $label }}
                </div>
                <div class="fs-4 fw-bold">{{ $val }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">By payment method</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <tbody>
                            @forelse($byMethod as $method => $amt)
                            <tr>
                                <td>{{ ucfirst(str_replace('_',' ',$method)) }}</td>
                                <td class="text-end fw-semibold">{{ $currency }} {{ number_format($amt,2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2"><div class="empty-state py-4">No payments in range</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">By table</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Sessions</th>
                                <th class="text-end">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byTable as $name => $row)
                            <tr>
                                <td class="fw-semibold">{{ $name }}</td>
                                <td>{{ $row['bookings'] }}</td>
                                <td class="text-end fw-semibold">{{ $currency }} {{ number_format($row['collected'],2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3"><div class="empty-state py-4">No table data</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
