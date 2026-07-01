<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\FollowUp;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'hr') {
            $stats = [
                'total_staff' => \App\Models\User::whereIn('role', ['staff', 'hr'])->count(),
                'present_today' => \App\Models\StaffAttendance::whereDate('signed_in_at', today())->count(),
                'recent_users' => \App\Models\User::orderBy('created_at', 'desc')->limit(5)->get(),
                'recent_attendance' => \App\Models\StaffAttendance::with('user')->orderBy('signed_in_at', 'desc')->limit(5)->get(),
            ];

            return view('dashboard.hr', compact('stats'));
        }

        $stats = [
            'total_customers' => Customer::count(),
            'total_sms_sent' => SmsLog::where('status', 'sent')->count(),
            'pending_follow_ups' => FollowUp::where('status', 'pending')->count(),
            'recent_customers' => Customer::orderByDesc('updated_at')->limit(5)->get(),
            'recent_sms' => SmsLog::with('customer')->orderBy('created_at', 'desc')->limit(5)->get(),
            'upcoming_follow_ups' => FollowUp::with('customer')
                ->where('status', 'pending')
                ->where('next_follow_up_date', '>=', now())
                ->orderBy('next_follow_up_date', 'asc')
                ->limit(5)
                ->get(),
        ];

        return view('dashboard.index', compact('stats'));
    }
}
