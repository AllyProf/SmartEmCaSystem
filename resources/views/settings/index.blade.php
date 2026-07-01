@extends('layouts.app')

@section('title', 'System Settings')
@section('icon', 'fa-cogs')
@section('page-title', 'System Settings')
@section('page-description', 'Configure attendance rules, HQ geofence, and SMS notifications.')

@section('content')
<style>
    .settings-section { margin-bottom: 1.25rem; }
    .settings-section .tile-title {
        color: #940000;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0f0f0;
    }
    .settings-hint { font-size: 0.85rem; color: #6c757d; }
    .settings-user-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    .settings-user-row:last-child { border-bottom: none; }
    @media (max-width: 767.98px) {
        .settings-actions { position: sticky; bottom: 0; background: #fff; padding: 12px 0; border-top: 1px solid #eee; z-index: 5; }
        .hq-settings-map { height: 260px; }
    }
    .hq-settings-map {
        height: 360px;
        width: 100%;
        border-radius: 10px;
        border: 2px solid #eee;
        z-index: 1;
    }
    .hq-marker-icon {
        width: 22px;
        height: 22px;
        background: #940000;
        border: 3px solid #fff;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        box-shadow: 0 2px 8px rgba(0,0,0,.35);
    }
    .hq-radius-handle {
        width: 16px;
        height: 16px;
        background: #fff;
        border: 3px solid #940000;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0,0,0,.3);
        cursor: ew-resize;
    }
    .hq-test-staff-dot {
        width: 18px;
        height: 18px;
        background: #007bff;
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,.35);
    }
    #hqTestModeToggle.active {
        background: #940000;
        color: #fff;
        border-color: #940000;
    }
