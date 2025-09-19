<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureCompanyActive
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('EnsureCompanyActive', [
            'company' => $request->attributes->get('company'),
            'company_id' => optional($request->attributes->get('company'))->id,
            'path' => $request->path(),
            'route' => optional($request->route())->getName(),
        ]);
        $company = $request->attributes->get('company');
        Log::info('EnsureCompanyActive', [
            'company_id' => $company ? $company->id : null,
            'status' => $company ? $company->status : null,
        ]);
        // Bypass za javne rute po IMENU (mora se poklapati sa routes/api.php)
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

        $company = $request->attributes->get('company');

        if (!$company || in_array($company->status, ['suspended','expired'], true)) {
            $reqId = $request->headers->get('X-Request-Id') ?? (string) Str::uuid();

            return response()
                ->json([
                    'error' => [
                        'code'    => 'SUSPENDED',
                        'message' => 'Company is suspended or expired',
                        'details' => (object)[],
                    ],
                    'request_id' => $reqId,
                ], 403)
                ->header('X-Request-Id', $reqId);
        }

        return $next($request);
    }
}
