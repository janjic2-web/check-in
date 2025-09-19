<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCompanyAdminRequest;
use App\Http\Requests\CreateEmployeeByCompanyAdminRequest;
use App\Http\Requests\CreateEmployeeByFacilityAdminRequest;
use App\Http\Requests\CreateFacilityAdminRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * SUPERADMIN → kreira COMPANY ADMIN korisnika u zadatoj kompaniji.
     */
    public function createCompanyAdmin(CreateCompanyAdminRequest $request): JsonResponse
    {
        $actor = Auth::user();
        $isSuperadminApiKey = request()->attributes->get('is_superadmin') === true;
        $isSuperadminJwt = $actor && $actor->role === 'superadmin';
        if (!$isSuperadminApiKey && !$isSuperadminJwt) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        /** @var User $user */
        $user = DB::transaction(function () use ($data) {
            return User::create([
                'company_id' => (int) $data['company_id'],
                'username'   => $data['username'],
                'email'      => $data['email'],
                'password'   => $data['password'], // auto-hash mutator
                'name'       => $data['name']    ?? null,
                'surname'    => $data['surname'] ?? null,
                'phone'      => $data['phone']   ?? null,
                'employee_id'=> $data['employee_id'] ?? null,
                'status'     => User::STATUS_ACTIVE,
                'role'       => User::ROLE_ADMIN,
            ]);
        });

        return response()->json([
            'message' => 'Company admin created',
            'id'      => $user->id,
        ], 201);
    }

    /**
     * COMPANY ADMIN → kreira FACILITY ADMIN korisnika u SVOJOJ kompaniji.
     * Podržava i 'facility_ids' (niz) i 'facility_id' (single) iz requesta.
     */
    public function createFacilityAdmin(CreateFacilityAdminRequest $request): JsonResponse
    {
        $actor = Auth::user();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data     = $request->validated();
        $company  = (int) ($actor->company_id); // forsiramo kompaniju aktera
        $facIds   = [];

        // backward-compat: prihvati single facility_id ili array facility_ids
        if (isset($data['facility_ids']) && is_array($data['facility_ids'])) {
            $facIds = array_map('intval', $data['facility_ids']);
        } elseif (isset($data['facility_id'])) {
            $facIds = [(int) $data['facility_id']];
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($data, $company, $facIds) {
            $u = User::create([
                'company_id' => $company,
                'username'   => $data['username'],
                'email'      => $data['email'],
                'password'   => $data['password'], // auto-hash mutator
                'name'       => $data['name']    ?? null,
                'surname'    => $data['surname'] ?? null,
                'phone'      => $data['phone']   ?? null,
                'employee_id'=> $data['employee_id'] ?? null,
                'status'     => User::STATUS_ACTIVE,
                'role'       => User::ROLE_FACILITY_ADMIN,
            ]);

            if (!empty($facIds)) {
                $u->assignFacilities($facIds, $company);
            }

            return $u;
        });

        return response()->json([
            'message' => 'Facility admin created',
            'id'      => $user->id,
        ], 201);
    }

    /**
     * COMPANY ADMIN → kreira EMPLOYEE korisnika (mora navesti bar jedan facility svoje kompanije).
     */
    public function createUserByCompanyAdmin(CreateEmployeeByCompanyAdminRequest $request): JsonResponse
    {
        $actor = Auth::user();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data    = $request->validated();
        $company = (int) ($request->attributes->get('company_id') ?? $actor->company_id);

        /** @var User $user */
        $user = DB::transaction(function () use ($data, $company) {
            $u = User::create([
                'company_id'  => $company,
                'username'    => $data['username'],
                'email'       => $data['email'] ?? null,
                'password'    => $data['password'], // auto-hash mutator
                'name'        => $data['name'] ?? null,
                'surname'     => $data['surname'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'status'      => User::STATUS_ACTIVE,
                'role'        => User::ROLE_EMPLOYEE,
            ]);

            $u->assignFacilities($data['facility_ids'], $company);

            return $u;
        });

        return response()->json([
            'message' => 'User created',
            'id'      => $user->id,
        ], 201);
    }

    /**
     * FACILITY ADMIN → kreira EMPLOYEE korisnika (samo u svojim facility-jima).
     */
    public function createUserByFacilityAdmin(CreateEmployeeByFacilityAdminRequest $request): JsonResponse
    {
        $actor = Auth::user();
        if (!$actor || !$actor->isFacilityAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data    = $request->validated();
        $company = (int) ($request->attributes->get('company_id') ?? $actor->company_id);

        /** @var User $user */
        $user = DB::transaction(function () use ($data, $company) {
            $u = User::create([
                'company_id'  => $company,
                'username'    => $data['username'],
                'email'       => $data['email'] ?? null,
                'password'    => $data['password'], // auto-hash mutator
                'name'        => $data['name'] ?? null,
                'surname'     => $data['surname'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'status'      => User::STATUS_ACTIVE,
                'role'        => User::ROLE_EMPLOYEE,
            ]);

            $u->assignFacilities($data['facility_ids'], $company);

            return $u;
        });

        return response()->json([
            'message' => 'User created',
            'id'      => $user->id,
        ], 201);
    }
}
