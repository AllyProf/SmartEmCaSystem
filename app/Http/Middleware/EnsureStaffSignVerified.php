<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffSignVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionEmail = strtolower((string) $request->session()->get('staff_sign_email', ''));
        $sessionUserId = (int) $request->session()->get('staff_sign_user_id', 0);
        $verified = $request->session()->get('staff_sign_verified', false);

        if (
            !Auth::check()
            || !$verified
            || $sessionEmail === ''
            || $sessionUserId !== (int) Auth::id()
            || strtolower(Auth::user()->email) !== $sessionEmail
        ) {
            $request->session()->forget([
                'staff_sign_verified',
                'staff_sign_email',
                'staff_sign_user_id',
                'staff_sign_last_activity',
            ]);
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enter your staff email on the sign page first.',
                ], 401);
            }

            return redirect()->route('staff.sign')
                ->with('error', 'Enter your registered staff email to continue.');
        }

        return $next($request);
    }
}
