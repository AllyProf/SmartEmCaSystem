<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\StaffToken;
use Illuminate\Support\Facades\Auth;

class StaffApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token missing.',
            ], 401);
        }

        $staffToken = StaffToken::where('token', hash('sha256', $token))->with('user')->first();

        if (!$staffToken || !$staffToken->user || !$staffToken->user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated or inactive user.',
            ], 401);
        }

        // Make the user available via $request->user()
        Auth::setUser($staffToken->user);

        return $next($request);
    }
}
