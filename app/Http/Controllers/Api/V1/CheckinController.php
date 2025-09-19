<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCheckinRequest;
use App\Models\Checkin;
use App\Models\Facility;
use App\Models\Location;
use App\Models\LocationBeacon;
use App\Models\LocationTag;
use App\Services\CheckinService;
use App\Services\QrVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckinController extends Controller
{
    /**
     * GET /api/v1/checkins
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->attributes->get('company_id');
        $perPage   = min(max((int) $request->integer('per_page', 50), 1), 500);

        $q = Checkin::query()
            ->with([
                'user:id,username,name,surname,role',
                'facility:id,name',
                'location:id,name',
            ])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at');

        if ($request->filled('from')) {
            $q->where('created_at', '>=', $request->date('from')->utc());
        }
        if ($request->filled('to')) {
            $q->where('created_at', '<=', $request->date('to')->utc());
        }
        if ($request->filled('user_id')) {
            $q->where('user_id', (int) $request->integer('user_id'));
        }
        if ($request->filled('username')) {
            $username = $request->string('username')->toString();
            $q->whereHas('user', fn (Builder $uq) => $uq->where('username', $username));
        }
        if ($request->filled('facility_id')) {
            $q->where('facility_id', (int) $request->integer('facility_id'));
        }
        if ($request->filled('location_id')) {
            $q->where('location_id', (int) $request->integer('location_id'));
        }
        if ($request->filled('method')) {
            $q->where('method', $request->string('method')->toString());
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }
        if ($request->filled('tag_uid')) {
            $q->where('details->tag_uid', $request->string('tag_uid')->toString());
        }

        return response()->json($q->paginate($perPage));
    }

    /**
     * POST /api/v1/checkin
     */
    public function store(StoreCheckinRequest $request): JsonResponse
    {
        // Kontekst kompanije i korisnika
        $company = $request->attributes->get('company');
        if (!$company) {
            return $this->error('NO_COMPANY', 'Company context missing', 500);
        }
        $companyId = (int) $company->id;

        $user = Auth::user();
        if (!$user) {
            return $this->error('UNAUTHENTICATED', 'User not authenticated', 401);
        }
        if ((int) $user->company_id !== $companyId) {
            return $this->error('FORBIDDEN', 'User does not belong to this company', 403);
        }

        // Bezbedno logovanje zahteva (bez tajni)
        Log::channel('checkins')->info('checkin.request', [
            'company_id' => $companyId,
            'user_id'    => $user->id,
            'ip'         => $request->ip(),
            'payload'    => $request->except(['password', 'token', 'authorization', 'hmac', 'details.hmac']),
            'headers'    => collect($request->headers->all())->except(['authorization', 'cookie'])->all(),
        ]);

        // Osnovni inputi
        $method = $request->string('method')->toString();
        $action = $request->string('action')->toString() ?: 'in';
        $lat    = $request->filled('lat') ? (float) $request->input('lat') : null;
        $lng    = $request->filled('lng') ? (float) $request->input('lng') : null;

        // RANA GPS POLITIKA (company-level)
        if ($company->require_gps_checkin ?? false) {
            $hasFacilityOnly = $request->filled('facility_id')
                && !$request->filled('location_id')
                && !$request->filled('details');

            if ($hasFacilityOnly && ($lat === null || $lng === null)) {
                return $this->error('GPS_REQUIRED', 'GPS coordinates required by company policy', 422);
            }
        }

        // Ako je BLE i rssi je u root payload-u, prebaci ga u details radi konzistentnosti
        if ($method === 'ble' && $request->filled('rssi')) {
            $request->merge([
                'details' => array_merge((array) $request->input('details', []), [
                    'rssi' => $request->input('rssi'),
                ]),
            ]);
        }

        // Podrazumevane politike (ako lokacija ne bude rezolvirana)
        $pol = [
            'require_gps_nfc'        => false,
            'require_gps_ble'        => false,
            'require_gps_qr'         => false,
            'ble_min_rssi'           => -120,
            'anti_spam_min_interval' => 0,
            'min_inout_gap_min'      => 0,
            'radius_m'               => 1000000,
            'allow_outside'          => true,
        ];

        $location   = null;
        $facilityId = null;
        $meta       = null;

        // POLITIKA & LOKACIJA
        if ($request->filled('facility_id') && !$request->filled('details')) {
            // "Simple" tok: poznat facility, bez details
            $facilityId = (int) $request->integer('facility_id');

            $facility = Facility::query()
                ->where('company_id', $companyId)
                ->where('id', $facilityId)
                ->first();

            if (!$facility) {
                return $this->error('NOT_FOUND', 'Facility not found', 404);
            }

            if ($request->filled('location_id')) {
                $location = Location::query()
                    ->where('company_id', $companyId)
                    ->where('facility_id', $facilityId)
                    ->where('id', (int) $request->integer('location_id'))
                    ->first();

                if (!$location) {
                    return $this->error('NOT_FOUND', 'Location not found in this facility', 404);
                }
            }

            if ($location) {
                $pol = CheckinService::resolvePolicies($company, $location);
            }
        } else {
            // Metod sa "details" (nfc/ble/qr)
            try {
                [$location, $meta] = $this->resolveLocation($companyId, $method, $request);
            } catch (ValidationException $ve) {
                // Propustimo ValidationException da Laravel vrati 422 sa detaljima
                throw $ve;
            } catch (RuntimeException $re) {
                // Business greška u detaljima
                return $this->error('INVALID_DETAILS', $re->getMessage(), 422);
            }

            if (!$location) {
                return $this->error('NOT_FOUND', 'Location not found for provided method details', 404);
            }

            $facilityId = (int) $location->facility_id;
            $pol = CheckinService::resolvePolicies($company, $location);
        }

        // METOD-LEVEL GPS politika
        $gpsRequired = match ($method) {
            'nfc' => (bool) $pol['require_gps_nfc'],
            'ble' => (bool) $pol['require_gps_ble'],
            'qr'  => (bool) $pol['require_gps_qr'],
            default => false,
        };

        if ($gpsRequired && ($lat === null || $lng === null)) {
            return $this->error('GPS_REQUIRED', 'GPS coordinates required by policy', 422);
        }

        // BLE RSSI politika
        if ($method === 'ble' && isset($meta['rssi'])) {
            // Slab signal – tretira se kao outside i blokira
            if ((int) $meta['rssi'] <= -85) {
                return $this->error('OUTSIDE_DENIED', 'BLE signal too weak, checkin denied', 403);
            }
            // Minimum RSSI prag
            if ((int) $meta['rssi'] < (int) $pol['ble_min_rssi']) {
                return $this->error('RSSI_TOO_LOW', 'BLE signal below minimum RSSI threshold', 422);
            }
        }

        // Anti-spam / minimalni IN→OUT razmak
        if (CheckinService::violatesAntiSpam($user, $method ?: 'manual', (int) $pol['anti_spam_min_interval'])) {
            return $this->error('ANTISPAM', 'Too frequent check-ins', 429);
        }

        if ($action === 'out') {
            $lastIn = $user->checkins()->where('action', 'in')->orderByDesc('id')->first();
            if (
                $lastIn
                && (int) $pol['min_inout_gap_min'] > 0
                && now()->diffInMinutes($lastIn->created_at) < (int) $pol['min_inout_gap_min']
            ) {
                return $this->error('MIN_GAP', 'Minimum IN→OUT gap not satisfied', 429);
            }
        }

        // Geofence
        $distance = 0;
        $status   = 'inside';
        if (
            $lat !== null && $lng !== null
            && $location?->lat !== null && $location?->lng !== null
        ) {
            $distance = CheckinService::distanceMeters(
                $lat,
                $lng,
                (float) $location->lat,
                (float) $location->lng
            );
            $status   = ($distance <= (int) $pol['radius_m']) ? 'inside' : 'outside';
        }
        if ($status === 'outside' && !$pol['allow_outside']) {
            return $this->error('OUTSIDE_DENIED', 'Outside geofence not allowed by policy', 403);
        }

        // Idempotentnost
        $clientEventId = $request->string('client_event_id')->toString() ?: null;
        if ($clientEventId) {
            $existing = Checkin::query()
                ->where('company_id', $companyId)
                ->where('client_event_id', $clientEventId)
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => [
                        'code'    => 'IDEMPOTENT',
                        'message' => 'Check-in already processed',
                    ],
                    'checkin_id' => $existing->id,
                    'data'       => $this->resource($existing),
                ], 409);
            }
        }

        // Upis audit event-a
        $checkin = null;
        DB::transaction(function () use (
            $request,
            $companyId,
            $user,
            $location,
            $facilityId,
            $method,
            $action,
            $meta,
            &$checkin
        ) {
            $checkin = Checkin::create([
                'company_id'      => $companyId,
                'facility_id'     => $facilityId ?? $location?->facility_id,
                'location_id'     => $location?->id, // može biti null u “simple” toku
                'user_id'         => $user->id,
                'method'          => $method ?: 'manual',
                'action'          => $action,
                'details'         => $meta ?: null,
                'device_id'       => $request->input('device_id'),
                'platform'        => $request->input('platform'),
                'app_version'     => $request->input('app_version'),
                'client_event_id' => $request->string('client_event_id')->toString() ?: null,
            ]);
        });

        // Session pairing (attendance_sessions)
        $sessionId = null;
        $attendanceSession = null;

        if ($action === 'in') {
            $sessionId = \App\Services\SessionPairingService::handleIn(
                $companyId,
                $user->id,
                $facilityId,
                $location?->id,
                $meta
            );
            $attendanceSession = \App\Models\AttendanceSession::forUser($user)->find($sessionId);
        } elseif ($action === 'out') {
            $sessionId = \App\Services\SessionPairingService::handleOut(
                $companyId,
                $user->id,
                $facilityId,
                $location?->id,
                $meta,
                $request->input('session_id'),
                (int) $pol['min_inout_gap_min']
            );
            $attendanceSession = \App\Models\AttendanceSession::forUser($user)->find($sessionId);
        }

        return response()->json([
            'message'    => 'Check-in recorded',
            'checkin_id' => $checkin->id,
            'session_id' => $sessionId,
            'attendance_session' => $attendanceSession ? [
                'id'              => $attendanceSession->id,
                'in_at'           => $attendanceSession->in_at,
                'out_at'          => $attendanceSession->out_at,
                'duration_sec'    => $attendanceSession->duration_sec,
                'status'          => $attendanceSession->status,
                'under_threshold' => $attendanceSession->under_threshold,
            ] : null,
            'data'       => array_merge($this->resource($checkin), ['status' => $status]),
        ]);
    }

    /** @return array{0: ?Location, 1: ?array} */
    private function resolveLocation(int $companyId, string $method, Request $request): array
    {
        if ($method === 'nfc') {
            $tagUid = $request->input('details.tag_uid');
            if (!$tagUid) {
                return [null, null];
            }

            $tag = LocationTag::query()
                ->where('company_id', $companyId)
                ->where('tag_uid', $tagUid)
                ->first();
            if (!$tag) {
                return [null, null];
            }

            $loc = Location::query()
                ->where('company_id', $companyId)
                ->where('id', $tag->location_id)
                ->first();

            return [$loc, ['tag_uid' => $tagUid]];
        }

        if ($method === 'ble') {
            $uuid  = (string) $request->input('details.uuid');
            $major = (int) $request->input('details.major');
            $minor = (int) $request->input('details.minor');
            $rssi  = (int) $request->input('details.rssi');

            if (!$uuid) {
                return [null, null];
            }

            $beacon = LocationBeacon::query()
                ->where('company_id', $companyId)
                ->where('uuid', $uuid)
                ->where('major', $major)
                ->where('minor', $minor)
                ->where('active', true)
                ->first();
            if (!$beacon) {
                return [null, null];
            }

            $loc = Location::query()
                ->where('company_id', $companyId)
                ->where('id', $beacon->location_id)
                ->first();

            return [$loc, ['uuid' => $uuid, 'major' => $major, 'minor' => $minor, 'rssi' => $rssi]];
        }

        if ($method === 'qr') {
            $payload = (string) $request->input('details.qr_payload');
            $hmac    = (string) $request->input('details.hmac');

            try {
                $data = QrVerifier::verifyAndExtract($request->attributes->get('company'), $payload, $hmac);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages(['details.hmac' => [$e->getMessage()]]);
            }

            $loc = Location::query()
                ->where('company_id', $companyId)
                ->where('id', (int) $data['location_id'])
                ->first();

            return [$loc, ['qr_payload' => $payload, 'hmac' => $hmac]];
        }

        return [null, null];
    }

    private function resource(Checkin $c): array
    {
        return [
            'id'          => $c->id,
            'status'      => $c->status,
            'distance_m'  => $c->distance_m,
            'method'      => $c->method,
            'action'      => $c->action,
            'location_id' => $c->location_id,
            'facility_id' => $c->facility_id,
            'ts'          => $c->created_at?->toISOString(),
        ];
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        $reqId = request()->headers->get('X-Request-Id') ?? (string) Str::uuid();

        return response()
            ->json(['error' => ['code' => $code, 'message' => $message], 'request_id' => $reqId], $status)
            ->header('X-Request-Id', $reqId);
    }
}
