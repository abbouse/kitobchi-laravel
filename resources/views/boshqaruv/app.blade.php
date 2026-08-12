<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title inertia>Kitobchi Boshqaruv</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  {{-- Leaflet (interaktiv xarita) — bepul, kalitsiz, OpenStreetMap --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  {{-- Yandex Maps 2.1 — logistika zonalari (polygon chizish/tahrirlash). Kalit: .env YANDEX_MAPS_API_KEY --}}
  @if (config('services.yandex_maps.key'))
    <script src="https://api-maps.yandex.ru/2.1/?apikey={{ config('services.yandex_maps.key') }}&lang={{ config('services.yandex_maps.lang', 'ru_RU') }}"></script>
  @endif
  <script>window.__YANDEX_MAPS_ENABLED__ = @json((bool) config('services.yandex_maps.key'));</script>
  @viteReactRefresh
  @vite(['resources/css/boshqaruv.css', 'resources/js/boshqaruv/main.tsx'])
  @inertiaHead
</head>
<body>
  @inertia
</body>
</html>
