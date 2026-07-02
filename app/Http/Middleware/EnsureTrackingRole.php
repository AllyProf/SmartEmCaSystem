<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrackingRole
{
    /**
     * Allow only super_admin/ceo/hr to access staff tracking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || (!$user->isSuperAdmin() && !$user->isCeo() && !$user->isHr())) {
            abort(403);
        }

        return $next($request);
    }
}

