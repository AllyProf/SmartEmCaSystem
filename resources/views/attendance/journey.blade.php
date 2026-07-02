@extends('layouts.app')

@section('title', 'Attendance Journey')
@section('icon', 'fa-map')
@section('page-title', 'Attendance Journey')
@section('page-description', 'Full movement trace for a staff session')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
<li class="breadcrumb-item">Journey</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #journeyMap {
        height: calc(100vh - 250px);
        min-height: 520px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .journey-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }
    .journey-chip {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 999px;
        padding: 8px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #333;
    }
    .legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        font-size: 0.85rem;
        color: #555;
    }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; display:inline-block; margin-right: 6px; }
</style>
@endpush

@section('content')
<div class="tile">
    <div class="tile-body">
        <div class="journey-meta">
            <div class="journey-chip">
                <i class="fa fa-user"></i>
                {{ $attendance->user->name ?? 'Staff' }}
                @if($attendance->user?->staff_id)
                    <span class="text-muted">· {{ $attendance->user->staff_id }}</span>
                @endif
            </div>
            <div class="journey-chip">
                <i class="fa fa-sign-in"></i>
                In: {{ $signedInAt ? $signedInAt->format('d M Y H:i') : 'N/A' }}
            </div>
            <div class="journey-chip">
                <i class="fa fa-sign-out"></i>
                Out: {{ $signedOutAt ? $signedOutAt->format('d M Y H:i') : 'Still working' }}
            </div>
            <div class="journey-chip">
                <i class="fa fa-map-marker"></i>
                {{ $hqName }} · {{ (int) $geofenceRadius }}m zone
            </div>
            @if(!$signedOutAt && isset($latestPing) && $latestPing && $latestPing->captured_at)
                @php
                    $lastSeenSeconds = now()->diffInSeconds($latestPing->captured_at);
                @endphp
                @if($lastSeenSeconds > 120)
                <div class="journey-chip" style="border-color:#dee2e6;background:#f8f9fa;color:#6c757d;">
                    <i class="fa fa-warning"></i>
                    GPS offline · last seen {{ max(1, round($lastSeenSeconds / 60)) }}m
                </div>
                @else
                <div class="journey-chip" style="border-color:#d4edda;background:#f1fbf3;color:#155724;">
                    <i class="fa fa-signal"></i>
                    Live · last seen {{ $lastSeenSeconds }}s
                </div>
                @endif
            @endif
        </div>

        <div class="legend mb-2">
            <span><span class="legend-dot" style="background:#28a745;"></span>Slow</span>
            <span><span class="legend-dot" style="background:#ffc107;"></span>Moderate</span>
            <span><span class="legend-dot" style="background:#fd7e14;"></span>Fast</span>
            <span><span class="legend-dot" style="background:#dc3545;"></span>Very fast</span>
            <span class="text-muted">· segments are colored by speed</span>
        </div>

        <div id="journeyMap"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const hqLat = {{ $hqLatitude }};
    const hqLng = {{ $hqLongitude }};
    const hqRadius = {{ $geofenceRadius }};
    const hqName = @json($hqName);
    const points = @json($points);

    function colorForSpeed(mps) {
        const kmh = (mps || 0) * 3.6;
        if (kmh < 5) return '#28a745';
        if (kmh < 12) return '#ffc107';
        if (kmh < 25) return '#fd7e14';
        return '#dc3545';
    }

    const map = L.map('journeyMap').setView([hqLat, hqLng], 15);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    L.circle([hqLat, hqLng], { color:'#940000', fillColor:'#940000', fillOpacity:0.12, radius: hqRadius }).addTo(map);
    L.marker([hqLat, hqLng]).addTo(map).bindPopup('<strong>' + hqName + '</strong><br>HQ center');

    if (points && points.length > 0) {
        const coords = points.map(p => [p.lat, p.lng]);

        // Draw colored segments
        for (let i = 1; i < points.length; i++) {
            const a = points[i - 1];
            const b = points[i];
            const color = colorForSpeed(b.speed);
            L.polyline([[a.lat, a.lng], [b.lat, b.lng]], {
                color,
                weight: 5,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round',
            }).addTo(map);
        }

        L.marker(coords[0]).addTo(map).bindPopup('Start');
        L.marker(coords[coords.length - 1]).addTo(map).bindPopup('Latest');

        map.fitBounds(L.latLngBounds(coords.concat([[hqLat, hqLng]])), { padding: [40, 40], maxZoom: 17 });
    }
</script>
@endpush

