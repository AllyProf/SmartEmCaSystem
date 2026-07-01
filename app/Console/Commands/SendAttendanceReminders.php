<?php

namespace App\Console\Commands;

use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAttendanceReminders extends Command
{
    protected $signature = 'attendance:send-reminders';
    protected $description = 'SMS staff who have not signed in by the reminder time';

    public function handle(AttendanceSettingService $settings, SmsService $sms): int
    {
        if (!$settings->signReminderSmsEnabled()) {
            $this->info('Sign-in reminder SMS is disabled in settings.');

            return self::SUCCESS;
        }

        if ($settings->isNonWorkingDay()) {
            $this->info('Non-working day. Skipping reminders.');

            return self::SUCCESS;
        }

        $reminderTime = $settings->signReminderTime();
        $now = now()->format('H:i:s');

        if ($now < $reminderTime) {
            $this->info('Before reminder time. Skipping.');

            return self::SUCCESS;
        }

        $signedUserIds = StaffAttendance::whereDate('signed_in_at', today())->pluck('user_id');

        $staff = User::where('is_active', true)
            ->whereIn('role', ['staff', 'hr'])
            ->whereNotIn('id', $signedUserIds)
            ->whereNotNull('phone')
            ->get();

        $sent = 0;
        $template = $settings->signReminderSmsTemplate();
        $expectedTime = substr($settings->expectedArrivalTime(), 0, 5);

        foreach ($staff as $user) {
            $message = str_replace('{expected_time}', $expectedTime, $template);

            try {
                $sms->sendSms($user->phone, $message);
                $sent++;
                $this->info("Sent to {$user->name}");
            } catch (\Throwable $e) {
                $this->warn("Failed for {$user->name}: {$e->getMessage()}");
            }
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
