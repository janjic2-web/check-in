<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class LocationsController extends Controller
{
    // --- NFC TAGS ---
    // POST /api/v1/locations/{id}/nfc-tags
    public function addNfcTag(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $data = $request->validate([
            'tag_uid' => ['required','string','max:64'],
            'description' => ['nullable','string','max:255'],
        ]);
        $tag = $loc->nfcTags()->create($data);
        return response()->json(['data' => $tag], 201);
    }

    // PATCH /api/v1/locations/{id}/nfc-tags/{tagId}
    public function updateNfcTag(Request $request, int $id, int $tagId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $tag = $loc->nfcTags()->find($tagId);
        if (!$tag) return response()->json(['error' => 'Tag not found'], 404);
        $data = $request->validate([
            'tag_uid' => ['nullable','string','max:64'],
            'description' => ['nullable','string','max:255'],
        ]);
        $tag->fill(array_filter($data, fn($v) => $v !== null));
        $tag->save();
        return response()->json(['data' => $tag]);
    }

    // DELETE /api/v1/locations/{id}/nfc-tags/{tagId}
    public function deleteNfcTag(Request $request, int $id, int $tagId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $tag = $loc->nfcTags()->find($tagId);
        if (!$tag) return response()->json(['error' => 'Tag not found'], 404);
        $tag->delete();
        return response()->json(['ok' => true]);
    }

    // --- BLE BEACONS ---
    // POST /api/v1/locations/{id}/ble-beacons
    public function addBleBeacon(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $data = $request->validate([
            'beacon_id' => ['required','string','max:64'],
            'description' => ['nullable','string','max:255'],
        ]);
        $beacon = $loc->bleBeacons()->create($data);
        return response()->json(['data' => $beacon], 201);
    }

    // PATCH /api/v1/locations/{id}/ble-beacons/{beaconId}
    public function updateBleBeacon(Request $request, int $id, int $beaconId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $beacon = $loc->bleBeacons()->find($beaconId);
        if (!$beacon) return response()->json(['error' => 'Beacon not found'], 404);
        $data = $request->validate([
            'beacon_id' => ['nullable','string','max:64'],
            'description' => ['nullable','string','max:255'],
        ]);
        $beacon->fill(array_filter($data, fn($v) => $v !== null));
        $beacon->save();
        return response()->json(['data' => $beacon]);
    }

    // DELETE /api/v1/locations/{id}/ble-beacons/{beaconId}
    public function deleteBleBeacon(Request $request, int $id, int $beaconId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $beacon = $loc->bleBeacons()->find($beaconId);
        if (!$beacon) return response()->json(['error' => 'Beacon not found'], 404);
        $beacon->delete();
        return response()->json(['ok' => true]);
    }

    // --- QR CODES ---
    // POST /api/v1/locations/{id}/qr-codes
    public function addQrCode(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $data = $request->validate([
            'qr_payload' => ['required','string','max:128'],
            'description' => ['nullable','string','max:255'],
        ]);
        $qr = $loc->qrCodes()->create($data);
        return response()->json(['data' => $qr], 201);
    }

    // PATCH /api/v1/locations/{id}/qr-codes/{qrId}
    public function updateQrCode(Request $request, int $id, int $qrId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $qr = $loc->qrCodes()->find($qrId);
        if (!$qr) return response()->json(['error' => 'QR code not found'], 404);
        $data = $request->validate([
            'qr_payload' => ['nullable','string','max:128'],
            'description' => ['nullable','string','max:255'],
        ]);
        $qr->fill(array_filter($data, fn($v) => $v !== null));
        $qr->save();
        return response()->json(['data' => $qr]);
    }

    // DELETE /api/v1/locations/{id}/qr-codes/{qrId}
    public function deleteQrCode(Request $request, int $id, int $qrId)
    {
        $companyId = $request->attributes->get('company_id');
        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) return response()->json(['error' => 'Location not found'], 404);
        $qr = $loc->qrCodes()->find($qrId);
        if (!$qr) return response()->json(['error' => 'QR code not found'], 404);
        $qr->delete();
        return response()->json(['ok' => true]);
    }
    // GET /api/v1/locations?facility_id=&active=1|0|all&q=&per_page=&include_trashed=1|0&only_trashed=1|0
    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $q = Location::query()
            ->where('company_id', $companyId);

        // trashed filteri
        if ($request->boolean('only_trashed')) {
            $q->onlyTrashed();
        } elseif ($request->boolean('include_trashed')) {
            $q->withTrashed();
        }

        // filter: facility
        if ($request->filled('facility_id')) {
            $q->where('facility_id', (int) $request->integer('facility_id'));
        }

        // filter: active
        $active = (string) $request->query('active', '1'); // default samo aktivne
        if ($active === '1')       { $q->where('active', true); }
        elseif ($active === '0')   { $q->where('active', false); }
        // 'all' -> bez filtera

        // full-text simple: ime
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $q->where('name', 'like', '%'.$term.'%');
        }

        $perPage = min(max((int)($request->integer('per_page') ?: 25), 1), 100);
        $page = $q->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'page'     => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total'    => $page->total(),
                'has_next' => $page->hasMorePages(),
            ],
        ]);
    }

    // POST /api/v1/locations
    public function store(Request $request)
    {
        $company   = $request->attributes->get('company');
        $companyId = $company->id;

        $data = $request->validate([
            'facility_id'      => ['required','integer','exists:facilities,id'],
            'name'             => [
                'required','string','min:2','max:120',
                Rule::unique('locations')->where(function ($q) use ($companyId, $request) {
                    return $q->where('company_id', $companyId)
                             ->where('facility_id', (int)$request->input('facility_id'))
                             ->whereNull('deleted_at'); // ignoriši soft-deleted
                }),
            ],
            'lat'              => ['required','numeric','between:-90,90'],
            'lng'              => ['required','numeric','between:-180,180'],
            // radius: lokacija ≥ max(company.default_radius_m, 150) i ≤ 1000
            'radius_m'         => ['required','integer','min:1','max:1000'],
            'outside_override' => ['nullable', Rule::in(['inherit','disallow'])],
            'active'           => ['nullable','boolean'],
        ]);

        // izračunaj minimalni radius
        $minRadius = max((int)($company->default_radius_m ?? 150), 150);
        if ((int)$data['radius_m'] < $minRadius) {
            return response()->json([
                'code'    => 'radius_below_min',
                'message' => "Radius must be ≥ {$minRadius} m (company/base rule).",
            ], 422);
        }

        $loc = Location::create([
            'company_id'       => $companyId,
            'facility_id'      => (int)$data['facility_id'],
            'name'             => $data['name'],
            'active'           => isset($data['active']) ? (bool)$data['active'] : true,
            'lat'              => $data['lat'],
            'lng'              => $data['lng'],
            'radius_m'         => (int)$data['radius_m'],
            'outside_override' => $data['outside_override'] ?? 'inherit',
        ]);

        return response()->json(['data' => $loc], 201);
    }

    // GET /api/v1/locations/{id}
    public function show(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $q = Location::where('company_id', $companyId);

        // dozvoli include_trashed i ovde (po potrebi)
        if ($request->boolean('include_trashed')) {
            $q->withTrashed();
        }

        $loc = $q->find($id);
        if (!$loc) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        return response()->json(['data' => $loc]);
    }

    // PATCH /api/v1/locations/{id}
    public function update(Request $request, int $id)
    {
        $company   = $request->attributes->get('company');
        $companyId = $company->id;

        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $data = $request->validate([
            'facility_id'      => ['nullable','integer','exists:facilities,id'],
            'name'             => [
                'nullable','string','min:2','max:120',
                Rule::unique('locations')
                    ->ignore($loc->id)
                    ->where(function ($q) use ($companyId, $request, $loc) {
                        // koristimo prosleđeni facility_id ako je setovan, inače trenutni
                        $facilityId = (int) ($request->input('facility_id') ?? $loc->facility_id);
                        return $q->where('company_id', $companyId)
                                 ->where('facility_id', $facilityId)
                                 ->whereNull('deleted_at');
                    }),
            ],
            'lat'              => ['nullable','numeric','between:-90,90'],
            'lng'              => ['nullable','numeric','between:-180,180'],
            'radius_m'         => ['nullable','integer','min:1','max:1000'],
            'outside_override' => ['nullable', Rule::in(['inherit','disallow'])],
            'active'           => ['nullable','boolean'],
        ]);

        // ako menjamo radius, primeni company minimum
        if (array_key_exists('radius_m', $data) && $data['radius_m'] !== null) {
            $minRadius = max((int)($company->default_radius_m ?? 150), 150);
            if ((int)$data['radius_m'] < $minRadius) {
                return response()->json([
                    'code'    => 'radius_below_min',
                    'message' => "Radius must be ≥ {$minRadius} m (company/base rule).",
                ], 422);
            }
        }

        $loc->fill(array_filter($data, fn($v) => $v !== null));
        $loc->save();

        return response()->json(['data' => $loc]);
    }

    // DELETE /api/v1/locations/{id}
    // Pravilo: po difoltu soft delete; hard delete DOZVOLJEN samo ako NEMA checkin-ova i ako je ?hard=1.
    public function destroy(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $loc = Location::where('company_id', $companyId)->find($id);
        if (!$loc) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $hasCheckins = DB::table('checkins')
            ->where('company_id', $companyId)
            ->where('location_id', $loc->id)
            ->exists();

        $wantHard = $request->boolean('hard');

        if ($wantHard && !$hasCheckins) {
            $loc->forceDelete();
            return response()->json(['ok' => true, 'hard_deleted' => true]);
        }

        // soft delete (i deaktiviraj)
        $loc->active = false;
        $loc->save();
        $loc->delete();

        return response()->json(['ok' => true, 'soft_deleted' => true]);
    }

    // POST /api/v1/locations/{id}/restore
    public function restore(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        // obavezno uključujemo soft-deleted
        $loc = Location::withTrashed()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        // ako nije obrisan, nema šta da se restoruje
        if (!$loc->trashed()) {
            return response()->json([
                'code'    => 'not_deleted',
                'message' => 'Location is not deleted.',
                'data'    => $loc,
            ], 409);
        }

        $loc->restore();

        // po dogovoru – posle restore neka bude aktivan
        $loc->active = true;
        $loc->save();

        return response()->json(['data' => $loc]);
    }
}
