<?php

namespace App\Services;

use App\Models\Customer;
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

        $phones = $this->settings->lateComerNotificationPhones();
        if ($phones === []) {
            Log::warning('Late comer SMS skipped: no valid recipient phone numbers', [
                'staff_user_id' => $staff->id,
                'attendance_id' => $attendance->id,
                'notify_roles' => $this->settings->lateComerNotifyRoles(),
                'notify_user_ids' => $this->settings->lateComerNotifyUserIds(),
            ]);

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

        foreach ($phones as $phone) {
            try {
                $log = $this->sms->sendAndLog($phone, $message, 'late_comer_alert', null, $staff->id);
                if ($log->status !== 'sent') {
                    Log::warning('Late comer SMS failed', [
                        'phone' => $phone,
                        'staff_user_id' => $staff->id,
                        'response' => $log->api_response,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Late comer SMS exception', [
                    'phone' => $phone,
                    'staff_user_id' => $staff->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function notifyForgotSignOut(User $staff, StaffAttendance $attendance): void
    {
        if (!$this->settings->forgotSignOutSmsEnabled() || !$attendance->auto_signed_out) {
            return;
        }

        $signedOutAt = Carbon::parse($attendance->signed_out_at);
        $hqName = $this->settings->hqName();
        $expectedDeparture = substr($this->settings->expectedDepartureTime(), 0, 5);

        $replacements = [
            '{name}' => $staff->name,
            '{staff_id}' => $staff->staff_id ?? 'N/A',
            '{time}' => $signedOutAt->format('h:i A'),
            '{date}' => $signedOutAt->format('d M Y'),
            '{expected_departure}' => $expectedDeparture,
            '{hq_name}' => $hqName,
        ];

        $staffPhone = Customer::normalizePhoneNumber($staff->phone);
        if ($staffPhone) {
            $staffMessage = $this->buildMessage($this->settings->forgotSignOutStaffSmsTemplate(), $replacements);
            try {
                $log = $this->sms->sendAndLog($staffPhone, $staffMessage, 'forgot_sign_out_staff', null, $staff->id);
                if ($log->status !== 'sent') {
                    Log::warning('Forgot sign-out staff SMS failed', [
                        'phone' => $staffPhone,
                        'staff_user_id' => $staff->id,
                        'response' => $log->api_response,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Forgot sign-out staff SMS exception', [
                    'staff_user_id' => $staff->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $managerPhones = $this->settings->lateComerNotificationPhones();
        if ($managerPhones === []) {
            Log::warning('Forgot sign-out manager SMS skipped: no CEO/HR phones configured', [
                'staff_user_id' => $staff->id,
                'attendance_id' => $attendance->id,
            ]);

            return;
        }

        $managerMessage = $this->buildMessage($this->settings->forgotSignOutManagerSmsTemplate(), $replacements);

        foreach ($managerPhones as $phone) {
            try {
                $log = $this->sms->sendAndLog($phone, $managerMessage, 'forgot_sign_out_alert', null, $staff->id);
                if ($log->status !== 'sent') {
                    Log::warning('Forgot sign-out manager SMS failed', [
                        'phone' => $phone,
                        'staff_user_id' => $staff->id,
                        'response' => $log->api_response,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Forgot sign-out manager SMS exception', [
                    'phone' => $phone,
                    'staff_user_id' => $staff->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildMessage(string $template, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
