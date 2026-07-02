<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\AttendanceGeofenceService;
use App\Services\AttendanceNotificationService;
use App\Services\AttendanceRulesService;
use App\Services\AttendanceSettingService;
use App\Services\GpsSpoofDetectionService;
use App\Services\ReverseGeocodeService;
use App\Services\StaffDeviceService;
use App\Models\StaffLiveLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StaffSignController extends Controller
{
    public function __construct(
        protected AttendanceGeofenceService $geofence,
        protected StaffDeviceService $devices,
        protected AttendanceRulesService $rules,
        protected AttendanceNotificationService $notifications,
        protected GpsSpoofDetectionService $gpsDetector,
        protected AttendanceSettingService $settings
    ) {}

    public function show()
    {
        $this->resetStaffSignSessionIfInvalid();

        $staffSignActive = $this->hasActiveStaffSignSession();

        $attendance = null;
        if ($staffSignActive) {
            $attendance = $this->rules->openSession(Auth::id());
            request()->session()->put('staff_sign_last_activity', now());
        }

        $mapConfig = $this->geofence->mapConfig();
        $userId = $staffSignActive ? (int) Auth::id() : 0;
        $mapConfig['sign_window'] = $this->settings->signWindowState(
            now(),
            (bool) $attendance,
            $userId > 0 && $this->rules->hasCompletedSessionToday($userId)
        );
        $mapConfig['server_time'] = now()->toIso8601String();

        return view('auth.staff-sign', [
            'mapConfig' => $mapConfig,
            'isSignedIn' => (bool) $attendance,
            'lastSignIn' => $attendance?->signed_in_at,
            'staffSignActive' => $staffSignActive,
            'isMobileStaffSign' => $this->devices->isMobileUserAgent(request()->userAgent()),
        ]);
    }

    private function hasActiveStaffSignSession(): bool
    {
        if (!Auth::check() || !session('staff_sign_verified', false)) {
            return false;
        }

        $sessionEmail = strtolower((string) session('staff_sign_email', ''));
        $sessionUserId = (int) session('staff_sign_user_id', 0);

        return $sessionEmail !== ''
            && $sessionUserId === (int) Auth::id()
            && strtolower(Auth::user()->email) === $sessionEmail;
    }

    private function resetStaffSignSessionIfInvalid(): void
    {
        if ($this->hasActiveStaffSignSession()) {
            return;
        }

        Auth::logout();
        session()->forget([
            'staff_sign_verified',
            'staff_sign_email',
            'staff_sign_user_id',
            'staff_sign_last_activity',
        ]);
    }

    public function pinStatus()
    {
        return response()->json([
            'has_pin' => $this->userHasSignPin(Auth::id()),
            'email' => Auth::user()->email,
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'staff_sign_verified',
            'staff_sign_email',
            'staff_sign_user_id',
            'staff_sign_last_activity',
        ]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.sign')->with('success', 'Logged out. This device stays locked to your staff email.');
    }

    public function deviceBinding(Request $request)
    {
        $deviceId = trim((string) $request->query('device_id', ''));

        if ($deviceId === '') {
            return response()->json(['bound' => false]);
        }

        $platform = $this->devices->resolveStaffSignPlatform($request);
        $owner = $this->devices->findUserByDevice($deviceId, $platform);

        if (!$owner) {
            return response()->json(['bound' => false]);
        }

        return response()->json([
            'bound' => true,
            'email' => strtolower($owner->email),
            'masked_email' => $this->devices->maskEmail($owner->email),
            'name' => $owner->name,
        ]);
    }

    private function userHasSignPin(int $userId): bool
    {
        $pin = User::whereKey($userId)->value('sign_pin');

        return is_string($pin) && $pin !== '';
    }

    private function freshUser(): User
    {
        return User::findOrFail(Auth::id());
    }

    public function authenticate(Request $request)
    {
        $email = $this->normalizeStaffEmail($request->input('email'));

        if ($email === '') {
            return $this->rejectStaffSignAuth($request, 'Please enter your staff email address.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->rejectStaffSignAuth($request, 'Please enter a valid email address.');
        }

        $deviceId = $request->input('device_id') ?: ('web_' . uniqid('', true));

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$user) {
            return $this->rejectStaffSignAuth($request, 'No staff account found for this email. Check the address and try again.');
        }

        if (!$user->is_active) {
            return $this->rejectStaffSignAuth($request, 'Your account is deactivated.');
        }

        $platform = $this->devices->resolveStaffSignPlatform($request);
        $deviceError = $this->devices->assertDevice($user, $deviceId, true, $platform);
        if ($deviceError) {
            return $this->rejectStaffSignAuth($request, $deviceError);
        }

        Auth::logout();
        $request->session()->forget([
            'staff_sign_verified',
            'staff_sign_email',
            'staff_sign_user_id',
            'staff_sign_last_activity',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('staff_sign_verified', true);
        $request->session()->put('staff_sign_email', strtolower($user->email));
        $request->session()->put('staff_sign_user_id', $user->id);
        $request->session()->put('staff_sign_last_activity', now());

        return redirect()->route('staff.sign');
    }

    private function normalizeStaffEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        return preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', $email) ?? $email;
    }

    private function rejectStaffSignAuth(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->forget([
            'staff_sign_verified',
            'staff_sign_email',
            'staff_sign_user_id',
            'staff_sign_last_activity',
        ]);
        $request->session()->regenerateToken();

        return redirect()->route('staff.sign')
            ->with('error', $message)
            ->withInput(['email' => $request->input('email')]);
    }

    public function history(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('staff.sign')
                ->with('error', 'Enter your staff email on the sign page to view attendance history.');
        }

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $records = StaffAttendance::where('user_id', Auth::id())
            ->whereBetween('signed_in_at', [$start, $end])
            ->orderByDesc('signed_in_at')
            ->get();

        $summary = [
            'days_present' => $records->count(),
            'late_count' => $records->where('is_late', true)->count(),
            'total_minutes' => $records->sum(fn ($r) => $r->workingMinutes() ?? 0),
            'overtime_minutes' => $records->sum('overtime_minutes'),
        ];

        return view('auth.staff-sign-history', [
            'records' => $records,
            'month' => $month,
            'summary' => $summary,
        ]);
    }

    public function replay(StaffAttendance $attendance)
    {
        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        return view('auth.staff-sign-replay', [
            'attendance' => $attendance,
            'mapConfig' => $this->geofence->mapConfig(),
        ]);
    }

    public function status()
    {
        $attendance = $this->rules->openSession(Auth::id());
        $userId = (int) Auth::id();

        return response()->json([
            'is_signed_in' => (bool) $attendance,
            'last_sign_in' => $attendance?->signed_in_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'sign_window' => $this->settings->signWindowState(
                now(),
                (bool) $attendance,
                $this->rules->hasCompletedSessionToday($userId)
            ),
        ]);
    }

    public function reverseGeocode(Request $request, ReverseGeocodeService $geocoder)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $placeName = $geocoder->resolve($latitude, $longitude);

        $hqName = $this->settings->hqName();

        if ($this->geofence->isWithinHq($latitude, $longitude)) {
            $placeName = $placeName ? "{$hqName} · {$placeName}" : $hqName;
        }

        return response()->json([
            'place_name' => $placeName ?? 'Unknown location',
        ]);
    }

    public function signIn(Request $request)
    {
        $user = $this->freshUser();

        $platform = $this->devices->resolveStaffSignPlatform($request);
        $deviceError = $this->devices->assertDevice($user, $request->input('device_id'), true, $platform);
        if ($deviceError) {
            return response()->json(['success' => false, 'message' => $deviceError], 403);
        }

        $ruleError = $this->rules->canSignIn($user);
        if ($ruleError) {
            return response()->json(['success' => false, 'message' => $ruleError], 400);
        }

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_id' => 'required|string|max:255',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'timestamp' => 'nullable|integer',
            'gps_trail' => 'nullable|array',
            'photo' => 'required|string',
        ]);

        $photoPath = $this->storePhoto($request->input('photo'), 'in', $user->id);
        if (!$photoPath) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance photo is required as proof.',
            ], 422);
        }

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $distance = $this->geofence->distanceFromHq($latitude, $longitude);

        $hqName = $this->settings->hqName();

        if (!$this->geofence->isWithinHq($latitude, $longitude)) {
            return response()->json([
                'success' => false,
                'message' => "You must be at {$hqName} to sign in. You are " . round($distance, 1) . 'm away.',
                'distance' => round($distance, 1),
                'allowed_radius' => $this->geofence->radiusMeters(),
            ], 403);
        }

        $gpsAnalysis = $this->gpsDetector->analyze(
            $latitude,
            $longitude,
            $request->input('accuracy') !== null ? (float) $request->accuracy : null,
            $request->input('speed') !== null ? (float) $request->speed : null,
            (int) ($request->input('timestamp') ?? (now()->timestamp * 1000)),
            $request->input('gps_trail', [])
        );

        $signedInAt = now();
        $attendance = StaffAttendance::create([
            'user_id' => $user->id,
            'signed_in_at' => $signedInAt,
            'latitude_in' => $latitude,
            'longitude_in' => $longitude,
            'verification_type_in' => $request->boolean('biometric_used') ? 'web_biometric' : 'web_gps_photo',
            'location_verified_in' => true,
            'photo_in' => $photoPath,
            'gps_flagged_in' => $gpsAnalysis['flagged'],
            'gps_flags' => $gpsAnalysis,
            'path_trace' => $request->input('gps_trail', []),
            'is_late' => $this->rules->isLate($signedInAt),
        ]);

        if ($attendance->is_late) {
            try {
                $this->notifications->notifyLateSignIn($user, $attendance);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Late comer notification failed', [
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $gpsAnalysis['flagged']
                ? "Signed in at {$hqName}. Location flagged for review."
                : "Signed in successfully at {$hqName}.",
            'distance' => round($distance, 1),
            'is_late' => $attendance->is_late,
            'gps_flagged' => $gpsAnalysis['flagged'],
            'timestamp' => $attendance->signed_in_at->toIso8601String(),
        ]);
    }

    public function signOut(Request $request)
    {
        $user = $this->freshUser();

        $platform = $this->devices->resolveStaffSignPlatform($request);
        $deviceError = $this->devices->assertDevice($user, $request->input('device_id'), true, $platform);
        if ($deviceError) {
            return response()->json(['success' => false, 'message' => $deviceError], 403);
        }

        $ruleError = $this->rules->canSignOut($user);
        if ($ruleError) {
            return response()->json(['success' => false, 'message' => $ruleError], 400);
        }

        $attendance = $this->rules->openSession($user->id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'You must sign in before signing out.',
            ], 400);
        }

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_id' => 'required|string|max:255',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'timestamp' => 'nullable|integer',
            'gps_trail' => 'nullable|array',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $distance = $this->geofence->distanceFromHq($latitude, $longitude);

        $hqName = $this->settings->hqName();

        if (!$this->geofence->isWithinHq($latitude, $longitude)) {
            return response()->json([
                'success' => false,
                'message' => "You must be at {$hqName} to sign out. You are " . round($distance, 1) . 'm away.',
                'distance' => round($distance, 1),
                'allowed_radius' => $this->geofence->radiusMeters(),
            ], 403);
        }

        $gpsAnalysis = $this->gpsDetector->analyze(
            $latitude,
            $longitude,
            $request->input('accuracy') !== null ? (float) $request->accuracy : null,
            $request->input('speed') !== null ? (float) $request->speed : null,
            (int) ($request->input('timestamp') ?? (now()->timestamp * 1000)),
            $request->input('gps_trail', [])
        );

        $signedOutAt = now();
        $pathTrace = array_merge($attendance->path_trace ?? [], $request->input('gps_trail', []));

        $attendance->update([
            'signed_out_at' => $signedOutAt,
            'latitude_out' => $latitude,
            'longitude_out' => $longitude,
            'verification_type_out' => $request->boolean('biometric_used') ? 'web_biometric' : 'web_gps',
            'location_verified_out' => true,
            'photo_out' => null,
            'gps_flagged_out' => $gpsAnalysis['flagged'],
            'gps_flags' => array_merge($attendance->gps_flags ?? [], ['sign_out' => $gpsAnalysis]),
            'path_trace' => $pathTrace,
            'is_early_out' => $this->rules->isEarlyOut($signedOutAt),
            'overtime_minutes' => $this->rules->overtimeMinutes($attendance->signed_in_at, $signedOutAt),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signed out successfully.',
            'distance' => round($distance, 1),
            'overtime_minutes' => $attendance->overtime_minutes,
            'gps_flagged' => $gpsAnalysis['flagged'],
            'timestamp' => Carbon::parse($attendance->signed_out_at)->toIso8601String(),
        ]);
    }

    public function pingLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'travel_mode' => 'nullable|string|max:24',
            'timestamp' => 'nullable|integer',
        ]);

        $capturedAt = $request->input('timestamp')
            ? Carbon::createFromTimestampMs((int) $request->timestamp)
            : now();

        StaffLiveLocation::create([
            'user_id' => Auth::id(),
            'latitude' => (float) $request->latitude,
            'longitude' => (float) $request->longitude,
            'accuracy' => $request->accuracy !== null ? (int) round((float) $request->accuracy) : null,
            'speed' => $request->speed !== null ? (float) $request->speed : null,
            'heading' => $request->heading !== null ? (int) round((float) $request->heading) : null,
            'travel_mode' => $request->input('travel_mode'),
            'captured_at' => $capturedAt,
            'meta' => [
                'device_id' => $request->input('device_id'),
                'ua' => substr((string) $request->userAgent(), 0, 255),
            ],
        ]);

        return response()->json(['success' => true]);
    }

    private function storePhoto(?string $base64, string $prefix, int $userId): ?string
    {
        if (empty($base64) || !str_starts_with($base64, 'data:image')) {
            return null;
        }

        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        if ($data === false) {
            return null;
        }

        $filename = sprintf('%s_%d_%s.jpg', $prefix, $userId, now()->format('YmdHis'));
        $path = 'attendance-photos/' . $filename;
        Storage::disk('public')->put($path, $data);

        return $path;
    }
}
