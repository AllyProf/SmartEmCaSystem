<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Services\AttendanceGeofenceService;
use App\Services\AttendanceNotificationService;
use App\Services\AttendanceRulesService;
use App\Services\StaffDeviceService;

class StaffAttendanceController extends Controller
{
    public function __construct(
        protected AttendanceGeofenceService $geofence,
        protected AttendanceRulesService $rules,
        protected AttendanceNotificationService $notifications
    ) {}

    /**
     * Staff Login - Returns API token
     * Supports staff_id or email
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'nullable|string',
            'email'    => 'nullable|string',
            'password' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        if (empty($validated['staff_id']) && empty($validated['email'])) {
            return response()->json([
                'success' => false,
                'error'   => 'Either staff_id or email must be provided'
            ], 422);
        }

        $user = null;
        if (!empty($validated['email'])) {
            $user = User::where('email', $validated['email'])->first();
        } elseif (!empty($validated['staff_id'])) {
            // Check if staff_id is actually an email
            if (filter_var($validated['staff_id'], FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $validated['staff_id'])->first();
            } else {
                $user = User::where('staff_id', $validated['staff_id'])->first();
            }
        }

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'error'   => 'Your account is deactivated'
            ], 403);
        }

        // Device binding: mobile app uses device_id, web staff sign uses web_device_id.
        $deviceError = app(StaffDeviceService::class)->assertDevice(
            $user,
            $request->device_id,
            true,
            StaffDeviceService::PLATFORM_MOBILE
        );
        if ($deviceError) {
            return response()->json([
                'success' => false,
                'error'   => $deviceError,
                'message' => $deviceError,
            ], 403);
        }

        // Generate API token
        StaffToken::where('user_id', $user->id)->delete();
        
        $token = Str::random(60);
        $expiresAt = now()->addDays(30);
        
        StaffToken::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'staff'   => [
                'id'           => $user->id,
                'staff_id'     => $user->staff_id ?? '',
                'name'         => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone ?? '',
                'role'         => $user->role,
            ],
            'expires_at'  => $expiresAt->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Staff Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            StaffToken::where('user_id', $user->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Staff Profile info
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'staff'   => [
                'id'           => $user->id,
                'staff_id'     => $user->staff_id ?? '',
                'name'         => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone ?? '',
                'role'         => $user->role,
                'joined_at'    => $user->created_at->format('M d, Y'),
            ]
        ]);
    }

    /**
     * Get Attendance Status
     */
    public function getAttendanceStatus(Request $request)
    {
        $user = $request->user();
        
        $attendance = StaffAttendance::where('user_id', $user->id)
            ->whereNull('signed_out_at')
            ->orderByDesc('id')
            ->first();
            
        return response()->json([
            'success'      => true,
            'is_signed_in' => (bool)$attendance,
            'last_sign_in' => $attendance ? $attendance->signed_in_at->toIso8601String() : null,
            'last_sign_out' => null,
            'status'       => $attendance ? 'signed_in' : 'signed_out',
            'server_time'  => now()->toIso8601String(),
        ]);
    }

