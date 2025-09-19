<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeByFacilityAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = auth()->user();
        return $u && method_exists($u, 'isFacilityAdmin') && $u->isFacilityAdmin();
    }

    public function rules(): array
    {
        $companyId = (int) $this->attributes->get('company_id');

        return [
            'role'       => ['prohibited'],
            'company_id' => ['prohibited'],

            'username' => ['required', 'string', 'max:120', 'unique:users,username'],
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],

            'name'       => ['nullable', 'string', 'max:120'],
            'surname'    => ['nullable', 'string', 'max:120'],
            'phone'      => ['nullable', 'string', 'max:40'],
            'employee_id'=> ['nullable', 'string', 'max:60'],

            // mora navesti facility-je; moraju pripadati company-ju (dodatno: subset FA ovlašćenja u withValidator)
            'facility_ids'   => ['required', 'array', 'min:1'],
            'facility_ids.*' => [
                'integer',
                Rule::exists('facilities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('facility_ids') && is_string($this->facility_ids)) {
            $ids = array_filter(array_map('trim', explode(',', $this->facility_ids)), 'strlen');
            $this->merge(['facility_ids' => array_map('intval', $ids)]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $actor = auth()->user();
            if (!$actor) return;

            // facility admin sme da dodeli SAMO facility-je nad kojima ima ovlašćenje
            $allowed = $actor->facilities()->pluck('facilities.id')->all();
            $requested = (array) $this->input('facility_ids', []);

            $diff = array_diff($requested, $allowed);
            if (!empty($diff)) {
                $v->errors()->add('facility_ids', 'One or more facilities are not managed by the current facility admin.');
            }
        });
    }
}
