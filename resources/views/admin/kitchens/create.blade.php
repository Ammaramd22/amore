@extends('layouts.admin')
@section('title', 'Add Kitchen')
@section('page_title', 'Add Kitchen')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Kitchen</h3>
        <a href="{{ route('kitchens.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form action="{{ route('kitchens.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Kitchen Name *</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Code *</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g., PIZZA, BUNS, KOTTHU">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">Kitchen Type *</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="kot" {{ old('type', 'kot') === 'kot' ? 'selected' : '' }}>KOT (Kitchen Order Ticket)</option>
                        <option value="bot" {{ old('type', 'kot') === 'bot' ? 'selected' : '' }}>BOT (Bar Order Ticket)</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="print_mode" class="form-label">Print Mode</label>
                    <select class="form-select @error('print_mode') is-invalid @enderror" id="print_mode" name="print_mode">
                        <option value="direct" @selected(old('print_mode', 'direct') === 'direct')>Direct — Print Bridge / Windows printer (no Chrome dialog)</option>
                        <option value="preview" @selected(old('print_mode', 'direct') === 'preview')>Preview — browser (BOT only; KOT always Direct)</option>
                    </select>
                    @error('print_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Same as receipt Direct print. Run Start-Print-Bridge.bat on the POS PC.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="printer_name" class="form-label">Windows Printer Name</label>
                    <input type="text" class="form-control @error('printer_name') is-invalid @enderror" id="printer_name" name="printer_name" value="{{ old('printer_name') }}" placeholder="e.g. Kitchen-XP80 / XP-80C">
                    @error('printer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Must match Devices and Printers name for USB Direct print.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="printer_ip" class="form-label">Printer IP (optional)</label>
                    <input type="text" class="form-control @error('printer_ip') is-invalid @enderror" id="printer_ip" name="printer_ip" value="{{ old('printer_ip') }}" placeholder="e.g., 192.168.1.100">
                    @error('printer_ip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Ethernet/Wi‑Fi printer. Leave blank for USB via Windows name + Print Bridge.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="printer_port" class="form-label">Printer Port</label>
                    <input type="number" class="form-control @error('printer_port') is-invalid @enderror" id="printer_port" name="printer_port" value="{{ old('printer_port', 9100) }}" min="1" max="65535">
                    @error('printer_port')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Usually 9100 for ESC/POS raw printing.</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Printing</label>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="auto_print" name="auto_print" value="1" {{ old('auto_print', false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="auto_print">Auto-print KOT/BOT when order is placed</label>
                </div>
                <small class="text-muted">Each kitchen can use a different printer. Direct mode uses Local Print Bridge (same as receipt).</small>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('kitchens.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
