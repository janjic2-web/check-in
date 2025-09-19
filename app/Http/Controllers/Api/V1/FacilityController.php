<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    // LIST: /api/v1/facilities?active=1|0|all
    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $active = $request->query('active', '1'); // default samo aktivne
        $q = Facility::query()->where('company_id', $companyId);

        if ($active === '1')        { $q->where('active', true); }
        elseif ($active === '0')    { $q->where('active', false); }
        else /* all */              { /* bez dodatnog filtera */ }

        // soft-deleted se ne prikazuju (default). Ako ikad zatreba: withTrashed()

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

    // CREATE: POST /api/v1/facilities
    public function store(Request $request)
    {
        $company   = $request->attributes->get('company');
        $companyId = $company->id;

        $data = $request->validate([
            'name'             => ['required','string','min:2','max:120',
                Rule::unique('facilities')->where(fn($q)=>$q->where('company_id',$companyId))
            ],
            'address'          => ['nullable','string','max:255'],
            'city'             => ['nullable','string','max:120'],
            'lat'              => ['nullable','numeric','between:-90,90'],
            'lng'              => ['nullable','numeric','between:-180,180'],
            'default_radius_m' => ['nullable','integer','min:1','max:1000'],
            'outside_override' => ['nullable', Rule::in(['inherit','disallow'])],
            'active'           => ['nullable','boolean'],
        ]);

        $facility = Facility::create([
            'company_id'       => $companyId,
            'name'             => $data['name'],
            'address'          => $data['address']          ?? null,
            'city'             => $data['city']             ?? null,
            'lat'              => $data['lat']              ?? null,
            'lng'              => $data['lng']              ?? null,
            'default_radius_m' => $data['default_radius_m'] ?? null,
            'outside_override' => $data['outside_override'] ?? 'inherit',
            'active'           => isset($data['active']) ? (bool)$data['active'] : true,
        ]);

        return response()->json([
            'id'      => $facility->id,
            'name'    => $facility->name,
            'address' => $facility->address,
            'city'    => $facility->city,
        ], 201);
    }

    // SHOW: GET /api/v1/facilities/{id}
    public function show(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $f = Facility::where('company_id', $companyId)->find($id);
        if (!$f) {
            return response()->json(['error' => 'Facility not found'], 404);
        }
        return response()->json(['data' => $f]);
    }

    // UPDATE: PATCH /api/v1/facilities/{id}
    public function update(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $f = Facility::where('company_id', $companyId)->find($id);
        if (!$f) {
            return response()->json(['error' => 'Facility not found'], 404);
        }

        $data = $request->validate([
            'name'             => ['nullable','string','min:2','max:120',
                Rule::unique('facilities')->ignore($f->id)->where(fn($q)=>$q->where('company_id',$companyId))
            ],
            'address'          => ['nullable','string','max:255'],
            'city'             => ['nullable','string','max:120'],
            'lat'              => ['nullable','numeric','between:-90,90'],
            'lng'              => ['nullable','numeric','between:-180,180'],
            'default_radius_m' => ['nullable','integer','min:1','max:1000'],
            'outside_override' => ['nullable', Rule::in(['inherit','disallow'])],
            'active'           => ['nullable','boolean'],
        ]);

        $f->fill(array_filter($data, fn($v)=>$v!==null));
        $f->save();

        return response()->json(['data' => $f]);
    }

    // DELETE (soft): DELETE /api/v1/facilities/{id}
    public function destroy(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $f = Facility::where('company_id', $companyId)->find($id);
        if (!$f) {
            return response()->json(['error' => 'Facility not found'], 404);
        }

        // umesto hard delete → deaktiviraj i soft-delete
        $f->active = false;
        $f->save();
        $f->delete(); // postavlja deleted_at, ali zapis ostaje zbog statistike

    return response()->json(['message' => 'Facility deleted']);
    }

    // (opciono) RESTORE: POST /api/v1/facilities/{id}/restore
    public function restore(Request $request, int $id)
    {
        $companyId = $request->attributes->get('company_id');

        $f = Facility::withTrashed()
            ->where('company_id', $companyId)
            ->find($id);

        if (!$f) {
            return response()->json(['error' => 'Facility not found'], 404);
        }

        $f->restore();
        $f->active = true;
        $f->save();

        return response()->json(['data' => $f]);
    }
}
