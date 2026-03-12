<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\User;
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

        return view('attendance.index', [
            'attendances' => $filteredResults,
            'date' => $date,
            'expectedInTime' => $expectedInTime,
            'expectedOutTime' => $expectedOutTime,
            'filterType' => $filterType,
            'statusFilter' => $statusFilter,
            'weeklyLateComers' => $weeklyLateComers,
            'weeklyEarlyArrivals' => $weeklyEarlyArrivals
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

    /**
     * Save attendance settings via AJAX or Form
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'expected_arrival_time'   => 'nullable|date_format:H:i',
            'expected_departure_time' => 'nullable|date_format:H:i',
        ]);

        if ($request->has('expected_arrival_time')) {
            Setting::updateOrCreate(
                ['key' => 'expected_arrival_time'],
                ['value' => $request->expected_arrival_time . ':00']
            );
        }

        if ($request->has('expected_departure_time')) {
            Setting::updateOrCreate(
                ['key' => 'expected_departure_time'],
                ['value' => $request->expected_departure_time . ':00']
            );
        }

        return back()->with('success', 'Attendance rules updated successfully.');
    }
}
