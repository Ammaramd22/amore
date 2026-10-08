@props([
    'resource' => null,
    'export' => 'export',
    'canDelete' => true,
])
<div class="bulk-toolbar d-none" data-bulk-toolbar data-resource="{{ $resource }}" data-export="{{ $export }}" data-can-delete="{{ $canDelete ? '1' : '0' }}">
    <div class="bulk-toolbar-inner">
        <span class="bulk-toolbar-count"><strong class="bulk-selected-count">0</strong> selected</span>
        <div class="bulk-toolbar-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary bulk-export-btn">
                <i class="fas fa-file-export me-1"></i>Export CSV
            </button>
            @if($canDelete && $resource)
            <button type="button" class="btn btn-sm btn-outline-danger bulk-delete-btn">
                <i class="fas fa-trash me-1"></i>Bulk Delete
            </button>
            @endif
            <button type="button" class="btn btn-sm btn-link text-muted bulk-clear-btn">Clear</button>
        </div>
    </div>
</div>
