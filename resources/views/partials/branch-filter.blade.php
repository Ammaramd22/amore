@php
    $bf = $branchFilter ?? ['enabled' => false, 'key' => 'all', 'branches' => collect()];
    $bfKey = (string) ($bf['key'] ?? 'all');
    $bfBranches = $bf['branches'] ?? collect();
    $autoSubmit = ! empty($branchFilterAutoSubmit);
@endphp
@if(! empty($bf['enabled']))
<div>
    <label for="{{ $branchFilterId ?? 'branch_id' }}">{{ $branchFilterLabel ?? 'Branch' }}</label>
    <select
        id="{{ $branchFilterId ?? 'branch_id' }}"
        name="branch_id"
        class="form-select form-select-sm"
        style="min-width:150px"
        @if($autoSubmit) onchange="this.form.submit()" @endif
    >
        <option value="all" @selected($bfKey === 'all')>All branches</option>
        <option value="main" @selected($bfKey === 'main')>Main branch</option>
        @foreach($bfBranches as $b)
            <option value="{{ $b->id }}" @selected($bfKey === (string) $b->id)>
                {{ $b->name }}{{ $b->is_main ? ' (Main)' : '' }}
            </option>
        @endforeach
    </select>
</div>
@endif
