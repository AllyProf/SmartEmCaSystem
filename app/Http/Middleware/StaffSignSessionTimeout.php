<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffSignSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = app(\App\Services\AttendanceSettingService::class)->sessionTimeoutMinutes();
        $lastActivity = $request->session()->get('staff_sign_last_activity');

        if ($lastActivity && now()->diffInMinutes($lastActivity) >= $timeoutMinutes) {
            $request->session()->forget([
                'staff_sign_verified',
                'staff_sign_email',
                'staff_sign_user_id',
                'staff_sign_last_activity',
            ]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired. Please enter your email again.',
                    'session_expired' => true,
                ], 401);
            }

            return redirect()->route('staff.sign')
                ->with('error', 'Session expired for security. Please sign in again.');
        }

        $request->session()->put('staff_sign_last_activity', now());

        return $next($request);
    }
}
