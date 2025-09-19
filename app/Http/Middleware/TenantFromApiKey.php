<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CompanyApiKey;

class TenantFromApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('x-api-key');
        if (!$key) {
            return response()->json(['code'=>'UNAUTHENTICATED','message'=>'x-api-key header is required'], 401);
        }

        $apiKey = CompanyApiKey::with('company')
            ->where('key', $key)
            ->where('active', true)
            ->first();

        if (!$apiKey) {
            return response()->json(['code'=>'invalid_api_key','message'=>'API key is invalid'], 401);
        }

        $company = $apiKey->company;

        if (!$company || $company->status === 'suspended') {
            return response()->json(['code'=>'company_suspended','message'=>'Company is suspended'], 403);
        }

        // Ubacimo kontekst za ostatak request-a
        $request->attributes->set('company_id', $company->id);
        $request->attributes->set('plan_code', $company->plan_code ?? 'free'); // ako imaš kolonu; u suprotnom stavi 'free'

        return $next($request);
    }
}
