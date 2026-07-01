<?php

namespace App\Console\Commands;

use App\Models\StaffAttendance;
use App\Services\AttendanceNotificationService;
use App\Services\AttendanceRulesService;
use App\Services\AttendanceSettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSignOutAttendance extends Command
{
    protected $signature = 'attendance:auto-sign-out';
    protected $description = 'Auto sign-out open sessions at expected departure and close leftover sessions';

    public function handle(
        AttendanceRulesService $rules,
        AttendanceSettingService $settings,
        AttendanceNotificationService $notifications
    ): int {
        $processed = 0;

        if ($settings->autoSignOutEnabled()) {
            $departureTime = $settings->expectedDepartureTime();
            $now = now();

            $openToday = StaffAttendance::with('user')
                ->whereNull('signed_out_at')
                ->whereDate('signed_in_at', today())
                ->get();

            foreach ($openToday as $attendance) {
                $signedOutAt = Carbon::parse($attendance->signed_in_at->toDateString() . ' ' . $departureTime);

                if ($now->lessThan($signedOutAt)) {
                    continue;
                }

                $this->closeSession($attendance, $signedOutAt, 'auto_departure', $rules, $notifications);
                $processed++;
            }
        }

        $openPast = StaffAttendance::with('user')
            ->whereNull('signed_out_at')
            ->whereDate('signed_in_at', '<', today())
            ->get();

        foreach ($openPast as $attendance) {
            $signedOutAt = Carbon::parse(
                $attendance->signed_in_at->toDateString() . ' ' . $settings->expectedDepartureTime()
            );

            $this->closeSession($attendance, $signedOutAt, 'auto_midnight', $rules, $notifications);
            $processed++;
        }

        $this->info('Done. Processed ' . $processed . ' record(s).');

        return self::SUCCESS;
    }

    private function closeSession(
        StaffAttendance $attendance,
        Carbon $signedOutAt,
        string $verificationType,
        AttendanceRulesService $rules,
        AttendanceNotificationService $notifications
    ): void {
        $attendance->update([
            'signed_out_at' => $signedOutAt,
            'verification_type_out' => $verificationType,
            'location_verified_out' => false,
            'auto_signed_out' => true,
            'is_early_out' => false,
            'overtime_minutes' => $rules->overtimeMinutes($attendance->signed_in_at, $signedOutAt),
        ]);

        $attendance->refresh();

        if ($attendance->user) {
            $notifications->notifyForgotSignOut($attendance->user, $attendance);
        }

        $this->info("Auto signed out user #{$attendance->user_id} for {$attendance->signed_in_at->toDateString()}");
    }
}
