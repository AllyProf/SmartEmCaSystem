<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function __construct(
        protected AttendanceSettingService $settings
    ) {}

    public function index()
    {
        $this->authorizeSettings();

        $notifyUsers = User::query()
            ->whereIn('role', ['super_admin', 'ceo', 'hr'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role']);

        return view('settings.index', [
            'expectedInTime' => substr($this->settings->expectedArrivalTime(), 0, 5),
            'expectedOutTime' => substr($this->settings->expectedDepartureTime(), 0, 5),
            'hqLatitude' => $this->settings->hqLatitude(),
            'hqLongitude' => $this->settings->hqLongitude(),
            'geofenceRadius' => $this->settings->geofenceRadius(),
            'hqName' => $this->settings->hqName(),
            'signReminderTime' => substr($this->settings->signReminderTime(), 0, 5),
            'sessionTimeout' => $this->settings->sessionTimeoutMinutes(),
            'weekendDays' => implode(',', $this->settings->weekendDays()),
            'publicHolidays' => implode(', ', $this->settings->publicHolidays()),
            'signReminderSmsEnabled' => $this->settings->signReminderSmsEnabled(),
            'signReminderSmsTemplate' => $this->settings->signReminderSmsTemplate(),
            'lateComerSmsEnabled' => $this->settings->lateComerSmsEnabled(),
            'lateComerSmsTemplate' => $this->settings->lateComerSmsTemplate(),
            'lateComerNotifyRoles' => $this->settings->lateComerNotifyRoles(),
            'lateComerNotifyUserIds' => $this->settings->lateComerNotifyUserIds(),
            'lateComerExtraPhones' => implode(', ', $this->settings->lateComerExtraPhones()),
            'notifyUsers' => $notifyUsers,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeSettings();

        $request->validate([
            'expected_arrival_time' => 'required|date_format:H:i',
            'expected_departure_time' => 'required|date_format:H:i',
            'hq_latitude' => 'required|numeric',
            'hq_longitude' => 'required|numeric',
            'hq_name' => 'required|string|max:80',
            'geofence_radius' => 'required|numeric|min:10|max:500',
            'sign_reminder_time' => 'required|date_format:H:i',
            'sign_session_timeout_minutes' => 'required|integer|min:5|max:240',
            'weekend_days' => 'nullable|string',
            'public_holidays' => 'nullable|string',
            'sign_reminder_sms_template' => 'nullable|string|max:500',
            'late_comer_sms_template' => 'nullable|string|max:500',
            'late_comer_extra_phones' => 'nullable|string|max:500',
            'late_comer_notify_roles' => 'nullable|array',
            'late_comer_notify_roles.*' => 'in:super_admin,ceo,hr',
            'late_comer_notify_user_ids' => 'nullable|array',
            'late_comer_notify_user_ids.*' => 'integer|exists:users,id',
        ]);

        $this->settings->set('expected_arrival_time', $request->expected_arrival_time . ':00');
        $this->settings->set('expected_departure_time', $request->expected_departure_time . ':00');
        $this->settings->set('hq_latitude', $request->hq_latitude);
        $this->settings->set('hq_longitude', $request->hq_longitude);
        $this->settings->set('hq_name', trim($request->hq_name));
        $this->settings->set('geofence_radius', $request->geofence_radius);
        $this->settings->set('sign_reminder_time', $request->sign_reminder_time . ':00');
        $this->settings->set('sign_session_timeout_minutes', $request->sign_session_timeout_minutes);
        $this->settings->set('weekend_days', $request->weekend_days ?? '0,6');

        $holidays = array_filter(array_map('trim', explode(',', $request->public_holidays ?? '')));
        $this->settings->set('public_holidays', json_encode(array_values($holidays)));

        $this->settings->set('sign_reminder_sms_enabled', $request->boolean('sign_reminder_sms_enabled') ? '1' : '0');
        $this->settings->set('late_comer_sms_enabled', $request->boolean('late_comer_sms_enabled') ? '1' : '0');

        if ($request->filled('sign_reminder_sms_template')) {
            $this->settings->set('sign_reminder_sms_template', $request->sign_reminder_sms_template);
        }
        if ($request->filled('late_comer_sms_template')) {
            $this->settings->set('late_comer_sms_template', $request->late_comer_sms_template);
        }

        $this->settings->set(
            'late_comer_notify_roles',
            json_encode(array_values($request->input('late_comer_notify_roles', [])))
        );
        $this->settings->set(
            'late_comer_notify_user_ids',
            json_encode(array_values($request->input('late_comer_notify_user_ids', [])))
        );
        $this->settings->set('late_comer_extra_phones', $request->late_comer_extra_phones ?? '');

        return back()->with('success', 'System settings saved successfully.');
    }

    private function authorizeSettings(): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isCeo() && !$user->isHr())) {
            abort(403, 'Unauthorized');
        }
    }
}
