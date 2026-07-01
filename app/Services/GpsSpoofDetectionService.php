<?php

namespace App\Services;

class GpsSpoofDetectionService
{
    /**
     * @param  array<int, array{lat: float, lng: float, accuracy: ?float, speed: ?float, timestamp: int}>  $recentPoints
     * @return array{flagged: bool, reasons: array<int, string>}
     */
    public function analyze(
        float $latitude,
        float $longitude,
        ?float $accuracy,
        ?float $speed,
        int $timestamp,
        array $recentPoints = []
    ): array {
        $reasons = [];

        if ($accuracy !== null && $accuracy <= 0) {
            $reasons[] = 'invalid_accuracy';
        }

        if ($accuracy !== null && $accuracy > 500) {
            $reasons[] = 'very_low_accuracy';
        }

        if ($speed !== null && $speed > 55) {
            $reasons[] = 'impossible_speed';
        }

        if (!empty($recentPoints)) {
            $last = end($recentPoints);
            if (is_array($last)) {
                $dt = max(1, ($timestamp - (int) ($last['timestamp'] ?? $timestamp)) / 1000);
                $dist = $this->distanceMeters(
                    (float) $last['lat'],
                    (float) $last['lng'],
                    $latitude,
                    $longitude
                );
                $impliedSpeed = $dist / $dt;

                if ($impliedSpeed > 45) {
                    $reasons[] = 'position_jump';
                }

                if ($dist < 1 && $dt > 30 && ($accuracy !== null && $accuracy < 5)) {
                    $reasons[] = 'frozen_coordinates';
                }
            }
        }

        if ($latitude === 0.0 && $longitude === 0.0) {
            $reasons[] = 'null_island';
        }

        return [
            'flagged' => !empty($reasons),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
