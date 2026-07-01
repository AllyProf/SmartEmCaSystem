<?php

namespace App\Services;

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

        return array_values(array_unique(array_filter($phones)));
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
            'session_timeout_minutes' => $this->sessionTimeoutMinutes(),
            'is_non_working_day' => $this->isNonWorkingDay(),
            'is_weekend' => $this->isWeekend(),
            'is_holiday' => $this->isPublicHoliday(),
        ];
    }
}
