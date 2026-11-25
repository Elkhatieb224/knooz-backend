@props([
    'lat' => null,
    'lng' => null,
    'address' => null,
    'latInput' => 'lat',
    'lngInput' => 'lng',
    'addressInput' => 'address',
    'height' => '280px',
])

@php
    $defaultLat = is_numeric($lat) ? (float) $lat : 24.7136;
    $defaultLng = is_numeric($lng) ? (float) $lng : 46.6753;
    $hasPoint = is_numeric($lat) && is_numeric($lng);
@endphp

<div class="location-map-picker" data-location-map-picker
     data-lat="{{ $defaultLat }}"
     data-lng="{{ $defaultLng }}"
     data-has-point="{{ $hasPoint ? '1' : '0' }}"
     data-lat-input="{{ $latInput }}"
     data-lng-input="{{ $lngInput }}"
     data-address-input="{{ $addressInput }}">
    <div class="d-flex flex-wrap gap-2 mb-2">
        <div class="flex-grow-1">
            <input type="search" class="form-control" data-location-search placeholder="ابحث عن موقع (حي، شارع، مدينة)..." autocomplete="off">
        </div>
        <button type="button" class="btn btn-outline-knooz" data-location-search-btn>بحث</button>
        <button type="button" class="btn btn-outline-secondary" data-location-my-btn title="موقعي الحالي">
            <i class="bi bi-geo-alt"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" data-location-clear-btn title="مسح الموقع">
            مسح
        </button>
    </div>
    <div data-location-map style="height: {{ $height }}; border-radius: 12px; border: 1px solid var(--border); overflow: hidden;"></div>
    <div class="small text-muted mt-2">
        اختياري — انقر على الخريطة لتحديد الموقع أو اتركه فارغاً.
        <span class="dir-ltr d-inline-block" data-location-coords>
            @if ($hasPoint)
                ({{ number_format((float) $lat, 5) }}, {{ number_format((float) $lng, 5) }})
            @endif
        </span>
    </div>
    <div class="small text-danger mt-1 d-none" data-location-error></div>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            .location-map-picker .leaflet-container { font-family: inherit; }
            .location-map-picker .leaflet-control-attribution { font-size: 10px; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
        (function () {
            const NOMINATIM = 'https://nominatim.openstreetmap.org';
            const headers = { 'Accept': 'application/json' };

            function qs(root, sel) { return root.querySelector(sel); }
            function formInput(root, name) {
                const form = root.closest('form');
                return form ? form.querySelector('[name="' + name + '"]') : null;
            }

            async function reverseGeocode(lat, lng) {
                const url = NOMINATIM + '/reverse?format=jsonv2&lat=' + encodeURIComponent(lat)
                    + '&lon=' + encodeURIComponent(lng) + '&accept-language=ar';
                const res = await fetch(url, { headers: Object.assign({ 'User-Agent': 'KnoozAdmin/1.0' }, headers) });
                if (!res.ok) throw new Error('تعذر جلب اسم الموقع');
                const data = await res.json();
                return formatPlace(data);
            }

            async function searchPlace(query) {
                const url = NOMINATIM + '/search?format=jsonv2&limit=1&q=' + encodeURIComponent(query)
                    + '&accept-language=ar&countrycodes=sa';
                const res = await fetch(url, { headers: Object.assign({ 'User-Agent': 'KnoozAdmin/1.0' }, headers) });
                if (!res.ok) throw new Error('تعذر البحث عن الموقع');
                const data = await res.json();
                if (!Array.isArray(data) || !data.length) throw new Error('لم يتم العثور على موقع مطابق');
                return data[0];
            }

            function formatPlace(data) {
                if (!data) return '';
                const a = data.address || {};
                const parts = [
                    a.road || a.pedestrian || a.neighbourhood || a.suburb,
                    a.city || a.town || a.village || a.state,
                    a.country,
                ].filter(Boolean);
                if (parts.length) return parts.join('، ');
                return data.display_name || data.name || '';
            }

            function initPicker(root) {
                if (root.dataset.ready === '1') return;
                root.dataset.ready = '1';

                const latName = root.dataset.latInput;
                const lngName = root.dataset.lngInput;
                const addressName = root.dataset.addressInput;
                const latInput = formInput(root, latName);
                const lngInput = formInput(root, lngName);
                const addressInput = formInput(root, addressName);
                const mapEl = qs(root, '[data-location-map]');
                const coordsEl = qs(root, '[data-location-coords]');
                const errorEl = qs(root, '[data-location-error]');
                const searchInput = qs(root, '[data-location-search]');
                const searchBtn = qs(root, '[data-location-search-btn]');
                const myBtn = qs(root, '[data-location-my-btn]');

                let lat = parseFloat(root.dataset.lat);
                let lng = parseFloat(root.dataset.lng);
                const hasPoint = root.dataset.hasPoint === '1';

                const map = L.map(mapEl, { scrollWheelZoom: false }).setView([lat, lng], hasPoint ? 15 : 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                let marker = null;
                function setPoint(nextLat, nextLng, placeName, opts) {
                    opts = opts || {};
                    lat = nextLat;
                    lng = nextLng;
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                        marker.on('dragend', async function () {
                            const p = marker.getLatLng();
                            await applyPoint(p.lat, p.lng, true);
                        });
                    }
                    if (latInput) latInput.value = lat.toFixed(7);
                    if (lngInput) lngInput.value = lng.toFixed(7);
                    if (coordsEl) coordsEl.textContent = '(' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';
                    if (placeName && addressInput) {
                        addressInput.value = placeName;
                    }
                    if (opts.fly !== false) {
                        map.setView([lat, lng], Math.max(map.getZoom(), 15));
                    }
                    if (marker && placeName) {
                        marker.bindPopup(placeName).openPopup();
                    }
                }

                async function applyPoint(nextLat, nextLng, doGeocode) {
                    hideError();
                    setPoint(nextLat, nextLng, null, { fly: true });
                    if (!doGeocode) return;
                    try {
                        const place = await reverseGeocode(nextLat, nextLng);
                        if (place) setPoint(nextLat, nextLng, place, { fly: false });
                    } catch (e) {
                        showError(e.message || 'تعذر جلب اسم الموقع');
                    }
                }

                function showError(msg) {
                    if (!errorEl) return;
                    errorEl.textContent = msg;
                    errorEl.classList.remove('d-none');
                }
                function hideError() {
                    if (!errorEl) return;
                    errorEl.textContent = '';
                    errorEl.classList.add('d-none');
                }

                map.on('click', function (e) {
                    applyPoint(e.latlng.lat, e.latlng.lng, true);
                });

                if (hasPoint) {
                    setPoint(lat, lng, addressInput ? addressInput.value : null, { fly: false });
                }

                async function runSearch() {
                    const q = (searchInput && searchInput.value || '').trim();
                    if (!q) return;
                    hideError();
                    try {
                        const hit = await searchPlace(q);
                        const nextLat = parseFloat(hit.lat);
                        const nextLng = parseFloat(hit.lon);
                        const place = formatPlace(hit) || hit.display_name || q;
                        setPoint(nextLat, nextLng, place, { fly: true });
                    } catch (e) {
                        showError(e.message || 'تعذر البحث');
                    }
                }

                if (searchBtn) searchBtn.addEventListener('click', runSearch);
                if (searchInput) {
                    searchInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            runSearch();
                        }
                    });
                }

                if (myBtn && navigator.geolocation) {
                    myBtn.addEventListener('click', function () {
                        hideError();
                        navigator.geolocation.getCurrentPosition(
                            function (pos) {
                                applyPoint(pos.coords.latitude, pos.coords.longitude, true);
                            },
                            function () {
                                showError('تعذر الحصول على موقعك الحالي');
                            },
                            { enableHighAccuracy: true, timeout: 12000 }
                        );
                    });
                }

                const clearBtn = qs(root, '[data-location-clear-btn]');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function () {
                        hideError();
                        if (marker) {
                            map.removeLayer(marker);
                            marker = null;
                        }
                        if (latInput) latInput.value = '';
                        if (lngInput) lngInput.value = '';
                        if (coordsEl) coordsEl.textContent = '';
                        root.dataset.hasPoint = '0';
                    });
                }

                setTimeout(function () { map.invalidateSize(); }, 200);
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-location-map-picker]').forEach(initPicker);
            });
        })();
        </script>
    @endpush
@endonce
