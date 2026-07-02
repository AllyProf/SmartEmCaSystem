<?php

namespace App\Services;

class AttendanceGeofenceService
{
    public function __construct(
        protected AttendanceSettingService $settings
    ) {}

    public function hqLatitude(): float
    {
        return $this->settings->hqLatitude();
    }

    public function hqLongitude(): float
    {
        return $this->settings->hqLongitude();
    }

    public function radiusMeters(): float
    {
        return $this->settings->geofenceRadius();
    }

    public function distanceFromHq(float $latitude, float $longitude): float
    {
        return $this->calculateDistanceInMeters(
            $this->hqLatitude(),
            $this->hqLongitude(),
            $latitude,
            $longitude
        );
    }

    public function isWithinHq(float $latitude, float $longitude): bool
    {
        return $this->distanceFromHq($latitude, $longitude) <= $this->radiusMeters();
    }

    /**
     * Map display point just outside the HQ geofence, along the ray from HQ center through the source point.
     *
     * @return array{lat: float, lng: float}
     */
    public function pointOutsideGeofence(float $latitude, float $longitude, float $bufferMeters = 35): array
    {
        $hqLat = $this->hqLatitude();
        $hqLng = $this->hqLongitude();
        $distance = $this->distanceFromHq($latitude, $longitude);

        if ($distance > $this->radiusMeters()) {
            return ['lat' => $latitude, 'lng' => $longitude];
        }

        $bearing = $distance < 1.0
            ? 90.0
            : $this->bearingDegrees($hqLat, $hqLng, $latitude, $longitude);

        return $this->destinationPoint($hqLat, $hqLng, $bearing, $this->radiusMeters() + $bufferMeters);
    }

    public function mapConfig(): array
    {
        return $this->settings->mapConfig();
    }

    private function calculateDistanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function bearingDegrees(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLon = deg2rad($lon2 - $lon1);

        $y = sin($deltaLon) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($deltaLon);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function destinationPoint(float $lat, float $lng, float $bearingDeg, float $distanceMeters): array
    {
        $earthRadius = 6371000;
        $bearing = deg2rad($bearingDeg);
        $lat1 = deg2rad($lat);
        $lng1 = deg2rad($lng);
        $angular = $distanceMeters / $earthRadius;

        $lat2 = asin(sin($lat1) * cos($angular) + cos($lat1) * sin($angular) * cos($bearing));
        $lng2 = $lng1 + atan2(
            sin($bearing) * sin($angular) * cos($lat1),
            cos($angular) - sin($lat1) * sin($lat2)
        );

        return ['lat' => rad2deg($lat2), 'lng' => rad2deg($lng2)];
    }
}
