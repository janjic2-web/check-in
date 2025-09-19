<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Location;
use Carbon\Carbon;

class CheckinService
{
    /**
     * Provera anti-spam intervala.
     * Vraća TRUE ako treba blokirati (tj. poslednji check-in je previše skoro).
     */
    public static function violatesAntiSpam($user, string $method, int $minInterval): bool
    {
        if (!$user || !$user->id || $minInterval <= 0) {
            return false;
        }

        $last = Checkin::query()
            ->where('user_id', $user->id)
            ->when($method, fn ($q) => $q->where('method', $method))
            ->orderByDesc('id')
            ->first();

        if (!$last || !$last->created_at) {
            return false;
        }

        return $last->created_at->gt(Carbon::now()->subMinutes($minInterval));
    }

    /**
     * Spaja politike sa nivoa company → facility → location uz podrazumevane vrednosti.
     * Radi i ako su politike kolone na modelu, ili JSON u polju "policy".
     *
     * @param  mixed     $company  Eloquent model kompanije (ili stdClass)
     * @param  Location  $location
     * @return array{
     *   require_gps_nfc: bool,
     *   require_gps_ble: bool,
     *   require_gps_qr: bool,
     *   ble_min_rssi: int,
     *   anti_spam_min_interval: int,
     *   min_inout_gap_min: int,
     *   radius_m: int,
     *   allow_outside: bool
     * }
     */
    public static function resolvePolicies($company, Location $location): array
    {
        $defaults = [
            'require_gps_nfc'        => false,
            'require_gps_ble'        => false,
            'require_gps_qr'         => false,
            'ble_min_rssi'           => -90,
            'anti_spam_min_interval' => 0,   // u minutima; 0 = nema ograničenja
            'min_inout_gap_min'      => 0,   // minimalni razmak između IN → OUT
            'radius_m'               => 100, // geofence poluprečnik
            'allow_outside'          => false,
        ];

        $facility = $location->facility ?? null;

        $keys = array_keys($defaults);
        $out  = [];

        foreach ($keys as $key) {
            $val = self::pick($location, $key,
                self::pick($facility, $key,
                    self::pick($company, $key, $defaults[$key])
                )
            );

            // type-cast zbog konzistentnosti
            $out[$key] = match ($key) {
                'require_gps_nfc', 'require_gps_ble', 'require_gps_qr', 'allow_outside' => (bool) $val,
                'ble_min_rssi', 'anti_spam_min_interval', 'min_inout_gap_min', 'radius_m' => (int) $val,
                default => $val,
            };
        }

        return $out;
    }

    /**
     * Haversine distanca u metrima.
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0; // m
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Bezbedno „čitanje“ ključa iz različitih struktura:
     * - object property (npr. $model->require_gps_qr)
     * - $object->policy (array/json) → ['require_gps_qr' => ...]
     * - plain array
     */
    private static function pick($source, string $key, $default)
    {
        if ($source === null) {
            return $default;
        }

        // Ako je niz
        if (is_array($source)) {
            return array_key_exists($key, $source) ? $source[$key] : $default;
        }

        // Ako je objekat sa direktnim properti-jem
        if (is_object($source) && isset($source->$key)) {
            return $source->$key;
        }

        // Ako objekat ima "policy" polje (array ili JSON)
        if (is_object($source) && isset($source->policy)) {
            $policy = $source->policy;

            if (is_string($policy)) {
                $decoded = json_decode($policy, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return array_key_exists($key, $decoded) ? $decoded[$key] : $default;
                }
            }

            if (is_array($policy) && array_key_exists($key, $policy)) {
                return $policy[$key];
            }
        }

        return $default;
    }
}
