@props([
    'capacity' => 4,
    'guests' => null,
    'occupied' => false,
    'theme' => 'light',
])
@php
    $n = max(1, min(12, (int) $capacity));
    $guestCount = $guests === null || $guests === '' ? null : max(0, min($n, (int) $guests));
    $radius = $n <= 2 ? 34 : ($n <= 4 ? 36 : ($n <= 6 ? 38 : 40));
@endphp
<div {{ $attributes->class(['seat-diagram', 'seat-theme-'.$theme, 'is-occupied' => $occupied, 'seats-n-'.$n]) }}
     data-seats="{{ $n }}"
     aria-label="{{ $n }} seats">
    <div class="seat-table-top"></div>
    @for ($i = 0; $i < $n; $i++)
        @php
            $angle = (360 / $n) * $i - 90;
            $rad = deg2rad($angle);
            $x = 50 + $radius * cos($rad);
            $y = 50 + $radius * sin($rad);
            $filled = $guestCount !== null ? ($i < $guestCount) : $occupied;
        @endphp
        <span class="seat-chair{{ $filled ? ' is-filled' : '' }}"
              style="left: {{ round($x, 2) }}%; top: {{ round($y, 2) }}%; --seat-rot: {{ round($angle + 90, 1) }}deg;"></span>
    @endfor
</div>
