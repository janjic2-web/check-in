<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login korisnika (login = email ili username) + password.
     * Zahteva važeći x-api-key (ApiKeyMiddleware) koji u request setuje company_id i company.
     */
    public function login(Request $request): JsonResponse
    {
        // 1) Validacija ulaza
        $data = $request->validate([
            'login'    => ['required', 'string'],  // email ili username
            'password' => ['required', 'string'],
            // (opciono) 'remember' => ['boolean']  // ako želiš različit TTL
        ]);

        // 2) Multi-tenant kontekst
        $company     = $request->attributes->get('company');
        $companyId   = (int) $request->attributes->get('company_id');
        if (!$company || !$companyId) {
            return $this->error('NO_COMPANY_CONTEXT', 'Company context missing (x-api-key required)', 400);
        }

        // (opciono) Ako želiš da blokiraš i login za suspended/expired kompanije:
        if (in_array($company->status, ['suspended', 'expired'], true)) {
            return $this->error('SUSPENDED', 'Company is suspended or expired', 403);
        }
        if ($company->expires_at && now()->greaterThan($company->expires_at)) {
            return $this->error('EXPIRED', 'Company subscription expired', 403);
        }

        // 3) Odredi polje (email ili username)
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 4) Pronađi korisnika unutar kompanije i proveri kredencijale
        /** @var User|null $user */
        $user = User::query()
            ->where('company_id', $companyId)
            ->where($field, $data['login'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error('INVALID_CREDENTIALS', 'Invalid username/email or password.', 401);
        }

        // 5) Status korisnika
        if ($user->status !== 'active') {
            return $this->error('USER_INACTIVE', 'User is not active.', 403);
        }

        // 6) Generiši JWT token (guard "api" = tymon/jwt-auth)
        // Ako želiš custom TTL (npr. remember=true) možeš uraditi:
        // if ($request->boolean('remember')) auth('api')->factory()->setTTL(60 * 24 * 30); // 30d u minutama
        if (!$token = auth('api')->login($user)) {
            return $this->error('UNAUTHORIZED', 'Unable to create token.', 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Logout korisnika (poništava token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh JWT tokena.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Trenutni korisnik.
     */
    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Helper: standardizovan JWT odgovor (usaglašen sa specifikacijom).
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60, // sekunde
            'user'         => [
                'id'         => $user->id,
                'username'   => $user->username,
                'email'      => $user->email,
                'role'       => $user->role,
                'company_id' => $user->company_id,
            ],
        ]);
    }

    /**
     * Helper: standardizovan error JSON + X-Request-Id header.
     */
    protected function error(string $code, string $message, int $status): JsonResponse
    {
        $reqId = request()->headers->get('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();

        return response()
            ->json(['error' => ['code' => $code, 'message' => $message], 'request_id' => $reqId], $status)
            ->header('X-Request-Id', $reqId);
        // Napomena: Globalni Handler već pokriva ostale izuzetke/validacije,
        // ovde samo vraćamo konzistentan format za eksplicitne grane.
    }
}
