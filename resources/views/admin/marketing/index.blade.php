@extends('layouts.admin')
@section('title', 'Marketing')
@section('page_title', 'Customer Display Marketing')
@section('content')
<div class="page-toolbar">
    <h2 class="toolbar-title">Customer Display Marketing</h2>
    <div class="toolbar-actions">
        <a href="{{ route('customer.display') }}" target="_blank" class="btn btn-primary btn-sm">
            <i class="fas fa-tv me-1"></i>Cart Display
        </a>
        <a href="{{ route('customer.display.status') }}" target="_blank" class="btn btn-secondary btn-sm">
            <i class="fas fa-columns me-1"></i>Status Board
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Settings</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.marketing.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="marketing_enabled" value="0">
                            <input class="form-check-input" type="checkbox" id="marketing_enabled" name="marketing_enabled" value="1" {{ ($settings['marketing_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="marketing_enabled"><strong>Enable Marketing Ads</strong></label>
                        </div>
                        <small class="text-muted">When off, customer display shows brand welcome only (no ads).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Welcome Text</label>
                        <input type="text" class="form-control" name="welcome_text" value="{{ $settings['welcome_text'] ?? 'Welcome! Order at the counter' }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ads per day</label>
                            <input type="number" class="form-control" name="ads_per_day" value="{{ $settings['ads_per_day'] ?? 3 }}" min="1" max="20">
                            <small class="text-muted">Max ads shown on the TV today (e.g. 3)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slide interval (sec)</label>
                            <input type="number" class="form-control" name="slide_interval" value="{{ $settings['slide_interval'] ?? 6 }}" min="3" max="60">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Idle timeout (sec)</label>
                        <input type="number" class="form-control" name="idle_timeout" value="{{ $settings['idle_timeout'] ?? 30 }}" min="10" max="300">
                    </div>

                    <div class="alert alert-light border small mb-3">
                        Showing <strong>{{ $todayCount }}</strong> ad(s) on customer display today
                        (limit {{ $settings['ads_per_day'] ?? 3 }}).
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title mb-0">Add Ad</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.marketing.ads.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title (optional)</label>
                        <input type="text" name="title" class="form-control" placeholder="Lunch special" maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poster image *</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                        <small class="text-muted">JPG/PNG/WebP · max 5MB</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Show on date (optional)</label>
                        <input type="date" name="show_on_date" class="form-control">
                        <small class="text-muted">Leave empty = every day. Set a date = only that day.</small>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="ad_active" value="1" checked>
                        <label class="form-check-label" for="ad_active">Enabled</label>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-plus me-1"></i>Add Ad
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Ads library ({{ $ads->count() }})</h3>
            </div>
            <div class="card-body">
                @forelse($ads as $ad)
                    @php $url = $ad->imageUrl(); @endphp
                    <div class="d-flex gap-3 align-items-start border rounded p-3 mb-3" style="border-color:#e7e5e4!important;">
                        <div style="width:120px;height:80px;border-radius:10px;overflow:hidden;background:#f5f5f4;flex-shrink:0;">
                            @if($url)
                                <img src="{{ $url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <div class="d-flex h-100 align-items-center justify-content-center text-muted small">Missing</div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $ad->title ?: 'Ad #'.$ad->id }}</div>
                            <div class="small text-muted mb-2">
                                @if($ad->show_on_date)
                                    Date: {{ $ad->show_on_date->format('Y-m-d') }}
                                @else
                                    Every day
                                @endif
                                ·
                                @if($ad->is_active)
                                    <span class="text-success">Enabled</span>
                                @else
                                    <span class="text-danger">Disabled</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.marketing.ads.toggle', $ad) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $ad->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                        {{ $ad->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.marketing.ads.destroy', $ad) }}" onsubmit="return confirm('Remove this ad?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border mb-0">No ads yet — add your first poster on the left (try 3 for daily rotation).</div>
                @endforelse
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title mb-0">How it works</h3></div>
            <div class="card-body text-muted small">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Turn <strong>Enable Marketing Ads</strong> on</li>
                    <li class="mb-2">Add up to your daily limit (default <strong>3 ads per day</strong>)</li>
                    <li class="mb-2">Enable/disable each ad individually</li>
                    <li class="mb-2">Optional: set a <strong>Show on date</strong> for one-day specials</li>
                    <li>Open <strong>Cart Display</strong> on the customer TV — idle screen slides today’s ads</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
