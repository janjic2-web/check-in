<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanySetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'language' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:active'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'vat_pib' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:64'],
            'allow_outside' => ['boolean'],
            'default_radius_m' => ['required', 'integer', 'min:1'],
            'anti_spam_min_interval' => ['required', 'integer', 'min:0'],
            'min_inout_gap_min' => ['required', 'integer', 'min:0'],
            'ble_min_rssi' => ['required', 'integer', 'between:-100,-30'],
            'require_gps_checkin' => ['boolean'],
            'offline_retention_hours' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Naziv kompanije je obavezan.',
            'display_name.max' => 'Naziv kompanije može imati najviše 255 karaktera.',
            'default_radius_m.min' => 'Default radius mora biti veći od 0.',
            'ble_min_rssi.between' => 'RSSI mora biti između -100 i -30.',
        ];
    }
}
