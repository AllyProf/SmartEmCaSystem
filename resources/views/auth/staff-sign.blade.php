<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ asset('manifest-staff-sign.json') }}">
    <meta name="theme-color" content="#940000">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/brand-overrides.css') }}">
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: "Century Gothic", CenturyGothic, AppleGothic, sans-serif;
            overflow: hidden;
            background: #111;
        }
        #signMap {
            position: fixed;
            inset: 0;
            z-index: 1;
        }
        .sign-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: linear-gradient(180deg, rgba(0,0,0,0.65) 0%, rgba(0,0,0,0) 100%);
            color: #fff;
        }
        .sign-topbar a {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .sign-topbar .user-pill {
            background: rgba(148, 0, 0, 0.9);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .sign-panel {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            background: #fff;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -8px 30px rgba(0,0,0,0.2);
            padding: 20px 20px calc(20px + env(safe-area-inset-bottom));
            max-height: 58vh;
            overflow-y: auto;
        }
        @media (max-width: 767px) {
            .sign-panel.sign-panel-guest {
                max-height: 38vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                padding: 10px 16px calc(12px + env(safe-area-inset-bottom));
            }
            .sign-panel.sign-panel-guest .sign-panel-handle {
                margin-bottom: 8px;
            }
        }
        .sign-panel-handle {
            width: 44px;
            height: 4px;
            background: #ddd;
            border-radius: 4px;
            margin: 0 auto 16px;
        }
        .distance-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        .distance-badge.outside {
            background: #fff3cd;
            color: #856404;
        }
        .distance-badge.inside {
            background: #d4edda;
            color: #155724;
        }
        .distance-badge.tracking {
            background: #e9ecef;
            color: #495057;
        }
        .status-line {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 16px;
        }
        .btn-sign-action {
            padding: 10px 8px;
            font-size: 0.82rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            color: #fff;
            background: #940000;
            transition: all 0.2s ease;
            line-height: 1.2;
        }
        .btn-sign-action:disabled {
            background: #adb5bd;
            cursor: not-allowed;
        }
        .btn-sign-action.sign-out-btn {
            background: #333;
        }
        .btn-sign-action:not(:disabled):active {
            transform: scale(0.98);
        }
        .btn-sign-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 10px 8px;
            font-size: 0.82rem;
            font-weight: 600;
            border: 2px solid #940000;
            border-radius: 10px;
            color: #940000;
            background: #fff;
            text-align: center;
            text-decoration: none;
            line-height: 1.2;
        }
        .btn-sign-secondary:active {
            background: #fdf5f5;
        }
        .sign-actions-row {
            display: flex;
            gap: 8px;
            margin-top: 4px;
        }
        .sign-actions-row .btn-sign-action,
        .sign-actions-row .btn-sign-secondary {
            flex: 1;
            min-width: 0;
        }
        .sign-actions-row .btn-sign-action i,
        .sign-actions-row .btn-sign-secondary i {
            font-size: 0.9rem;
        }
        .btn-sign-logout {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            color: #666;
            background: #f0f0f0;
            text-align: center;
            cursor: pointer;
        }
        .btn-sign-logout:active {
            background: #e2e2e2;
        }
        .topbar-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.5);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
        }
        .topbar-logout:active {
            background: rgba(255,255,255,0.3);
        }
        .device-note {
            font-size: 0.78rem;
            color: #888;
            margin-top: 10px;
            line-height: 1.4;
        }
        .map-fab {
            position: fixed;
            right: 16px;
            z-index: 1000;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: none;
            background: #fff;
            color: #940000;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .map-fab:active { transform: scale(0.95); }
        .map-fab.recenter { bottom: calc(220px + env(safe-area-inset-bottom)); }
        .gps-live {
            position: fixed;
            top: 58px;
            right: 16px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.95);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #155724;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .gps-live .live-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: liveBlink 1.2s infinite;
        }
        .gps-live.searching { color: #856404; }
        .gps-live.searching .live-dot { background: #ffc107; animation: none; }
        .gps-live.error { color: #721c24; }
        .gps-live.error .live-dot { background: #dc3545; animation: none; }
        @keyframes liveBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .user-marker-wrap {
            position: relative;
            width: 48px;
            height: 48px;
        }
        .user-walker, .user-idle {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            line-height: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.35));
            z-index: 2;
        }
        .user-walker {
            font-size: 34px;
            color: #007bff;
        }
        .user-idle {
            font-size: 28px;
            color: #007bff;
        }
        .user-marker-wrap.walking .user-walker {
            animation: walkBob 0.55s ease-in-out infinite;
        }
        @keyframes walkBob {
            0%, 100% { transform: translate(-50%, -50%); }
            50% { transform: translate(-50%, calc(-50% - 5px)); }
        }
        .user-marker-dot {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            background: #007bff;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.35);
            z-index: 2;
        }
        .user-marker-pulse {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 40px;
            height: 40px;
            margin: -20px 0 0 -20px;
            background: rgba(0, 123, 255, 0.25);
            border-radius: 50%;
            animation: markerPulse 2s ease-out infinite;
        }
        .user-marker-heading {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 0;
            height: 0;
            margin-left: -6px;
            margin-top: -22px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 14px solid rgba(0, 123, 255, 0.85);
            transform-origin: 50% 100%;
            z-index: 1;
        }
        @keyframes markerPulse {
            0% { transform: scale(0.4); opacity: 0.9; }
            100% { transform: scale(1.4); opacity: 0; }
        }
        @keyframes hqPulse {
            0%, 100% { fill-opacity: 0.12; stroke-opacity: 0.9; }
            50% { fill-opacity: 0.22; stroke-opacity: 1; }
        }
        .auth-form .form-control {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .auth-form .btn-sign-action {
            width: 100%;
            padding: 12px;
            font-size: 0.95rem;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .auth-header-compact {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            margin-bottom: 14px;
        }
        .auth-header-compact .auth-header-icon {
            margin: 0;
            flex-shrink: 0;
        }
        .auth-header-compact .auth-header-text h4 {
            margin: 0 0 4px;
            font-size: 1.05rem;
        }
        .auth-header-compact .auth-header-text p {
            max-width: none;
            font-size: 0.8rem;
            line-height: 1.35;
        }
        .auth-header-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: linear-gradient(145deg, #b30000 0%, #940000 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            box-shadow: 0 6px 18px rgba(148, 0, 0, 0.35);
        }
        .auth-header h4 {
            margin: 0 0 6px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #940000;
            letter-spacing: 0.02em;
        }
        .auth-header p {
            margin: 0;
            font-size: 0.88rem;
            color: #6c757d;
            line-height: 1.45;
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
        }
        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 14px;
        }
        .auth-alert i {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .auth-alert-danger {
            background: #fdecea;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .auth-alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .auth-alert-info {
            background: #e8f4fd;
            color: #084298;
            border: 1px solid #b6d4fe;
        }
        .staff-device-danger {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            background: #fdecea;
            border: 1px solid #f5c2c7;
            border-radius: 10px;
            padding: 8px 10px;
            margin-bottom: 10px;
            color: #842029;
            font-size: 0.72rem;
            line-height: 1.35;
        }
        .device-lock-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 0 10px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fdecea;
            border: 1px solid #f5c2c7;
            color: #842029;
            font-size: 0.72rem;
            line-height: 1.3;
        }
        .device-lock-chip .fa {
            flex-shrink: 0;
            font-size: 0.75rem;
            color: #dc3545;
        }
        .device-lock-chip span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .device-lock-chip--warn {
            background: #fff8e6;
            border-color: #ffe08a;
            color: #7a5b00;
        }
        .device-lock-chip--warn .fa { color: #d39e00; }
        .device-lock-chip--locked {
            background: #fff0f0;
            border-color: #e8b4b4;
        }
        .sign-panel-guest .auth-header-compact {
            margin-bottom: 10px;
        }
        .sign-panel-guest .auth-header-compact .auth-header-text p {
            display: none;
        }
        .staff-device-danger .fa {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 1px;
            flex-shrink: 0;
        }
        .staff-device-danger strong {
            display: inline;
            font-size: inherit;
            margin-bottom: 0;
            color: #940000;
        }
        .staff-device-danger--locked {
            border-color: #e8b4b4;
            background: #fff0f0;
        }
        .auth-field {
            margin-bottom: 16px;
        }
        .sign-panel-guest .auth-field {
            margin-bottom: 12px;
        }
        .sign-panel-guest .auth-header-icon {
            width: 42px;
            height: 42px;
            font-size: 1.05rem;
            box-shadow: 0 4px 12px rgba(148, 0, 0, 0.28);
        }
        .sign-panel-guest .auth-input {
            padding: 12px 12px 12px 40px;
            font-size: 0.95rem;
        }
        .sign-panel-guest .btn-auth-continue {
            padding: 12px 16px;
            font-size: 0.95rem;
        }
        .sign-panel-guest .auth-divider {
            margin: 12px 0 8px;
        }
        .sign-panel-guest .device-note {
            margin-top: 0;
            font-size: 0.72rem;
        }
        .auth-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 8px;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .auth-input-wrap {
            position: relative;
        }
        .auth-input-wrap .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #940000;
            font-size: 1rem;
            pointer-events: none;
            opacity: 0.85;
        }
        .auth-input {
            width: 100%;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 14px 14px 14px 42px;
            font-size: 1rem;
            color: #222;
            background: #fafafa;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            -webkit-appearance: none;
        }
        .auth-input::placeholder {
            color: #aaa;
        }
        .auth-input:focus {
            outline: none;
            border-color: #940000;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(148, 0, 0, 0.12);
        }
        .auth-input:disabled,
        .auth-input[readonly] {
            background: #f0f0f0;
            color: #555;
            cursor: wait;
        }
        .btn-auth-continue {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: none;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(145deg, #b30000 0%, #940000 100%);
            box-shadow: 0 6px 20px rgba(148, 0, 0, 0.35);
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }
        .btn-auth-continue:not(:disabled):active {
            transform: scale(0.98);
            box-shadow: 0 3px 12px rgba(148, 0, 0, 0.3);
        }
        .btn-auth-continue:disabled,
        .btn-auth-continue.is-loading {
            opacity: 0.88;
            cursor: wait;
            pointer-events: none;
        }
        .btn-auth-continue .btn-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-auth-continue.is-loading .btn-label {
            visibility: hidden;
        }
        .btn-auth-continue .btn-spinner {
            display: none;
            position: absolute;
            left: 50%;
            top: 50%;
            margin: -11px 0 0 -11px;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            border-top-color: #fff;
            animation: authSpin 0.75s linear infinite;
        }
        .btn-auth-continue.is-loading .btn-spinner {
            display: block;
        }
        .btn-auth-continue-wrap {
            position: relative;
        }
        @keyframes authSpin {
            to { transform: rotate(360deg); }
        }
        .auth-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e8e8e8, transparent);
            margin: 18px 0 14px;
        }
        .hq-chip {
            position: fixed;
            top: 58px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(255,255,255,0.95);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #940000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            white-space: nowrap;
        }
        .pulse-dot {
            width: 14px;
            height: 14px;
            background: #007bff;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 6px rgba(0,123,255,0.25);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0,123,255,0.45); }
            70% { box-shadow: 0 0 0 12px rgba(0,123,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(0,123,255,0); }
        }
        .leaflet-control-attribution { font-size: 9px !important; }
        .route-to-hq {
            stroke-dasharray: 8 8;
            animation: dashMove 1s linear infinite;
        }
        @keyframes dashMove {
            to { stroke-dashoffset: -16; }
        }
        .map-style-bar {
            position: fixed;
            left: 16px;
            bottom: calc(220px + env(safe-area-inset-bottom));
            z-index: 1000;
            display: flex;
            gap: 6px;
        }
        .map-style-btn {
            border: none;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 8px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .map-style-btn.active { background: #940000; color: #fff; }
        #etaText {
            font-size: 0.8rem;
            color: #666;
            margin-left: 6px;
            font-weight: 600;
        }
        canvas#signCanvas { display: none; }
        .swal-camera-popup { padding: 0 0 1em !important; }
        .swal-camera-popup .swal2-title { font-size: 1.15rem; color: #940000; }
        .camera-modal-body { text-align: left; }
        .camera-modal-hint {
            font-size: 0.88rem;
            color: #555;
            margin: 0 0 12px;
            line-height: 1.45;
        }
        .camera-viewport {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            background: #111;
            min-height: 220px;
            border: 2px solid #eee;
        }
        .camera-viewport video {
            width: 100%;
            display: block;
            max-height: 46vh;
            object-fit: cover;
        }
        .camera-viewport img {
            width: 100%;
            display: block;
            max-height: 46vh;
            object-fit: contain;
            background: #111;
        }
        .camera-viewport video { transform: scaleX(-1); }
        .camera-location-strip {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
            border: 1px solid rgba(148, 0, 0, 0.18);
            font-size: 0.82rem;
            line-height: 1.45;
            color: #333;
        }
        .camera-location-strip .loc-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #940000;
            margin-bottom: 6px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .camera-location-strip .loc-place {
            font-weight: 600;
            color: #222;
            margin-bottom: 4px;
            word-break: break-word;
        }
        .camera-location-strip .loc-meta {
            color: #666;
            font-size: 0.78rem;
        }
        .camera-location-strip.is-loading .loc-place {
            color: #888;
            font-style: italic;
        }
        .camera-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 220px;
            color: #ccc;
            font-size: 0.9rem;
            padding: 20px;
            text-align: center;
        }
        .camera-status .fa { font-size: 2rem; color: #940000; }
        @media (min-width: 768px) {
            .sign-panel {
                left: auto;
                right: 24px;
                bottom: 24px;
                width: 380px;
                border-radius: 16px;
                max-height: calc(100vh - 48px);
            }
            .sign-panel-handle { display: none; }
        }
    </style>
</head>
<body>
    <div id="signMap"></div>

    <div class="sign-topbar">
        @if($staffSignActive)
            <form action="{{ route('staff.sign.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="topbar-logout">
                    <i class="fa fa-sign-out"></i> Log out
                </button>
            </form>
            <span class="user-pill"><i class="fa fa-user"></i> {{ Auth::user()->name }}</span>
        @else
            <a href="{{ route('login') }}"><i class="fa fa-arrow-left"></i> Login</a>
            <span class="user-pill">Staff Sign</span>
        @endif
    </div>

    @if($staffSignActive)
    <div class="hq-chip"><i class="fa fa-building"></i> HQ Zone &middot; {{ $mapConfig['geofence_radius'] }}m radius</div>
    <div class="gps-live searching" id="gpsLive"><span class="live-dot"></span> <span id="gpsLiveText">GPS...</span></div>
    <div class="map-style-bar">
        <button type="button" class="map-style-btn active" data-map-style="voyager">Map</button>
        <button type="button" class="map-style-btn" data-map-style="dark">Night</button>
        <button type="button" class="map-style-btn" data-map-style="satellite">Satellite</button>
    </div>
    <button type="button" class="map-fab recenter" id="recenterBtn" title="Center on me"><i class="fa fa-crosshairs"></i></button>
    @endif

    <div class="sign-panel{{ !$staffSignActive ? ' sign-panel-guest' : '' }}" id="signPanel">
        <div class="sign-panel-handle d-md-none" id="panelHandle"></div>

        @if(!$staffSignActive)
            <div class="auth-header auth-header-compact">
                <div class="auth-header-icon"><i class="fa fa-id-badge"></i></div>
                <div class="auth-header-text">
                    <h4>Staff Attendance</h4>
                    <p>Enter your staff email. Sign in/out only at HQ.</p>
                </div>
            </div>

            @if(session('error'))
                <div class="auth-alert auth-alert-danger">
                    <i class="fa fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if(session('success'))
                <div class="auth-alert auth-alert-success">
                    <i class="fa fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <p class="device-lock-chip device-lock-chip--warn" id="devicePolicyWarning">
                <i class="fa fa-exclamation-triangle"></i>
                <span>This device will lock to your email after you continue</span>
            </p>

            <form action="{{ route('staff.sign.auth') }}" method="POST" class="auth-form" id="staffAuthForm">
                @csrf
                <input type="hidden" name="device_id" id="deviceIdField" value="">
                <p class="device-lock-chip device-lock-chip--locked" id="deviceLockNotice" style="display:none;">
                    <i class="fa fa-lock"></i>
                    <span id="deviceLockNoticeText">Device locked</span>
                </p>
                <div class="auth-field">
                    <label for="staffEmail">Staff email</label>
                    <div class="auth-input-wrap">
                        <i class="fa fa-envelope field-icon"></i>
                        <input class="auth-input" type="email" id="staffEmail" name="email" placeholder="you@emca.tech" value="{{ old('email') }}" required autofocus autocomplete="email">
                    </div>
                </div>
                <div class="btn-auth-continue-wrap">
                    <button type="submit" class="btn-auth-continue" id="staffAuthContinueBtn">
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span class="btn-label"><i class="fa fa-arrow-right"></i> Continue</span>
                    </button>
                </div>
            </form>
        @else
            @if(session('error'))
                <div class="alert alert-danger py-2 mb-2">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success py-2 mb-2">{{ session('success') }}</div>
            @endif

            <p class="device-lock-chip device-lock-chip--locked mb-2">
                <i class="fa fa-lock"></i>
                <span>Device locked · {{ Auth::user()->email }}</span>
            </p>

            <div class="non-working-banner" id="nonWorkingBanner"></div>
            <h4 class="mb-1 font-weight-bold" style="color:#940000;">
                {{ $isSignedIn ? 'Signed In' : 'Ready to Sign In' }}
            </h4>
            <p class="status-line" id="statusText">
                @if($isSignedIn && $lastSignIn)
                    Signed in at {{ $lastSignIn->format('h:i A') }}. Move to HQ boundary to sign out.
                @else
                    Enable location and go to HQ to sign in.
                @endif
            </p>

            <div class="distance-badge tracking" id="distanceBadge">
                <i class="fa fa-location-arrow"></i>
                <span id="distanceText">Locating...</span>
                <span id="etaText" style="display:none;"></span>
            </div>

            <div class="sign-actions-row">
                <button type="button" class="btn-sign-action" id="signActionBtn" disabled>
                    <i class="fa fa-map-marker"></i>
                    <span id="signActionLabel">{{ $isSignedIn ? 'Sign Out' : 'Sign In' }}</span>
                </button>
                <a href="{{ route('staff.sign.history') }}" class="btn-sign-secondary">
                    <i class="fa fa-history"></i> History
                </a>
            </div>

            <form action="{{ route('staff.sign.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-sign-logout">
                    <i class="fa fa-sign-out"></i> Log out &mdash; enter email again
                </button>
            </form>

            <p class="device-note"><i class="fa fa-shield"></i> Photo with place name stamp required for sign in only. Session expires after {{ $mapConfig['session_timeout_minutes'] ?? 30 }} min.</p>
        @endif
    </div>

    <canvas id="signCanvas"></canvas>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function getDeviceId() {
            const key = 'smartemca_device_id';
            let id = localStorage.getItem(key);
            if (!id) {
                id = 'web_' + (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '_' + Math.random().toString(36).slice(2));
                localStorage.setItem(key, id);
            }
            return id;
        }
        window.deviceId = getDeviceId();
        const deviceField = document.getElementById('deviceIdField');
        if (deviceField) deviceField.value = window.deviceId;

        const authForm = document.getElementById('staffAuthForm');
        const authBtn = document.getElementById('staffAuthContinueBtn');
        const authEmail = document.getElementById('staffEmail');
        const deviceLockNotice = document.getElementById('deviceLockNotice');
        const deviceLockNoticeText = document.getElementById('deviceLockNoticeText');
        const devicePolicyWarning = document.getElementById('devicePolicyWarning');
        let deviceIsBound = false;

        async function applyDeviceEmailLock() {
            if (!authEmail || !window.deviceId) return;

            try {
                const url = @json(route('staff.sign.device-binding')) + '?device_id=' + encodeURIComponent(window.deviceId);
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;

                const data = await res.json();
                if (!data.bound || !data.email) return;

                deviceIsBound = true;
                authEmail.value = data.email;
                authEmail.readOnly = true;

                if (devicePolicyWarning) devicePolicyWarning.style.display = 'none';

                if (deviceLockNotice && deviceLockNoticeText) {
                    const label = data.masked_email || data.email;
                    deviceLockNoticeText.textContent = 'Locked to ' + label;
                    deviceLockNotice.style.display = 'flex';
                }
            } catch (e) {}
        }

        if (authForm && authBtn) {
            applyDeviceEmailLock();
            authForm.addEventListener('submit', async function (e) {
                if (deviceField) deviceField.value = window.deviceId || deviceField.value;

                if (!deviceIsBound && authForm.dataset.deviceWarningConfirmed !== '1') {
                    e.preventDefault();
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Device will be locked',
                        html: '<p style="text-align:left;margin:0;line-height:1.5;">This <strong>phone or browser</strong> will be permanently linked to the email you enter.</p><p style="text-align:left;margin:12px 0 0;line-height:1.5;color:#842029;"><strong>Warning:</strong> You cannot use another staff email on this device later. Only an admin can reset the lock.</p>',
                        confirmButtonColor: '#940000',
                        confirmButtonText: 'I understand, continue',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                    });

                    if (!result.isConfirmed) return;

                    authForm.dataset.deviceWarningConfirmed = '1';
                    authBtn.classList.add('is-loading');
                    authBtn.disabled = true;
                    if (authEmail) authEmail.readOnly = true;
                    authForm.submit();
                    return;
                }

                authBtn.classList.add('is-loading');
                authBtn.disabled = true;
                if (authEmail) authEmail.readOnly = true;
            });
        }
    </script>

    @if(!$staffSignActive)
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => Promise.all(regs.map(r => r.unregister())));
            if (window.caches) {
                caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k))));
            }
        }
        const mapConfig = @json($mapConfig);
        const map = L.map('signMap', { zoomControl: false }).setView([mapConfig.hq_latitude, mapConfig.hq_longitude], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);
        L.control.zoom({ position: 'topright' }).addTo(map);
        L.circle([mapConfig.hq_latitude, mapConfig.hq_longitude], {
            color: '#940000', fillColor: '#940000', fillOpacity: 0.15, radius: mapConfig.geofence_radius
        }).addTo(map);
        L.marker([mapConfig.hq_latitude, mapConfig.hq_longitude]).addTo(map).bindPopup('EmCa HQ');
    </script>
    @endif

    @if($staffSignActive)
    <script>
        window.STAFF_SIGN_CONFIG = {
            authenticated: @json($staffSignActive),
            mapConfig: @json($mapConfig),
            isSignedIn: @json($isSignedIn),
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            routes: {
                signIn: @json(route('staff.sign.in')),
                signOut: @json(route('staff.sign.out')),
                signPage: @json(route('staff.sign')),
                reverseGeocode: @json(route('staff.sign.reverse-geocode')),
            },
        };
    </script>
    <script src="{{ asset('js/staff-sign.js') }}?v={{ filemtime(public_path('js/staff-sign.js')) }}"></script>
    @endif
</body>
</html>
