<?php

namespace App\Modules\Search\Support;

/**
 * Distances for "near my campus" and the approximate public location of a property.
 */
final class Geo
{
    private const EARTH_RADIUS_KM = 6371.0;

    /** Public coordinates snap to a ~500 m grid: the exact address stays private until a rental is confirmed. */
    public const APPROXIMATION_STEP = 0.005;

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }

    public static function approximate(float $coordinate): float
    {
        return round(round($coordinate / self::APPROXIMATION_STEP) * self::APPROXIMATION_STEP, 3);
    }

    /**
     * Bounding box to pre-filter in SQL before the exact distance is computed.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    public static function boundingBox(float $lat, float $lng, float $radiusKm): array
    {
        $dLat = rad2deg($radiusKm / self::EARTH_RADIUS_KM);
        $dLng = rad2deg($radiusKm / (self::EARTH_RADIUS_KM * max(cos(deg2rad($lat)), 0.01)));

        return ['min_lat' => $lat - $dLat, 'max_lat' => $lat + $dLat, 'min_lng' => $lng - $dLng, 'max_lng' => $lng + $dLng];
    }
}
