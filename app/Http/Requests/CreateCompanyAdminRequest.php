<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCompanyAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Dozvoljeno samo superadminu iz API ključa (ne iz users.role)
        return (bool) $this->attributes->get('is_superadmin', false);
    }

    public function rules(): array
    {
        return [
            // Superadmin MORA da kaže za koju kompaniju pravi admina
            'company_id' => ['required', 'integer', 'exists:companies,id'],

            'username' => ['required', 'string', 'max:120', 'unique:users,username'],
            // email je opcion, ali ako je dat mora biti jedinstven
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email'],
            // u ostatku koda dozvoljeno je min 6 (u skladu s mutatorom i našim testovima)
            'password' => ['required', 'string', 'min:6'],

            'name'    => ['nullable', 'string', 'max:120'],
            'surname' => ['nullable', 'string', 'max:120'],
            'phone'   => ['nullable', 'string', 'max:40'],
        ];
    }
}
