<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

class AttendanceSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    public function hqLatitude(): float
    {
        return (float) ($this->get('hq_latitude') ?? config('attendance.hq_latitude'));
    }

    public function hqLongitude(): float
    {
        return (float) ($this->get('hq_longitude') ?? config('attendance.hq_longitude'));
    }

    public function geofenceRadius(): float
    {
        return (float) ($this->get('geofence_radius') ?? config('attendance.geofence_radius'));
    }

    public function hqName(): string
    {
        $name = trim((string) ($this->get('hq_name') ?? config('attendance.hq_name', 'EmCa HQ')));

        return $name !== '' ? $name : 'EmCa HQ';
    }

    public function expectedArrivalTime(): string
    {
        return $this->get('expected_arrival_time', '08:00:00');
    }

    public function expectedDepartureTime(): string
    {
        return $this->get('expected_departure_time', '17:00:00');
    }

    public function allowSignInTime(): string
    {
        $time = (string) $this->get('allow_sign_in_time', '');

        if ($time !== '' && preg_match('/^\d{2}:\d{2}/', $time)) {
            return strlen($time) === 5 ? $time . ':00' : $time;
        }

        return $this->expectedArrivalTime();
    }

    /**
     * @return array{
     *     state: string,
     *     sign_in_allowed: bool,
     *     sign_out_allowed: bool,
     *     page_active: bool,
     *     message: string,
     *     opens_at: string|null,
     *     opens_at_label: string|null,
     *     closes_at: string|null,
     *     closes_at_label: string|null,
     *     allow_sign_in_time: string,
     *     expected_departure_time: string
     * }
     */
    public function signWindowState(?Carbon $now = null, bool $hasOpenSession = false, bool $completedSessionToday = false): array
    {
        $now ??= now();
        $allowTime = $this->allowSignInTime();
        $departureTime = $this->expectedDepartureTime();
        $opensAt = Carbon::parse($now->toDateString() . ' ' . $allowTime);
        $closesAt = Carbon::parse($now->toDateString() . ' ' . $departureTime);
        $nextOpensAt = $this->nextSignInOpenTime($now);
        $base = [
            'allow_sign_in_time' => substr($allowTime, 0, 5),
            'expected_departure_time' => substr($departureTime, 0, 5),
            'opens_at' => null,
            'opens_at_label' => null,
            'closes_at' => $closesAt->toIso8601String(),
            'closes_at_label' => $closesAt->format('h:i A'),
        ];

        if ($completedSessionToday && !$hasOpenSession) {
            return array_merge($base, [
                'state' => 'day_complete',
                'sign_in_allowed' => false,
                'sign_out_allowed' => false,
                'page_active' => false,
                'message' => 'Today\'s attendance is complete. Sign-in opens next at ' . $nextOpensAt->format('D, d M Y h:i A') . '.',
                'opens_at' => $nextOpensAt->toIso8601String(),
                'opens_at_label' => $nextOpensAt->format('D, d M Y h:i A'),
            ]);
        }

        if ($this->isNonWorkingDay($now)) {
            $dayLabel = $this->isPublicHoliday($now) ? 'public holiday' : 'weekend / non-working day';

            return array_merge($base, [
                'state' => 'non_working_day',
                'sign_in_allowed' => false,
                'sign_out_allowed' => $hasOpenSession,
                'page_active' => $hasOpenSession,
                'message' => "Today is a {$dayLabel}. Sign-in opens on the next working day at " . $nextOpensAt->format('h:i A') . '.',
                'opens_at' => $nextOpensAt->toIso8601String(),
                'opens_at_label' => $nextOpensAt->format('D, d M Y h:i A'),
            ]);
        }

        if ($now->lessThan($opensAt)) {
            return array_merge($base, [
                'state' => 'before_open',
                'sign_in_allowed' => false,
                'sign_out_allowed' => $hasOpenSession,
                'page_active' => $hasOpenSession,
                'message' => 'Sign-in opens today at ' . $opensAt->format('h:i A') . '. Please wait.',
                'opens_at' => $opensAt->toIso8601String(),
                'opens_at_label' => $opensAt->format('D, d M Y h:i A'),
            ]);
        }

        if ($now->greaterThanOrEqualTo($closesAt)) {
            return array_merge($base, [
                'state' => 'closed',
                'sign_in_allowed' => false,
                'sign_out_allowed' => false,
                'page_active' => false,
                'message' => 'Today\'s sign-in/sign-out is closed. Opens next at ' . $nextOpensAt->format('D, d M Y h:i A') . '.',
                'opens_at' => $nextOpensAt->toIso8601String(),
                'opens_at_label' => $nextOpensAt->format('D, d M Y h:i A'),
            ]);
        }

        return array_merge($base, [
            'state' => 'open',
            'sign_in_allowed' => true,
            'sign_out_allowed' => true,
            'page_active' => true,
            'message' => 'Sign-in is open until ' . $closesAt->format('h:i A') . ' today.',
            'opens_at' => $opensAt->toIso8601String(),
            'opens_at_label' => $opensAt->format('h:i A'),
        ]);
    }

    public function nextSignInOpenTime(?Carbon $from = null): Carbon
    {
        $from ??= now();
        $allowTime = $this->allowSignInTime();
        $todayOpens = Carbon::parse($from->toDateString() . ' ' . $allowTime);

        if ($from->lessThan($todayOpens) && !$this->isNonWorkingDay($from)) {
            return $todayOpens;
        }

        $day = $from->copy()->addDay()->startOfDay();
        for ($i = 0; $i < 21; $i++) {
            if (!$this->isNonWorkingDay($day)) {
                return Carbon::parse($day->toDateString() . ' ' . $allowTime);
            }
            $day->addDay();
        }

        return $todayOpens->copy()->addDay();
    }

    public function lateGraceMinutes(): int
    {
        return max(0, (int) $this->get('late_grace_minutes', 10));
    }

    public function blockSignInOnNonWorkingDays(): bool
    {
        return filter_var($this->get('block_sign_in_non_working_days', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function monthlyAttendanceSmsEnabled(): bool
    {
        return $this->weeklyAttendanceSmsEnabled();
    }

    public function weeklyAttendanceSmsEnabled(): bool
    {
        return filter_var($this->get('weekly_attendance_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function weeklyAttendanceStaffSmsEnabled(): bool
    {
        return filter_var($this->get('weekly_attendance_staff_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function weeklyAttendanceCeoSmsEnabled(): bool
    {
        return filter_var($this->get('weekly_attendance_ceo_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function weeklySummaryDay(): int
    {
        $day = (int) $this->get('weekly_summary_day', 5);

        return ($day >= 0 && $day <= 6) ? $day : 5;
    }

    public function weeklySummaryTime(): string
    {
        $time = (string) $this->get('weekly_summary_time', '18:00:00');

        return preg_match('/^\d{2}:\d{2}/', $time) ? $time : '18:00:00';
    }

    public function weeklyAttendanceStaffSmsTemplate(): string
    {
        return (string) $this->get(
            'weekly_attendance_staff_sms_template',
            $this->get(
                'monthly_attendance_sms_template',
                'Hi {name}, week {week}: {days_present} days, {late_count} late, {forgot_sign_out_count} forgot sign-out. — {hq_name}'
            )
        );
    }

    public function weeklyAttendanceCeoSmsTemplate(): string
    {
        return (string) $this->get(
            'weekly_attendance_ceo_sms_template',
            'Week {week} — {staff_count} staff: {total_present} attendances, {total_late} late, {total_forgot} forgot sign-out. — {hq_name}'
        );
    }

    public function monthlyAttendanceSmsTemplate(): string
    {
        return $this->weeklyAttendanceStaffSmsTemplate();
    }

    public function scheduledSmsConfirmationEnabled(): bool
    {
        return filter_var($this->get('scheduled_sms_confirmation_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function scheduledSmsConfirmationTemplate(): string
    {
        return (string) $this->get(
            'scheduled_sms_confirmation_template',
            'Hi {name}, your scheduled SMS ({total} recipients) for {scheduled_time} is complete. Sent: {sent}, Failed: {failed}.'
        );
    }

    public function signReminderTime(): string
    {
        return $this->get('sign_reminder_time', '08:30:00');
    }

    public function signReminderSmsEnabled(): bool
    {
        return filter_var($this->get('sign_reminder_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function signReminderSmsTemplate(): string
    {
        return (string) $this->get(
            'sign_reminder_sms_template',
            'Reminder: Please sign in at HQ using Staff Sign. Expected arrival before {expected_time}.'
        );
    }

    public function lateComerSmsEnabled(): bool
    {
        return filter_var($this->get('late_comer_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function autoSignOutEnabled(): bool
    {
        return filter_var($this->get('auto_sign_out_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function forgotSignOutSmsEnabled(): bool
    {
        return filter_var($this->get('forgot_sign_out_sms_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public function forgotSignOutStaffSmsTemplate(): string
    {
        return (string) $this->get(
            'forgot_sign_out_staff_sms_template',
            'Hi {name}, auto sign-out at {expected_departure} on {date} ({hq_name}). Today is closed. Sign in tomorrow. See your CEO for further action.'
        );
    }

    public function forgotSignOutManagerSmsTemplate(): string
    {
        return (string) $this->get(
            'forgot_sign_out_manager_sms_template',
            'Alert: {name} ({staff_id}) forgot sign-out. Auto signed out {time}, {date} at {hq_name}.'
        );
    }

    public function lateComerSmsTemplate(): string
    {
        return (string) $this->get(
            'late_comer_sms_template',
            'Late sign-in: {name} ({staff_id}) signed in at {time} on {date}. Expected before {expected_time}.'
        );
    }

    public function lateComerNotifyRoles(): array
    {
        $decoded = json_decode((string) $this->get('late_comer_notify_roles', '["ceo","hr"]'), true);

        return is_array($decoded) ? $decoded : ['ceo', 'hr'];
    }

    public function lateComerNotifyUserIds(): array
    {
        $decoded = json_decode((string) $this->get('late_comer_notify_user_ids', '[]'), true);

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    public function lateComerExtraPhones(): array
    {
        $raw = (string) $this->get('late_comer_extra_phones', '');

        return array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw))));
    }

    /** @return list<string> */
    public function lateComerNotificationPhones(): array
    {
        $phones = [];

        $roles = $this->lateComerNotifyRoles();
        if ($roles !== []) {
            $phones = array_merge(
                $phones,
                User::query()
                    ->whereIn('role', $roles)
                    ->where('is_active', true)
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->pluck('phone')
                    ->all()
            );
        }

        $userIds = $this->lateComerNotifyUserIds();
        if ($userIds !== []) {
            $phones = array_merge(
                $phones,
                User::query()
                    ->whereIn('id', $userIds)
                    ->where('is_active', true)
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->pluck('phone')
                    ->all()
            );
        }

        $phones = array_merge($phones, $this->lateComerExtraPhones());

        $normalized = [];
        foreach ($phones as $phone) {
            $formatted = Customer::normalizePhoneNumber($phone);
            if ($formatted) {
                $normalized[] = $formatted;
            }
        }

        return array_values(array_unique($normalized));
    }

    public function sessionTimeoutMinutes(): int
    {
        return (int) $this->get('sign_session_timeout_minutes', 30);
    }

    public function weekendDays(): array
    {
        $raw = $this->get('weekend_days', '0,6');

        return array_map('intval', array_filter(explode(',', (string) $raw), fn ($d) => $d !== ''));
    }

    public function publicHolidays(): array
    {
        $raw = $this->get('public_holidays', '[]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isWeekend(?Carbon $date = null): bool
    {
        $date ??= now();

        return in_array((int) $date->dayOfWeek, $this->weekendDays(), true);
    }

    public function isPublicHoliday(?Carbon $date = null): bool
    {
        $date ??= now();
        $day = $date->toDateString();

        return in_array($day, $this->publicHolidays(), true);
    }

    public function isNonWorkingDay(?Carbon $date = null): bool
    {
        return $this->isWeekend($date) || $this->isPublicHoliday($date);
    }

    public function mapConfig(): array
    {
        return [
            'hq_latitude' => $this->hqLatitude(),
            'hq_longitude' => $this->hqLongitude(),
            'geofence_radius' => $this->geofenceRadius(),
            'hq_name' => $this->hqName(),
            'expected_arrival' => substr($this->expectedArrivalTime(), 0, 5),
            'expected_departure' => substr($this->expectedDepartureTime(), 0, 5),
            'allow_sign_in_time' => substr($this->allowSignInTime(), 0, 5),
            'late_grace_minutes' => $this->lateGraceMinutes(),
            'block_sign_in_non_working_days' => $this->blockSignInOnNonWorkingDays(),
            'session_timeout_minutes' => $this->sessionTimeoutMinutes(),
            'is_non_working_day' => $this->isNonWorkingDay(),
            'is_weekend' => $this->isWeekend(),
            'is_holiday' => $this->isPublicHoliday(),
        ];
    }
}
