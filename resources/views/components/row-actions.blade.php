@props(['align' => 'end'])
<div class="dropdown row-actions">
    <button
        type="button"
        class="btn-icon dropdown-toggle"
        data-bs-toggle="dropdown"
        data-bs-boundary="viewport"
        data-bs-popper-config='{"strategy":"fixed","modifiers":[{"name":"preventOverflow","options":{"boundary":"viewport"}}]}'
        aria-expanded="false"
        aria-label="Actions"
    >
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-{{ $align }} shadow">
        {{ $slot }}
    </ul>
</div>
