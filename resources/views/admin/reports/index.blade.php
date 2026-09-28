@extends('layouts.admin')
@section('title', 'Reports')
@section('page_title', 'Reports')
@section('content')
<div class="rpt-shell">
    <div class="rpt-toolbar">
        <div>
            <h3><i class="fas fa-chart-bar"></i> Report hub</h3>
            <p class="rpt-sub">Charts, KPIs and AI insights for every report</p>
        </div>
    </div>

    <div class="report-hub">
        <a href="{{ route('reports.sales') }}" class="report-card">
            <i class="fas fa-chart-line"></i>
            <h5>Sales Report</h5>
            <p>Daily and period sales totals</p>
        </a>
        <a href="{{ route('reports.products') }}" class="report-card">
            <i class="fas fa-hamburger"></i>
            <h5>Product Report</h5>
            <p>Top sellers and item performance</p>
        </a>
        <a href="{{ route('reports.customers') }}" class="report-card">
            <i class="fas fa-users"></i>
            <h5>Customer Report</h5>
            <p>Loyalty and spend by guest</p>
        </a>
        <a href="{{ route('reports.stock') }}" class="report-card">
            <i class="fas fa-warehouse"></i>
            <h5>Stock Report</h5>
            <p>Inventory levels and valuation</p>
        </a>
        <a href="{{ route('reports.waiters') }}" class="report-card">
            <i class="fas fa-user-tie"></i>
            <h5>Waiter Report</h5>
            <p>Service sales by staff</p>
        </a>
        <a href="{{ route('reports.payments') }}" class="report-card">
            <i class="fas fa-money-check-alt"></i>
            <h5>Payment Methods</h5>
            <p>Cash, card, and other tenders</p>
        </a>
        <a href="{{ route('reports.shifts') }}" class="report-card">
            <i class="fas fa-clock"></i>
            <h5>Shift Report</h5>
            <p>Register opens, closes, and cash</p>
        </a>
        <a href="{{ route('reports.day-ends') }}" class="report-card">
            <i class="fas fa-calendar-check"></i>
            <h5>Day End Report</h5>
            <p>Full day totals with all shifts</p>
        </a>
        @if(\App\Services\LoyaltyService::enabled())
        <a href="{{ route('reports.loyalty') }}" class="report-card">
            <i class="fas fa-stamp"></i>
            <h5>Loyalty Report</h5>
            <p>Stamp members and free drinks issued</p>
        </a>
        @endif
        <a href="{{ route('reports.cancelled-bills') }}" class="report-card">
            <i class="fas fa-ban"></i>
            <h5>Cancelled Bills</h5>
            <p>Cancelled bills with staff reasons</p>
        </a>
    </div>

    <div class="rpt-footer">QRPOS BY AVENQUE <span>|</span> 076 822 2201</div>
</div>
@endsection
