<?php

namespace App\Support;

final class QrSignature
{
    public static function verify(string $payload, string $b64Secret, string $givenSigB64Url): bool
    {
        $secret = self::b64url_decode($b64Secret);
        $mac = hash_hmac('sha256', $payload, $secret, true);
        $calc = self::b64url_encode($mac);
        return hash_equals($calc, $givenSigB64Url);
    }

    public static function b64url_encode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64url_decode(string $b64): string
    {
        $b64 = strtr($b64, '-_', '+/');
        return base64_decode($b64);
    }
}
