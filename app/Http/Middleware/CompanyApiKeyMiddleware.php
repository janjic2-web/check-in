<?php

namespace App\Http\Middleware;

use App\Models\CompanyApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CompanyApiKeyMiddleware
{
    /**
     * API key tenant middleware
     * - Bypass: javni endpointi i CORS preflight (OPTIONS)
     * - Enforce: validan x-api-key
     * - Context: ubacuje company, company_id, (opciono plan_code), is_superadmin, api_key
     *
     * Napomena: PROVERU statusa kompanije (active/suspended/expired) radi
     * zasebni middleware EnsureCompanyActive, da se ne duplira logika.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debug log — bezbedno
        Log::info('CompanyApiKeyMiddleware', [
            'apiKeyValue' => $request->header('x-api-key'),
            'path'        => $request->path(),
            'route'       => optional($request->route())->getName(),
        ]);

        // 0) Dozvoli CORS preflight bez x-api-key
        if ($request->isMethod('OPTIONS')) {
            return response()->noContent(204);
        }

        // 0.1) Bypass za javne rute (po imenu rute)
        if ($request->routeIs([
            'v1.healthz',
            'v1.readyz',
            'v1.public.*',
            'v1.billing.webhook',
        ])) {
            return $next($request);
        }

        // 0.2) Fallback na path prefikse, ako rute nisu imenovane
        $path = $request->path(); // obično "api/v1/..."
        foreach ([
            'api/v1/healthz',
            'api/v1/readyz',
            'api/v1/public/',
            'api/v1/billing/webhook',
        ] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return $next($request);
            }
        }

        // 1) Uzmemo API key iz header-a
        $apiKeyValue = $request->header('x-api-key');
        if (!$apiKeyValue) {
            return response()->json([
                'error' => [
                        'code'    => 'UNAUTHENTICATED',
                    'message' => 'x-api-key header is required',
                ],
            ], 401);
        }

        // 2) Nađemo ključ i kompaniju (aktivni)
        // Napomena: kolona je 'key' (test koristi $apiKey->key)
        $apiKey = CompanyApiKey::with('company')
            ->where('key', $apiKeyValue)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$apiKey || !$apiKey->company) {
            return response()->json([
                'error' => [
                    'code'    => 'INVALID_API_KEY',
                    'message' => 'API key is invalid',
                ],
            ], 401);
        }

        $company = $apiKey->company;

        // 3) Ubaci kontekst u request
        $request->attributes->set('company', $company);
        $request->attributes->set('company_id', (int) $company->id);
        if (isset($company->plan_code)) {
            $request->attributes->set('plan_code', (string) $company->plan_code);
        }
        $request->attributes->set('is_superadmin', (bool) ($apiKey->is_superadmin ?? false));
        $request->attributes->set('api_key', $apiKey);

        // 4) Tiho ažuriraj last_used_at
        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }
}
