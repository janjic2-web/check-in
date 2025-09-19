<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFacilityAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Samo COMPANY ADMIN (ne superadmin, ne facility admin) po specifikaciji
        $user = auth()->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        // company_id izvlačimo iz konteksta (postavio ga je ApiKeyMiddleware)
        $companyId = (int) $this->attributes->get('company_id');

        return [
            'username' => ['required', 'string', 'max:120', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],

            // zahtevamo bar jedan facility; validiramo da pripada istoj kompaniji
            'facility_ids'   => ['required', 'array', 'min:1'],
            'facility_ids.*' => [
                'integer',
                Rule::exists('facilities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'name'    => ['nullable', 'string', 'max:120'],
            'surname' => ['nullable', 'string', 'max:120'],
            'phone'   => ['nullable', 'string', 'max:40'],
        ];
    }
}
