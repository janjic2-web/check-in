<?php

namespace App\Services;

use App\Models\Company;
use App\Support\QrSignature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

final class QrVerifier
{
    /** @throws RuntimeException */
    public static function verifyAndExtract(Company $company, string $payloadJson, string $hmacB64Url): array
    {
        if (!$company->qr_secret) {
            throw new RuntimeException('QR secret not configured for company');
        }

        // 1) HMAC
        if (!QrSignature::verify($payloadJson, $company->qr_secret, $hmacB64Url)) {
            throw new RuntimeException('Invalid QR signature');
        }

        // 2) Parse payload (canonical JSON expected)
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

        foreach (['company_id','location_id','ts','nonce'] as $k) {
            if (!isset($payload[$k])) throw new RuntimeException("QR payload missing: {$k}");
        }

        if ((int)$payload['company_id'] !== (int)$company->id) {
            throw new RuntimeException('QR payload company mismatch');
        }

        // 3) Anti-replay window 120s (+30s skew)
        $now = now()->getTimestamp();
        if (abs($now - (int)$payload['ts']) > 120) {
            throw new RuntimeException('QR payload expired');
        }

        // 4) Nonce cache (TTL 130s)
        $cacheKey = 'qr_nonce:'.$company->id.':'.$payload['nonce'];
        $created = Cache::add($cacheKey, 1, now()->addSeconds(130)); // set if not exists
        if (!$created) {
            throw new RuntimeException('QR payload replayed');
        }

        // 5) OK
        return $payload;
    }

    /** Helper za generisanje canonical payloada (npr. u alatima) */
    public static function canonicalPayload(int $companyId, int $locationId): string
    {
        return json_encode([
            'company_id'  => $companyId,
            'location_id' => $locationId,
            'ts'          => now()->getTimestamp(),
            'nonce'       => (string) Str::uuid(),
        ], JSON_UNESCAPED_SLASHES);
    }
}
