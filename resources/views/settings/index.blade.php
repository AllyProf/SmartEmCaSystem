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
                <p class="settings-hint">Sign-in after arrival time is marked <strong>Late</strong>. Sign-out before departure may be flagged early.</p>
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
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Staff sign session timeout (minutes)</label>
                    <input type="number" name="sign_session_timeout_minutes" class="form-control" min="5" max="240" value="{{ old('sign_session_timeout_minutes', $sessionTimeout) }}" required>
                </div>
            </div>
        </div>

        <div class="col-lg-6 settings-section">
            <div class="tile">
                <h3 class="tile-title"><i class="fa fa-map-marker"></i> HQ Geofence</h3>
                <p class="settings-hint">Staff must be inside this zone to sign in/out on web and mobile.</p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Latitude</label>
                        <input type="number" step="any" name="hq_latitude" class="form-control" value="{{ old('hq_latitude', $hqLatitude) }}" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Longitude</label>
                        <input type="number" step="any" name="hq_longitude" class="form-control" value="{{ old('hq_longitude', $hqLongitude) }}" required>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Radius (metres)</label>
                    <input type="number" name="geofence_radius" class="form-control" min="10" max="500" value="{{ old('geofence_radius', $geofenceRadius) }}" required>
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
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Public holidays</label>
                    <input type="text" name="public_holidays" class="form-control" value="{{ old('public_holidays', $publicHolidays) }}" placeholder="2026-12-25, 2026-01-01">
                    <small class="text-muted">Comma-separated dates (YYYY-MM-DD)</small>
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
                <h3 class="tile-title"><i class="fa fa-exclamation-triangle"></i> Late Comer SMS Alerts</h3>
                <p class="settings-hint">When a staff member signs in late, SMS is sent to the people selected below.</p>

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
                                        {{ in_array($notifyUser->id, old('late_comer_notify_user_ids', $lateComerNotifyUserIds), true) ? 'checked' : '' }}>
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
    </div>

    <div class="settings-actions text-right">
        <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
    </div>
</form>
@endsection
