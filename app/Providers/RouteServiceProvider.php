<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Rate limiter za POST /api/v1/checkin (throttle:checkin)
         * Grupisanje po: company_id + (user_id ili device_id ili IP) da jedan klijent ne uguši celu firmu.
         */
        RateLimiter::for('checkin', function (Request $request) {
            $companyId  = (string) ($request->attributes->get('company_id') ?? 'anon');
            $userPart   = auth()->id() ?: 'guest';
            $devicePart = $request->input('device_id')
                ?: $request->header('X-Device-Id')
                ?: $request->ip();

            $key = implode(':', ['checkin', $companyId, $userPart, $devicePart]);

            // Podesi broj prema potrebi
            return [
                Limit::perMinute(12)->by($key),
            ];
        });

        /**
         * Definiši standardni API rate limiter (throttle:api)
         */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        /**
         * (Opcionalno) Globalni API limiter (throttle:api-global)
         */
        RateLimiter::for('api-global', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        /**
         * (Opcionalno) Lagani limiter za secure-ping (throttle:secure-ping)
         */
        RateLimiter::for('secure-ping', function (Request $request) {
            $cid = (string) ($request->attributes->get('company_id') ?? 'anon');
            return [
                Limit::perMinute(30)->by('ping:' . $cid),
            ];
        });
    }
}
