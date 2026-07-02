(() => {
    'use strict';

    const cfg = window.STAFF_SIGN_CONFIG || {};
    if (!cfg.mapConfig) return;

    const isAuthenticated = !!cfg.authenticated;
    const mapConfig = cfg.mapConfig;
    const hqName = mapConfig.hq_name || 'EmCa HQ';
    let isSignedIn = isAuthenticated && cfg.isSignedIn;
    const csrfToken = cfg.csrfToken;
    const routes = cfg.routes;

    let map, hqCircle, userMarker, accuracyCircle, routeLine, pathLine, osrmLine, osrmOutlineLine, watchId = null;
    let displayLat = null, displayLng = null, targetLat = null, targetLng = null;
    let currentDistance = null, currentAccuracy = null, currentHeading = null, currentSpeed = null;
    let followUser = true, animFrame = null, pathPoints = [], gpsTrail = [];
    let isWalking = false, wasInside = false, isInside = false;
    let lastPosTime = null, lastPosLat = null, lastPosLng = null;
    let mapStyle = 'voyager';
    let panelExpanded = true;
    let currentPlaceName = null;
    let lastGeocodeAt = 0;
    let hasFittedBounds = false;
    let gpsReady = false;
    const MAX_PATH = 40;
    const WALK_SPEED = 0.35;
    const OFFLINE_KEY = 'smartemca_offline_sign_queue';
    let lastPingAt = 0;
    const PING_INTERVAL_MS = 15000;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function hqMarkerLabelHtml() {
        return `<div style="background:#940000;color:#fff;padding:6px 10px;border-radius:8px;font-weight:bold;font-size:11px;box-shadow:0 2px 8px rgba(0,0,0,.3);white-space:nowrap;">${escapeHtml(hqName)}</div>`;
    }

    const TILES = {
        voyager: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    };

    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function haptic(ms = 40) {
        if (navigator.vibrate) navigator.vibrate(ms);
    }

    function playArrivalChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = 880;
            g.gain.value = 0.08;
            o.start();
            o.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }

    function detectWalking(lat, lng, pos) {
        const now = pos.timestamp;
        let speed = pos.coords.speed;
        if (speed !== null && !isNaN(speed) && speed >= 0) {
            isWalking = speed > WALK_SPEED;
            currentSpeed = speed;
        } else if (lastPosTime !== null && lastPosLat !== null) {
            const dt = (now - lastPosTime) / 1000;
            if (dt >= 0.5 && dt < 20) {
                const dist = haversine(lastPosLat, lastPosLng, lat, lng);
                currentSpeed = dist / dt;
                isWalking = currentSpeed > WALK_SPEED;
            }
        }
        lastPosTime = now;
        lastPosLat = lat;
        lastPosLng = lng;
    }

    function userMarkerHtml(heading, walking) {
        const rot = heading !== null && !isNaN(heading) ? `transform:rotate(${heading}deg)` : '';
        if (walking) {
            return `<div class="user-marker-wrap walking" style="${rot}">
                <div class="user-marker-pulse"></div>
                <div class="user-walker"><i class="fa fa-street-view"></i></div>
            </div>`;
        }
        return `<div class="user-marker-wrap idle">
            <div class="user-marker-pulse idle-pulse"></div>
            <div class="user-idle"><i class="fa fa-user"></i></div>
        </div>`;
    }

    function refreshUserMarker() {
        if (!userMarker) return;
        userMarker.setIcon(L.divIcon({
            className: '',
            html: userMarkerHtml(currentHeading, isWalking),
            iconSize: [48, 48],
            iconAnchor: [24, 24],
        }));
    }

    function etaMinutes(distanceM) {
        const walkMps = 1.4;
        return Math.max(1, Math.round((distanceM / walkMps) / 60));
    }

    async function fetchOsrmRoute(fromLat, fromLng) {
        if (!osrmLine) return;
        try {
            const url = `https://router.project-osrm.org/route/v1/foot/${fromLng},${fromLat};${mapConfig.hq_longitude},${mapConfig.hq_latitude}?overview=full&geometries=geojson`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.routes && data.routes[0]) {
                const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                if (osrmOutlineLine) osrmOutlineLine.setLatLngs(coords);
                osrmLine.setLatLngs(coords);
            }
        } catch (e) {
            if (routeLine) routeLine.setLatLngs([[fromLat, fromLng], [mapConfig.hq_latitude, mapConfig.hq_longitude]]);
            if (osrmOutlineLine) osrmOutlineLine.setLatLngs([]);
            if (osrmLine) osrmLine.setLatLngs([]);
        }
    }

    let tileLayer = null;
    function setMapStyle(style) {
        mapStyle = style;
        if (tileLayer) map.removeLayer(tileLayer);
        tileLayer = L.tileLayer(TILES[style] || TILES.voyager, { maxZoom: 20, attribution: '&copy; OSM' }).addTo(map);
        document.querySelectorAll('[data-map-style]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.mapStyle === style);
        });
    }

    function initMap(lat, lng) {
        displayLat = lat;
        displayLng = lng;
        map = L.map('signMap', { zoomControl: false, zoomAnimation: true }).setView([lat, lng], 17);
        setMapStyle('voyager');
        L.control.zoom({ position: 'topright' }).addTo(map);

        L.marker([mapConfig.hq_latitude, mapConfig.hq_longitude], {
            icon: L.divIcon({
                className: '',
                html: hqMarkerLabelHtml(),
                iconAnchor: [40, 20],
            }),
        }).addTo(map);

        hqCircle = L.circle([mapConfig.hq_latitude, mapConfig.hq_longitude], {
            color: '#940000', fillColor: '#940000', fillOpacity: 0.12, weight: 2, radius: mapConfig.geofence_radius,
        }).addTo(map);

        // short path preview (subtle)
        pathLine = L.polyline([], { color: '#007bff', weight: 4, opacity: 0.25 }).addTo(map);

        // fallback straight route (when OSRM unavailable)
        routeLine = L.polyline([], { color: '#1f8a3b', weight: 6, opacity: 0.45 }).addTo(map);

        // navigation-style route (bold with outline)
        osrmOutlineLine = L.polyline([], { color: '#ffffff', weight: 10, opacity: 0.95, lineCap: 'round', lineJoin: 'round' }).addTo(map);
        osrmLine = L.polyline([], { color: '#1f8a3b', weight: 7, opacity: 0.95, lineCap: 'round', lineJoin: 'round' }).addTo(map);

        userMarker = L.marker([lat, lng], {
            icon: L.divIcon({ className: '', html: userMarkerHtml(null, false), iconSize: [48, 48], iconAnchor: [24, 24] }),
            zIndexOffset: 1000,
            opacity: gpsReady ? 1 : 0,
        }).addTo(map).bindPopup(userMarkerPopupHtml());

        accuracyCircle = L.circle([lat, lng], { color: '#007bff', fillColor: '#007bff', fillOpacity: 0.1, weight: 2, radius: 20 }).addTo(map);
        map.on('dragstart', () => { followUser = false; });
        startMarkerAnimation();
        fetchOsrmRoute(lat, lng);
    }

    function startMarkerAnimation() {
        if (animFrame) cancelAnimationFrame(animFrame);
        const step = () => {
            if (displayLat !== null && targetLat !== null) {
                const d = haversine(displayLat, displayLng, targetLat, targetLng);
                const t = d > 30 ? 0.18 : d > 5 ? 0.28 : 0.45;
                displayLat += (targetLat - displayLat) * t;
                displayLng += (targetLng - displayLng) * t;
                userMarker.setLatLng([displayLat, displayLng]);
                if (accuracyCircle) accuracyCircle.setLatLng([displayLat, displayLng]);
                if (followUser && map) map.panTo([displayLat, displayLng], { animate: true });
            }
            animFrame = requestAnimationFrame(step);
        };
        animFrame = requestAnimationFrame(step);
    }

    function updateGpsChip(state, text) {
        const chip = document.getElementById('gpsLive');
        const label = document.getElementById('gpsLiveText');
        const spinner = document.getElementById('gpsChipSpinner');
        const loader = document.getElementById('gpsMapLoader');
        const loaderHint = document.getElementById('gpsLoaderHint');

        if (!chip || !label) return;

        chip.className = state === 'tracking' ? 'gps-live' : 'gps-live ' + state;
        label.textContent = text;

        if (spinner) {
            spinner.style.display = state === 'searching' ? 'block' : 'none';
        }

        if (loader) {
            const showLoader = state === 'searching';
            loader.classList.toggle('is-hidden', !showLoader);
            loader.setAttribute('aria-hidden', showLoader ? 'false' : 'true');
            document.body.classList.toggle('gps-locating', showLoader);
        }

        if (loaderHint && state === 'searching') {
            loaderHint.textContent = 'Scanning map · allow GPS when asked';
        }
    }

    function updateUI() {
        const { badge, distanceText } = getDistanceElements();
        const etaText = document.getElementById('etaText');
        const signBtn = document.getElementById('signActionBtn');

        if (currentDistance === null) {
            if (badge) badge.className = 'distance-badge tracking' + (badge.id === 'guestDistanceBadge' ? ' guest-location-badge' : '');
            if (distanceText) distanceText.textContent = 'Locating you on map...';
            if (signBtn) signBtn.disabled = true;
            return;
        }

        isInside = currentDistance <= mapConfig.geofence_radius;
        const badgeClass = 'distance-badge ' + (isInside ? 'inside' : 'outside') + (badge && badge.id === 'guestDistanceBadge' ? ' guest-location-badge' : '');
        if (badge) badge.className = badgeClass;

        if (distanceText) {
            const walkPrefix = isWalking ? 'Walking · ' : '';
            distanceText.textContent = isInside
                ? `${walkPrefix}At ${hqName} · ${currentDistance.toFixed(0)}m`
                : `${walkPrefix}${currentDistance.toFixed(0)}m from ${hqName}`;
        }

        if (etaText && !isInside) {
            etaText.textContent = `~${etaMinutes(currentDistance)} min walk`;
            etaText.style.display = 'inline';
        } else if (etaText) {
            etaText.style.display = 'none';
        }

        if (signBtn) {
            const signInBlocked = !isSignedIn && mapConfig.block_sign_in_non_working_days && mapConfig.is_non_working_day;
            signBtn.disabled = signInBlocked || !isAuthenticated || !isInside;
        }

        if (hqCircle) {
            hqCircle.setStyle({
                color: isInside ? '#28a745' : '#940000',
                fillColor: isInside ? '#28a745' : '#940000',
                fillOpacity: isInside ? 0.18 : 0.12,
            });
        }

        if (isInside && !wasInside) {
            haptic([30, 50, 30]);
            playArrivalChime();
        }

        if (!isInside && wasInside && isSignedIn) {
            Swal.fire({
                toast: true, position: 'top', timer: 4000, showConfirmButton: false,
                icon: 'warning', title: `You left ${hqName} while still signed in`,
            });
        }

        wasInside = isInside;
        updateLocationHints();
        refreshUserMarkerPopup();
    }

    function onPosition(pos) {
        targetLat = pos.coords.latitude;
        targetLng = pos.coords.longitude;
        currentAccuracy = pos.coords.accuracy;
        if (pos.coords.heading !== null && !isNaN(pos.coords.heading)) currentHeading = pos.coords.heading;

        currentDistance = haversine(targetLat, targetLng, mapConfig.hq_latitude, mapConfig.hq_longitude);
        if (!map) initMap(mapConfig.hq_latitude, mapConfig.hq_longitude);

        if (!gpsReady) {
            gpsReady = true;
            displayLat = targetLat;
            displayLng = targetLng;
            if (userMarker) userMarker.setOpacity(1);
            // Make the map clearly show the real live location immediately.
            followUser = true;
            if (map) {
                map.setView([targetLat, targetLng], 17, { animate: false });
            }
        }

        if (accuracyCircle && currentAccuracy) accuracyCircle.setRadius(Math.min(currentAccuracy, 80));

        pathPoints.push([targetLat, targetLng]);
        if (pathPoints.length > MAX_PATH) pathPoints.shift();
        if (pathLine) pathLine.setLatLngs(pathPoints);

        gpsTrail.push({
            lat: targetLat, lng: targetLng,
            accuracy: currentAccuracy, speed: pos.coords.speed,
            timestamp: pos.timestamp,
        });
        if (gpsTrail.length > 20) gpsTrail.shift();

        detectWalking(targetLat, targetLng, pos);
        refreshUserMarker();
        updateUI();

        if (pathPoints.length % 5 === 0) fetchOsrmRoute(targetLat, targetLng);

        const acc = currentAccuracy ? ` · ±${Math.round(currentAccuracy)}m` : '';
        const walk = isWalking ? ` · ${formatSpeedLabel()}` : '';
        updateGpsChip('tracking', `Live GPS${acc}${walk}`);

        maybeRefreshPlaceName();
        fitMapToUserAndHq();

        maybePingLiveLocation(pos);
    }

    async function maybePingLiveLocation(pos) {
        if (!isAuthenticated || !routes.pingLocation) return;
        const now = Date.now();
        if (now - lastPingAt < PING_INTERVAL_MS) return;
        if (targetLat === null || targetLng === null) return;
        lastPingAt = now;

        try {
            await fetch(routes.pingLocation, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    latitude: targetLat,
                    longitude: targetLng,
                    accuracy: currentAccuracy,
                    speed: currentSpeed,
                    heading: currentHeading,
                    travel_mode: travelMode,
                    timestamp: pos?.timestamp ? Math.round(pos.timestamp) : now,
                    device_id: window.deviceId,
                }),
            });
        } catch (e) {
            // ignore ping failures (best-effort)
        }
    }

    function onPositionError(err) {
        updateGpsChip('error', 'GPS off — allow location');
        const { distanceText } = getDistanceElements();
        if (distanceText) distanceText.textContent = 'Location denied — enable GPS';
        const guestLine = document.getElementById('guestWhereLine');
        if (guestLine) guestLine.textContent = 'Allow location permission in your browser to see your position on the map.';
        const loaderHint = document.getElementById('gpsLoaderHint');
        if (loaderHint) loaderHint.textContent = 'Location blocked — enable GPS in browser settings';
    }

    function formatPhotoTimestamp(date) {
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    }

    function wrapCanvasText(ctx, text, maxWidth) {
        const words = String(text).split(' ');
        const lines = [];
        let line = '';

        words.forEach((word) => {
            const test = line ? `${line} ${word}` : word;
            if (ctx.measureText(test).width > maxWidth && line) {
                lines.push(line);
                line = word;
            } else {
                line = test;
            }
        });

        if (line) lines.push(line);
        return lines.length ? lines : [text];
    }

    async function resolvePlaceName(lat, lng) {
        if (!routes.reverseGeocode || lat === null || lng === null) {
            return null;
        }

        try {
            const url = `${routes.reverseGeocode}?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lng)}`;
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return null;
            const data = await res.json();
            const name = (data.place_name || '').trim();
            return name && name !== 'Unknown location' ? name : null;
        } catch (e) {
            return null;
        }
    }

    function formatCoordsLabel(lat, lng) {
        if (lat === null || lng === null) return '';
        return `${Number(lat).toFixed(5)}, ${Number(lng).toFixed(5)}`;
    }

    function formatSpeedLabel() {
        if (currentSpeed === null || isNaN(currentSpeed)) return '';
        const kmh = currentSpeed * 3.6;
        if (kmh < 1.5) return 'Standing still';
        return `Walking · ${kmh.toFixed(1)} km/h`;
    }

    function userMarkerPopupHtml() {
        if (targetLat === null) return '<strong>You are here</strong><br>Waiting for GPS...';
        const walk = isWalking
            ? '<br><span style="color:#007bff;font-weight:700;"><i class="fa fa-street-view"></i> Walking now</span>'
            : '<br><span style="color:#666;">Standing / not moving</span>';
        const place = currentPlaceName ? `<br>${currentPlaceName}` : '';
        const dist = currentDistance !== null
            ? (isInside ? `<br><strong style="color:#28a745;">Inside ${escapeHtml(hqName)} · ${Math.round(currentDistance)}m</strong>`
                : `<br><strong style="color:#fd7e14;">${Math.round(currentDistance)}m from ${escapeHtml(hqName)}</strong>`)
            : '';
        return `<strong>You are here</strong>${walk}${place}<br><small>${formatCoordsLabel(targetLat, targetLng)}</small>${dist}`;
    }

    function refreshUserMarkerPopup() {
        if (userMarker) userMarker.setPopupContent(userMarkerPopupHtml());
    }

    async function maybeRefreshPlaceName() {
        if (!routes.reverseGeocode || targetLat === null || targetLng === null) return;
        const now = Date.now();
        if (now - lastGeocodeAt < 45000) return;
        lastGeocodeAt = now;
        const name = await resolvePlaceName(targetLat, targetLng);
        if (name) {
            currentPlaceName = name;
            updateLocationHints();
            refreshUserMarkerPopup();
        }
    }

    function updateLocationHints() {
        const guestLine = document.getElementById('guestWhereLine');
        const statusText = document.getElementById('statusText');

        if (targetLat === null || currentDistance === null) return;

        if (guestLine) {
            // Keep guest hint hidden for a cleaner UI.
            guestLine.style.display = 'none';
        }
        if (statusText && isAuthenticated && !isSignedIn) {
            statusText.textContent = isInside
                ? `You are at ${hqName}. You can sign in now.`
                : (isWalking ? `Walking to ${hqName}… follow the route on the map.` : `Go to ${hqName} to sign in. Your location updates live on the map.`);
        }
    }

    function getDistanceElements() {
        const badge = document.getElementById('distanceBadge') || document.getElementById('guestDistanceBadge');
        const distanceText = document.getElementById('distanceText') || document.getElementById('guestDistanceText');
        return { badge, distanceText };
    }

    function fitMapToUserAndHq() {
        if (!map || targetLat === null || hasFittedBounds) return;
        hasFittedBounds = true;
        const bounds = L.latLngBounds([
            [targetLat, targetLng],
            [mapConfig.hq_latitude, mapConfig.hq_longitude],
        ]);
        map.fitBounds(bounds, { padding: [60, 60], maxZoom: 17 });
        setTimeout(() => { followUser = true; }, 800);
    }

    function buildPhotoStampLines(placeName) {
        const stampTime = formatPhotoTimestamp(new Date());
        const zoneLabel = isInside ? `At ${hqName} zone` : `Outside ${hqName} zone`;
        const distLabel = currentDistance != null ? `${Math.round(currentDistance)}m from ${hqName}` : '';
        const accLabel = currentAccuracy != null ? `±${Math.round(currentAccuracy)}m GPS` : '';
        const coordLabel = formatCoordsLabel(targetLat, targetLng);
        const resolvedPlace = (placeName || '').trim();
        const locationLine = resolvedPlace && resolvedPlace !== 'Unknown location'
            ? `Location: ${resolvedPlace}`
            : `Coordinates: ${coordLabel}`;

        return [
            `EmCa Staff Sign In · ${stampTime}`,
            locationLine,
            resolvedPlace && resolvedPlace !== 'Unknown location' ? `Coordinates: ${coordLabel}` : null,
            [zoneLabel, distLabel, accLabel].filter(Boolean).join(' · '),
        ].filter(Boolean);
    }

    function updateCameraLocationPanel(placeName, loading = false) {
        const panel = document.getElementById('cameraLocationStrip');
        if (!panel) return;

        const placeEl = panel.querySelector('.loc-place');
        const metaEl = panel.querySelector('.loc-meta');
        const resolvedPlace = (placeName || '').trim();
        const hasPlace = resolvedPlace && resolvedPlace !== 'Unknown location';

        panel.classList.toggle('is-loading', loading);

        if (placeEl) {
            if (loading && !hasPlace) {
                placeEl.textContent = 'Resolving place name…';
            } else if (hasPlace) {
                placeEl.textContent = resolvedPlace;
            } else {
                placeEl.textContent = formatCoordsLabel(targetLat, targetLng);
            }
        }

        if (metaEl) {
            const zoneLabel = isInside ? 'At HQ zone' : 'Outside HQ zone';
            const distLabel = currentDistance != null ? `${Math.round(currentDistance)}m from HQ` : '';
            const accLabel = currentAccuracy != null ? `±${Math.round(currentAccuracy)}m accuracy` : '';
            const timeLabel = formatPhotoTimestamp(new Date());
            metaEl.textContent = [zoneLabel, distLabel, accLabel, timeLabel].filter(Boolean).join(' · ');
        }
    }

    function drawPhotoLocationOverlay(ctx, width, height, placeName) {
        const pad = Math.max(12, Math.round(Math.min(width, height) * 0.028));
        const fontSize = Math.max(16, Math.round(Math.min(width, height) * 0.032));
        const lineHeight = Math.round(fontSize * 1.35);
        const barWidth = Math.max(5, Math.round(width * 0.01));
        const textStartX = pad + barWidth;
        const maxTextWidth = width - textStartX - pad;

        ctx.font = `600 ${fontSize}px Arial, Helvetica, sans-serif`;
        const lines = buildPhotoStampLines(placeName).flatMap((line) => wrapCanvasText(ctx, line, maxTextWidth));
        const boxHeight = pad * 2 + lines.length * lineHeight;

        ctx.fillStyle = 'rgba(0, 0, 0, 0.72)';
        ctx.fillRect(0, height - boxHeight, width, boxHeight);

        ctx.fillStyle = '#940000';
        ctx.fillRect(0, height - boxHeight, barWidth, boxHeight);

        ctx.fillStyle = '#ffffff';
        ctx.textBaseline = 'top';
        ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
        ctx.shadowBlur = 3;

        lines.forEach((line, index) => {
            ctx.fillText(line, textStartX, height - boxHeight + pad + index * lineHeight);
        });

        ctx.shadowBlur = 0;
    }

    // Camera capture for sign in (selfie proof)
    async function capturePhoto() {
        const canvas = document.getElementById('signCanvas');
        if (!canvas) return null;

        let stream = null;
        let previewDataUrl = null;

        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach((t) => t.stop());
                stream = null;
            }
        };

        const cameraHtml = `
            <div class="camera-modal-body">
                <p class="camera-modal-hint">Allow camera access, position your face in the frame, then tap <strong>Capture</strong>. Location details are stamped on the photo.</p>
                <div class="camera-viewport" id="cameraViewport">
                    <div class="camera-status" id="cameraLoading"><i class="fa fa-spinner fa-spin"></i> Opening camera...</div>
                    <video id="swalVideo" autoplay playsinline muted style="display:none"></video>
                    <img id="swalPreview" alt="Preview" style="display:none">
                </div>
                <div class="camera-location-strip is-loading" id="cameraLocationStrip">
                    <div class="loc-title"><i class="fa fa-map-marker"></i> Sign-in location</div>
                    <div class="loc-place">Loading location…</div>
                    <div class="loc-meta"></div>
                </div>
            </div>`;

        const showLiveCamera = async () => {
            const video = document.getElementById('swalVideo');
            const preview = document.getElementById('swalPreview');
            const loading = document.getElementById('cameraLoading');
            if (!video) return false;

            previewDataUrl = null;
            if (preview) {
                preview.style.display = 'none';
                preview.removeAttribute('src');
            }
            video.style.display = 'none';
            if (loading) {
                loading.style.display = 'flex';
                loading.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Opening camera...';
            }

            stopCamera();

            if (!navigator.mediaDevices?.getUserMedia) {
                if (loading) loading.innerHTML = '<i class="fa fa-camera"></i> Camera not supported in this browser.';
                return false;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'user' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                video.srcObject = stream;
                await video.play();
                if (loading) loading.style.display = 'none';
                video.style.display = 'block';
                return true;
            } catch (e) {
                if (loading) {
                    loading.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Camera blocked. Allow camera permission in your browser settings.';
                }
                return false;
            }
        };

        const showPreview = (dataUrl) => {
            const video = document.getElementById('swalVideo');
            const preview = document.getElementById('swalPreview');
            const loading = document.getElementById('cameraLoading');
            stopCamera();
            if (video) video.style.display = 'none';
            if (loading) loading.style.display = 'none';
            if (preview) {
                preview.src = dataUrl;
                preview.style.display = 'block';
            }
            previewDataUrl = dataUrl;
        };

        let cachedPlaceName = null;

        const prefetchPlaceName = async () => {
            updateCameraLocationPanel(null, true);

            if (targetLat === null || targetLng === null) {
                cachedPlaceName = null;
                updateCameraLocationPanel(null, false);
                return;
            }

            cachedPlaceName = null;
            updateCameraLocationPanel(null, true);

            const resolved = await resolvePlaceName(targetLat, targetLng);
            cachedPlaceName = resolved;
            updateCameraLocationPanel(resolved, false);
        };

        const grabFrame = async () => {
            const video = document.getElementById('swalVideo');
            if (!video || !video.videoWidth) return null;

            if (!cachedPlaceName) {
                await prefetchPlaceName();
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            drawPhotoLocationOverlay(ctx, canvas.width, canvas.height, cachedPlaceName);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.82);
            updateCameraLocationPanel(cachedPlaceName, false);
            return dataUrl;
        };

        previewDataUrl = null;
        cachedPlaceName = null;

        const result = await Swal.fire({
                title: 'Take attendance photo',
                html: cameraHtml,
                width: 'min(100%, 420px)',
                customClass: { popup: 'swal-camera-popup' },
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#940000',
                confirmButtonText: 'Capture',
                showDenyButton: true,
                denyButtonText: 'Retake',
                denyButtonColor: '#6c757d',
                didOpen: () => {
                    const denyBtn = Swal.getDenyButton();
                    if (denyBtn) denyBtn.style.display = 'none';
                    showLiveCamera();
                    prefetchPlaceName();
                },
                willClose: () => stopCamera(),
                preConfirm: async () => {
                    if (previewDataUrl) return previewDataUrl;

                    const dataUrl = await grabFrame();
                    if (!dataUrl) {
                        Swal.showValidationMessage('Camera not ready. Allow camera access and try again.');
                        return false;
                    }
                    showPreview(dataUrl);
                    const confirmBtn = Swal.getConfirmButton();
                    const denyBtn = Swal.getDenyButton();
                    if (confirmBtn) confirmBtn.textContent = 'Use this photo';
                    if (denyBtn) denyBtn.style.display = 'inline-block';
                    return false;
                },
                preDeny: () => {
                    const confirmBtn = Swal.getConfirmButton();
                    const denyBtn = Swal.getDenyButton();
                    if (confirmBtn) confirmBtn.textContent = 'Capture';
                    if (denyBtn) denyBtn.style.display = 'none';
                    cachedPlaceName = null;
                    showLiveCamera();
                    prefetchPlaceName();
                    return false;
                },
            });

        stopCamera();

        if (result.isConfirmed && typeof result.value === 'string' && result.value.startsWith('data:image')) {
            return result.value;
        }
        return null;
    }

    function queueOffline(payload) {
        const q = JSON.parse(localStorage.getItem(OFFLINE_KEY) || '[]');
        q.push({ ...payload, queued_at: Date.now() });
        localStorage.setItem(OFFLINE_KEY, JSON.stringify(q));
    }

    async function flushOfflineQueue() {
        if (!navigator.onLine) return;
        const q = JSON.parse(localStorage.getItem(OFFLINE_KEY) || '[]');
        if (!q.length) return;
        const remaining = [];
        for (const item of q) {
            try {
                const res = await fetch(item.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: JSON.stringify(item.body),
                });
                const data = await res.json();
                if (!data.success) remaining.push(item);
            } catch (e) {
                remaining.push(item);
            }
        }
        localStorage.setItem(OFFLINE_KEY, JSON.stringify(remaining));
        if (q.length && !remaining.length) {
            Swal.fire({ toast: true, position: 'top', timer: 3000, icon: 'success', title: 'Offline sign synced' });
            location.reload();
        }
    }

    async function performSign() {
        if (!isAuthenticated || targetLat === null) return;

        const actionLabel = isSignedIn ? 'sign out' : 'sign in';
        const confirm = await Swal.fire({
            title: isSignedIn ? 'Sign Out?' : `Sign In at ${hqName}?`,
            text: `Confirm ${actionLabel} at your current location.`,
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#940000', confirmButtonText: 'Yes, ' + actionLabel,
        });
        if (!confirm.isConfirmed) return;

        let photo = null;
        if (!isSignedIn) {
            photo = await capturePhoto();
            if (!photo) {
                Swal.fire({
                    icon: 'info',
                    title: 'Photo required',
                    text: 'Sign in needs a camera photo. Tap Sign In again, allow camera access, then Capture.',
                    confirmButtonColor: '#940000',
                });
                return;
            }
        }

        const url = isSignedIn ? routes.signOut : routes.signIn;
        const body = {
            latitude: targetLat, longitude: targetLng,
            device_id: window.deviceId,
            accuracy: currentAccuracy, speed: currentSpeed,
            timestamp: Date.now(), gps_trail: gpsTrail,
        };
        if (photo) body.photo = photo;

        const btn = document.getElementById('signActionBtn');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();

            if (data.session_expired) {
                location.href = routes.signPage;
                return;
            }

            if (data.success) {
                haptic([20, 40, 20]);
                await Swal.fire({ icon: 'success', title: isSignedIn ? 'Signed Out' : 'Signed In', text: data.message, confirmButtonColor: '#940000' });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Not Allowed', text: data.message, confirmButtonColor: '#940000' });
                updateUI();
            }
        } catch (e) {
            queueOffline({ url, body });
            haptic(60);
            Swal.fire({ icon: 'info', title: 'Saved offline', text: 'Sign queued. Will sync when online.', confirmButtonColor: '#940000' });
        }

        if (btn) btn.disabled = false;
    }

    // Draggable panel
    function initDraggablePanel() {
        const panel = document.getElementById('signPanel');
        const handle = document.getElementById('panelHandle');
        if (!panel || !handle) return;

        let startY = 0, startH = 0;
        const minH = 120, maxH = window.innerHeight * 0.72;

        const onMove = (clientY) => {
            const dy = startY - clientY;
            const h = Math.min(maxH, Math.max(minH, startH + dy));
            panel.style.height = h + 'px';
            panelExpanded = h > minH + 40;
        };

        handle.addEventListener('touchstart', e => { startY = e.touches[0].clientY; startH = panel.offsetHeight; }, { passive: true });
        handle.addEventListener('touchmove', e => onMove(e.touches[0].clientY), { passive: true });
        handle.addEventListener('mousedown', e => {
            startY = e.clientY; startH = panel.offsetHeight;
            const mm = ev => onMove(ev.clientY);
            const mu = () => { document.removeEventListener('mousemove', mm); document.removeEventListener('mouseup', mu); };
            document.addEventListener('mousemove', mm);
            document.addEventListener('mouseup', mu);
        });
    }

    // Bind UI
    document.getElementById('recenterBtn')?.addEventListener('click', () => {
        followUser = true;
        if (displayLat !== null && map) map.setView([displayLat, displayLng], 17, { animate: true });
    });

    document.querySelectorAll('[data-map-style]').forEach(btn => {
        btn.addEventListener('click', () => setMapStyle(btn.dataset.mapStyle));
    });

    document.getElementById('signActionBtn')?.addEventListener('click', performSign);

    if (isAuthenticated && mapConfig.is_non_working_day) {
        const banner = document.getElementById('nonWorkingBanner');
        if (banner) {
            banner.style.display = 'block';
            const dayLabel = mapConfig.is_holiday ? 'public holiday' : 'weekend';
            if (mapConfig.block_sign_in_non_working_days && !isSignedIn) {
                banner.textContent = `Sign-in blocked today (${dayLabel}).`;
            } else {
                banner.textContent = mapConfig.is_holiday ? 'Today is a public holiday.' : 'Today is a weekend.';
            }
        }
    }

    if (navigator.geolocation) {
        // Initialize map, then lock view onto first real GPS fix.
        initMap(mapConfig.hq_latitude, mapConfig.hq_longitude);
        updateGpsChip('searching', 'Finding location...');
        navigator.geolocation.getCurrentPosition(onPosition, onPositionError, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        watchId = navigator.geolocation.watchPosition(onPosition, onPositionError, { enableHighAccuracy: true, maximumAge: 1000, timeout: 25000 });
    } else {
        initMap(mapConfig.hq_latitude, mapConfig.hq_longitude);
        onPositionError();
    }

    initDraggablePanel();

    window.addEventListener('online', flushOfflineQueue);
    flushOfflineQueue();

    window.addEventListener('beforeunload', () => {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        if (animFrame) cancelAnimationFrame(animFrame);
    });
})();
