<?php

namespace App\Services;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AttendanceRulesService
{
    public function __construct(
        protected AttendanceSettingService $settings
    ) {}

    public function hasSignedInToday(int $userId): bool
    {
        return StaffAttendance::where('user_id', $userId)
            ->whereDate('signed_in_at', today())
            ->exists();
    }

    public function openSession(int $userId): ?StaffAttendance
    {
        return StaffAttendance::where('user_id', $userId)
            ->whereNull('signed_out_at')
            ->latest('id')
            ->first();
    }

    public function canSignIn(User $user): ?string
    {
        $window = $this->settings->signWindowState(null, (bool) $this->openSession($user->id));
        if (!$window['sign_in_allowed']) {
            return $window['message'];
        }

        if ($this->settings->blockSignInOnNonWorkingDays() && $this->settings->isNonWorkingDay()) {
            if ($this->settings->isPublicHoliday()) {
                return 'Sign-in is not allowed today — public holiday.';
            }

            return 'Sign-in is not allowed today — weekend / non-working day.';
        }

        if ($this->hasSignedInToday($user->id)) {
            return 'You have already signed in today. Only one sign-in per day is allowed.';
        }

        if ($this->openSession($user->id)) {
            return 'You have an open attendance session. Sign out first.';
        }

        return null;
    }

    public function canSignOut(User $user): ?string
    {
        $window = $this->settings->signWindowState(null, true);

        if (!$window['sign_out_allowed']) {
            return $window['message'];
        }

        if (!$this->openSession($user->id)) {
            return 'You must sign in before signing out.';
        }

        return null;
    }

    public function isLate(Carbon $signedInAt): bool
    {
        $expected = Carbon::parse($signedInAt->toDateString() . ' ' . $this->settings->expectedArrivalTime());
        $deadline = $expected->copy()->addMinutes($this->settings->lateGraceMinutes());

        return $signedInAt->greaterThan($deadline);
    }

    public function isEarlyOut(Carbon $signedOutAt): bool
    {
        $expected = Carbon::parse($signedOutAt->toDateString() . ' ' . $this->settings->expectedDepartureTime());

        return $signedOutAt->lessThan($expected);
    }

    public function overtimeMinutes(Carbon $signedInAt, Carbon $signedOutAt): int
    {
        $expectedEnd = Carbon::parse($signedInAt->toDateString() . ' ' . $this->settings->expectedDepartureTime());

        if ($signedOutAt->lessThanOrEqualTo($expectedEnd)) {
            return 0;
        }

        return (int) $expectedEnd->diffInMinutes($signedOutAt);
    }

    public function verifySignPin(User $user, ?string $pin): ?string
    {
        $storedPin = User::whereKey($user->id)->value('sign_pin');

        if (!is_string($storedPin) || $storedPin === '') {
            return 'Sign PIN not set. Tap Switch account, enter your email again, or contact admin.';
        }

        if (empty($pin)) {
            return 'PIN is required to sign.';
        }

        if (!Hash::check(trim($pin), $storedPin)) {
            return 'Incorrect sign PIN.';
        }

        return null;
    }
}
