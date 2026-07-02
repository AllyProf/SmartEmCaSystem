<?php

namespace App\Http\Controllers;

use App\Models\StaffLiveLocation;
use Illuminate\Http\Request;

class StaffTrackingController extends Controller
{
    public function index()
    {
        return view('tracking.staff-live');
    }

    public function data(Request $request)
    {
        $minutes = max(1, min(180, (int) $request->query('minutes', 30)));
        $since = now()->subMinutes($minutes);

        $latest = StaffLiveLocation::with('user')
            ->where('captured_at', '>=', $since)
            ->orderByDesc('captured_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->first())
            ->values()
            ->map(function ($loc) {
                return [
                    'user_id' => $loc->user_id,
                    'name' => $loc->user?->name,
                    'email' => $loc->user?->email,
                    'role' => $loc->user?->role,
                    'lat' => (float) $loc->latitude,
                    'lng' => (float) $loc->longitude,
                    'accuracy' => $loc->accuracy !== null ? (int) $loc->accuracy : null,
                    'speed' => $loc->speed !== null ? (float) $loc->speed : null,
                    'heading' => $loc->heading !== null ? (int) $loc->heading : null,
                    'travel_mode' => $loc->travel_mode,
                    'captured_at' => $loc->captured_at?->toIso8601String(),
                    'last_seen_seconds' => $loc->captured_at ? now()->diffInSeconds($loc->captured_at) : null,
                ];
            });

        return response()->json([
            'since_minutes' => $minutes,
            'server_time' => now()->toIso8601String(),
            'latest' => $latest,
        ]);
    }
}

