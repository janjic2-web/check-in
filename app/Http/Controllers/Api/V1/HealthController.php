<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        try { DB::select('SELECT 1'); $db = 'ok'; }
        catch (\Throwable $e) { $db = 'error: '.$e->getMessage(); }

        return response()->json([
            'ok'   => true,
            'app'  => 'checkin-app',
            'db'   => $db,
            'time' => now()->toIso8601String(),
        ]);
    }
}