    /**
     * Sign-In
     */
    public function signIn(Request $request)
    {
        $user = $request->user();

        // Check if already signed in (open session)
        $existing = StaffAttendance::where('user_id', $user->id)
            ->whereNull('signed_out_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error'   => 'You have already signed in'
            ], 400);
        }

        $request->validate([
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'timestamp'         => 'required|date',
            'verification_type' => 'required|in:fingerprint,phone_pin',
        ]);

        $userLat = (float) $request->input('latitude');
        $userLng = (float) $request->input('longitude');

        // 2. Calculate the exact distance using the Haversine formula
        $distance = $this->geofence->distanceFromHq($userLat, $userLng);

        if (!$this->geofence->isWithinHq($userLat, $userLng)) {
            return response()->json([
                'success' => false,
                'message' => 'Geofence Violation: Restricted Access. You are ' . round($distance, 1) . 'm away from HQ.',
                'distance_logged' => round($distance, 1) . 'm'
            ], 403);
        }

        // 4. Verification checks out -> Log the attendance
        $signedInAt = Carbon::parse($request->input('timestamp'));

        $attendance = StaffAttendance::create([
            'user_id'              => $user->id,
            'signed_in_at'         => $signedInAt,
            'latitude_in'          => $userLat,
            'longitude_in'         => $userLng,
            'verification_type_in' => $request->input('verification_type'),
            'location_verified_in' => true,
            'is_late'              => $this->rules->isLate($signedInAt),
        ]);

        if ($attendance->is_late) {
            $this->notifications->notifyLateSignIn($user, $attendance);
        }

        return response()->json([
            'success'         => true,
            'message'         => 'Verification Successful: You have signed in.',
            'distance_logged' => round($distance, 1) . 'm',
            'timestamp'       => $attendance->signed_in_at->toIso8601String(),
            'status'          => 'signed_in'
        ], 200);
    }

    /**
     * Sign-Out
     */
    public function signOut(Request $request)
    {
        $user = $request->user();

        $attendance = StaffAttendance::where('user_id', $user->id)
            ->whereNull('signed_out_at')
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'error'   => 'You must sign in before signing out'
            ], 400);
        }

        $request->validate([
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'timestamp'         => 'required|date',
            'verification_type' => 'required|in:fingerprint,phone_pin',
        ]);

        $userLat = (float) $request->input('latitude');
        $userLng = (float) $request->input('longitude');

        // 2. Calculate the exact distance using the Haversine formula
        $distance = $this->geofence->distanceFromHq($userLat, $userLng);

        if (!$this->geofence->isWithinHq($userLat, $userLng)) {
            return response()->json([
                'success' => false,
                'message' => 'Geofence Violation: Restricted Access. You are ' . round($distance, 1) . 'm away from HQ.',
                'distance_logged' => round($distance, 1) . 'm'
            ], 403);
        }

        $attendance->update([
            'signed_out_at'         => Carbon::parse($request->input('timestamp')),
            'latitude_out'          => $userLat,
            'longitude_out'         => $userLng,
            'verification_type_out' => $request->input('verification_type'),
            'location_verified_out' => true,
        ]);

        return response()->json([
            'success'         => true,
            'message'         => 'Verification Successful: You have signed out.',
            'distance_logged' => round($distance, 1) . 'm',
            'timestamp'       => $attendance->signed_out_at->toIso8601String()
        ], 200);
    }

    /**
     * Get Attendance History
     */
    public function getAttendanceHistory(Request $request)
    {
        $user  = $request->user();
        $limit = min((int) $request->get('limit', 100), 500);

        $records = StaffAttendance::where('user_id', $user->id)
            ->orderByDesc('signed_in_at')
            ->limit($limit)
            ->get();

        $logs = [];
        foreach ($records as $record) {
            if ($record->signed_in_at) {
                $logs[] = [
                    'type'      => 'sign_in',
                    'date'      => $record->signed_in_at->format('Y-m-d'),
                    'time'      => $record->signed_in_at->format('h:i A'),
                    'timestamp' => $record->signed_in_at->toIso8601String(),
                    'latitude'  => $record->latitude_in ? (float)$record->latitude_in : null,
                    'longitude' => $record->longitude_in ? (float)$record->longitude_in : null,
                ];
            }
            if ($record->signed_out_at) {
                $logs[] = [
                    'type'      => 'sign_out',
                    'date'      => $record->signed_out_at->format('Y-m-d'),
                    'time'      => $record->signed_out_at->format('h:i A'),
                    'timestamp' => $record->signed_out_at->toIso8601String(),
                    'latitude'  => $record->latitude_out ? (float)$record->latitude_out : null,
                    'longitude' => $record->longitude_out ? (float)$record->longitude_out : null,
                ];
            }
        }

        // Keep them sorted by timestamp descending
        usort($logs, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return response()->json([
            'success' => true,
            'total'   => count($logs),
            'logs'    => $logs,
        ]);
    }
}
