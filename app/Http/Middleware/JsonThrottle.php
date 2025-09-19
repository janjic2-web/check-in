<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;

class JsonThrottle extends ThrottleRequests
{
    public function handle($request, $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        try {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];
            $retryAfter = $headers['Retry-After'] ?? 60;
            $reqId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();
            return response()->json([
                'error' => [
                    'code'    => 'RATE_LIMITED',
                    'message' => 'Too many requests. Try again later.',
                    'details' => ['retry_after_seconds' => $retryAfter],
                ],
                'request_id' => $reqId,
            ], 429, array_merge($headers, [
                'X-Request-Id' => $reqId,
            ]));
        }
    }
}