</style>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('settings.update') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-lg-6 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-clock-o"></i> Working Hours</h3>
                <p class="settings-hint">Sign-in after arrival + grace minutes is marked <strong>Late</strong>. Sign-out before departure may be flagged early. Open sessions are <strong>auto closed at expected departure</strong> if staff forget to sign out.</p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Expected arrival</label>
                        <input type="time" name="expected_arrival_time" class="form-control" value="{{ old('expected_arrival_time', $expectedInTime) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Expected departure</label>
                        <input type="time" name="expected_departure_time" class="form-control" value="{{ old('expected_departure_time', $expectedOutTime) }}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Late grace period (minutes)</label>
                        <input type="number" name="late_grace_minutes" class="form-control" min="0" max="120" value="{{ old('late_grace_minutes', $lateGraceMinutes) }}" required>
                        <small class="text-muted">e.g. 10 = not late until 10 min after arrival time</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Staff sign session timeout (minutes)</label>
                        <input type="number" name="sign_session_timeout_minutes" class="form-control" min="5" max="240" value="{{ old('sign_session_timeout_minutes', $sessionTimeout) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-map-marker"></i> HQ Geofence</h3>
                <p class="settings-hint">Drag the HQ pin and radius handle on the map, or use <strong>Use my location</strong> while standing at the office. Enable <strong>Test mode</strong> to preview sign-in with a sample staff dot.</p>

                <div id="hqSettingsMap" class="hq-settings-map mb-3"></div>

                <div class="d-flex flex-wrap mb-3" style="gap:8px;">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="hqUseMyLocation">
                        <i class="fa fa-crosshairs"></i> Use my location
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="hqTestModeToggle">
                        <i class="fa fa-user"></i> Test mode
                    </button>
                </div>
                <div id="hqTestStatus" class="small mb-3"></div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label class="font-weight-bold">Location name (shown on map)</label>
                        <input type="text" name="hq_name" id="hq_name" class="form-control" maxlength="80"
                               value="{{ old('hq_name', $hqName) }}" placeholder="EmCa HQ" required>
                        <small class="text-muted">Used on the staff sign map marker, distance labels, and alerts.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Latitude</label>
                        <input type="number" step="any" name="hq_latitude" id="hq_latitude" class="form-control" value="{{ old('hq_latitude', $hqLatitude) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Longitude</label>
                        <input type="number" step="any" name="hq_longitude" id="hq_longitude" class="form-control" value="{{ old('hq_longitude', $hqLongitude) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">Radius (metres)</label>
                        <input type="number" name="geofence_radius" id="geofence_radius" class="form-control" min="10" max="500" value="{{ old('geofence_radius', $geofenceRadius) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-calendar"></i> Work Calendar</h3>
                <div class="form-group">
                    <label class="font-weight-bold">Weekend days</label>
                    <input type="text" name="weekend_days" class="form-control" value="{{ old('weekend_days', $weekendDays) }}" placeholder="0,6">
                    <small class="text-muted">0=Sunday, 1=Monday … 6=Saturday</small>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Public holidays</label>
                    <input type="text" name="public_holidays" class="form-control" value="{{ old('public_holidays', $publicHolidays) }}" placeholder="2026-12-25, 2026-01-01">
                    <small class="text-muted">Comma-separated dates (YYYY-MM-DD)</small>
                </div>
                <div class="custom-control custom-checkbox mb-0">
                    <input type="checkbox" class="custom-control-input" id="block_sign_in_non_working_days" name="block_sign_in_non_working_days" value="1" {{ old('block_sign_in_non_working_days', $blockSignInNonWorkingDays) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="block_sign_in_non_working_days">Block staff sign-in on weekends and public holidays</label>
                </div>
            </div>
        </div>

        <div class="col-12 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-bar-chart"></i> Weekly Attendance SMS</h3>
                <p class="settings-hint">At <strong>end of each week</strong> (day &amp; time below), active staff get their summary. CEO/HR get one SMS with <strong>totals for all active staff</strong>. Deactivated staff are never included.</p>

                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="weekly_attendance_sms_enabled" name="weekly_attendance_sms_enabled" value="1" {{ old('weekly_attendance_sms_enabled', $weeklyAttendanceSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="weekly_attendance_sms_enabled">Enable weekly attendance summaries</label>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="weekly_attendance_staff_sms_enabled" name="weekly_attendance_staff_sms_enabled" value="1" {{ old('weekly_attendance_staff_sms_enabled', $weeklyAttendanceStaffSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="weekly_attendance_staff_sms_enabled">Send each active staff member their own summary</label>
                </div>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="weekly_attendance_ceo_sms_enabled" name="weekly_attendance_ceo_sms_enabled" value="1" {{ old('weekly_attendance_ceo_sms_enabled', $weeklyAttendanceCeoSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="weekly_attendance_ceo_sms_enabled">Send CEO/HR one combined summary for all staff</label>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Send on (end of week)</label>
                        <select name="weekly_summary_day" class="form-control" required>
                            @foreach([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $dayNum => $dayLabel)
                                <option value="{{ $dayNum }}" {{ (int) old('weekly_summary_day', $weeklySummaryDay) === $dayNum ? 'selected' : '' }}>{{ $dayLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Send time</label>
                        <input type="time" name="weekly_summary_time" class="form-control" value="{{ old('weekly_summary_time', $weeklySummaryTime) }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">SMS to each staff member</label>
                    <textarea name="weekly_attendance_staff_sms_template" class="form-control" rows="2">{{ old('weekly_attendance_staff_sms_template', $weeklyAttendanceStaffSmsTemplate) }}</textarea>
                    <small class="text-muted">Placeholders: <code>{name}</code>, <code>{week}</code>, <code>{days_present}</code>, <code>{late_count}</code>, <code>{forgot_sign_out_count}</code>, <code>{hq_name}</code></small>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">SMS to CEO / HR (all staff totals)</label>
                    <textarea name="weekly_attendance_ceo_sms_template" class="form-control" rows="2">{{ old('weekly_attendance_ceo_sms_template', $weeklyAttendanceCeoSmsTemplate) }}</textarea>
                    <small class="text-muted">Placeholders: <code>{week}</code>, <code>{staff_count}</code>, <code>{total_present}</code>, <code>{total_late}</code>, <code>{total_forgot}</code>, <code>{hq_name}</code>. Recipients = <strong>Late Comer SMS Alerts</strong> list below.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-6 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-bell"></i> Sign-In Reminder SMS</h3>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="sign_reminder_sms_enabled" name="sign_reminder_sms_enabled" value="1" {{ old('sign_reminder_sms_enabled', $signReminderSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="sign_reminder_sms_enabled">Send reminder SMS to staff who have not signed in</label>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Reminder send time</label>
                    <input type="time" name="sign_reminder_time" class="form-control" value="{{ old('sign_reminder_time', $signReminderTime) }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Message template</label>
                    <textarea name="sign_reminder_sms_template" class="form-control" rows="3">{{ old('sign_reminder_sms_template', $signReminderSmsTemplate) }}</textarea>
                    <small class="text-muted">Placeholders: <code>{expected_time}</code></small>
                </div>
            </div>
        </div>

        <div class="col-12 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-clock-o"></i> Forgot Sign-Out &amp; Auto Close</h3>
                <p class="settings-hint">If staff forget to sign out, the system <strong>auto-closes</strong> their session at expected departure. Staff receive an SMS that today is closed; <strong>next day they sign in fresh</strong> for the new day. CEO/HR also get an alert.</p>

                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="auto_sign_out_enabled" name="auto_sign_out_enabled" value="1" {{ old('auto_sign_out_enabled', $autoSignOutEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="auto_sign_out_enabled">Auto sign-out at expected departure time</label>
                </div>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="forgot_sign_out_sms_enabled" name="forgot_sign_out_sms_enabled" value="1" {{ old('forgot_sign_out_sms_enabled', $forgotSignOutSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="forgot_sign_out_sms_enabled">Send SMS to staff and CEO/HR when auto sign-out happens</label>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">SMS to staff (forgot to sign out)</label>
                    <textarea name="forgot_sign_out_staff_sms_template" class="form-control" rows="2">{{ old('forgot_sign_out_staff_sms_template', $forgotSignOutStaffSmsTemplate) }}</textarea>
                    <small class="text-muted">Keep it short. Placeholders: <code>{name}</code>, <code>{staff_id}</code>, <code>{time}</code>, <code>{date}</code>, <code>{expected_departure}</code>, <code>{hq_name}</code></small>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">SMS to CEO / HR (alert)</label>
                    <textarea name="forgot_sign_out_manager_sms_template" class="form-control" rows="2">{{ old('forgot_sign_out_manager_sms_template', $forgotSignOutManagerSmsTemplate) }}</textarea>
                    <small class="text-muted">Keep it short. Uses CEO/HR from <strong>Late Comer SMS Alerts</strong> below.</small>
                </div>
            </div>
        </div>

        <div class="col-12 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-exclamation-triangle"></i> Late Comer SMS Alerts</h3>
                <p class="settings-hint">When a staff member signs in late, SMS is sent to the people selected below. Each person must have a valid phone number on their profile (or use extra numbers).</p>

                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="late_comer_sms_enabled" name="late_comer_sms_enabled" value="1" {{ old('late_comer_sms_enabled', $lateComerSmsEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="late_comer_sms_enabled">Enable late comer SMS notifications</label>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label class="font-weight-bold d-block mb-2">Notify by role</label>
                        @foreach(['ceo' => 'CEO', 'hr' => 'HR Manager', 'super_admin' => 'Super Admin'] as $roleKey => $roleLabel)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="role_{{ $roleKey }}" name="late_comer_notify_roles[]" value="{{ $roleKey }}"
                                    {{ in_array($roleKey, old('late_comer_notify_roles', $lateComerNotifyRoles), true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="role_{{ $roleKey }}">{{ $roleLabel }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold d-block mb-2">Also notify specific people</label>
                        <div style="max-height:160px;overflow-y:auto;border:1px solid #eee;border-radius:6px;padding:8px;">
                            @forelse($notifyUsers as $notifyUser)
                                <div class="settings-user-row">
                                    <input type="checkbox" name="late_comer_notify_user_ids[]" value="{{ $notifyUser->id }}" id="notify_user_{{ $notifyUser->id }}"
                                        {{ in_array($notifyUser->id, array_map('intval', old('late_comer_notify_user_ids', $lateComerNotifyUserIds)), true) ? 'checked' : '' }}>
                                    <label for="notify_user_{{ $notifyUser->id }}" class="mb-0 small">
                                        <strong>{{ $notifyUser->name }}</strong>
                                        <span class="text-muted">({{ ucfirst(str_replace('_', ' ', $notifyUser->role)) }})</span>
                                        @if($notifyUser->phone)
                                            <br><span class="text-muted">{{ $notifyUser->phone }}</span>
                                        @else
                                            <br><span class="text-danger">No phone on file</span>
                                        @endif
                                    </label>
                                </div>
                            @empty
                                <span class="text-muted small">No admin users found.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label class="font-weight-bold">Extra phone numbers</label>
                    <input type="text" name="late_comer_extra_phones" class="form-control" value="{{ old('late_comer_extra_phones', $lateComerExtraPhones) }}" placeholder="2557XXXXXXXX, 2556XXXXXXXX">
                    <small class="text-muted">Comma-separated. Use for directors or numbers not in the system.</small>
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">Late comer message template</label>
                    <textarea name="late_comer_sms_template" class="form-control" rows="3">{{ old('late_comer_sms_template', $lateComerSmsTemplate) }}</textarea>
                    <small class="text-muted">Placeholders: <code>{name}</code>, <code>{staff_id}</code>, <code>{time}</code>, <code>{date}</code>, <code>{expected_time}</code></small>
                </div>
            </div>
        </div>

        <div class="col-lg-6 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-envelope"></i> Scheduled SMS Confirmations</h3>
                <p class="settings-hint">When a scheduled SMS batch finishes sending, the staff member who created it receives a summary SMS on their profile phone number.</p>
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="scheduled_sms_confirmation_enabled" name="scheduled_sms_confirmation_enabled" value="1" {{ old('scheduled_sms_confirmation_enabled', $scheduledSmsConfirmationEnabled) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold" for="scheduled_sms_confirmation_enabled">Notify staff when scheduled SMS is sent</label>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Confirmation message template</label>
                    <textarea name="scheduled_sms_confirmation_template" class="form-control" rows="3">{{ old('scheduled_sms_confirmation_template', $scheduledSmsConfirmationTemplate) }}</textarea>
                    <small class="text-muted">Placeholders: <code>{name}</code>, <code>{total}</code>, <code>{sent}</code>, <code>{failed}</code>, <code>{scheduled_time}</code>, <code>{time}</code></small>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-actions text-right">
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
    </div>
</form>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const latInput = document.getElementById('hq_latitude');
    const lngInput = document.getElementById('hq_longitude');
    const radiusInput = document.getElementById('geofence_radius');
    const hqNameInput = document.getElementById('hq_name');
    const mapEl = document.getElementById('hqSettingsMap');
    if (!mapEl || !latInput || !lngInput || !radiusInput) return;

    let map, hqMarker, hqLabelMarker, geofenceCircle, radiusHandle, testMarker;
    let testMode = false;

    function getLat() { return parseFloat(latInput.value); }
    function getLng() { return parseFloat(lngInput.value); }
    function getRadius() {
        const r = parseInt(radiusInput.value, 10);
        return isNaN(r) ? 70 : Math.min(500, Math.max(10, r));
    }

    function getHqName() {
        const name = hqNameInput ? hqNameInput.value.trim() : '';
        return name || 'EmCa HQ';
    }

    function escapeMapHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function hqLabelMarkerHtml() {
        return '<div style="background:#940000;color:#fff;padding:6px 10px;border-radius:8px;font-weight:bold;font-size:11px;box-shadow:0 2px 8px rgba(0,0,0,.3);white-space:nowrap;">'
            + escapeMapHtml(getHqName()) + '</div>';
    }

    function refreshHqLabelMarker() {
        if (!hqLabelMarker || !hqMarker) return;
        const pos = hqMarker.getLatLng();
        hqLabelMarker.setLatLng(pos);
        hqLabelMarker.setIcon(L.divIcon({
            className: '',
            html: hqLabelMarkerHtml(),
            iconSize: [80, 24],
            iconAnchor: [40, 28],
        }));
    }

    function haversineM(lat1, lng1, lat2, lng2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function radiusHandleLatLng(lat, lng, radiusM) {
        const earth = 6371000;
        const brng = Math.PI / 2;
        const lat1 = lat * Math.PI / 180;
        const lng1 = lng * Math.PI / 180;
        const lat2 = Math.asin(
            Math.sin(lat1) * Math.cos(radiusM / earth)
            + Math.cos(lat1) * Math.sin(radiusM / earth) * Math.cos(brng)
        );
        const lng2 = lng1 + Math.atan2(
            Math.sin(brng) * Math.sin(radiusM / earth) * Math.cos(lat1),
            Math.cos(radiusM / earth) - Math.sin(lat1) * Math.sin(lat2)
        );
        return L.latLng(lat2 * 180 / Math.PI, lng2 * 180 / Math.PI);
    }

    function syncInputs(lat, lng, radius) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        if (radius !== undefined) radiusInput.value = Math.round(radius);
    }

    function hqIcon() {
        return L.divIcon({
            className: '',
            html: '<div class="hq-marker-icon"></div>',
            iconSize: [22, 22],
            iconAnchor: [11, 22],
        });
    }

    function radiusIcon() {
        return L.divIcon({
            className: '',
            html: '<div class="hq-radius-handle" title="Drag to resize zone"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });
    }

    function testStaffIcon() {
        return L.divIcon({
            className: '',
            html: '<div class="hq-test-staff-dot" title="Sample staff"></div>',
            iconSize: [18, 18],
            iconAnchor: [9, 9],
        });
    }

    function updateGeofenceVisuals() {
        const lat = getLat();
        const lng = getLng();
        const r = getRadius();
        if (isNaN(lat) || isNaN(lng)) return;

        hqMarker.setLatLng([lat, lng]);
        geofenceCircle.setLatLng([lat, lng]).setRadius(r);
        radiusHandle.setLatLng(radiusHandleLatLng(lat, lng, r));
        refreshHqLabelMarker();
        updateTestStatus();
    }

    function updateTestStatus() {
        const el = document.getElementById('hqTestStatus');
        if (!el) return;
        if (!testMode || !testMarker) {
            el.innerHTML = '';
            return;
        }
        const center = hqMarker.getLatLng();
        const pos = testMarker.getLatLng();
        const dist = haversineM(center.lat, center.lng, pos.lat, pos.lng);
        const inside = dist <= getRadius();
        const name = escapeMapHtml(getHqName());
        el.innerHTML = inside
            ? '<span class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> Sample staff inside ' + name + ' — sign-in would be allowed</span>'
            : '<span class="text-danger font-weight-bold"><i class="fa fa-times-circle"></i> Sample staff outside ' + name + ' — '
                + Math.round(dist) + 'm from center (' + Math.max(0, Math.round(dist - getRadius())) + 'm outside zone)</span>';
    }

    function toggleTestMode() {
        const btn = document.getElementById('hqTestModeToggle');
        testMode = !testMode;
        if (testMode) {
            const center = hqMarker.getLatLng();
            const start = radiusHandleLatLng(center.lat, center.lng, getRadius() * 0.55);
            testMarker = L.marker(start, { draggable: true, icon: testStaffIcon() }).addTo(map);
            testMarker.on('drag', updateTestStatus);
            if (btn) btn.classList.add('active');
        } else {
            if (testMarker) map.removeLayer(testMarker);
            testMarker = null;
            if (btn) btn.classList.remove('active');
        }
        updateTestStatus();
    }

    map = L.map('hqSettingsMap', { zoomControl: true }).setView([getLat(), getLng()], 17);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    geofenceCircle = L.circle([getLat(), getLng()], {
        color: '#940000',
        fillColor: '#940000',
        fillOpacity: 0.14,
        weight: 2,
        radius: getRadius(),
    }).addTo(map);

    hqMarker = L.marker([getLat(), getLng()], { draggable: true, icon: hqIcon() }).addTo(map);
    hqLabelMarker = L.marker([getLat(), getLng()], {
        icon: L.divIcon({ className: '', html: hqLabelMarkerHtml(), iconSize: [80, 24], iconAnchor: [40, 28] }),
        interactive: false,
    }).addTo(map);
    hqMarker.on('drag', function (e) {
        const pos = e.target.getLatLng();
        syncInputs(pos.lat, pos.lng);
        geofenceCircle.setLatLng(pos);
        radiusHandle.setLatLng(radiusHandleLatLng(pos.lat, pos.lng, getRadius()));
        refreshHqLabelMarker();
        updateTestStatus();
    });

    radiusHandle = L.marker(
        radiusHandleLatLng(getLat(), getLng(), getRadius()),
        { draggable: true, icon: radiusIcon() }
    ).addTo(map);
    radiusHandle.on('drag', function (e) {
        const center = hqMarker.getLatLng();
        const pos = e.target.getLatLng();
        const dist = haversineM(center.lat, center.lng, pos.lat, pos.lng);
        const clamped = Math.min(500, Math.max(10, Math.round(dist)));
        radiusInput.value = clamped;
        geofenceCircle.setRadius(clamped);
        updateTestStatus();
    });
    radiusHandle.on('dragend', function () {
        const center = hqMarker.getLatLng();
        radiusHandle.setLatLng(radiusHandleLatLng(center.lat, center.lng, getRadius()));
    });

    [latInput, lngInput].forEach(function (input) {
        input.addEventListener('change', updateGeofenceVisuals);
    });
    radiusInput.addEventListener('change', updateGeofenceVisuals);
    if (hqNameInput) {
        hqNameInput.addEventListener('input', refreshHqLabelMarker);
    }

    document.getElementById('hqUseMyLocation')?.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported in this browser.');
            return;
        }
        this.disabled = true;
        navigator.geolocation.getCurrentPosition(function (pos) {
            syncInputs(pos.coords.latitude, pos.coords.longitude);
            updateGeofenceVisuals();
            map.setView([pos.coords.latitude, pos.coords.longitude], 18);
            document.getElementById('hqUseMyLocation').disabled = false;
        }, function () {
            alert('Could not get your location. Allow GPS permission and try again.');
            document.getElementById('hqUseMyLocation').disabled = false;
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });

    document.getElementById('hqTestModeToggle')?.addEventListener('click', toggleTestMode);

    setTimeout(function () { map.invalidateSize(); }, 250);
    window.addEventListener('resize', function () { map.invalidateSize(); });
})();
</script>
@endpush
