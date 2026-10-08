{{-- Inject configured system timezone + server epoch for BusinessClock JS --}}
@php
    $__bizTz = \App\Models\Setting::timezone();
    $__bizServerMs = (int) round(now()->getTimestampMs());
@endphp
<script>
    window.__BUSINESS_CLOCK__ = {
        timezone: @json($__bizTz),
        server_ms: {{ $__bizServerMs }}
    };
</script>
<script src="{{ asset('js/business-clock.js') }}?v=1"></script>
