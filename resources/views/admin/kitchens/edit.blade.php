@extends('layouts.admin')
@section('title', 'Edit Kitchen')
@section('page_title', 'Edit Kitchen')
@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Kitchen</h3>
                <a href="{{ route('kitchens.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('kitchens.update', $kitchen) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Kitchen Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $kitchen->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Code *</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $kitchen->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Kitchen Type *</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="kot" {{ old('type', $kitchen->type) === 'kot' ? 'selected' : '' }}>KOT (Kitchen Order Ticket)</option>
                                <option value="bot" {{ old('type', $kitchen->type) === 'bot' ? 'selected' : '' }}>BOT (Bar Order Ticket)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $kitchen->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="print_mode" class="form-label">Print Mode</label>
                            <select class="form-select @error('print_mode') is-invalid @enderror" id="print_mode" name="print_mode">
                        <option value="direct" @selected(old('print_mode', $kitchen->print_mode ?? 'direct') === 'direct')>Direct — Print Bridge / Windows printer (no Chrome dialog)</option>
                                <option value="preview" @selected(old('print_mode', $kitchen->print_mode ?? 'direct') === 'preview')>Preview — browser (BOT only; KOT always Direct)</option>
                            </select>
                            @error('print_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Same as receipt Direct print. Run Start-Print-Bridge.bat on the POS PC.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="printer_name" class="form-label">Windows Printer Name</label>
                            <input type="text" class="form-control @error('printer_name') is-invalid @enderror" id="printer_name" name="printer_name" value="{{ old('printer_name', $kitchen->printer_name) }}" placeholder="e.g. Kitchen-XP80 / XP-80C">
                            @error('printer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Must match Devices and Printers name for USB Direct print.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="printer_ip" class="form-label">Printer IP (optional)</label>
                            <input type="text" class="form-control @error('printer_ip') is-invalid @enderror" id="printer_ip" name="printer_ip" value="{{ old('printer_ip', $kitchen->printer_ip) }}" placeholder="e.g., 192.168.1.100">
                            @error('printer_ip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">LAN ESC/POS IP (e.g. 192.168.1.11). Cloud POS prints via Local Print Bridge on this PC → TCP to this IP. Keep Start-Print-Bridge.bat running on the POS PC.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="printer_port" class="form-label">Printer Port</label>
                            <input type="number" class="form-control @error('printer_port') is-invalid @enderror" id="printer_port" name="printer_port" value="{{ old('printer_port', $kitchen->printer_port ?? 9100) }}" min="1" max="65535">
                            @error('printer_port')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Usually 9100 for ESC/POS raw printing.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Printing</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="auto_print" name="auto_print" value="1" {{ old('auto_print', $kitchen->auto_print) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_print">Auto-print KOT/BOT when order is placed</label>
                        </div>
                        <small class="text-muted">Each kitchen can use a different printer. Direct mode uses Local Print Bridge (same as receipt).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $kitchen->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                        <a href="{{ route('kitchens.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Assign Categories</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('kitchens.assign-categories', $kitchen) }}" method="POST">
                    @csrf

                    <p class="text-muted small mb-3">
                        Tick categories for this kitchen, then save.
                        Includes <strong>KOT</strong>, <strong>BOT</strong>, and <strong>Direct</strong> categories.
                    </p>

                    @if($categories->isEmpty())
                        <div class="text-muted small">No categories yet. Create categories first.</div>
                    @else
                    @php
                        $groups = [
                            'kot' => ['label' => 'KOT (Kitchen)', 'class' => 'warning text-dark'],
                            'bot' => ['label' => 'BOT (Bar)', 'class' => 'info'],
                            'direct' => ['label' => 'Direct items', 'class' => 'success'],
                        ];
                        $grouped = $categories->groupBy(fn ($c) => $c->type ?: 'kot');
                    @endphp
                    <div class="mb-3" style="max-height: 420px; overflow-y: auto;">
                        @foreach($groups as $type => $meta)
                            @php $items = $grouped->get($type, collect()); @endphp
                            @if($items->isEmpty())
                                @continue
                            @endif
                            <div class="mb-2 mt-2 small fw-bold text-uppercase text-muted d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $meta['class'] }}">{{ strtoupper($type) }}</span>
                                {{ $meta['label'] }}
                                <span class="fw-normal">({{ $items->count() }})</span>
                            </div>
                            @foreach($items as $category)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category->id }}" id="cat_{{ $category->id }}"
                                    @checked((int) $category->kitchen_id === (int) $kitchen->id)>
                                <label class="form-check-label" for="cat_{{ $category->id }}">
                                    {{ $category->name }}
                                    @if($category->kitchen_id && (int) $category->kitchen_id !== (int) $kitchen->id)
                                        <span class="badge bg-warning text-dark ms-1">{{ $category->kitchen?->name ?? 'Other kitchen' }}</span>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        @endforeach

                        {{-- Any unexpected type leftover --}}
                        @foreach($grouped as $type => $items)
                            @if(isset($groups[$type])) @continue @endif
                            <div class="mb-2 mt-2 small fw-bold text-muted">{{ strtoupper($type) }}</div>
                            @foreach($items as $category)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category->id }}" id="cat_{{ $category->id }}"
                                    @checked((int) $category->kitchen_id === (int) $kitchen->id)>
                                <label class="form-check-label" for="cat_{{ $category->id }}">{{ $category->name }}</label>
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                    @endif

                    <div class="form-actions border-0 pt-0">
                        <button type="submit" class="btn btn-primary w-100" @disabled($categories->isEmpty())>
                            <i class="fas fa-link me-1"></i>Save Assignments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
