@extends('layouts.app')

@section('title', 'Staff Live Tracking')
@section('icon', 'fa-map-marker')
@section('page-title', 'Staff Live Tracking')
@section('page-description', 'View current staff locations and movement')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item">Staff Tracking</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #staffLiveMap {
        height: calc(100vh - 220px);
        min-height: 520px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .tracking-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .tracking-chip {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 999px;
        padding: 8px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #333;
    }
    .tracking-chip small { font-weight: 600; color: #666; }
    .legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        font-size: 0.85rem;
        color: #555;
    }
    .legend-dot {
        width: 10px; height: 10px; border-radius: 50%;
        display: inline-block; margin-right: 6px;
    }
</style>
@endpush

@section('content')
<div class="tile">
    <div class="tile-body">
        <div class="tracking-toolbar">
            <div class="legend">
                <span><span class="legend-dot" style="background:#6c757d;"></span>Stationary</span>
                <span><span class="legend-dot" style="background:#007bff;"></span>Walking</span>
                <span><span class="legend-dot" style="background:#fd7e14;"></span>Motorcycle</span>
                <span><span class="legend-dot" style="background:#28a745;"></span>Driving</span>
                <span><span class="legend-dot" style="background:#adb5bd;"></span>Offline</span>
            </div>
            <div class="tracking-chip">
                <span id="trackingStatus">Loading…</span>
                <small class="ml-2" id="trackingMeta"></small>
            </div>
        </div>

        <div id="staffLiveMap"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('staffLiveMap').setView([-6.7924, 39.2083], 12); // default TZ
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 20 }).addTo(map);

    const markers = new Map();
    const OFFLINE_AFTER_SECONDS = 120;
    let hasFitBounds = false;

    function modeColor(mode) {
        if (mode === 'walking') return '#007bff';
        if (mode === 'motorcycle') return '#fd7e14';
        if (mode === 'driving') return '#28a745';
        return '#6c757d';
    }

    function markerIconHtml(loc) {
        const stale = loc.last_seen_seconds != null && loc.last_seen_seconds > OFFLINE_AFTER_SECONDS;
        const color = stale ? '#adb5bd' : modeColor(loc.travel_mode);
        const ring = stale ? 'rgba(173,181,189,0.35)' : (color + '33');
        return `
        <div style="width:42px;height:42px;border-radius:50%;background:${ring};display:flex;align-items:center;justify-content:center;">
          <div style="width:38px;height:38px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;">
            <i class="fa fa-user"></i>
          </div>
        </div>`;
    }

    function upsertMarker(loc) {
        const key = String(loc.user_id);
        const latlng = [loc.lat, loc.lng];
        const stale = loc.last_seen_seconds != null && loc.last_seen_seconds > OFFLINE_AFTER_SECONDS;
        const lastSeen = loc.last_seen_seconds != null ? (loc.last_seen_seconds + 's ago') : 'N/A';
        const popup = `
            <strong>${(loc.name || 'Staff')}</strong><br>
            <small>${(loc.email || '')}</small><br>
            <strong>Status:</strong> ${stale ? '<span style="color:#6c757d;font-weight:800;">Offline</span>' : '<span style="color:#28a745;font-weight:800;">Live</span>'}<br>
            <strong>Mode:</strong> ${(loc.travel_mode || 'stationary')}<br>
            <strong>Accuracy:</strong> ${loc.accuracy ? ('±' + loc.accuracy + 'm') : 'N/A'}<br>
            <strong>Last seen:</strong> ${lastSeen}
        `;

        if (!markers.has(key)) {
            const m = L.marker(latlng, {
                icon: L.divIcon({ className: '', html: markerIconHtml(loc), iconSize: [38, 38], iconAnchor: [19, 19] })
            }).addTo(map);
            m.bindPopup(popup);
            markers.set(key, m);
        } else {
            const m = markers.get(key);
            m.setLatLng(latlng);
            m.setIcon(L.divIcon({ className: '', html: markerIconHtml(loc), iconSize: [38, 38], iconAnchor: [19, 19] }));
            m.setPopupContent(popup);
        }
    }

    async function poll() {
        const statusEl = document.getElementById('trackingStatus');
        const metaEl = document.getElementById('trackingMeta');
        try {
            const res = await fetch(@json(route('staff.tracking.data')) + '?minutes=60', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('Failed');
            const data = await res.json();
            const list = data.latest || [];
            list.forEach(upsertMarker);
            if (statusEl) statusEl.textContent = 'Live';
            if (metaEl) metaEl.textContent = `${list.length} staff · updated ${new Date().toLocaleTimeString()}`;

            if (list.length && !hasFitBounds) {
                const bounds = L.latLngBounds(list.map(l => [l.lat, l.lng]));
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
                hasFitBounds = true;
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Offline';
            if (metaEl) metaEl.textContent = 'Retrying…';
        }
    }

    poll();
    setInterval(poll, 10000);
</script>
@endpush

