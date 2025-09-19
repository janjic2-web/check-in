<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /**
         * Standardni API limiter koji mnoge rute očekuju: throttle:api
         * Identifikuje korisnika ako je ulogovan; u suprotnom IP.
         */
        RateLimiter::for('api', function (Request $request) {
            $id = optional($request->user())->getAuthIdentifier();
            return Limit::perMinute(60)->by($id ?: $request->ip());
        });

        /**
         * Limiter po kompaniji (x-api-key): throttle:company
         * Štiti rute koje se pozivaju iz mobilne app-a / integracija.
         */
        RateLimiter::for('company', function (Request $request) {
            $key = $request->header('x-api-key') ?: $request->ip();
            return [
                Limit::perMinute(120)->by($key),
                Limit::perSecond(5)->by($key), // anti-burst
            ];
        });

        /**
         * Tvoj specifični limiter za /checkin: throttle:checkin
         * Ključ je kombinacija company_id + user + device/ip.
         */
        RateLimiter::for('checkin', function (Request $request) {
            $companyId  = (string) ($request->attributes->get('company_id') ?? 'anon');
            $userPart   = auth()->id() ?: 'guest';
            $devicePart = $request->input('device_id')
                ?: $request->header('X-Device-Id')
                ?: $request->ip();

            $key = implode(':', ['checkin', $companyId, $userPart, $devicePart]);

            return [ Limit::perMinute(12)->by($key) ];
        });

        // (opciono) globalni API limiter za druge rute
        RateLimiter::for('api-global', function (Request $request) {
            return [ Limit::perMinute(60)->by($request->ip()) ];
        });

        // (opciono) secure-ping
        RateLimiter::for('secure-ping', function (Request $request) {
            $cid = (string) ($request->attributes->get('company_id') ?? 'anon');
            return [ Limit::perMinute(30)->by('ping:'.$cid) ];
        });
    }
}
