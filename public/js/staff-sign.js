(() => {
    'use strict';

    const cfg = window.STAFF_SIGN_CONFIG || {};
    if (!cfg.authenticated) return;

    const mapConfig = cfg.mapConfig;
    const isSignedIn = cfg.isSignedIn;
    const csrfToken = cfg.csrfToken;
    const routes = cfg.routes;

    let map, hqCircle, userMarker, accuracyCircle, routeLine, pathLine, osrmLine, watchId = null;
    let displayLat = null, displayLng = null, targetLat = null, targetLng = null;
    let currentDistance = null, currentAccuracy = null, currentHeading = null, currentSpeed = null;
    let followUser = true, animFrame = null, pathPoints = [], gpsTrail = [];
    let isWalking = false, wasInside = false, isInside = false;
    let lastPosTime = null, lastPosLat = null, lastPosLng = null;
    let mapStyle = 'voyager';
    let panelExpanded = true;
    const MAX_PATH = 40;
    const WALK_SPEED = 0.35;
    const OFFLINE_KEY = 'smartemca_offline_sign_queue';

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
                osrmLine.setLatLngs(coords);
            }
        } catch (e) {
            if (routeLine) routeLine.setLatLngs([[fromLat, fromLng], [mapConfig.hq_latitude, mapConfig.hq_longitude]]);
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
                html: '<div style="background:#940000;color:#fff;padding:6px 10px;border-radius:8px;font-weight:bold;font-size:11px;box-shadow:0 2px 8px rgba(0,0,0,.3);">HQ</div>',
                iconAnchor: [24, 20],
            }),
        }).addTo(map);

        hqCircle = L.circle([mapConfig.hq_latitude, mapConfig.hq_longitude], {
            color: '#940000', fillColor: '#940000', fillOpacity: 0.12, weight: 2, radius: mapConfig.geofence_radius,
        }).addTo(map);

        pathLine = L.polyline([], { color: '#007bff', weight: 4, opacity: 0.55 }).addTo(map);
        routeLine = L.polyline([], { color: '#940000', weight: 2, opacity: 0.4, dashArray: '6 6' }).addTo(map);
        osrmLine = L.polyline([], { color: '#940000', weight: 4, opacity: 0.85 }).addTo(map);

        userMarker = L.marker([lat, lng], {
            icon: L.divIcon({ className: '', html: userMarkerHtml(null, false), iconSize: [48, 48], iconAnchor: [24, 24] }),
            zIndexOffset: 1000,
        }).addTo(map);

        accuracyCircle = L.circle([lat, lng], { color: '#007bff', fillColor: '#007bff', fillOpacity: 0.08, weight: 1, radius: 20 }).addTo(map);
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
        if (!chip || !label) return;
        chip.className = state === 'tracking' ? 'gps-live' : 'gps-live ' + state;
        label.textContent = text;
    }

    function updateUI() {
        const badge = document.getElementById('distanceBadge');
        const distanceText = document.getElementById('distanceText');
        const etaText = document.getElementById('etaText');
        const signBtn = document.getElementById('signActionBtn');

        if (currentDistance === null) {
            if (badge) badge.className = 'distance-badge tracking';
            if (distanceText) distanceText.textContent = 'Locating...';
            if (signBtn) signBtn.disabled = true;
            return;
        }

        isInside = currentDistance <= mapConfig.geofence_radius;
        if (badge) badge.className = 'distance-badge ' + (isInside ? 'inside' : 'outside');

        if (distanceText) {
            distanceText.textContent = isInside
                ? `At HQ · ${currentDistance.toFixed(0)}m`
                : `${currentDistance.toFixed(0)}m from HQ`;
        }

        if (etaText && !isInside) {
            etaText.textContent = `~${etaMinutes(currentDistance)} min walk`;
            etaText.style.display = 'inline';
        } else if (etaText) {
            etaText.style.display = 'none';
        }

        if (signBtn) signBtn.disabled = !isInside;

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
                icon: 'warning', title: 'You left HQ while still signed in',
            });
        }

        wasInside = isInside;
    }

    function onPosition(pos) {
        targetLat = pos.coords.latitude;
        targetLng = pos.coords.longitude;
        currentAccuracy = pos.coords.accuracy;
        if (pos.coords.heading !== null && !isNaN(pos.coords.heading)) currentHeading = pos.coords.heading;

        currentDistance = haversine(targetLat, targetLng, mapConfig.hq_latitude, mapConfig.hq_longitude);
        if (!map) initMap(targetLat, targetLng);

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
        updateGpsChip('tracking', `Live GPS${acc}`);
    }

    function onPositionError() {
        updateGpsChip('error', 'GPS off');
        const el = document.getElementById('distanceText');
        if (el) el.textContent = 'Location denied';
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
            return 'Unknown location';
        }

        try {
            const url = `${routes.reverseGeocode}?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lng)}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            return data.place_name || 'Unknown location';
        } catch (e) {
            return 'Unknown location';
        }
    }

    function drawPhotoLocationOverlay(ctx, width, height, placeName) {
        if (targetLat === null || targetLng === null) return;

        const pad = Math.max(10, Math.round(width * 0.028));
        const fontSize = Math.max(13, Math.round(width * 0.028));
        const lineHeight = Math.round(fontSize * 1.38);
        const stampTime = formatPhotoTimestamp(new Date());
        const zoneLabel = isInside ? 'At HQ zone' : 'Outside HQ zone';
        const distLabel = currentDistance != null ? `${Math.round(currentDistance)}m from HQ center` : '';
        const accLabel = currentAccuracy != null ? `±${Math.round(currentAccuracy)}m GPS accuracy` : '';
        const barWidth = Math.max(4, Math.round(width * 0.008));
        const textStartX = pad + barWidth;
        const maxTextWidth = width - textStartX - pad;

        ctx.font = `600 ${fontSize}px "Century Gothic", CenturyGothic, AppleGothic, sans-serif`;
        const locationLines = wrapCanvasText(ctx, `Location: ${placeName}`, maxTextWidth);

        const lines = [
            `EmCa Staff Sign In · ${stampTime}`,
            ...locationLines,
            [zoneLabel, distLabel, accLabel].filter(Boolean).join(' · '),
        ];

        const boxHeight = pad * 2 + lines.length * lineHeight;

        ctx.fillStyle = 'rgba(0, 0, 0, 0.68)';
        ctx.fillRect(0, height - boxHeight, width, boxHeight);

        ctx.fillStyle = '#940000';
        ctx.fillRect(0, height - boxHeight, barWidth, boxHeight);

        ctx.fillStyle = '#ffffff';
        ctx.textBaseline = 'top';
        ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
        ctx.shadowBlur = 2;

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
                <p class="camera-modal-hint">Allow camera access, position your face in the frame, then tap <strong>Capture</strong>. The place name and time will be stamped on the photo.</p>
                <div class="camera-viewport" id="cameraViewport">
                    <div class="camera-status" id="cameraLoading"><i class="fa fa-spinner fa-spin"></i> Opening camera...</div>
                    <video id="swalVideo" autoplay playsinline muted style="display:none"></video>
                    <img id="swalPreview" alt="Preview" style="display:none">
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
            if (targetLat !== null && targetLng !== null) {
                cachedPlaceName = await resolvePlaceName(targetLat, targetLng);
            }
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
            drawPhotoLocationOverlay(ctx, canvas.width, canvas.height, cachedPlaceName || 'Unknown location');
            return canvas.toDataURL('image/jpeg', 0.82);
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
        if (targetLat === null) return;

        const actionLabel = isSignedIn ? 'sign out' : 'sign in';
        const confirm = await Swal.fire({
            title: isSignedIn ? 'Sign Out?' : 'Sign In at HQ?',
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

    if (mapConfig.is_non_working_day) {
        const banner = document.getElementById('nonWorkingBanner');
        if (banner) {
            banner.style.display = 'block';
            banner.textContent = mapConfig.is_holiday ? 'Today is a public holiday.' : 'Today is a weekend.';
        }
    }

    if (navigator.geolocation) {
        updateGpsChip('searching', 'Finding GPS...');
        navigator.geolocation.getCurrentPosition(onPosition, onPositionError, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        watchId = navigator.geolocation.watchPosition(onPosition, onPositionError, { enableHighAccuracy: true, maximumAge: 2000, timeout: 25000 });
    }

    initDraggablePanel();

    window.addEventListener('online', flushOfflineQueue);
    flushOfflineQueue();

    window.addEventListener('beforeunload', () => {
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        if (animFrame) cancelAnimationFrame(animFrame);
    });
})();
