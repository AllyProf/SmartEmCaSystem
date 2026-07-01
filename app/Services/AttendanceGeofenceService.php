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
}
