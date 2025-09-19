<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Redovni Laravel global middlewares
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // (Opc.) \App\Http\Middleware\TrustHosts::class,
    ];

    /**
     * Route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // Tenancy i status kompanije za sve API rute
            'company.api',
            'company.active',

            // Rate limit
            'throttle:api',

            // Route model binding
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

            // Napomena: 'auth:api' dodaj po potrebi NA NIVOU RUTE,
            // jer postoje javne API rute (healthz/webhook/signup).
        ],
    ];

    /**
     * Route middleware aliases.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
    'jsonthrottle' => \App\Http\Middleware\JsonThrottle::class,
    'company.web' => \App\Http\Middleware\WebCompanyContextMiddleware::class,
        // Tenancy chain
        'company.api'    => \App\Http\Middleware\CompanyApiKeyMiddleware::class,
        'company.active' => \App\Http\Middleware\EnsureCompanyActive::class,

    // API key middleware
    'apikey' => \App\Http\Middleware\ApiKeyMiddleware::class,

        // Standardni Laravel aliasi (koristi po potrebi)
        'auth'       => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'        => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'      => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed'     => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle'   => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified'   => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    'forcejsonthrottle' => \App\Http\Middleware\ForceJsonThrottleResponse::class,
    'jsonthrottle' => \App\Http\Middleware\JsonThrottle::class,
    ];
}
