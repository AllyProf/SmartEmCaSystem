<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendWeeklyAttendanceSummary extends Command
{
    protected $signature = 'attendance:send-weekly-summary';
    protected $description = 'SMS weekly attendance summary to active staff and CEO/HR';

    public function handle(AttendanceSettingService $settings, SmsService $sms): int
    {
        if (!$settings->weeklyAttendanceSmsEnabled()) {
            $this->info('Weekly attendance SMS is disabled in settings.');

            return self::SUCCESS;
        }

        if ((int) now()->dayOfWeek !== $settings->weeklySummaryDay()) {
            $this->info('Not the configured weekly summary day. Skipping.');

            return self::SUCCESS;
        }

        $sendAfter = Carbon::parse(now()->toDateString() . ' ' . $settings->weeklySummaryTime());
        if (now()->lessThan($sendAfter)) {
            $this->info('Before weekly summary send time. Skipping.');

            return self::SUCCESS;
        }

        $periodEnd = now()->subDay()->endOfDay();
        $periodStart = $periodEnd->copy()->subDays(6)->startOfDay();
        $weekLabel = $periodStart->format('d M') . ' – ' . $periodEnd->format('d M Y');
        $hqName = $settings->hqName();

        $activeStaff = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['staff', 'hr'])
            ->orderBy('name')
            ->get();

        $staffSent = 0;
        $aggregate = [
            'staff_count' => 0,
            'total_present' => 0,
            'total_late' => 0,
            'total_forgot' => 0,
        ];

        if ($settings->weeklyAttendanceStaffSmsEnabled()) {
            $staffTemplate = $settings->weeklyAttendanceStaffSmsTemplate();

            foreach ($activeStaff as $user) {
                $phone = Customer::normalizePhoneNumber($user->phone);
                if (!$phone) {
                    $this->warn("Skipping {$user->name}: no valid phone.");

                    continue;
                }

                if ($this->alreadySentThisWeek($phone, 'weekly_attendance_staff')) {
                    $this->line("Already sent staff summary to {$user->name} this week.");

                    continue;
                }

                $stats = $this->statsForUser($user->id, $periodStart, $periodEnd);
                $aggregate['staff_count']++;
                $aggregate['total_present'] += $stats['days_present'];
                $aggregate['total_late'] += $stats['late_count'];
                $aggregate['total_forgot'] += $stats['forgot_sign_out_count'];

                $message = str_replace(
                    ['{name}', '{week}', '{days_present}', '{late_count}', '{forgot_sign_out_count}', '{hq_name}'],
                    [$user->name, $weekLabel, (string) $stats['days_present'], (string) $stats['late_count'], (string) $stats['forgot_sign_out_count'], $hqName],
                    $staffTemplate
                );

                if ($this->dispatchSms($sms, $phone, $message, 'weekly_attendance_staff', $user->id)) {
                    $staffSent++;
                    $this->info("Staff summary sent to {$user->name}");
                }
            }
        } else {
            foreach ($activeStaff as $user) {
                $stats = $this->statsForUser($user->id, $periodStart, $periodEnd);
                $aggregate['staff_count']++;
                $aggregate['total_present'] += $stats['days_present'];
                $aggregate['total_late'] += $stats['late_count'];
                $aggregate['total_forgot'] += $stats['forgot_sign_out_count'];
            }
        }

        $ceoSent = 0;
        if ($settings->weeklyAttendanceCeoSmsEnabled()) {
            $ceoTemplate = $settings->weeklyAttendanceCeoSmsTemplate();
            $ceoMessage = str_replace(
                ['{week}', '{staff_count}', '{total_present}', '{total_late}', '{total_forgot}', '{hq_name}'],
                [$weekLabel, (string) $aggregate['staff_count'], (string) $aggregate['total_present'], (string) $aggregate['total_late'], (string) $aggregate['total_forgot'], $hqName],
                $ceoTemplate
            );

            foreach ($settings->lateComerNotificationPhones() as $phone) {
                if ($this->alreadySentThisWeek($phone, 'weekly_attendance_ceo')) {
                    $this->line("Already sent CEO summary to {$phone} this week.");

                    continue;
                }

                if ($this->dispatchSms($sms, $phone, $ceoMessage, 'weekly_attendance_ceo', null)) {
                    $ceoSent++;
                    $this->info("CEO summary sent to {$phone}");
                }
            }
        }

        $this->info("Weekly summaries — staff: {$staffSent}, CEO/HR: {$ceoSent}");

        return self::SUCCESS;
    }

    private function statsForUser(int $userId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $records = StaffAttendance::query()
            ->where('user_id', $userId)
            ->whereBetween('signed_in_at', [$periodStart, $periodEnd])
            ->get();

        return [
            'days_present' => $records->count(),
            'late_count' => $records->where('is_late', true)->count(),
            'forgot_sign_out_count' => $records->where('auto_signed_out', true)->count(),
        ];
    }

    private function alreadySentThisWeek(string $phone, string $smsType): bool
    {
        return SmsLog::query()
            ->where('sms_type', $smsType)
            ->where('phone_number', $phone)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
    }

    private function dispatchSms(SmsService $sms, string $phone, string $message, string $type, ?int $sentBy): bool
    {
        try {
            $log = $sms->sendAndLog($phone, $message, $type, null, $sentBy);

            return $log->status === 'sent';
        } catch (\Throwable $e) {
            $this->warn("SMS error ({$type}): {$e->getMessage()}");

            return false;
        }
    }
}
