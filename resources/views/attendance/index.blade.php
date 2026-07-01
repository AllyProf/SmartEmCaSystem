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
    .attendance-record-card .card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
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
            <p class="mb-0 small">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</p>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3 attendance-stat-col">
        <div class="tile p-3 shadow-sm" style="border-radius: 10px; border-left: 5px solid #940000; min-height: 125px;">
            <p class="text-uppercase small font-weight-bold mb-2" style="color: #940000; letter-spacing: 0.5px;">This Week's Late Comers</p>
            <div class="px-1">
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
            <div class="px-1">
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
                    <span class="d-block small text-muted">Out</span>
                    <span class="font-weight-bold" style="font-size: 1.1rem;">{{ \Carbon\Carbon::parse($expectedOutTime)->format('h:i A') }}</span>
                </div>
            </div>
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
                    <p class="text-muted small mb-0">{{ $filterPeriodLabel }} · {{ $mapPins->count() }} location(s) on map</p>
                </div>
                <span class="badge badge-pill px-3 py-2" style="background:#940000;color:#fff;">
                    <i class="fa fa-building"></i> {{ $geofenceRadius }}m HQ zone
                </span>
            </div>
            <div id="attendanceOverviewMap" class="attendance-overview-map"></div>
            <div class="attendance-map-legend">
                <span><i class="dot" style="background:#28a745;"></i> On time · inside HQ</span>
                <span><i class="dot" style="background:#dc3545;"></i> Late · inside HQ</span>
                <span><i class="dot" style="background:#fd7e14;"></i> Outside HQ</span>
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
                    <tbody style="border-top: 3px solid #940000;">
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
                                    @else
                                        <span class="badge badge-pill badge-warning px-3 border border-warning text-dark">
                                            Still Working
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->working_hours == 'Pending')
                                        <span class="text-info font-italic">Calculating...</span>
                                    @else
                                        <span class="font-weight-bold">{{ $log->working_hours }}</span>
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

                                    <hr class="my-1">
                                    
                                    <!-- Geofence & Method -->
                                    <small>
                                        @if(!$log->location_verified_in)
                                            <i class="fa fa-map-marker text-danger"></i> Outside HQ 
                                        @else
                                            <i class="fa fa-map-marker text-success"></i> inside HQ
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

            <div class="attendance-cards-mobile px-1 pb-2">
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
                            </div>
                        </div>
                        <div class="small">
                            <div><i class="fa fa-sign-in text-info"></i> <strong>In:</strong> {{ \Carbon\Carbon::parse($log->signed_in_at)->format('d M Y h:i A') }}</div>
                            <div class="mt-1">
                                <i class="fa fa-sign-out"></i> <strong>Out:</strong>
                                @if($log->signed_out_at)
                                    {{ \Carbon\Carbon::parse($log->signed_out_at)->format('d M Y h:i A') }}
                                @else
                                    <span class="text-warning">Still working</span>
                                @endif
                            </div>
                            <div class="mt-1"><strong>Hours:</strong> {{ $log->working_hours }}</div>
                            <div class="mt-1 text-muted">
                                @if(!$log->location_verified_in)
                                    <i class="fa fa-map-marker text-danger"></i> Outside HQ
                                @else
                                    <i class="fa fa-map-marker text-success"></i> Inside HQ
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
<script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('liveClock').textContent = timeStr;
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

    const hqLat = {{ $hqLatitude }};
    const hqLng = {{ $hqLongitude }};
    const hqRadius = {{ $geofenceRadius }};
    const overviewPins = @json($mapPins);
    let attendanceLocationMap = null;
    let attendanceMarkersLayer = null;
    let attendanceOverviewMap = null;

    function attendancePinIcon(color) {
        return L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;background:' + color + ';border:2px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
        });
    }

    function overviewPinColor(pin) {
        if (!pin.inside_hq) return '#fd7e14';
        return pin.is_late ? '#dc3545' : '#28a745';
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
        }).addTo(attendanceOverviewMap).bindPopup('EmCa HQ · ' + hqRadius + 'm zone');

        L.marker([hqLat, hqLng], {
            icon: attendancePinIcon('#940000'),
        }).addTo(attendanceOverviewMap).bindPopup('<strong>HQ Center</strong>');

        const bounds = [[hqLat, hqLng]];

        overviewPins.forEach(function (pin) {
            const color = overviewPinColor(pin);
            const popup = '<strong>' + pin.name + '</strong><br>Sign in: ' + pin.signed_in_date + ' ' + pin.signed_in
                + (pin.still_working ? '<br><em>Still working</em>' : '');
            L.marker([pin.lat, pin.lng], { icon: attendancePinIcon(color) })
                .addTo(attendanceOverviewMap)
                .bindPopup(popup);
            bounds.push([pin.lat, pin.lng]);

            if (pin.lat_out && pin.lng_out && (pin.lat_out !== pin.lat || pin.lng_out !== pin.lng)) {
                L.marker([pin.lat_out, pin.lng_out], { icon: attendancePinIcon('#333') })
                    .addTo(attendanceOverviewMap)
                    .bindPopup('<strong>' + pin.name + '</strong><br>Sign out location');
                bounds.push([pin.lat_out, pin.lng_out]);
            }
        });

        if (bounds.length > 1) {
            attendanceOverviewMap.fitBounds(L.latLngBounds(bounds), { padding: [32, 32], maxZoom: 18 });
        }

        setTimeout(function () { attendanceOverviewMap.invalidateSize(); }, 200);
    }

    $(document).ready(function () {
        initAttendanceOverviewMap();
        $(window).on('resize', function () {
            if (attendanceOverviewMap) attendanceOverviewMap.invalidateSize();
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
        }).addTo(attendanceLocationMap).bindPopup('EmCa HQ · ' + hqRadius + 'm zone');
        L.marker([hqLat, hqLng]).addTo(attendanceLocationMap).bindPopup('HQ center');
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
            ? ' <span class="badge badge-success">Inside HQ</span>'
            : ' <span class="badge badge-danger">Outside HQ</span>';
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
