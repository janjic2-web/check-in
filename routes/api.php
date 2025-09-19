<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckinController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\UserManagementController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\LocationsController;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| API Routes (/api) — v1 grupa
| Napomena:
| - Public rute su imenovane kao v1.healthz, v1.public.* i v1.billing.webhook,
|   pa ih CompanyApiKeyMiddleware bypass-uje po imenu.
| - Za rate limit koristimo JsonThrottle alias: jsonthrottle:<limiter>.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->name('v1.')
    ->middleware(['company.api', 'company.active'])
    ->group(function () {

        // -------- PUBLIC (bypass x-api-key & company status) --------

        Route::get('/healthz', fn () => response()->json(['status' => 'ok']))
            ->name('healthz');

        // v1.public.readyz pokriva bypass kroz pattern v1.public.*
        Route::get('/readyz', fn () => response()->json(['ready' => true]))
            ->name('public.readyz');

        Route::get('/public/ping', fn () => response()->json(['pong' => true]))
            ->name('public.ping');

        Route::any('/public/debug/headers', function (Request $r) {
            return response()->json([
                'headers' => $r->headers->all(),
                'payload' => $r->all(),
                'method'  => $r->method(),
            ]);
        })->name('public.debug.headers');

        // Stripe webhook (public bypass po imenu)
        Route::post('/billing/webhook', [StripeWebhookController::class, 'handle'])
            ->name('billing.webhook');

        // -------- AUTH (zahteva x-api-key & aktivnu kompaniju; JWT samo na protected) --------
        Route::prefix('auth')->name('auth.')->group(function () {
            // Login nije public (traži x-api-key)
            Route::post('/login', [AuthController::class, 'login'])
                ->name('login')
                ->middleware(\App\Http\Middleware\JsonThrottle::class);

            // Zaštićene JWT rute
            Route::middleware('auth:api')->group(function () {
                Route::post('/logout',  [AuthController::class, 'logout'])
                    ->name('logout')->middleware(\App\Http\Middleware\JsonThrottle::class);

                Route::post('/refresh', [AuthController::class, 'refresh'])
                    ->name('refresh')->middleware(\App\Http\Middleware\JsonThrottle::class);

                Route::get('/me',       [AuthController::class, 'me'])
                    ->name('me')->middleware(\App\Http\Middleware\JsonThrottle::class);
            });
        });

        // -------- PROTECTED API (x-api-key + active company + JWT) --------
    Route::middleware(['auth:api', \App\Http\Middleware\JsonThrottle::class])->group(function () {

            // Check-in rute
            Route::get('/checkins', [CheckinController::class, 'index'])
                ->name('checkins.index')
                ->middleware(\App\Http\Middleware\JsonThrottle::class);

            Route::post('/checkin', [CheckinController::class, 'store'])
                ->name('checkins.store')
                ->middleware([\App\Http\Middleware\JsonThrottle::class . ':3,1']);

            Route::post('/checkins/bulk', [CheckinController::class, 'storeBulk'])
                ->name('checkins.bulk')
                ->middleware(\App\Http\Middleware\JsonThrottle::class);

            // Zaštićen debug echo
            Route::post('/debug/echo', function (Request $r) {
                return response()->json([
                    'headers' => $r->headers->all(),
                    'input'   => $r->all(),
                    'raw'     => $r->getContent(),
                ]);
            })->name('debug.echo')->middleware(\App\Http\Middleware\JsonThrottle::class);

            // -------- FACILITY API --------
            Route::post('/facilities',        [FacilityController::class, 'store'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::post('/facilities',        [FacilityController::class, 'store'])->middleware('json_throttle:api');
            Route::post('/facilities',        [FacilityController::class, 'store'])->middleware('json_throttle');
            Route::post('/facilities',        [FacilityController::class, 'store'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::patch('/facilities/{id}',  [FacilityController::class, 'update'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::patch('/facilities/{id}',  [FacilityController::class, 'update'])->middleware('json_throttle:api');
            Route::patch('/facilities/{id}',  [FacilityController::class, 'update'])->middleware('json_throttle');
            Route::patch('/facilities/{id}',  [FacilityController::class, 'update'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::delete('/facilities/{id}', [FacilityController::class, 'destroy'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::delete('/facilities/{id}', [FacilityController::class, 'destroy'])->middleware('json_throttle:api');
            Route::delete('/facilities/{id}', [FacilityController::class, 'destroy'])->middleware('json_throttle');
            Route::delete('/facilities/{id}', [FacilityController::class, 'destroy'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::get('/facilities/{id}',    [FacilityController::class, 'show'])->middleware(\App\Http\Middleware\JsonThrottle::class);
            Route::get('/facilities/{id}',    [FacilityController::class, 'show'])->middleware('json_throttle:api');
            Route::get('/facilities/{id}',    [FacilityController::class, 'show'])->middleware('json_throttle');
            Route::get('/facilities/{id}',    [FacilityController::class, 'show'])->middleware(\App\Http\Middleware\JsonThrottle::class);

            // Location identifikatori (NFC, BLE, QR)
            Route::post('/locations/{id}/nfc-tags', [LocationsController::class, 'addNfcTag']);
            Route::patch('/locations/{id}/nfc-tags/{tagId}', [LocationsController::class, 'updateNfcTag']);
            Route::delete('/locations/{id}/nfc-tags/{tagId}', [LocationsController::class, 'deleteNfcTag']);

            Route::post('/locations/{id}/ble-beacons', [LocationsController::class, 'addBleBeacon']);
            Route::patch('/locations/{id}/ble-beacons/{beaconId}', [LocationsController::class, 'updateBleBeacon']);
            Route::delete('/locations/{id}/ble-beacons/{beaconId}', [LocationsController::class, 'deleteBleBeacon']);

            Route::post('/locations/{id}/qr-codes', [LocationsController::class, 'addQrCode']);
            Route::patch('/locations/{id}/qr-codes/{qrId}', [LocationsController::class, 'updateQrCode']);
            Route::delete('/locations/{id}/qr-codes/{qrId}', [LocationsController::class, 'deleteQrCode']);
        });

    // Export (generic: /exports/{resource})
    Route::get('/exports/{resource}', [ExportController::class, 'exportSync']);
    });
