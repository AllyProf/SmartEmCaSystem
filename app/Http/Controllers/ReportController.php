<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\VisitConfirmation;
use App\Models\SmsLog;
use App\Models\FollowUp;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index(Request $request)
    {
        // Date range filters
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        if ($startDateInput) {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
        } else {
            $startDate = now()->subDays(30)->startOfDay();
        }

        if ($endDateInput) {
            $endDate = Carbon::parse($endDateInput)->endOfDay();
        } else {
            $endDate = now()->endOfDay();
        }

        // 1. Customers Report
        $totalCustomers = Customer::count();
        $newCustomersInRange = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Customers registered per day (for Line Chart)
        $customerRegistrations = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Customers by Location (Top 5)
        $topLocations = Customer::select('location', DB::raw('count(*) as count'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // 2. SMS Report
        $smsTotal = SmsLog::whereBetween('created_at', [$startDate, $endDate])->count();
        $smsSent = SmsLog::whereBetween('created_at', [$startDate, $endDate])->where('status', 'sent')->count();
        $smsFailed = SmsLog::whereBetween('created_at', [$startDate, $endDate])->where('status', 'failed')->count();
        $smsScheduled = SmsLog::whereBetween('created_at', [$startDate, $endDate])->where('status', 'scheduled')->count();

        // SMS Type breakdown
        $smsTypes = SmsLog::select('sms_type', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('sms_type')
            ->get();

        // 3. Follow-Ups Report
        $followUpsTotal = FollowUp::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Follow-ups by status
        $followUpStatusCounts = FollowUp::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // 4. Visit Confirmations Report
        $visitsTotal = VisitConfirmation::whereBetween('visit_date', [$startDate, $endDate])->count();
        $singleVisits = VisitConfirmation::whereBetween('visit_date', [$startDate, $endDate])->where('type', 'single')->count();
        $groupVisits = VisitConfirmation::whereBetween('visit_date', [$startDate, $endDate])->where('type', 'group')->count();

        $satisfactionCounts = VisitConfirmation::select('satisfaction_level', DB::raw('count(*) as count'))
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('satisfaction_level')
            ->where('satisfaction_level', '!=', '')
            ->groupBy('satisfaction_level')
            ->get();

        // Format data for Chart.js
        $chartLabels = [];
        $chartCustomerData = [];
        
        $current = clone $startDate;
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $chartLabels[] = $current->format('M d');
            
            $found = $customerRegistrations->firstWhere('date', $dateStr);
            $chartCustomerData[] = $found ? $found->count : 0;
            
            $current->addDay();
        }

        return view('reports.index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => [
                'total_customers' => $totalCustomers,
                'new_customers_range' => $newCustomersInRange,
                'sms_total' => $smsTotal,
                'sms_sent' => $smsSent,
                'sms_failed' => $smsFailed,
                'sms_scheduled' => $smsScheduled,
                'followups_total' => $followUpsTotal,
                'visits_total' => $visitsTotal,
                'visits_single' => $singleVisits,
                'visits_group' => $groupVisits,
            ],
            'chartLabels' => $chartLabels,
            'chartCustomerData' => $chartCustomerData,
            'topLocations' => $topLocations,
            'smsTypes' => $smsTypes,
            'followUpStatusCounts' => $followUpStatusCounts,
            'satisfactionCounts' => $satisfactionCounts,
        ]);
    }
}
