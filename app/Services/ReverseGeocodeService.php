<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReverseGeocodeService
{
    public function resolve(float $latitude, float $longitude): ?string
    {
        $lat = round($latitude, 5);
        $lng = round($longitude, 5);

        return Cache::remember("reverse_geocode_{$lat}_{$lng}", 3600, function () use ($lat, $lng) {
            return $this->fetchFromNominatim($lat, $lng);
        });
    }

    private function fetchFromNominatim(float $lat, float $lng): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SmartEmCaSystem/1.0 (staff-attendance; contact@emca.tech)',
                'Accept-Language' => 'en',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $this->formatPlaceName($response->json());
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatPlaceName(?array $data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        $address = $data['address'] ?? [];

        $parts = array_filter([
            $data['name'] ?? null,
            $address['building'] ?? $address['amenity'] ?? $address['office'] ?? null,
            $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? null,
            $address['suburb'] ?? $address['neighbourhood'] ?? $address['quarter'] ?? $address['district'] ?? null,
            $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? null,
            $address['state'] ?? $address['region'] ?? null,
            $address['country'] ?? null,
        ]);

        $unique = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '' && !in_array($part, $unique, true)) {
                $unique[] = $part;
            }
        }

        if ($unique !== []) {
            return implode(', ', array_slice($unique, 0, 4));
        }

        $display = trim((string) ($data['display_name'] ?? ''));

        return $display !== '' ? Str::limit($display, 120, '…') : null;
    }
}
