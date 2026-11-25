@props([
    'lat' => null,
    'lng' => null,
    'address' => null,
    'label' => null,
    'height' => '240px',
])

@php
    $hasPoint = is_numeric($lat) && is_numeric($lng);
    $place = trim((string) ($label ?: $address ?: ''));
    $mapsUrl = $hasPoint
        ? 'https://www.google.com/maps?q='.urlencode(((float) $lat).','.((float) $lng)).($place !== '' ? '('.str_replace(['(',')'], '', $place).')' : '')
        : ($place !== '' ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($place) : null);
@endphp

<div class="location-map-view">
    @if ($place !== '')
        <div class="mb-2 fw-bold">{{ $place }}</div>
    @elseif ($hasPoint)
        <div class="mb-2 text-muted">موقع محفوظ على الخريطة</div>
    @else
        <div class="text-muted">لا يوجد موقع محفوظ</div>
    @endif

    @if ($hasPoint)
        <div
            data-location-map-view
            data-lat="{{ (float) $lat }}"
            data-lng="{{ (float) $lng }}"
            data-label="{{ $place }}"
            style="height: {{ $height }}; border-radius: 12px; border: 1px solid var(--border); overflow: hidden;"
        ></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <span class="small text-muted dir-ltr">{{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }}</span>
            @if ($mapsUrl)
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-geo-alt"></i> عرض على الخريطة
                </a>
            @endif
        </div>
    @elseif ($mapsUrl)
        <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-1">
            <i class="bi bi-geo-alt"></i> عرض على الخريطة
        </a>
    @endif
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-location-map-view]').forEach(function (el) {
                if (el.dataset.ready === '1') return;
                el.dataset.ready = '1';
                const lat = parseFloat(el.dataset.lat);
                const lng = parseFloat(el.dataset.lng);
                const label = el.dataset.label || '';
                const map = L.map(el, { scrollWheelZoom: false, dragging: false, doubleClickZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                const marker = L.marker([lat, lng]).addTo(map);
                if (label) marker.bindPopup(label);
                setTimeout(function () { map.invalidateSize(); }, 200);
                el.style.cursor = 'pointer';
                el.addEventListener('click', function () {
                    const q = encodeURIComponent(lat + ',' + lng + (label ? '(' + label.replace(/[()]/g, '') + ')' : ''));
                    window.open('https://www.google.com/maps?q=' + q, '_blank', 'noopener');
                });
            });
        });
        </script>
    @endpush
@endonce
