<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\StaffAttendance;
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

        $mapPins = collect($filteredResults)
            ->filter(fn ($att) => $att->latitude_in && $att->longitude_in)
            ->map(function ($att) use ($geofence) {
                $lat = (float) $att->latitude_in;
                $lng = (float) $att->longitude_in;

                return [
                    'id' => $att->id,
                    'name' => $att->user->name,
                    'staff_id' => $att->user->staff_id ?? '',
                    'lat' => $lat,
                    'lng' => $lng,
                    'lat_out' => $att->latitude_out ? (float) $att->latitude_out : null,
                    'lng_out' => $att->longitude_out ? (float) $att->longitude_out : null,
                    'signed_in' => Carbon::parse($att->signed_in_at)->format('h:i A'),
                    'signed_in_date' => Carbon::parse($att->signed_in_at)->format('d M Y'),
                    'signed_out' => $att->signed_out_at
                        ? Carbon::parse($att->signed_out_at)->format('h:i A')
                        : null,
                    'is_late' => (bool) $att->is_late,
                    'inside_hq' => (bool) $att->location_verified_in,
                    'still_working' => !$att->signed_out_at,
                    'auto_signed_out' => (bool) $att->auto_signed_out,
                    'forgot_sign_out' => (bool) $att->auto_signed_out,
                    'distance_m' => (int) round($geofence->distanceFromHq($lat, $lng)),
                    'photo_url' => $att->photoInUrl(),
                    'gps_flagged' => (bool) $att->gps_flagged_in,
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
