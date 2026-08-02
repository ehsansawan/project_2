<?php

namespace App\Services;

class GeofencingService
{
    public function isWithinMunicipality(float $lat, float $lng): bool
    {
        $centerLat = config('municipality.center_lat');
        $centerLng = config('municipality.center_lng');
        $radius = config('municipality.allowed_radius_meters');

        $earthRadius = 6371000; 

        $latFrom = deg2rad($centerLat);
        $lonFrom = deg2rad($centerLng);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
            
        $distance = $angle * $earthRadius;

        return $distance <= $radius;
    }
}