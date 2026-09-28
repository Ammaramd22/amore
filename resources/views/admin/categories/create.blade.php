@extends('layouts.admin')
@section('title', 'Add Category')
@section('page_title', 'Add Category')
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Category</h3>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('categories.store') }}" class="category-form" enctype="multipart/form-data">
            @csrf
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
            <div class="mb-3">
                <label class="form-label">Category image</label>
                <label for="image" class="cat-upload">
                    <div class="cat-upload-preview" id="imagePreview">
                        <i class="fas fa-camera"></i>
                        <span>Click to upload (POS bakery categories)</span>
                    </div>
                    <input type="file" id="image" name="image" accept="image/*" class="d-none" onchange="previewCategoryImage(this)">
                </label>
                <div class="form-text">PNG/JPG/WebP, max 2MB. Shown on Bakery POS category list.</div>
            </div>
            <div class="mb-3"><label class="form-label">Color</label><input type="color" name="color" class="form-control" value="#e74c3c"></div>
            <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control" value="0"></div>
            <div class="mb-3">
                <label class="form-label">Category Type</label>
                <select name="type" class="form-select" required>
                    <option value="kot">KOT Product (Kitchen order)</option>
                    <option value="direct">Direct Item (Track stock)</option>
                    <option value="bot">BOT Product (Bar order)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Kitchen / Bar</label>
                <select name="kitchen_id" id="kitchen_id" class="form-select">
                    <option value="">-- Select Kitchen/Bar --</option>
                    @foreach($kitchens as $kitchen)
                    <option value="{{ $kitchen->id }}">{{ $kitchen->name }} ({{ strtoupper($kitchen->type) }})</option>
                    @endforeach
                </select>
                <small class="text-muted" id="kitchenHint">KOT/BOT categories should be linked to a kitchen or bar. Direct items can also be linked if needed.</small>
            </div>
            <div class="mb-3 form-check"><input type="checkbox" name="show_in_pos" value="1" class="form-check-input" checked id="sip"><label class="form-check-label" for="sip">Show in POS</label></div>
            <div class="mb-3 form-check"><input type="checkbox" name="show_in_qr" value="1" class="form-check-input" checked id="siq"><label class="form-check-label" for="siq">Show in QR Menu</label></div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    .category-form .form-control,
    .category-form .form-select {
        border: 2px solid #000;
    }
    .cat-upload { display: block; cursor: pointer; }
    .cat-upload-preview {
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #64748b;
        background: #f8fafc;
        overflow: hidden;
    }
    .cat-upload-preview:hover { border-color: #f59e0b; background: #fffbeb; color: #c2410c; }
    .cat-upload-preview.has-image { border-style: solid; padding: 0; background: #fff; }
    .cat-upload-preview.has-image img { width: 100%; height: 160px; object-fit: cover; display: block; }
</style>
<script>
function previewCategoryImage(input) {
    const box = document.getElementById('imagePreview');
    if (!input.files?.[0] || !box) return;
    const url = URL.createObjectURL(input.files[0]);
    box.classList.add('has-image');
    box.innerHTML = `<img src="${url}" alt="">`;
}
</script>
@endsection
