<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Path Replay - Smart EmCa</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { margin: 0; font-family: "Century Gothic", sans-serif; }
        #replayMap { height: 100vh; }
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 16px; background: rgba(148,0,0,0.92); color: #fff;
        }
        .topbar a { color: #fff; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('staff.sign.history', ['month' => $attendance->signed_in_at->format('Y-m')]) }}"><i class="fa fa-arrow-left"></i> Back</a>
        <span>{{ $attendance->signed_in_at->format('d M Y') }}</span>
        <span style="width:40px;"></span>
    </div>
    <div id="replayMap"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const mapConfig = @json($mapConfig);
        const trace = @json($attendance->path_trace ?? []);
        const map = L.map('replayMap').setView([mapConfig.hq_latitude, mapConfig.hq_longitude], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);
        L.circle([mapConfig.hq_latitude, mapConfig.hq_longitude], {
            color: '#940000', fillColor: '#940000', fillOpacity: 0.12, radius: mapConfig.geofence_radius
        }).addTo(map);
        if (trace.length) {
            const pts = trace.map(p => [p.lat, p.lng]);
            L.polyline(pts, { color: '#007bff', weight: 4 }).addTo(map);
            L.marker(pts[0]).addTo(map).bindPopup('Start');
            L.marker(pts[pts.length - 1]).addTo(map).bindPopup('End');
            map.fitBounds(L.latLngBounds(pts), { padding: [40, 40] });
        }
        @if($attendance->latitude_in)
        L.marker([{{ $attendance->latitude_in }}, {{ $attendance->longitude_in }}]).addTo(map).bindPopup('Sign In');
        @endif
    </script>
</body>
</html>
