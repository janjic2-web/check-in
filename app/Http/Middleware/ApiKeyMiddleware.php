<?php

namespace App\Http\Middleware;

use App\Models\CompanyApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // BYPASS: javne/health/webhook rute (po imenu)
        $routeName = $request->route()?->getName();
        if ($routeName &&
            (
                $routeName === 'v1.healthz' ||
                str_starts_with($routeName, 'v1.public.') ||
                $routeName === 'v1.billing.webhook'
            )
        ) {
            return $next($request);
        }

        $key = $request->header('x-api-key');

        if (!$key) {
            return $this->unauthorized('Missing API key');
        }

        /** @var CompanyApiKey|null $apiKey */
        $apiKey = CompanyApiKey::query()
            ->with('company')
            ->where('api_key', $key)            // kolona je 'api_key'
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiKey || !$apiKey->company) {
            return $this->unauthorized('Invalid API key');
        }

        // Stavi company kontekst u Request attributes
        $request->attributes->set('company', $apiKey->company);
        $request->attributes->set('company_id', (int) $apiKey->company->id);
        $request->attributes->set('api_key_is_superadmin', (bool) $apiKey->is_superadmin);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        $reqId = request()->headers->get('X-Request-Id') ?? (string) Str::uuid();

        return response()
            ->json([
                'error' => [
                    'code'    => 'UNAUTHORIZED',
                    'message' => $message,
                    'details' => (object)[],
                ],
                'request_id' => $reqId,
            ], 401)
            ->header('X-Request-Id', $reqId);
    }
}
