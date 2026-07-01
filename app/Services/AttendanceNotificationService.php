<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceNotificationService
{
    public function __construct(
        protected AttendanceSettingService $settings,
        protected SmsService $sms
    ) {}

    public function notifyLateSignIn(User $staff, StaffAttendance $attendance): void
    {
        if (!$this->settings->lateComerSmsEnabled() || !$attendance->is_late) {
            return;
        }

        $signedInAt = Carbon::parse($attendance->signed_in_at);
        $message = $this->buildMessage($this->settings->lateComerSmsTemplate(), [
            '{name}' => $staff->name,
            '{staff_id}' => $staff->staff_id ?? 'N/A',
            '{time}' => $signedInAt->format('h:i A'),
            '{date}' => $signedInAt->format('d M Y'),
            '{expected_time}' => substr($this->settings->expectedArrivalTime(), 0, 5),
        ]);

        foreach ($this->settings->lateComerNotificationPhones() as $phone) {
            try {
                $this->sms->sendSms($phone, $message);
            } catch (\Throwable $e) {
                Log::warning('Late comer SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            }
        }
    }

    private function buildMessage(string $template, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
