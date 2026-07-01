<?php

namespace App\Console\Commands;

use App\Models\StaffAttendance;
use App\Services\AttendanceRulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSignOutAttendance extends Command
{
    protected $signature = 'attendance:auto-sign-out';
    protected $description = 'Auto sign-out open attendance sessions at end of day';

    public function handle(AttendanceRulesService $rules): int
    {
        $open = StaffAttendance::whereNull('signed_out_at')
            ->whereDate('signed_in_at', '<', today())
            ->get();

        foreach ($open as $attendance) {
            $signedOutAt = Carbon::parse($attendance->signed_in_at->toDateString() . ' 23:59:59');

            $attendance->update([
                'signed_out_at' => $signedOutAt,
                'verification_type_out' => 'auto_midnight',
                'location_verified_out' => false,
                'auto_signed_out' => true,
                'is_early_out' => false,
                'overtime_minutes' => $rules->overtimeMinutes($attendance->signed_in_at, $signedOutAt),
            ]);

            $this->info("Auto signed out user #{$attendance->user_id} for {$attendance->signed_in_at->toDateString()}");
        }

        $this->info('Done. Processed ' . $open->count() . ' record(s).');

        return self::SUCCESS;
    }
}
