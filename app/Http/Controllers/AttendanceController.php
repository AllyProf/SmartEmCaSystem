<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\AttendanceGeofenceService;
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
        $date = $request->query('date', Carbon::today()->toDateString());
        $filterType = $request->query('filter', 'daily'); // daily, weekly, monthly
        $statusFilter = $request->query('status'); // late, overdue
        
        $query = StaffAttendance::with('user');

        // Apply Time Filters
        if ($filterType === 'weekly') {
            $query->whereBetween('signed_in_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filterType === 'monthly') {
            $query->whereBetween('signed_in_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } else {
            $query->whereDate('signed_in_at', $date);
        }

        $attendances = $query->orderBy('signed_in_at', 'desc')->get();

        // Get Settings
        $expectedIn = Setting::where('key', 'expected_arrival_time')->first();
        $expectedInTime = $expectedIn ? $expectedIn->value : '08:00:00';
        
        $expectedOut = Setting::where('key', 'expected_departure_time')->first();
        $expectedOutTime = $expectedOut ? $expectedOut->value : '17:00:00';

        $filteredResults = [];
        
        foreach ($attendances as $att) {
            $signInTime = Carbon::parse($att->signed_in_at)->format('H:i:s');
            $att->is_late = $signInTime > $expectedInTime;
            
            // Check Overdue (Not signed out and past expected departure)
            $att->is_overdue = false;
            if (!$att->signed_out_at) {
                $nowTime = Carbon::now();
                $departureDateTime = Carbon::parse($att->signed_in_at->format('Y-m-d') . ' ' . $expectedOutTime);
                if ($nowTime->greaterThan($departureDateTime)) {
                    $att->is_overdue = true;
                }
            }

            // Working Hours Calculation
            if ($att->signed_out_at) {
                $diff = $att->signed_in_at->diff($att->signed_out_at);
                $att->working_hours = $diff->format('%h hours %i mins');
            } else {
                $att->working_hours = 'Pending';
            }

            // Apply Status Filters
            if ($statusFilter === 'late' && !$att->is_late) continue;
            if ($statusFilter === 'overdue' && !$att->is_overdue) continue;

            $filteredResults[] = $att;
        }

        // Stats for the widgets
        $weeklyLateComers = $this->getWeeklyStats('late', $expectedInTime);
        $weeklyEarlyArrivals = $this->getWeeklyStats('early', $expectedInTime);

        $attendanceSettings = app(\App\Services\AttendanceSettingService::class);
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

        return view('attendance.index', [
            'attendances' => $filteredResults,
            'mapPins' => $mapPins,
            'filterPeriodLabel' => $filterPeriodLabel,
            'date' => $date,
            'expectedInTime' => $expectedInTime,
            'expectedOutTime' => $expectedOutTime,
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
        ]);
    }

    /**
     * Get Stats for top employees
     */
    private function getWeeklyStats($type, $threshold)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = StaffAttendance::whereBetween('signed_in_at', [$startOfWeek, $endOfWeek])
            ->join('users', 'staff_attendances.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(*) as frequency'));

        if ($type === 'late') {
            $query->whereRaw('TIME(signed_in_at) > ?', [$threshold]);
        } else {
            $query->whereRaw('TIME(signed_in_at) <= ?', [$threshold]);
        }

        return $query->groupBy('users.id', 'users.name')
            ->orderByDesc('frequency')
            ->limit(5)
            ->get();
    }
}
