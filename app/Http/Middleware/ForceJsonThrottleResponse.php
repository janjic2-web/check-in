
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class ForceJsonThrottleResponse
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (ThrottleRequestsException $e) {
            $reqId = $request->headers->get('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => $e->getMessage() ?: 'Rate limit exceeded',
                ],
                'request_id' => $reqId,
            ], 429)->header('X-Request-Id', $reqId);
        }
    }
}
