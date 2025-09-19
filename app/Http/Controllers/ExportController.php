<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;

class ExportController extends Controller
{
    /**
     * Whitelist kolona po resource-u (generički exporti).
     * Napomena: 'attendance-sessions' se obrađuje specijalno, nije deo ovog whitelista.
     */
    protected $whitelists = [
        'users' => [
            // Superadmin ne može eksportovati lične podatke korisnika — kontrolisaće se u getFilteredQuery
            'id', 'company_id', 'username', 'email', 'name', 'surname', 'role', 'phone',
            'employee_id', 'status', 'email_verified_at', 'created_at', 'updated_at', 'facility_display_name'
        ],
        'companies' => [
            'id', 'display_name', 'legal_name', 'vat_pib', 'address', 'city', 'zip', 'country',
            'timezone', 'language', 'status', 'expires_at', 'allow_outside', 'default_radius_m',
            'created_at', 'updated_at', 'facilities_count', 'users_count'
        ],
        'facilities' => [
            'id', 'company_id', 'display_name', 'address', 'city', 'zip', 'country',
            'status', 'created_at', 'updated_at', 'users_count'
        ],
    ];

    /**
     * GET /api/exports/{resource}
     * Sinhroni export (CSV ili XLSX).
     */
    public function exportSync(Request $request, string $resource)
    {
        $user = Auth::user();
        $company = optional($user)->company;
        $companySlug = $company->display_name ?? $company->name ?? 'company';
        $facility = optional($user->facilities()->first())->display_name;
        $date = Carbon::now()->format('Ymd_His');
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        $format = strtolower($request->get('format', 'csv'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'csv';
        }

        $filename = sprintf(
            '%s_%s_%s_%s.%s',
            $resource,
            $facility ?: $companySlug,
            $date,
            $requestId,
            $format
        );

        // Specijalni slučaj: attendance-sessions (sa svojim kolonama, filtrima i CSV streamingom)
        if ($resource === 'attendance-sessions') {
            return $this->exportAttendanceSessions($request, $user, $company, $filename, $format);
        }

        // Generički export za ostale resurse preko GenericExport (XLSX i CSV oba idu kroz Excel::download)
        $query = $this->getFilteredQuery($user, $resource, $request->all());
        $columns = $this->whitelists[$resource] ?? [];
        if (empty($columns)) {
            return response()->json([
                'error' => ['code' => 'UNSUPPORTED_RESOURCE', 'message' => 'Unsupported export resource.']
            ], 422);
        }

        $data = $query->get($columns);

        // Poseban tretman: users → dodaj naziv(e) facility-ja
        if ($resource === 'users' && in_array('facility_display_name', $columns, true)) {
            $data->transform(function ($u) {
                $facilityNames = $u->facilities()->pluck('display_name')->toArray();
                $u['facility_display_name'] = implode(', ', $facilityNames);
                return $u;
            });
        }

        // Audit log
        Log::info('Export sync (generic)', [
            'user_id' => $user->id ?? null,
            'resource' => $resource,
            'request_id' => $requestId,
            'filters' => $request->all(),
            'format' => $format,
        ]);

        $export = new GenericExport($data, $columns);
        /** @var BinaryFileResponse $resp */
        $resp = Excel::download($export, $filename, $format === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        // (CSV preko Excel-a dodaće BOM i header red; dovoljno dobro ako ne želiš stream)
        return $resp;
    }

    /**
     * POST /api/exports/{resource}
     * Async queue (ostavljeno kao tvoj postojeći mehanizam).
     */
    public function exportAsync(Request $request, string $resource)
    {
        $user = Auth::user();
        $company = optional($user)->company;
        $companySlug = $company->display_name ?? $company->name ?? 'company';
        $facility = optional($user->facilities()->first())->display_name;
        $date = Carbon::now()->format('Ymd_His');
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();
        $format = strtolower($request->get('format', 'csv'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'csv';
        }
        $filename = sprintf('%s_%s_%s_%s.%s', $resource, $facility ?: $companySlug, $date, $requestId, $format);

        // Za sada: ostavljamo postojeći Job, ako ga imaš u kodu; u suprotnom vrati 501
        if (!class_exists(\App\Jobs\ExportJob::class)) {
            return response()->json([
                'error' => ['code' => 'NOT_IMPLEMENTED', 'message' => 'Async export job not found.']
            ], 501)->header('X-Request-Id', $requestId);
        }

        // Priprema dataset-a (oprez za velike setove — možda je bolje da Job sam formira query)
        if ($resource === 'attendance-sessions') {
            // Prosledi parametre Jobu da sam odradi (bolje za velike setove)
            Queue::push(new \App\Jobs\ExportJob(
                /* payload */ [
                    'resource' => $resource,
                    'filters'  => $request->all(),
                    'format'   => $format,
                    'company_id' => $company->id ?? null,
                    'user_id'    => $user->id ?? null,
                ],
                /* columns */ $this->attendanceHeaders((bool) ((int) ($request->get('include_meta', 1)))),
                /* filename */ $filename,
                /* owner_user_id */ $user->id ?? null,
                /* request_id */ $requestId
            ));
        } else {
            $query = $this->getFilteredQuery($user, $resource, $request->all());
            $columns = $this->whitelists[$resource] ?? [];
            $data = $columns ? $query->get($columns) : collect();
            Queue::push(new \App\Jobs\ExportJob($data, $columns, $filename, $user->id ?? null, $requestId, $format));
        }

        Log::info('Export async queued', [
            'user_id'    => $user->id ?? null,
            'resource'   => $resource,
            'request_id' => $requestId,
            'filters'    => $request->all(),
            'format'     => $format,
        ]);

        return response()->json([
            'message'    => 'Export job queued',
            'request_id' => $requestId
        ], 202)->header('X-Request-Id', $requestId);
    }

    /* ============================================================
     *           ATTENDANCE SESSIONS — CSV & XLSX EXPORT
     * ============================================================ */

    /**
     * Specijalizovani export za attendance-sessions (CSV stream ili XLSX preko GenericExport).
     */
    private function exportAttendanceSessions(Request $request, $authUser, $company, string $filename, string $format)
    {
        // Ograničenje: maksimalan period za eksport je 1 godina
        $from = !empty($request->get('from')) ? Carbon::parse($request->get('from')) : null;
        $to = !empty($request->get('to')) ? Carbon::parse($request->get('to')) : null;
        if ($from && $to && $from->diffInDays($to) > 366) {
            return response()->json([
                'error' => [
                    'code' => 'PERIOD_TOO_LARGE',
                    'message' => 'Maximum export period is 1 year. Please reduce the date range.'
                ]
            ], 422);
        }
        $companyId = (int) ($company->id ?? 0);
        if (!$authUser || !$companyId) {
            return response()->json([
                'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Missing company or user context.']
            ], 401);
        }

        $tz = $company->timezone ?: 'UTC';
        $includeMeta = (bool) ((int) $request->get('include_meta', 1));

        // Validacija filtera
        $validated = $request->validate([
            'from'              => ['nullable', 'date'],
            'to'                => ['nullable', 'date'],
            'date_type'         => ['nullable', 'in:in_at,out_at,both'],
            'user_id'           => ['nullable', 'integer'],
            'username'          => ['nullable', 'string'],
            'facility_id'       => ['nullable', 'integer'],
            'location_id'       => ['nullable', 'integer'],
            'status'            => ['nullable', 'in:open,closed,orphan_in,orphan_out'],
            'under_threshold'   => ['nullable', 'in:0,1'],
            'min_duration_sec'  => ['nullable', 'integer', 'min:0'],
            'max_duration_sec'  => ['nullable', 'integer', 'min:0'],
            'q'                 => ['nullable', 'string'],
        ]);

        // Query + scope po company i ulozi
        $q = AttendanceSession::query()
            ->with([
                'user:id,username,name,surname',
                'facility:id,name',
                'location:id,name',
            ])
            ->where('company_id', $companyId);

        // Role-based ograničenje: employee → samo svoje sesije; facility_admin → sesije iz svojih facilitija; admin → sve u kompaniji
        $role = $authUser->role ?? null;
        if ($role === User::ROLE_EMPLOYEE) {
            $q->where('user_id', $authUser->id);
        } elseif ($role === User::ROLE_FACILITY_ADMIN) {
            $facilityIds = $authUser->facilities()->pluck('id')->all();
            $q->whereIn('facility_id', $facilityIds);
        } else {
            // admin/company_admin/superadmin → već je ograničeno company_id-jem
        }

        // Filteri
        if (!empty($validated['user_id'])) {
            $q->where('user_id', (int) $validated['user_id']);
        }
        if (!empty($validated['facility_id'])) {
            $q->where('facility_id', (int) $validated['facility_id']);
        }
        if (!empty($validated['location_id'])) {
            $q->where('location_id', (int) $validated['location_id']);
        }
        if (!empty($validated['status'])) {
            $q->where('status', $validated['status']);
        }
        if (isset($validated['under_threshold'])) {
            $q->where('under_threshold', (int) $validated['under_threshold'] === 1);
        }
        if (!empty($validated['username'])) {
            $username = $validated['username'];
            $q->whereHas('user', function ($uq) use ($username) {
                $uq->where('username', $username);
            });
        }
        if (!empty($validated['q'])) {
            $term = '%' . mb_strtolower($validated['q']) . '%';
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('facility', function ($fq) use ($term) {
                    $fq->whereRaw('LOWER(name) LIKE ?', [$term]);
                })->orWhereHas('location', function ($lq) use ($term) {
                    $lq->whereRaw('LOWER(name) LIKE ?', [$term]);
                })->orWhereHas('user', function ($uq) use ($term) {
                    $uq->whereRaw('LOWER(username) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(surname) LIKE ?', [$term]);
                });
            });
        }

        $dateType = $validated['date_type'] ?? 'in_at';
        $from     = !empty($validated['from']) ? $validated['from'] : null;
        $to       = !empty($validated['to']) ? $validated['to'] : null;

        if ($from || $to) {
            if ($dateType === 'both') {
                $q->where(function ($qq) use ($from, $to) {
                    if ($from) {
                        $qq->where('in_at', '>=', $from)->orWhere('out_at', '>=', $from);
                    }
                    if ($to) {
                        $qq->where('in_at', '<=', $to)->orWhere('out_at', '<=', $to);
                    }
                });
            } elseif ($dateType === 'out_at') {
                if ($from) $q->where('out_at', '>=', $from);
                if ($to)   $q->where('out_at', '<=', $to);
            } else { // 'in_at'
                if ($from) $q->where('in_at', '>=', $from);
                if ($to)   $q->where('in_at', '<=', $to);
            }
        }

        // Sort po in_at DESC (stabilno)
        $q->orderByDesc('in_at')->orderByDesc('id');

        // Excel (XLSX) varijanta — pripremi redove i koristi GenericExport
        if ($format === 'xlsx') {
            $headers = $this->attendanceHeaders($includeMeta);
            $rows = [];
            foreach ($q->cursor() as $row) {
                $mapped = $this->mapAttendanceRow($row, $tz, $includeMeta, $validated);
                if ($mapped === null) {
                    continue; // filtriran po min/max trajanja nakon računanja
                }
                // Pretvori indexed array u asocijativni po headerima (za čitljiv XLSX)
                $assoc = [];
                foreach ($headers as $i => $col) {
                    $assoc[$col] = $mapped[$i] ?? null;
                }
                $rows[] = $assoc;
            }

            $data = collect($rows);

            Log::info('Export sync (attendance-sessions XLSX)', [
                'user_id' => $authUser->id ?? null,
                'request_filters' => $request->all(),
                'rows' => count($rows),
            ]);

            $export = new GenericExport($data, $headers);
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
        }

        // CSV stream (default)
        $response = new StreamedResponse(function () use ($q, $tz, $includeMeta, $validated) {
            $out = fopen('php://output', 'w');

            $headers = $this->attendanceHeaders($includeMeta);

            // BOM (ako želiš za Excel)
            // echo "\xEF\xBB\xBF";

            fputcsv($out, $headers);

            foreach ($q->cursor() as $row) {
                $mapped = $this->mapAttendanceRow($row, $tz, $includeMeta, $validated);
                if ($mapped === null) {
                    continue;
                }
                fputcsv($out, $mapped);
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        Log::info('Export sync (attendance-sessions CSV)', [
            'user_id' => $authUser->id ?? null,
            'filters' => $request->all(),
            'format'  => 'csv',
        ]);

        return $response;
    }

    /**
     * Kolone za attendance-sessions CSV/XLSX.
     */
    private function attendanceHeaders(bool $includeMeta): array
    {
        $base = [
            'session_id',
            'company_id',
            'user_id', 'user_username', 'user_name', 'user_surname',
            'facility_id', 'facility_name',
            'location_id', 'location_name',
            'in_at', 'out_at',
            'duration_sec', 'duration_hours',
            'status',
            'under_threshold',
        ];

        if (!$includeMeta) {
            return $base;
        }

        $meta = [
            'in_method', 'in_device_id', 'in_platform', 'in_app_version',
            'out_method', 'out_device_id', 'out_platform', 'out_app_version',
            'in_tag_uid', 'in_beacon_uuid', 'in_beacon_major', 'in_beacon_minor', 'in_rssi', 'in_qr_used',
            'out_tag_uid', 'out_beacon_uuid', 'out_beacon_major', 'out_beacon_minor', 'out_rssi', 'out_qr_used',
        ];

        return array_merge($base, $meta);
    }

    /**
     * Mapira jedan AttendanceSession u red CSV/XLSX u tačnom redosledu kolona.
     * Ako min/max duration filtriranje ne prođe (posle računanja), vraća null.
     */
    private function mapAttendanceRow(AttendanceSession $row, string $tz, bool $includeMeta, array $validated): ?array
    {
        $user     = $row->user;
        $facility = $row->facility;
        $location = $row->location;

        $inAt  = $row->in_at  ? $row->in_at->clone()->setTimezone($tz)->toIso8601String()  : null;
        $outAt = $row->out_at ? $row->out_at->clone()->setTimezone($tz)->toIso8601String() : null;

        // Trajanje: koristi kolonu ako postoji, inače računaj
        $durationSec = $row->duration_sec;
        if ($durationSec === null && $row->in_at && $row->out_at) {
            $durationSec = $row->out_at->diffInSeconds($row->in_at);
        }
        $durationHours = $durationSec !== null ? round($durationSec / 3600, 2) : null;

        // Post-filter (min/max) sada kad znamo realno trajanje
        if (isset($validated['min_duration_sec']) && $durationSec !== null) {
            if ($durationSec < (int) $validated['min_duration_sec']) {
                return null;
            }
        }
        if (isset($validated['max_duration_sec']) && $durationSec !== null) {
            if ($durationSec > (int) $validated['max_duration_sec']) {
                return null;
            }
        }

        $base = [
            $row->id,
            $row->company_id,
            $row->user_id,
            $user->username ?? null,
            $user->name ?? null,
            $user->surname ?? null,
            $row->facility_id,
            $facility->name ?? null,
            $row->location_id,
            $location->name ?? null,
            $inAt,
            $outAt,
            $durationSec,
            $durationHours,
            $row->status,                 // accessor vraća open/closed ako nije upisano
            $row->under_threshold ? 1 : 0,
        ];

        if (!$includeMeta) {
            return $base;
        }

        $inMeta  = is_array($row->in_meta)  ? $row->in_meta  : [];
        $outMeta = is_array($row->out_meta) ? $row->out_meta : [];

        $inMethod     = $inMeta['method']      ?? null;
        $inDeviceId   = $inMeta['device_id']   ?? null;
        $inPlatform   = $inMeta['platform']    ?? null;
        $inAppVersion = $inMeta['app_version'] ?? null;

        $outMethod     = $outMeta['method']      ?? null;
        $outDeviceId   = $outMeta['device_id']   ?? null;
        $outPlatform   = $outMeta['platform']    ?? null;
        $outAppVersion = $outMeta['app_version'] ?? null;

        $inTagUid  = $inMeta['tag_uid']  ?? null;
        $inUuid    = $inMeta['uuid']     ?? null;
        $inMajor   = $inMeta['major']    ?? null;
        $inMinor   = $inMeta['minor']    ?? null;
        $inRssi    = $inMeta['rssi']     ?? null;
        $inQrUsed  = array_key_exists('qr_payload', $inMeta) ? 1 : 0;

        $outTagUid = $outMeta['tag_uid'] ?? null;
        $outUuid   = $outMeta['uuid']    ?? null;
        $outMajor  = $outMeta['major']   ?? null;
        $outMinor  = $outMeta['minor']   ?? null;
        $outRssi   = $outMeta['rssi']    ?? null;
        $outQrUsed = array_key_exists('qr_payload', $outMeta) ? 1 : 0;

        return array_merge($base, [
            $inMethod, $inDeviceId, $inPlatform, $inAppVersion,
            $outMethod, $outDeviceId, $outPlatform, $outAppVersion,
            $inTagUid, $inUuid, $inMajor, $inMinor, $inRssi, $inQrUsed,
            $outTagUid, $outUuid, $outMajor, $outMinor, $outRssi, $outQrUsed,
        ]);
    }

    /* ============================================================
     *           GENERIČKI QUERY ZA OSTALE RESURSE
     * ============================================================ */

    private function getFilteredQuery($user, string $resource, array $filters)
    {
        // Pristup po ulozi
        switch ($user->role ?? null) {
            case 'superadmin':
                // Superadmin može videti samo kompanije i facilitije, bez usera
                if ($resource === 'companies') {
                    $query = app(Company::class)->newQuery();
                } elseif ($resource === 'facilities') {
                    $query = app(Facility::class)->newQuery();
                } else {
                    $query = app(User::class)->whereRaw('1=0'); // zabranjeno
                }
                break;

            case 'admin':
            case 'company_admin':
                $modelClass = 'App\\Models\\' . Str::studly(Str::singular($resource));
                $query = app($modelClass)->where('company_id', $user->company_id);
                break;

            case 'facility_admin':
                if ($resource === 'users') {
                    $userIds = $user->facilities()->with('users')->get()->pluck('users.*.id')->flatten();
                    $query = app(User::class)->whereIn('id', $userIds);
                } elseif ($resource === 'facilities') {
                    $facilityIds = $user->facilities()->pluck('id');
                    $query = app(Facility::class)->whereIn('id', $facilityIds);
                } else {
                    $modelClass = 'App\\Models\\' . Str::studly(Str::singular($resource));
                    $query = app($modelClass)->whereRaw('1=0');
                }
                break;

            default:
                $modelClass = 'App\\Models\\' . Str::studly(Str::singular($resource));
                $query = app($modelClass)->whereRaw('1=0'); // podrazumevano: ništa
        }

        // Primeni filtere po whitelistu
        $whitelist = $this->whitelists[$resource] ?? [];
        foreach ($filters as $key => $value) {
            if (in_array($key, $whitelist, true)) {
                $query->where($key, $value);
            }
        }

        // companies: withCount
        if ($resource === 'companies') {
            $query->withCount(['facilities', 'users']);
        }

        // facilities: withCount users
        if ($resource === 'facilities') {
            $query->withCount(['users']);
        }

        // users: dodatni filter facility_id kroz pivot
        if ($resource === 'users' && isset($filters['facility_id'])) {
            $facilityId = $filters['facility_id'];
            $query->whereHas('facilities', function ($q) use ($facilityId) {
                $q->where('facilities.id', $facilityId);
            });
        }

        return $query;
    }
}
