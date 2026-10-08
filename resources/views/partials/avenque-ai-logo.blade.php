{{-- Avenque Guide: real character mascot (waving man) --}}
@php
    $src = $src ?? asset('images/avenque-guide.png');
    $alt = $title ?? 'Avenque Guide';
@endphp
<img src="{{ $src }}"
     alt="{{ $alt }}"
     class="avenque-guide-mascot {{ $class ?? '' }}"
     loading="lazy"
     decoding="async"
     draggable="false">
