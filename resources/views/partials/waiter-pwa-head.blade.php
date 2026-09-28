@php
    $pwa = \App\Models\Setting::pwaConfig();
@endphp
{{-- Shared PWA head tags for QRPOS Waiter Panel --}}
<link rel="manifest" href="{{ url('/manifest-waiter.webmanifest') }}">
<meta name="theme-color" content="{{ $pwa['theme_color'] }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $pwa['short_name'] }}">
<link rel="apple-touch-icon" href="/pwa/waiter/apple-touch-icon.png?v={{ @filemtime(public_path('pwa/waiter/apple-touch-icon.png')) ?: time() }}">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/waiter/icon-192.png?v={{ @filemtime(public_path('pwa/waiter/icon-192.png')) ?: time() }}">
<link rel="icon" type="image/png" sizes="96x96" href="/pwa/waiter/icon-96.png?v={{ @filemtime(public_path('pwa/waiter/icon-96.png')) ?: time() }}">
