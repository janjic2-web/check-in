<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebCompanyContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $companyId = session('company_id');
        if (!$companyId) {
            abort(500, 'NO_COMPANY');
        }

        $company = \App\Models\Company::find($companyId);
        if (!$company) {
            abort(500, 'NO_COMPANY');
        }

        if (in_array($company->status, ['suspended', 'expired'])) {
            return response()->json([
                'error' => 'COMPANY_STATUS_INVALID',
                'message' => 'Company status is suspended or expired.'
            ], 403);
        }

        // Propagacija konteksta
        $request->attributes->set('company', $company);
        $request->attributes->set('company_id', $company->id);

        return $next($request);
    }
}
