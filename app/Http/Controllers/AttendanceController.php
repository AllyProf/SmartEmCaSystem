<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\StaffLiveLocation;
use App\Services\AttendanceGeofenceService;
use App\Services\AttendanceRulesService;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * View today's attendance with advanced filtering
     */
    public function index(Request $request)
    {
        $data = $this->resolveAttendanceData($request);

        return view('attendance.index', $data);
    }

    /**
     * JSON payload for background sync on the attendance dashboard
     */
    public function sync(Request $request)
    {
        $data = $this->resolveAttendanceData($request);

        return response()->json([
            'server_time' => now()->format('H:i:s'),
            'server_date' => now()->format('l, d M Y'),
            'filter_period_label' => $data['filterPeriodLabel'],
            'map_pin_count' => $data['mapPins']->count(),
            'at_hq_now' => $data['mapPins']->filter(fn ($p) => $p['still_working'] && $p['inside_hq'])->count(),
            'weekly_late_comers' => $data['weeklyLateComers']->map(fn ($s) => [
                'name' => $s->name,
                'frequency' => (int) $s->frequency,
            ])->values(),
            'weekly_early_arrivals' => $data['weeklyEarlyArrivals']->map(fn ($s) => [
                'name' => $s->name,
                'frequency' => (int) $s->frequency,
            ])->values(),
            'records' => collect($data['attendances'])->map(fn ($log) => $this->formatRecord($log))->values(),
            'map_pins' => $data['mapPins'],
        ]);
    }

    public function journey(StaffAttendance $attendance)
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isCeo() && !$user->isHr())) {
            abort(403);
        }

        $attendance->load('user');

        $signedInAt = $attendance->signed_in_at ? Carbon::parse($attendance->signed_in_at) : null;
        $signedOutAt = $attendance->signed_out_at ? Carbon::parse($attendance->signed_out_at) : null;
        $end = $signedOutAt ?: now();

        $trace = collect($attendance->path_trace ?? [])
            ->map(function ($p) {
                return [
                    'lat' => isset($p['lat']) ? (float) $p['lat'] : null,
                    'lng' => isset($p['lng']) ? (float) $p['lng'] : null,
                    'speed' => isset($p['speed']) ? (float) $p['speed'] : null,
                    'accuracy' => isset($p['accuracy']) ? (float) $p['accuracy'] : null,
                    'timestamp' => isset($p['timestamp']) ? (int) $p['timestamp'] : null,
                    'source' => 'trace',
                ];
            })
            ->filter(fn ($p) => $p['lat'] !== null && $p['lng'] !== null)
            ->values();

        $pings = collect();
        $latestPing = null;
        if ($signedInAt) {
            $pings = StaffLiveLocation::where('user_id', $attendance->user_id)
                ->whereBetween('captured_at', [$signedInAt, $end])
                ->orderBy('captured_at')
                ->get()
                ->map(function (StaffLiveLocation $loc) {
                    return [
                        'lat' => (float) $loc->latitude,
                        'lng' => (float) $loc->longitude,
                        'speed' => $loc->speed !== null ? (float) $loc->speed : null,
                        'accuracy' => $loc->accuracy !== null ? (float) $loc->accuracy : null,
                        'timestamp' => $loc->captured_at ? (int) ($loc->captured_at->timestamp * 1000) : null,
                        'source' => 'ping',
                    ];
                });

            $latestPing = StaffLiveLocation::where('user_id', $attendance->user_id)
                ->orderByDesc('captured_at')
                ->first();
        }

        // Merge trace + pings (avoid duplicates by timestamp+coords).
        $points = $trace
            ->merge($pings)
            ->unique(function ($p) {
                return ($p['timestamp'] ?? 0) . '::' . round((float) $p['lat'], 6) . '::' . round((float) $p['lng'], 6);
            })
            ->sortBy(fn ($p) => $p['timestamp'] ?? 0)
            ->values();

        $attendanceSettings = app(AttendanceSettingService::class);

        return view('attendance.journey', [
            'attendance' => $attendance,
            'hqLatitude' => $attendanceSettings->hqLatitude(),
            'hqLongitude' => $attendanceSettings->hqLongitude(),
            'geofenceRadius' => $attendanceSettings->geofenceRadius(),
            'hqName' => $attendanceSettings->hqName(),
            'points' => $points,
            'signedInAt' => $signedInAt,
            'signedOutAt' => $signedOutAt,
            'latestPing' => $latestPing,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAttendanceData(Request $request): array
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $filterType = $request->query('filter', 'daily');
        $statusFilter = $request->query('status');

        $query = StaffAttendance::with('user');

        if ($filterType === 'weekly') {
            $query->whereBetween('signed_in_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filterType === 'monthly') {
            $query->whereBetween('signed_in_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } else {
            $query->whereDate('signed_in_at', $date);
        }

        $attendances = $query->orderBy('signed_in_at', 'desc')->get();

        $rules = app(AttendanceRulesService::class);

        $expectedIn = Setting::where('key', 'expected_arrival_time')->first();
        $expectedInTime = $expectedIn ? $expectedIn->value : '08:00:00';

        $expectedOut = Setting::where('key', 'expected_departure_time')->first();
        $expectedOutTime = $expectedOut ? $expectedOut->value : '17:00:00';

        $filteredResults = [];

        foreach ($attendances as $att) {
            $att->is_late = $rules->isLate(Carbon::parse($att->signed_in_at));

            $att->is_overdue = false;
            if (!$att->signed_out_at) {
                $departureDateTime = Carbon::parse($att->signed_in_at->format('Y-m-d') . ' ' . $expectedOutTime);
                if (now()->greaterThan($departureDateTime)) {
                    $att->is_overdue = true;
                }
            }

            $att->is_forgot_sign_out = (bool) $att->auto_signed_out;

            if ($att->signed_out_at) {
                $diff = $att->signed_in_at->diff($att->signed_out_at);
                $att->working_hours = $diff->format('%h hours %i mins');
            } else {
                $diff = $att->signed_in_at->diff(now());
                $att->working_hours = $diff->format('%h hours %i mins');
            }

            if ($statusFilter === 'late' && !$att->is_late) {
                continue;
            }
            if ($statusFilter === 'overdue' && !$att->is_overdue) {
                continue;
            }
            if ($statusFilter === 'forgot' && !$att->is_forgot_sign_out) {
                continue;
            }

            $filteredResults[] = $att;
        }

        $attendanceSettings = app(AttendanceSettingService::class);
        $lateGraceMinutes = $attendanceSettings->lateGraceMinutes();
        $lateAfterTime = Carbon::parse($expectedInTime)->addMinutes($lateGraceMinutes)->format('H:i:s');

        $weeklyLateComers = $this->getWeeklyStats('late', $expectedInTime, $lateAfterTime);
        $weeklyEarlyArrivals = $this->getWeeklyStats('early', $expectedInTime);

        $geofence = app(AttendanceGeofenceService::class);

        $userIds = collect($filteredResults)->pluck('user_id')->filter()->unique()->values();
        $pingsByUser = collect();
        if ($userIds->isNotEmpty()) {
            $earliestSignIn = collect($filteredResults)->min(fn ($att) => $att->signed_in_at);
            $pingsByUser = StaffLiveLocation::whereIn('user_id', $userIds)
                ->when($earliestSignIn, fn ($q) => $q->where('captured_at', '>=', $earliestSignIn))
                ->orderBy('captured_at')
                ->get()
                ->groupBy('user_id');
        }

        $mapPins = collect($filteredResults)
            ->filter(fn ($att) => $att->latitude_in && $att->longitude_in)
            ->map(function ($att) use ($geofence, $pingsByUser) {
                $latIn = (float) $att->latitude_in;
                $lngIn = (float) $att->longitude_in;
                $signedOut = (bool) $att->signed_out_at;
                $signedInAt = Carbon::parse($att->signed_in_at);

                $sessionPings = $pingsByUser
                    ->get($att->user_id, collect())
                    ->filter(fn ($ping) => $ping->captured_at && $ping->captured_at->gte($signedInAt))
                    ->values();

                $ping = $sessionPings->last();
                $hasLive = !$signedOut && $ping !== null;
                $liveLat = $hasLive ? (float) $ping->latitude : null;
                $liveLng = $hasLive ? (float) $ping->longitude : null;

                $movementPath = $this->buildAttendanceMovementPath(
                    $att,
                    $sessionPings,
                    $latIn,
                    $lngIn
                );

                if (!$signedOut && count($movementPath) >= 2) {
                    $last = $movementPath[count($movementPath) - 1];
                    $lat = (float) $last[0];
                    $lng = (float) $last[1];
                    $liveLat = $lat;
                    $liveLng = $lng;
                    $hasLive = true;
                    $insideHq = $geofence->isWithinHq($lat, $lng);
                } elseif ($signedOut && $att->latitude_out && $att->longitude_out) {
                    $lat = (float) $att->latitude_out;
                    $lng = (float) $att->longitude_out;
                    $insideHq = $geofence->isWithinHq($lat, $lng);
                } elseif ($hasLive) {
                    $lat = $liveLat;
                    $lng = $liveLng;
                    $insideHq = $geofence->isWithinHq($lat, $lng);
                } else {
                    $lat = $latIn;
                    $lng = $lngIn;
                    $insideHq = (bool) $att->location_verified_in;
                }

                $lastSeenSeconds = ($ping && $ping->captured_at) ? now()->diffInSeconds($ping->captured_at) : null;
                if ($lastSeenSeconds === null) {
                    $lastTraceTs = collect($att->path_trace ?? [])
                        ->pluck('timestamp')
                        ->filter()
                        ->last();
                    if ($lastTraceTs) {
                        $lastSeenSeconds = now()->diffInSeconds(Carbon::createFromTimestampMs((int) $lastTraceTs));
                    }
                }

                $movedSinceSignIn = !$signedOut
                    && count($movementPath) >= 2
                    && $geofence->distanceBetween($latIn, $lngIn, $lat, $lng) > 5;

                $hasLive = !$signedOut && count($movementPath) >= 2;

                $actualLat = $lat;
                $actualLng = $lng;
                $mapLat = $lat;
                $mapLng = $lng;

                if ($signedOut) {
                    $insideHq = false;
                    if ($geofence->isWithinHq($actualLat, $actualLng)) {
                        $outside = $geofence->pointOutsideGeofence($actualLat, $actualLng);
                        $mapLat = $outside['lat'];
                        $mapLng = $outside['lng'];
                    }
                }

                return [
                    'id' => $att->id,
                    'name' => $att->user->name,
                    'staff_id' => $att->user->staff_id ?? '',
                    'lat' => $mapLat,
                    'lng' => $mapLng,
                    'actual_lat' => $actualLat,
                    'actual_lng' => $actualLng,
                    'lat_in' => $latIn,
                    'lng_in' => $lngIn,
                    'live_lat' => $liveLat,
                    'live_lng' => $liveLng,
                    'has_live' => $hasLive,
                    'moved_since_sign_in' => $movedSinceSignIn,
                    'movement_path' => $movementPath,
                    'lat_out' => $att->latitude_out ? (float) $att->latitude_out : null,
                    'lng_out' => $att->longitude_out ? (float) $att->longitude_out : null,
                    'signed_in' => Carbon::parse($att->signed_in_at)->format('h:i A'),
                    'signed_in_date' => Carbon::parse($att->signed_in_at)->format('d M Y'),
                    'signed_out' => $att->signed_out_at
                        ? Carbon::parse($att->signed_out_at)->format('h:i A')
                        : null,
                    'is_late' => (bool) $att->is_late,
                    'inside_hq' => $insideHq,
                    'inside_hq_at_sign_in' => (bool) $att->location_verified_in,
                    'still_working' => !$signedOut,
                    'auto_signed_out' => (bool) $att->auto_signed_out,
                    'forgot_sign_out' => (bool) $att->auto_signed_out,
                    'distance_m' => (int) round($geofence->distanceFromHq($actualLat, $actualLng)),
                    'photo_url' => $att->photoInUrl(),
                    'gps_flagged' => (bool) $att->gps_flagged_in,
                    'last_seen_seconds' => $lastSeenSeconds,
                ];
            })
            ->values();

        $filterPeriodLabel = match ($filterType) {
            'weekly' => 'This week (' . Carbon::now()->startOfWeek()->format('d M') . ' – ' . Carbon::now()->endOfWeek()->format('d M Y') . ')',
            'monthly' => Carbon::now()->format('F Y'),
            default => Carbon::parse($date)->format('l, d M Y'),
        };

        return [
            'attendances' => $filteredResults,
            'mapPins' => $mapPins,
            'filterPeriodLabel' => $filterPeriodLabel,
            'date' => $date,
            'expectedInTime' => $expectedInTime,
            'expectedOutTime' => $expectedOutTime,
            'lateGraceMinutes' => $lateGraceMinutes,
            'lateAfterTime' => $lateAfterTime,
            'filterType' => $filterType,
            'statusFilter' => $statusFilter,
            'weeklyLateComers' => $weeklyLateComers,
            'weeklyEarlyArrivals' => $weeklyEarlyArrivals,
            'hqLatitude' => $attendanceSettings->hqLatitude(),
            'hqLongitude' => $attendanceSettings->hqLongitude(),
            'geofenceRadius' => $attendanceSettings->geofenceRadius(),
            'hqName' => $attendanceSettings->hqName(),
            'signReminderTime' => substr($attendanceSettings->signReminderTime(), 0, 5),
            'sessionTimeout' => $attendanceSettings->sessionTimeoutMinutes(),
            'weekendDays' => implode(',', $attendanceSettings->weekendDays()),
            'publicHolidays' => implode(', ', $attendanceSettings->publicHolidays()),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, StaffLiveLocation>  $sessionPings
     * @return array<int, array{0: float, 1: float}>
     */
    private function buildAttendanceMovementPath(StaffAttendance $att, $sessionPings, float $latIn, float $lngIn): array
    {
        $points = [[$latIn, $lngIn]];

        foreach ($att->path_trace ?? [] as $point) {
            if (!is_array($point) || !isset($point['lat'], $point['lng'])) {
                continue;
            }
            $points[] = [(float) $point['lat'], (float) $point['lng']];
        }

        foreach ($sessionPings as $ping) {
            $points[] = [(float) $ping->latitude, (float) $ping->longitude];
        }

        $deduped = [];
        foreach ($points as $point) {
            $last = $deduped[count($deduped) - 1] ?? null;
            if ($last && abs($last[0] - $point[0]) < 0.00002 && abs($last[1] - $point[1]) < 0.00002) {
                continue;
            }
            $deduped[] = $point;
        }

        if (!empty($deduped)) {
            $deduped[0] = [$latIn, $lngIn];
        }

        return $deduped;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRecord(StaffAttendance $log): array
    {
        return [
            'id' => $log->id,
            'name' => $log->user->name,
            'staff_id' => $log->user->staff_id ?? '',
            'signed_in' => Carbon::parse($log->signed_in_at)->format('h:i A'),
            'signed_in_full' => Carbon::parse($log->signed_in_at)->format('d M Y h:i A'),
            'signed_in_date' => Carbon::parse($log->signed_in_at)->format('d M, Y'),
            'signed_out' => $log->signed_out_at
                ? Carbon::parse($log->signed_out_at)->format('h:i A')
                : null,
            'signed_out_full' => $log->signed_out_at
                ? Carbon::parse($log->signed_out_at)->format('d M Y h:i A')
                : null,
            'still_working' => !$log->signed_out_at,
            'working_hours' => $log->working_hours,
            'is_late' => (bool) $log->is_late,
            'is_overdue' => (bool) $log->is_overdue,
            'is_forgot_sign_out' => (bool) $log->is_forgot_sign_out,
            'location_verified_in' => (bool) $log->location_verified_in,
            'location_verified_out' => $log->location_verified_out !== null ? (bool) $log->location_verified_out : null,
            'verification_type_in' => $log->verification_type_in,
            'photo_url' => $log->photo_in ? $log->photoInUrl() : null,
            'latitude_in' => $log->latitude_in,
            'longitude_in' => $log->longitude_in,
            'latitude_out' => $log->latitude_out,
            'longitude_out' => $log->longitude_out,
            'path_trace' => $log->path_trace ?? [],
        ];
    }

    /**
     * Get Stats for top employees
     */
    private function getWeeklyStats(string $type, string $expectedArrivalTime, ?string $lateAfterTime = null)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = StaffAttendance::whereBetween('staff_attendances.signed_in_at', [$startOfWeek, $endOfWeek])
            ->join('users', 'staff_attendances.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(*) as frequency'));

        if ($type === 'late') {
            $cutoff = $lateAfterTime ?? $expectedArrivalTime;
            $query->whereRaw('TIME(staff_attendances.signed_in_at) > ?', [$cutoff]);
        } else {
            $query->whereRaw('TIME(staff_attendances.signed_in_at) <= ?', [$expectedArrivalTime]);
        }

        return $query->groupBy('users.id', 'users.name')
            ->orderByDesc('frequency')
            ->limit(5)
            ->get();
    }
}
