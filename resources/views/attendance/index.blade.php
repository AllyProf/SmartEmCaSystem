@extends('layouts.app')

@section('title', 'Attendance Dashboard')
@section('icon', 'fa-clock-o')
@section('page-title', 'Attendance Management')
@section('page-description', 'Real-time staff monitoring, geofence verification, and productivity tracking.')

@section('content')
<style>
    .attendance-photo-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
        display: block;
        border: 2px solid #dee2e6;
    }
    .attendance-photo-btn:hover .attendance-photo-thumb {
        border-color: #940000;
    }
    .attendance-stat-col {
        margin-bottom: 1rem;
    }
    .attendance-overview-map {
        height: 280px;
        width: 100%;
        border-radius: 0 0 10px 10px;
        z-index: 1;
    }
    .attendance-map-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        padding: 10px 14px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
        font-size: 0.78rem;
    }
    .attendance-map-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .attendance-map-legend i.dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        border: 1px solid rgba(0,0,0,.15);
    }
    .attendance-map-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 0 14px 10px;
    }
    .attendance-map-toolbar .btn { font-size: 0.78rem; }
    .attendance-at-hq-badge {
        font-size: 0.78rem;
        font-weight: 700;
        color: #155724;
        background: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 20px;
        padding: 4px 12px;
    }
    .attendance-pin-wrap {
        position: relative;
        width: 18px;
        height: 18px;
    }
    .attendance-marker-icon {
        background: transparent !important;
        border: none !important;
    }
    .attendance-pin-marker {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        transform: translate(-50%, calc(-100% - 2px));
        width: max-content;
        max-width: 160px;
        pointer-events: none;
    }
    .attendance-pin-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.25;
        padding: 2px 8px;
        margin-bottom: 3px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.97);
        color: #222;
        border: 1.5px solid var(--pin-color, #666);
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.28);
        white-space: nowrap;
        max-width: 160px;
        pointer-events: auto;
    }
    .attendance-pin-dot {
        width: 14px;
        height: 14px;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,.35);
        position: absolute;
        left: 2px;
        top: 2px;
        z-index: 2;
    }
    .attendance-pin-pulse {
        position: absolute;
        left: 0;
        top: 0;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: rgba(40, 167, 69, 0.45);
        animation: attendancePinPulse 1.8s ease-out infinite;
        z-index: 1;
    }
    @keyframes attendancePinPulse {
        0% { transform: scale(0.6); opacity: 0.9; }
        100% { transform: scale(2.2); opacity: 0; }
    }
    .attendance-map-popup { min-width: 180px; max-width: 220px; }
    .attendance-map-popup .popup-photo-thumb {
        width: 100%;
        max-height: 100px;
        object-fit: cover;
        border-radius: 8px;
        margin: 8px 0 6px;
        border: 2px solid #eee;
        cursor: pointer;
    }
    .attendance-map-popup .popup-badge {
        display: inline-block;
        font-size: 0.68rem;
        padding: 2px 8px;
        border-radius: 10px;
        margin: 2px 2px 0 0;
        color: #fff;
    }
    .attendance-filters-form .form-control {
        min-width: 0;
    }
    .attendance-cards-mobile {
        display: none;
    }
    .attendance-record-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,.04);
    }
    .attendance-record-card .card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
    }
    .attendance-sync-badge {
        font-size: 0.72rem;
        color: rgba(255,255,255,0.85);
        margin-top: 6px;
    }
    .attendance-sync-badge.syncing { opacity: 0.75; }
    .attendance-sync-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #28a745;
        margin-right: 5px;
        animation: attendanceSyncPulse 2s ease-in-out infinite;
    }
    @keyframes attendanceSyncPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.35; }
    }
    @media (min-width: 768px) {
        .attendance-overview-map { height: 400px; }
    }
    @media (max-width: 767.98px) {
        #liveClock { font-size: 1.65rem !important; }
        .tile-title-w-btn {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .tile-title-w-btn .title {
            margin-bottom: 12px !important;
        }
        .attendance-filters-form {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 8px;
        }
        .attendance-filters-form .form-control {
            width: 100% !important;
            margin-right: 0 !important;
        }
        .attendance-table-desktop { display: none !important; }
        .attendance-cards-mobile { display: block; }
        .attendance-overview-header {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .attendance-overview-map { height: 240px; }
        #attendanceLocationMap { height: 260px !important; }
        .modal-dialog.modal-lg {
            margin: 0.5rem auto;
            max-width: calc(100% - 1rem);
        }
        .attendance-map-btn {
            width: 100%;
        }
    }
</style>
<div class="row">
    <div class="col-12 col-sm-6 col-lg-3 attendance-stat-col">
        <div class="tile p-3 text-center shadow-sm" style="background-color: #940000; color: #ffffff; border-radius: 10px; border-bottom: 5px solid #000000;">
            <p class="mb-1 text-uppercase small" style="letter-spacing: 1px; opacity: 0.8;">Current Server Time</p>
            <h2 id="liveClock" class="font-weight-bold mb-0" style="font-size: 2.2rem;">--:--:--</h2>
            <p id="serverDate" class="mb-0 small">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</p>
            <p id="syncStatus" class="attendance-sync-badge mb-0"><span class="attendance-sync-dot"></span>Live sync on</p>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 attendance-stat-col">
        <div class="tile p-3 shadow-sm" style="border-radius: 10px; border-left: 5px solid #940000; min-height: 125px;">
            <p class="text-uppercase small font-weight-bold mb-2" style="color: #940000; letter-spacing: 0.5px;">This Week's Late Comers</p>
            <div class="px-1" id="weeklyLateComersList">
                @forelse($weeklyLateComers as $stat)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ $stat->name }}</span>
                        <span class="badge badge-pill text-white" style="background-color: #940000;">{{ $stat->frequency }} times</span>
                    </div>
                @empty
                    <div class="text-center py-2">
                        <span class="text-muted small italic">No late records this week</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 attendance-stat-col">
        <div class="tile p-3 shadow-sm" style="border-radius: 10px; border-left: 5px solid #000000; min-height: 125px;">
            <p class="text-uppercase small font-weight-bold mb-2" style="color: #000000; letter-spacing: 0.5px;">Top Early Arrivals</p>
            <div class="px-1" id="weeklyEarlyArrivalsList">
                @forelse($weeklyEarlyArrivals as $stat)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-weight-bold text-dark" style="font-size: 0.9rem;">{{ $stat->name }}</span>
                        <span class="badge badge-pill text-white" style="background-color: #000000;">{{ $stat->frequency }} times</span>
                    </div>
                @empty
                    <div class="text-center py-2">
                        <span class="text-muted small italic">No early records this week</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 attendance-stat-col">
        <div class="tile p-3 text-center shadow-sm" style="background-color: #000000; color: #ffffff; border-radius: 10px;">
            <p class="mb-2 text-uppercase small font-weight-bold" style="letter-spacing: 1px; color: #940000;">Rules Summary</p>
            <div class="d-flex justify-content-around mb-2">
                <div>
                    <span class="d-block small text-muted">In</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($expectedInTime)->format('h:i A') }}</span>
                </div>
                <div style="width: 1px; background-color: rgba(255,255,255,0.2); height: 35px;"></div>
                <div>
                    <span class="d-block small text-muted">Late after</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($lateAfterTime)->format('h:i A') }}</span>
                </div>
                <div style="width: 1px; background-color: rgba(255,255,255,0.2); height: 35px;"></div>
                <div>
                    <span class="d-block small text-muted">Out</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($expectedOutTime)->format('h:i A') }}</span>
                </div>
            </div>
            @if($lateGraceMinutes > 0)
                <p class="small text-muted mb-2">{{ $lateGraceMinutes }}-minute grace before late</p>
            @endif
            <a href="{{ route('settings.index') }}" class="btn btn-sm btn-block text-white" style="background-color: #940000; border-radius: 5px;">
                <i class="fa fa-cog"></i> SYSTEM SETTINGS
            </a>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="tile p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center flex-wrap attendance-overview-header p-3 pb-2">
                <div>
                    <h3 class="title mb-1">HQ Sign-In Overview</h3>
                    <p class="text-muted small mb-0"><span id="attendancePeriodLabel">{{ $filterPeriodLabel }}</span> · <span id="mapPinCount">{{ $mapPins->count() }}</span> location(s) on map</p>
                </div>
                <span class="badge badge-pill px-3 py-2" style="background:#940000;color:#fff;">
                    <i class="fa fa-building"></i> {{ $geofenceRadius }}m {{ $hqName }} zone
                </span>
            </div>
            <div class="attendance-map-toolbar">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleHeatmapBtn">
                    <i class="fa fa-fire"></i> Heatmap
                </button>
                <span class="attendance-at-hq-badge" id="atHqNowBadge">
                    <i class="fa fa-users"></i> <span id="atHqNowCount">0</span> at {{ $hqName }} now
                </span>
            </div>
            <div id="attendanceOverviewMap" class="attendance-overview-map"></div>
            <div class="attendance-map-legend">
                <span><i class="dot" style="background:#28a745;"></i> On time · inside HQ</span>
                <span><i class="dot" style="background:#dc3545;"></i> Late · inside HQ</span>
                <span><i class="dot" style="background:#fd7e14;"></i> Outside HQ</span>
                <span><i class="dot" style="background:#6c757d;"></i> Signed out · left HQ</span>
                <span><i class="dot" style="background:#28a745;box-shadow:0 0 0 3px rgba(40,167,69,.35);"></i> Pulsing = still at HQ</span>
                <span><i class="dot" style="background:#940000;"></i> HQ center</span>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Attendance Records</h3>
                <div class="btn-group w-100 w-md-auto">
                    <form action="{{ route('attendance.index') }}" method="GET" id="filterForm" class="form-inline attendance-filters-form">
                        <select name="filter" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="daily" {{ $filterType == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $filterType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="late" {{ $statusFilter == 'late' ? 'selected' : '' }}>Late Comers</option>
                            <option value="overdue" {{ $statusFilter == 'overdue' ? 'selected' : '' }}>Overdue Logout</option>
                            <option value="forgot" {{ $statusFilter == 'forgot' ? 'selected' : '' }}>Auto Sign-Out (Forgot)</option>
                        </select>
                        @if($filterType == 'daily')
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}" onchange="this.form.submit()">
                        @endif
                    </form>
                </div>
            </div>

            <div class="table-responsive attendance-table-desktop">
                <table class="table table-hover table-bordered" id="attendanceTable">
                    <thead>
                        <tr style="background-color: #000000; color: #ffffff;">
                            <th class="border-0">Staff Info</th>
                            <th class="border-0">Time In</th>
                            <th class="border-0">Time Out</th>
                            <th class="border-0">Working Hours</th>
                            <th class="border-0">Sign-In Photo</th>
                            <th class="border-0">Status & Location</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: 3px solid #940000;" id="attendanceTableBody">
                        @forelse($attendances as $log)
                            <tr>
                                <td>
                                    <strong>{{ $log->user->name }}</strong><br>
                                    <small class="text-muted">ID: {{ $log->user->staff_id }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-pill badge-info px-3">
                                        <i class="fa fa-sign-in"></i> {{ \Carbon\Carbon::parse($log->signed_in_at)->format('h:i A') }}
                                    </span><br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M, Y') }}</small>
                                </td>
                                <td>
                                    @if($log->signed_out_at)
                                        <span class="badge badge-pill badge-dark px-3">
                                            <i class="fa fa-sign-out"></i> {{ \Carbon\Carbon::parse($log->signed_out_at)->format('h:i A') }}
                                        </span>
                                        @if($log->is_forgot_sign_out)
                                            <br><span class="badge badge-secondary mt-1" title="System closed session — staff did not sign out">AUTO OUT</span>
                                        @endif
                                    @else
                                        <span class="badge badge-pill badge-warning px-3 border border-warning text-dark">
                                            Still Working
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->signed_out_at)
                                        <span class="font-weight-bold">{{ $log->working_hours }}</span>
                                    @else
                                        <span class="text-info font-italic working-hours-live" data-signed-in="{{ $log->signed_in_at->toIso8601String() }}">{{ $log->working_hours }}</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($log->photo_in)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger attendance-photo-btn p-0"
                                                title="View sign-in photo"
                                                data-photo-url="{{ $log->photoInUrl() }}"
                                                data-staff-name="{{ $log->user->name }}"
                                                data-signed-in="{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}">
                                            <img src="{{ $log->photoInUrl() }}" alt="Sign-in photo" class="attendance-photo-thumb">
                                        </button>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->is_late)
                                        <span class="badge badge-danger">Late</span>
                                    @else
                                        <span class="badge badge-success">On Time</span>
                                    @endif

                                    <!-- Overdue Status -->
                                    @if($log->is_overdue)
                                        <span class="badge badge-warning text-dark border border-warning" title="Forgot to sign out or working extra hours">OVERDUE</span>
                                    @endif
                                    @if($log->is_forgot_sign_out)
                                        <span class="badge badge-secondary" title="Auto closed at expected departure — SMS sent to staff and CEO">FORGOT SIGN-OUT</span>
                                    @endif

                                    <hr class="my-1">
                                    
                                    <!-- Geofence & Method -->
                                    <small>
                                        @if($log->signed_out_at)
                                            @if($log->location_verified_in)
                                                <i class="fa fa-sign-in text-success"></i> Signed in at HQ
                                            @else
                                                <i class="fa fa-sign-in text-danger"></i> Signed in outside HQ
                                            @endif
                                            @if($log->latitude_out && $log->longitude_out)
                                                <br>
                                                @if($log->location_verified_out)
                                                    <i class="fa fa-sign-out text-success"></i> Signed out at HQ
                                                @else
                                                    <i class="fa fa-sign-out text-warning"></i> Signed out outside HQ
                                                @endif
                                            @endif
                                        @elseif(!$log->location_verified_in)
                                            <i class="fa fa-map-marker text-danger"></i> Outside HQ
                                        @else
                                            <i class="fa fa-map-marker text-success"></i> Inside HQ now
                                        @endif
                                        | <i class="fa fa-fingerprint {{ $log->verification_type_in == 'fingerprint' ? 'text-primary' : 'text-info' }}"></i> {{ ucfirst(str_replace('_', ' ', $log->verification_type_in)) }}
                                    </small>
                                    @if($log->latitude_in && $log->longitude_in)
                                        <div class="mt-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary attendance-map-btn"
                                                    data-lat-in="{{ $log->latitude_in }}"
                                                    data-lng-in="{{ $log->longitude_in }}"
                                                    data-lat-out="{{ $log->latitude_out }}"
                                                    data-lng-out="{{ $log->longitude_out }}"
                                                    data-path-trace="{{ htmlspecialchars(json_encode($log->path_trace ?? []), ENT_QUOTES, 'UTF-8') }}"
                                                    data-staff-name="{{ $log->user->name }}"
                                                    data-signed-in="{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}"
                                                    data-signed-out="{{ $log->signed_out_at ? \Carbon\Carbon::parse($log->signed_out_at)->format('d M Y h:i A') : '' }}"
                                                    data-verified-in="{{ $log->location_verified_in ? '1' : '0' }}">
                                                <i class="fa fa-map"></i> View sign location
                                            </button>
                                            <div class="text-muted small mt-1">
                                                In: {{ number_format((float) $log->latitude_in, 5) }}, {{ number_format((float) $log->longitude_in, 5) }}
                                                @if($log->latitude_out && $log->longitude_out)
                                                    <br>Out: {{ number_format((float) $log->latitude_out, 5) }}, {{ number_format((float) $log->longitude_out, 5) }}
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted small mt-1">No GPS recorded</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No records found matching your selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="attendance-cards-mobile px-1 pb-2" id="attendanceCardsMobile">
                @forelse($attendances as $log)
                    <div class="attendance-record-card">
                        <div class="card-head">
                            <div>
                                <strong>{{ $log->user->name }}</strong><br>
                                <small class="text-muted">ID: {{ $log->user->staff_id }}</small>
                            </div>
                            <div class="text-right">
                                @if($log->is_late)
                                    <span class="badge badge-danger">Late</span>
                                @else
                                    <span class="badge badge-success">On Time</span>
                                @endif
                                @if($log->is_overdue)
                                    <span class="badge badge-warning text-dark">OVERDUE</span>
                                @endif
                                @if($log->is_forgot_sign_out)
                                    <span class="badge badge-secondary">FORGOT SIGN-OUT</span>
                                @endif
                            </div>
                        </div>
                        <div class="small">
                            <div><i class="fa fa-sign-in text-info"></i> <strong>In:</strong> {{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}</div>
                            <div class="mt-1">
                                <i class="fa fa-sign-out"></i> <strong>Out:</strong>
                                @if($log->signed_out_at)
                                    {{ \Carbon\Carbon::parse($log->signed_out_at)->format('d M Y h:i A') }}
                                    @if($log->is_forgot_sign_out)
                                        <span class="badge badge-secondary ml-1">AUTO</span>
                                    @endif
                                @else
                                    <span class="text-warning">Still working</span>
                                @endif
                            </div>
                            <div class="mt-1"><strong>Hours:</strong> {{ $log->working_hours }}</div>
                            <div class="mt-1 text-muted">
                                @if($log->signed_out_at)
                                    @if($log->location_verified_in)
                                        <i class="fa fa-sign-in text-success"></i> Signed in at HQ
                                    @else
                                        <i class="fa fa-sign-in text-danger"></i> Signed in outside HQ
                                    @endif
                                    @if($log->latitude_out && $log->longitude_out)
                                        <br>
                                        @if($log->location_verified_out)
                                            <i class="fa fa-sign-out text-success"></i> Signed out at HQ
                                        @else
                                            <i class="fa fa-sign-out text-warning"></i> Signed out outside HQ
                                        @endif
                                    @endif
                                @elseif(!$log->location_verified_in)
                                    <i class="fa fa-map-marker text-danger"></i> Outside HQ
                                @else
                                    <i class="fa fa-map-marker text-success"></i> Inside HQ now
                                @endif
                            </div>
                        </div>
                        <div class="card-actions">
                            @if($log->photo_in)
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger attendance-photo-btn p-0"
                                        data-photo-url="{{ $log->photoInUrl() }}"
                                        data-staff-name="{{ $log->user->name }}"
                                        data-signed-in="{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}">
                                    <img src="{{ $log->photoInUrl() }}" alt="Photo" class="attendance-photo-thumb">
                                </button>
                            @endif
                            @if($log->latitude_in && $log->longitude_in)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary attendance-map-btn"
                                        data-lat-in="{{ $log->latitude_in }}"
                                        data-lng-in="{{ $log->longitude_in }}"
                                        data-lat-out="{{ $log->latitude_out }}"
                                        data-lng-out="{{ $log->longitude_out }}"
                                        data-path-trace="{{ htmlspecialchars(json_encode($log->path_trace ?? []), ENT_QUOTES, 'UTF-8') }}"
                                        data-staff-name="{{ $log->user->name }}"
                                        data-signed-in="{{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}"
                                        data-signed-out="{{ $log->signed_out_at ? \Carbon\Carbon::parse($log->signed_out_at)->format('d M Y h:i A') : '' }}"
                                        data-verified-in="{{ $log->location_verified_in ? '1' : '0' }}">
                                    <i class="fa fa-map"></i> Location
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">No records found matching your selection.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Sign-in location map modal -->
<div class="modal fade" id="attendanceLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0">
            <div class="modal-header text-white" style="background-color: #000;">
                <h5 class="modal-title font-weight-bold" id="attendanceLocationModalTitle">Sign Location</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="attendanceLocationMap" style="height:420px;width:100%;"></div>
                <div class="p-3 small" id="attendanceLocationDetails"></div>
            </div>
            <div class="modal-footer bg-light">
                <a href="#" id="attendanceLocationGoogleLink" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-external-link"></i> Open in Google Maps
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sign-in photo modal -->
<div class="modal fade" id="attendancePhotoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0">
            <div class="modal-header text-white" style="background-color: #940000;">
                <h5 class="modal-title font-weight-bold" id="attendancePhotoModalTitle">Sign-In Photo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-2" style="background:#111;">
                <img id="attendancePhotoModalImage" src="" alt="Sign-in photo" style="max-width:100%;max-height:75vh;border-radius:8px;">
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    const hqLat = {{ $hqLatitude }};
    const hqLng = {{ $hqLongitude }};
    const hqRadius = {{ $geofenceRadius }};
    const hqName = @json($hqName);
    const attendanceSyncUrl = @json(route('attendance.sync'));
    let overviewPins = @json($mapPins);
    let serverTimeOffsetMs = 0;
    let syncInFlight = false;
    let attendanceOverviewMap = null;
    let overviewMarkersLayer = null;
    let overviewHeatLayer = null;
    let heatmapActive = false;
    let attendanceLocationMap = null;
    let attendanceMarkersLayer = null;

    function serverNow() {
        return new Date(Date.now() + serverTimeOffsetMs);
    }

    function updateClock() {
        const now = serverNow();
        const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('liveClock').textContent = timeStr;
    }

    function formatVerificationType(type) {
        return String(type || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    function renderWeeklyStats(containerId, items, badgeColor, emptyText) {
        const el = document.getElementById(containerId);
        if (!el) return;

        if (!items || !items.length) {
            el.innerHTML = '<div class="text-center py-2"><span class="text-muted small italic">' + escapeHtml(emptyText) + '</span></div>';
            return;
        }

        el.innerHTML = items.map(function (stat) {
            return '<div class="d-flex justify-content-between align-items-center mb-1">'
                + '<span class="font-weight-bold text-dark" style="font-size: 0.9rem;">' + escapeHtml(stat.name) + '</span>'
                + '<span class="badge badge-pill text-white" style="background-color: ' + badgeColor + ';">'
                + stat.frequency + ' times</span></div>';
        }).join('');
    }

    function buildRecordRowHtml(log) {
        let statusHtml = log.is_late
            ? '<span class="badge badge-danger">Late</span>'
            : '<span class="badge badge-success">On Time</span>';
        if (log.is_overdue) {
            statusHtml += ' <span class="badge badge-warning text-dark border border-warning" title="Forgot to sign out or working extra hours">OVERDUE</span>';
        }
        if (log.is_forgot_sign_out) {
            statusHtml += ' <span class="badge badge-secondary" title="Auto closed at expected departure — SMS sent to staff and CEO">FORGOT SIGN-OUT</span>';
        }

        let outHtml = '';
        if (log.signed_out) {
            outHtml = '<span class="badge badge-pill badge-dark px-3"><i class="fa fa-sign-out"></i> ' + escapeHtml(log.signed_out) + '</span>';
            if (log.is_forgot_sign_out) {
                outHtml += '<br><span class="badge badge-secondary mt-1" title="System closed session — staff did not sign out">AUTO OUT</span>';
            }
        } else {
            outHtml = '<span class="badge badge-pill badge-warning px-3 border border-warning text-dark">Still Working</span>';
        }

        let photoHtml = '<span class="text-muted small">—</span>';
        if (log.photo_url) {
            photoHtml = '<button type="button" class="btn btn-sm btn-outline-danger attendance-photo-btn p-0" title="View sign-in photo"'
                + ' data-photo-url="' + escapeHtml(log.photo_url) + '"'
                + ' data-staff-name="' + escapeHtml(log.name) + '"'
                + ' data-signed-in="' + escapeHtml(log.signed_in_full) + '">'
                + '<img src="' + escapeHtml(log.photo_url) + '" alt="Sign-in photo" class="attendance-photo-thumb"></button>';
        }

        let locationHtml = '<div class="text-muted small mt-1">No GPS recorded</div>';
        if (log.latitude_in && log.longitude_in) {
            const pathTrace = JSON.stringify(log.path_trace || []);
            locationHtml = '<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary attendance-map-btn"'
                + ' data-lat-in="' + log.latitude_in + '" data-lng-in="' + log.longitude_in + '"'
                + ' data-lat-out="' + (log.latitude_out || '') + '" data-lng-out="' + (log.longitude_out || '') + '"'
                + ' data-path-trace="' + escapeHtml(pathTrace) + '"'
                + ' data-staff-name="' + escapeHtml(log.name) + '"'
                + ' data-signed-in="' + escapeHtml(log.signed_in_full) + '"'
                + ' data-signed-out="' + escapeHtml(log.signed_out_full || '') + '"'
                + ' data-verified-in="' + (log.location_verified_in ? '1' : '0') + '">'
                + '<i class="fa fa-map"></i> View sign location</button>'
                + '<div class="text-muted small mt-1">In: ' + parseFloat(log.latitude_in).toFixed(5) + ', ' + parseFloat(log.longitude_in).toFixed(5);
            if (log.latitude_out && log.longitude_out) {
                locationHtml += '<br>Out: ' + parseFloat(log.latitude_out).toFixed(5) + ', ' + parseFloat(log.longitude_out).toFixed(5);
            }
            locationHtml += '</div></div>';
        }

        const hoursClass = log.still_working ? 'text-info font-italic' : 'font-weight-bold';

        let locationLabel = '';
        if (!log.still_working) {
            locationLabel = log.location_verified_in
                ? '<i class="fa fa-sign-in text-success"></i> Signed in at HQ'
                : '<i class="fa fa-sign-in text-danger"></i> Signed in outside HQ';
            if (log.latitude_out && log.longitude_out) {
                locationLabel += log.location_verified_out
                    ? '<br><i class="fa fa-sign-out text-success"></i> Signed out at HQ'
                    : '<br><i class="fa fa-sign-out text-warning"></i> Signed out outside HQ';
            }
        } else {
            locationLabel = log.location_verified_in
                ? '<i class="fa fa-map-marker text-success"></i> Inside HQ now'
                : '<i class="fa fa-map-marker text-danger"></i> Outside HQ';
        }

        return '<tr>'
            + '<td><strong>' + escapeHtml(log.name) + '</strong><br><small class="text-muted">ID: ' + escapeHtml(log.staff_id) + '</small></td>'
            + '<td><span class="badge badge-pill badge-info px-3"><i class="fa fa-sign-in"></i> ' + escapeHtml(log.signed_in) + '</span><br>'
            + '<small class="text-muted">' + escapeHtml(log.signed_in_date) + '</small></td>'
            + '<td>' + outHtml + '</td>'
            + '<td><span class="' + hoursClass + '">' + escapeHtml(log.working_hours) + '</span></td>'
            + '<td class="text-center align-middle">' + photoHtml + '</td>'
            + '<td>' + statusHtml + '<hr class="my-1"><small>'
            + locationLabel
            + ' | <i class="fa fa-fingerprint text-info"></i> ' + escapeHtml(formatVerificationType(log.verification_type_in))
            + '</small>' + locationHtml + '</td></tr>';
    }

    function buildRecordCardHtml(log) {
        let badges = log.is_late
            ? '<span class="badge badge-danger">Late</span>'
            : '<span class="badge badge-success">On Time</span>';
        if (log.is_overdue) badges += ' <span class="badge badge-warning text-dark">OVERDUE</span>';
        if (log.is_forgot_sign_out) badges += ' <span class="badge badge-secondary">FORGOT SIGN-OUT</span>';

        let actions = '';
        if (log.photo_url) {
            actions += '<button type="button" class="btn btn-sm btn-outline-danger attendance-photo-btn p-0"'
                + ' data-photo-url="' + escapeHtml(log.photo_url) + '"'
                + ' data-staff-name="' + escapeHtml(log.name) + '"'
                + ' data-signed-in="' + escapeHtml(log.signed_in_full) + '">'
                + '<img src="' + escapeHtml(log.photo_url) + '" alt="Photo" class="attendance-photo-thumb"></button>';
        }
        if (log.latitude_in && log.longitude_in) {
            const pathTrace = JSON.stringify(log.path_trace || []);
            actions += '<button type="button" class="btn btn-sm btn-outline-primary attendance-map-btn"'
                + ' data-lat-in="' + log.latitude_in + '" data-lng-in="' + log.longitude_in + '"'
                + ' data-lat-out="' + (log.latitude_out || '') + '" data-lng-out="' + (log.longitude_out || '') + '"'
                + ' data-path-trace="' + escapeHtml(pathTrace) + '"'
                + ' data-staff-name="' + escapeHtml(log.name) + '"'
                + ' data-signed-in="' + escapeHtml(log.signed_in_full) + '"'
                + ' data-signed-out="' + escapeHtml(log.signed_out_full || '') + '"'
                + ' data-verified-in="' + (log.location_verified_in ? '1' : '0') + '">'
                + '<i class="fa fa-map"></i> Location</button>';
        }

        let outLine = log.signed_out
            ? escapeHtml(log.signed_out_full) + (log.is_forgot_sign_out ? ' <span class="badge badge-secondary ml-1">AUTO</span>' : '')
            : '<span class="text-warning">Still working</span>';

        let locationLine = '';
        if (!log.still_working) {
            locationLine = log.location_verified_in
                ? '<i class="fa fa-sign-in text-success"></i> Signed in at HQ'
                : '<i class="fa fa-sign-in text-danger"></i> Signed in outside HQ';
            if (log.latitude_out && log.longitude_out) {
                locationLine += log.location_verified_out
                    ? '<br><i class="fa fa-sign-out text-success"></i> Signed out at HQ'
                    : '<br><i class="fa fa-sign-out text-warning"></i> Signed out outside HQ';
            }
        } else {
            locationLine = log.location_verified_in
                ? '<i class="fa fa-map-marker text-success"></i> Inside HQ now'
                : '<i class="fa fa-map-marker text-danger"></i> Outside HQ';
        }

        return '<div class="attendance-record-card"><div class="card-head"><div><strong>' + escapeHtml(log.name) + '</strong><br>'
            + '<small class="text-muted">ID: ' + escapeHtml(log.staff_id) + '</small></div><div class="text-right">' + badges + '</div></div>'
            + '<div class="small"><div><i class="fa fa-sign-in text-info"></i> <strong>In:</strong> ' + escapeHtml(log.signed_in_full) + '</div>'
            + '<div class="mt-1"><i class="fa fa-sign-out"></i> <strong>Out:</strong> ' + outLine + '</div>'
            + '<div class="mt-1"><strong>Hours:</strong> ' + escapeHtml(log.working_hours) + '</div>'
            + '<div class="mt-1 text-muted">' + locationLine + '</div></div>'
            + '<div class="card-actions">' + actions + '</div></div>';
    }

    function renderAttendanceRecords(records) {
        const tbody = document.getElementById('attendanceTableBody');
        const cards = document.getElementById('attendanceCardsMobile');
        if (!tbody || !cards) return;

        if (!records.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No records found matching your selection.</td></tr>';
            cards.innerHTML = '<div class="text-center py-4 text-muted">No records found matching your selection.</div>';
            return;
        }

        tbody.innerHTML = records.map(buildRecordRowHtml).join('');
        cards.innerHTML = records.map(buildRecordCardHtml).join('');
    }

    function haversineMeters(lat1, lng1, lat2, lng2) {
        const earthRadius = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
            * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function offsetLatLng(lat, lng, bearingDeg, distanceM) {
        const earthRadius = 6371000;
        const bearing = bearingDeg * Math.PI / 180;
        const lat1 = lat * Math.PI / 180;
        const lng1 = lng * Math.PI / 180;
        const angular = distanceM / earthRadius;
        const lat2 = Math.asin(
            Math.sin(lat1) * Math.cos(angular)
            + Math.cos(lat1) * Math.sin(angular) * Math.cos(bearing)
        );
        const lng2 = lng1 + Math.atan2(
            Math.sin(bearing) * Math.sin(angular) * Math.cos(lat1),
            Math.cos(angular) - Math.sin(lat1) * Math.sin(lat2)
        );
        return { lat: lat2 * 180 / Math.PI, lng: lng2 * 180 / Math.PI };
    }

    function spreadPinsForDisplay(pins) {
        const thresholdM = 10;
        const spreadRadiusM = 22;
        const clones = pins.map(function (pin) { return Object.assign({}, pin); });
        const groups = [];

        clones.forEach(function (pin) {
            let group = null;
            for (let i = 0; i < groups.length; i++) {
                const ref = groups[i][0];
                if (haversineMeters(pin.lat, pin.lng, ref.lat, ref.lng) < thresholdM) {
                    group = groups[i];
                    break;
                }
            }
            if (!group) {
                group = [];
                groups.push(group);
            }
            group.push(pin);
        });

        const displayPins = [];
        groups.forEach(function (group) {
            const centerLat = group.reduce(function (sum, pin) { return sum + pin.lat; }, 0) / group.length;
            const centerLng = group.reduce(function (sum, pin) { return sum + pin.lng; }, 0) / group.length;

            group.forEach(function (pin, index) {
                if (group.length > 1) {
                    const angle = ((360 / group.length) * index) - 90;
                    const spread = offsetLatLng(centerLat, centerLng, angle, spreadRadiusM);
                    pin.display_lat = spread.lat;
                    pin.display_lng = spread.lng;
                } else {
                    pin.display_lat = pin.lat;
                    pin.display_lng = pin.lng;
                }
                displayPins.push(pin);
            });
        });

        return displayPins;
    }

    function pinDisplayCoords(pin) {
        return {
            lat: pin.display_lat != null ? pin.display_lat : pin.lat,
            lng: pin.display_lng != null ? pin.display_lng : pin.lng,
        };
    }

    function refreshOverviewMapPins(fitBounds) {
        if (!attendanceOverviewMap) return;

        if (overviewMarkersLayer) {
            attendanceOverviewMap.removeLayer(overviewMarkersLayer);
        }
        overviewMarkersLayer = L.layerGroup().addTo(attendanceOverviewMap);

        const bounds = [[hqLat, hqLng]];
        const displayPins = spreadPinsForDisplay(overviewPins);

        displayPins.forEach(function (pin, index) {
            const color = overviewPinColor(pin);
            const pulsing = pin.still_working && pin.inside_hq;
            const coords = pinDisplayCoords(pin);
            const marker = L.marker([coords.lat, coords.lng], {
                icon: attendancePinIcon(color, pulsing, pin.name),
                zIndexOffset: 1000 + index,
            })
                .addTo(overviewMarkersLayer)
                .bindPopup(buildOverviewPopup(pin));
            bindOverviewPopupEvents(pin, marker);
            bounds.push([coords.lat, coords.lng]);

            if (pin.lat_in && pin.lng_in && pin.still_working
                && (pin.lat_in !== pin.lat || pin.lng_in !== pin.lng)) {
                L.marker([pin.lat_in, pin.lng_in], {
                    icon: attendancePinIcon('#17a2b8', false, pin.name + ' · in'),
                    zIndexOffset: 500 + index,
                })
                    .addTo(overviewMarkersLayer)
                    .bindPopup('<strong>' + escapeHtml(pin.name) + '</strong><br>Sign-in location');
                bounds.push([pin.lat_in, pin.lng_in]);
            }
        });

        if (fitBounds && bounds.length > 1) {
            attendanceOverviewMap.fitBounds(L.latLngBounds(bounds), { padding: [32, 32], maxZoom: 18 });
        }

        if (heatmapActive && overviewHeatLayer) {
            attendanceOverviewMap.removeLayer(overviewHeatLayer);
            overviewHeatLayer = null;
            const points = overviewPins
                .filter(function (pin) { return pin.still_working; })
                .map(function (pin) {
                return [pin.lat, pin.lng, 1.0];
            });
            if (points.length) {
                overviewHeatLayer = L.heatLayer(points, {
                    radius: 28,
                    blur: 22,
                    maxZoom: 18,
                    gradient: { 0.2: '#ffe08a', 0.5: '#fd7e14', 0.8: '#dc3545', 1.0: '#940000' },
                }).addTo(attendanceOverviewMap);
            }
        }

        updateAtHqNowCount();
    }

    function applySyncPayload(data) {
        if (data.server_time) {
            const parts = data.server_time.split(':').map(Number);
            const serverDate = new Date();
            serverDate.setHours(parts[0], parts[1], parts[2], 0);
            serverTimeOffsetMs = serverDate.getTime() - Date.now();
            updateClock();
        }
        if (data.server_date) {
            const dateEl = document.getElementById('serverDate');
            if (dateEl) dateEl.textContent = data.server_date;
        }

        renderWeeklyStats('weeklyLateComersList', data.weekly_late_comers, '#940000', 'No late records this week');
        renderWeeklyStats('weeklyEarlyArrivalsList', data.weekly_early_arrivals, '#000000', 'No early records this week');

        const periodEl = document.getElementById('attendancePeriodLabel');
        const pinCountEl = document.getElementById('mapPinCount');
        if (periodEl) periodEl.textContent = data.filter_period_label || '';
        if (pinCountEl) pinCountEl.textContent = data.map_pin_count || 0;

        overviewPins = data.map_pins || [];
        renderAttendanceRecords(data.records || []);
        refreshOverviewMapPins();
    }

    function syncAttendanceData() {
        if (syncInFlight || document.hidden) return;

        syncInFlight = true;
        const statusEl = document.getElementById('syncStatus');
        if (statusEl) statusEl.classList.add('syncing');

        const query = window.location.search || '';
        fetch(attendanceSyncUrl + query, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Sync failed');
                return res.json();
            })
            .then(function (data) {
                applySyncPayload(data);
                if (statusEl) {
                    statusEl.innerHTML = '<span class="attendance-sync-dot"></span>Updated ' + serverNow().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                }
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.innerHTML = '<span class="attendance-sync-dot" style="background:#ffc107;"></span>Sync paused — retrying';
                }
            })
            .finally(function () {
                syncInFlight = false;
                if (statusEl) statusEl.classList.remove('syncing');
            });
    }

    setInterval(updateClock, 1000);
    updateClock();

    $(document).on('click', '.attendance-photo-btn', function () {
        const url = $(this).data('photo-url');
        const name = $(this).data('staff-name');
        const signedIn = $(this).data('signed-in');
        $('#attendancePhotoModalTitle').text(name + ' · ' + signedIn);
        $('#attendancePhotoModalImage').attr('src', url);
        $('#attendancePhotoModal').modal('show');
    });

    function attendancePinIcon(color, pulsing, name) {
        const pulse = pulsing
            ? '<div class="attendance-pin-pulse"></div>'
            : '';
        const labelHtml = name
            ? '<span class="attendance-pin-label" style="--pin-color:' + color + '">' + escapeHtml(name) + '</span>'
            : '';
        return L.divIcon({
            className: 'attendance-marker-icon',
            html: '<div class="attendance-pin-marker">' + labelHtml
                + '<div class="attendance-pin-wrap">' + pulse
                + '<div class="attendance-pin-dot" style="background:' + color + '"></div></div></div>',
            iconSize: [0, 0],
            iconAnchor: [0, 0],
        });
    }

    function overviewPinColor(pin) {
        if (!pin.still_working) return '#6c757d';
        if (!pin.inside_hq) return '#fd7e14';
        return pin.is_late ? '#dc3545' : '#28a745';
    }

    function openOverviewPhoto(pin) {
        if (!pin.photo_url) return;
        $('#attendancePhotoModalTitle').text(pin.name + ' · ' + pin.signed_in_date + ' ' + pin.signed_in);
        $('#attendancePhotoModalImage').attr('src', pin.photo_url);
        $('#attendancePhotoModal').modal('show');
    }

    function buildOverviewPopup(pin) {
        const color = overviewPinColor(pin);
        let html = '<div class="attendance-map-popup">';
        html += '<strong>' + escapeHtml(pin.name) + '</strong>';
        if (pin.staff_id) {
            html += '<div class="text-muted small">ID: ' + escapeHtml(pin.staff_id) + '</div>';
        }
        html += '<div class="mt-1">';
        if (!pin.still_working) {
            html += '<span class="popup-badge" style="background:#6c757d;">Signed out · left HQ</span>';
            html += '<span class="popup-badge" style="background:#fd7e14;">Outside ' + escapeHtml(hqName) + ' zone</span>';
        } else {
            html += '<span class="popup-badge" style="background:' + color + ';">'
                + (pin.inside_hq ? 'Inside ' + escapeHtml(hqName) : 'Outside ' + escapeHtml(hqName)) + '</span>';
            if (pin.is_late) {
                html += '<span class="popup-badge" style="background:#dc3545;">Late</span>';
            } else if (pin.inside_hq) {
                html += '<span class="popup-badge" style="background:#28a745;">On time</span>';
            }
            html += '<span class="popup-badge" style="background:#007bff;">At ' + escapeHtml(hqName) + ' now</span>';
            if (pin.last_seen_seconds !== null && pin.last_seen_seconds > 120) {
                const mins = Math.max(1, Math.round(pin.last_seen_seconds / 60));
                html += '<span class="popup-badge" style="background:#6c757d;">GPS offline · last seen ' + mins + 'm</span>';
            }
        }
        if (pin.forgot_sign_out) {
            html += '<span class="popup-badge" style="background:#6c757d;">Forgot sign-out</span>';
        }
        if (pin.gps_flagged) {
            html += '<span class="popup-badge" style="background:#6f42c1;">GPS flagged</span>';
        }
        html += '</div>';
        html += '<div class="small mt-1"><i class="fa fa-clock-o"></i> In: ' + escapeHtml(pin.signed_in_date)
            + ' ' + escapeHtml(pin.signed_in) + '</div>';
        if (pin.signed_out) {
            html += '<div class="small"><i class="fa fa-sign-out"></i> Out: ' + escapeHtml(pin.signed_out) + '</div>';
        }
        const locationLabel = !pin.still_working
            ? 'Left HQ after sign-out'
            : 'Current location';
        html += '<div class="small text-muted">' + locationLabel + ' · sign-out GPS ' + pin.distance_m + 'm from ' + escapeHtml(hqName) + ' center</div>';
        if (pin.photo_url) {
            html += '<img src="' + escapeHtml(pin.photo_url) + '" class="popup-photo-thumb overview-popup-photo" alt="Sign-in photo" data-pin-id="' + pin.id + '">';
            html += '<button type="button" class="btn btn-sm btn-danger btn-block overview-popup-photo-btn" data-pin-id="' + pin.id + '">'
                + '<i class="fa fa-camera"></i> View photo</button>';
        }
        html += '<a class="btn btn-sm btn-outline-secondary btn-block mt-2" target="_blank" rel="noopener" href="' + escapeHtml(@json(url('/attendance/journey')) + '/' + pin.id) + '">'
            + '<i class="fa fa-map"></i> View journey</a>';
        html += '</div>';
        return html;
    }

    function updateAtHqNowCount() {
        const count = overviewPins.filter(function (p) {
            return p.still_working && p.inside_hq;
        }).length;
        $('#atHqNowCount').text(count);
    }

    function bindOverviewPopupEvents(pin, marker) {
        marker.on('popupopen', function () {
            const popupEl = marker.getPopup().getElement();
            if (!popupEl) return;
            popupEl.querySelectorAll('.overview-popup-photo, .overview-popup-photo-btn').forEach(function (el) {
                el.addEventListener('click', function () {
                    openOverviewPhoto(pin);
                });
            });
        });
    }

    function toggleOverviewHeatmap() {
        if (!attendanceOverviewMap || typeof L.heatLayer !== 'function') return;

        if (heatmapActive && overviewHeatLayer) {
            attendanceOverviewMap.removeLayer(overviewHeatLayer);
            overviewHeatLayer = null;
            heatmapActive = false;
            $('#toggleHeatmapBtn').removeClass('btn-danger text-white').addClass('btn-outline-secondary');
            return;
        }

        const points = overviewPins
            .filter(function (pin) { return pin.still_working; })
            .map(function (pin) {
            return [pin.lat, pin.lng, 1.0];
        });

        if (!points.length) return;

        overviewHeatLayer = L.heatLayer(points, {
            radius: 28,
            blur: 22,
            maxZoom: 18,
            gradient: { 0.2: '#ffe08a', 0.5: '#fd7e14', 0.8: '#dc3545', 1.0: '#940000' },
        }).addTo(attendanceOverviewMap);
        heatmapActive = true;
        $('#toggleHeatmapBtn').removeClass('btn-outline-secondary').addClass('btn-danger text-white');
    }

    function initAttendanceOverviewMap() {
        const el = document.getElementById('attendanceOverviewMap');
        if (!el || attendanceOverviewMap) return;

        attendanceOverviewMap = L.map('attendanceOverviewMap', { zoomControl: true }).setView([hqLat, hqLng], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(attendanceOverviewMap);

        L.circle([hqLat, hqLng], {
            color: '#940000',
            fillColor: '#940000',
            fillOpacity: 0.12,
            radius: hqRadius,
        }).addTo(attendanceOverviewMap).bindPopup(escapeHtml(hqName) + ' · ' + hqRadius + 'm zone');

        L.marker([hqLat, hqLng], {
            icon: attendancePinIcon('#940000', false, 'HQ'),
            zIndexOffset: 50,
        }).addTo(attendanceOverviewMap).bindPopup('<strong>' + escapeHtml(hqName) + ' center</strong>');

        overviewMarkersLayer = L.layerGroup().addTo(attendanceOverviewMap);
        refreshOverviewMapPins(true);

        setTimeout(function () { attendanceOverviewMap.invalidateSize(); }, 200);
    }

    $(document).ready(function () {
        initAttendanceOverviewMap();
        $('#toggleHeatmapBtn').on('click', toggleOverviewHeatmap);
        $(window).on('resize', function () {
            if (attendanceOverviewMap) attendanceOverviewMap.invalidateSize();
        });

        syncAttendanceData();
        setInterval(syncAttendanceData, 30000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) syncAttendanceData();
        });
    });

    function ensureAttendanceLocationMap() {
        if (attendanceLocationMap) {
            attendanceLocationMap.invalidateSize();
            return;
        }
        attendanceLocationMap = L.map('attendanceLocationMap').setView([hqLat, hqLng], 16);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(attendanceLocationMap);
        L.circle([hqLat, hqLng], {
            color: '#940000',
            fillColor: '#940000',
            fillOpacity: 0.12,
            radius: hqRadius,
        }).addTo(attendanceLocationMap).bindPopup(escapeHtml(hqName) + ' · ' + hqRadius + 'm zone');
        L.marker([hqLat, hqLng]).addTo(attendanceLocationMap).bindPopup(escapeHtml(hqName) + ' center');
        attendanceMarkersLayer = L.layerGroup().addTo(attendanceLocationMap);
    }

    function clearAttendanceMarkers() {
        if (attendanceMarkersLayer) {
            attendanceMarkersLayer.clearLayers();
        }
    }

    $(document).on('click', '.attendance-map-btn', function () {
        const $btn = $(this);
        const latIn = parseFloat($btn.data('lat-in'));
        const lngIn = parseFloat($btn.data('lng-in'));
        const latOut = parseFloat($btn.data('lat-out'));
        const lngOut = parseFloat($btn.data('lng-out'));
        let pathTrace = [];
        try {
            pathTrace = JSON.parse($btn.attr('data-path-trace') || '[]');
        } catch (e) {
            pathTrace = [];
        }

        $('#attendanceLocationModalTitle').text($btn.data('staff-name') + ' · Sign locations');
        $('#attendanceLocationGoogleLink').attr('href', 'https://www.google.com/maps?q=' + latIn + ',' + lngIn);

        let details = '<strong>Sign in:</strong> ' + $btn.data('signed-in') + '<br>';
        details += '<span class="text-muted">' + latIn.toFixed(6) + ', ' + lngIn.toFixed(6) + '</span>';
        details += $btn.data('verified-in') === 1
            ? ' <span class="badge badge-success">Inside ' + escapeHtml(hqName) + '</span>'
            : ' <span class="badge badge-danger">Outside ' + escapeHtml(hqName) + '</span>';
        if ($btn.data('signed-out')) {
            details += '<br><strong>Sign out:</strong> ' + $btn.data('signed-out');
            if (!isNaN(latOut) && !isNaN(lngOut)) {
                details += '<br><span class="text-muted">' + latOut.toFixed(6) + ', ' + lngOut.toFixed(6) + '</span>';
            }
        }
        $('#attendanceLocationDetails').html(details);

        $('#attendanceLocationModal').modal('show');

        $('#attendanceLocationModal').one('shown.bs.modal', function () {
            ensureAttendanceLocationMap();
            clearAttendanceMarkers();

            const bounds = [];

            if (pathTrace.length > 1) {
                const pts = pathTrace.map(p => [parseFloat(p.lat), parseFloat(p.lng)]).filter(p => !isNaN(p[0]) && !isNaN(p[1]));
                if (pts.length > 1) {
                    L.polyline(pts, { color: '#007bff', weight: 4, opacity: 0.85 }).addTo(attendanceMarkersLayer);
                    pts.forEach(pt => bounds.push(pt));
                }
            }

            if (!isNaN(latIn) && !isNaN(lngIn)) {
                L.marker([latIn, lngIn]).addTo(attendanceMarkersLayer).bindPopup('Sign in');
                bounds.push([latIn, lngIn]);
            }

            if (!isNaN(latOut) && !isNaN(lngOut)) {
                L.marker([latOut, lngOut]).addTo(attendanceMarkersLayer).bindPopup('Sign out');
                bounds.push([latOut, lngOut]);
            }

            bounds.push([hqLat, hqLng]);

            if (bounds.length) {
                attendanceLocationMap.fitBounds(L.latLngBounds(bounds), { padding: [36, 36], maxZoom: 18 });
            }
        });
    });
</script>
@endpush
@endsection
