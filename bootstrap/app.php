<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias-i (za korišćenje po potrebi u rutama)
        $middleware->alias([
            'apikey'               => \App\Http\Middleware\CompanyApiKeyMiddleware::class,
            'company.api'          => \App\Http\Middleware\CompanyApiKeyMiddleware::class, // legacy
            'company.active'       => \App\Http\Middleware\EnsureCompanyActive::class,
            'ensure.company.active'=> \App\Http\Middleware\EnsureCompanyActive::class,      // legacy
        ]);

        // Garantovano ubacivanje u 'api' grupu po klasama (redosled bitan)
        $middleware->group('api', [
            \App\Http\Middleware\CompanyApiKeyMiddleware::class,
            \App\Http\Middleware\EnsureCompanyActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // mesto za globalne exception handlere po potrebi
    })
    ->create();

$middleware->alias([
    'apikey'              => \App\Http\Middleware\CompanyApiKeyMiddleware::class,
    'company.api'         => \App\Http\Middleware\CompanyApiKeyMiddleware::class,
    'company.active'      => \App\Http\Middleware\EnsureCompanyActive::class,
    'ensure.company.active' => \App\Http\Middleware\EnsureCompanyActive::class,
    'jsonthrottle'        => \App\Http\Middleware\JsonThrottle::class,
    'forcejsonthrottle'   => \App\Http\Middleware\JsonThrottle::class,
]);