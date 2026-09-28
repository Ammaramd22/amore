@extends('layouts.admin')
@section('title', 'User Guide')
@section('page_title', 'User Guide')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">{{ $guides[$lang]['title'] ?? 'Guide' }}</h2>
    <div class="toolbar-actions" style="display:flex;gap:.4rem;flex-wrap:wrap">
        <a href="{{ route('guide.index', ['lang' => 'en']) }}" class="btn btn-sm {{ $lang === 'en' ? 'btn-primary' : 'btn-outline-secondary' }}">English</a>
        <a href="{{ route('guide.index', ['lang' => 'ta']) }}" class="btn btn-sm {{ $lang === 'ta' ? 'btn-primary' : 'btn-outline-secondary' }}">தமிழ்</a>
        <a href="{{ route('guide.index', ['lang' => 'si']) }}" class="btn btn-sm {{ $lang === 'si' ? 'btn-primary' : 'btn-outline-secondary' }}">සිංහල</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                @foreach(($guides[$lang]['sections'] ?? []) as $section)
                <div class="mb-4">
                    <h4 style="font-weight:750;letter-spacing:-.02em;">{{ $section['h'] }}</h4>
                    <p class="text-muted mb-0" style="line-height:1.55;">{{ $section['p'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Languages</h3></div>
            <div class="card-body text-muted small">
                Switch English / Tamil / Sinhala anytime. This guide stays available under Settings for every handover.
            </div>
        </div>
        @if(auth()->user()?->isSoftwareOwner() || auth()->user()?->can('settings.system'))
        <div class="card border-danger">
            <div class="card-header"><h3 class="card-title text-danger">Fresh start (owner)</h3></div>
            <div class="card-body">
                <p class="small text-muted">Wipe demo / restaurant data when giving the system to a customer. Keeps schema &amp; creates a new admin.</p>
                @if(session('error'))
                    <div class="alert alert-danger py-2">{{ session('error') }}</div>
                @endif
                <form method="POST" action="{{ route('guide.fresh-start') }}" onsubmit="return confirm('This deletes orders, products, customers, etc. Continue?');">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Type FRESH START</label>
                        <input type="text" name="confirm" class="form-control form-control-sm" required placeholder="FRESH START">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Business name</label>
                        <input type="text" name="business_name" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New admin name</label>
                        <input type="text" name="name" class="form-control form-control-sm" required value="Administrator">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New admin email</label>
                        <input type="email" name="email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New password</label>
                        <input type="password" name="password" class="form-control form-control-sm" required minlength="6">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">PIN</label>
                        <input type="text" name="pin" class="form-control form-control-sm" value="1111">
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100">Run fresh start</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
