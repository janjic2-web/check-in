<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeByCompanyAdminRequest extends FormRequest
{
    /**
     * Odredi da li korisnik ima pravo da izvrši ovaj zahtev.
     */
    public function authorize(): bool
    {
        // Dozvoli samo company adminima
        return auth()->check() && auth()->user()->role === 'company_admin';
    }

    /**
     * Pravila validacije za kreiranje zaposlenog od strane company admina.
     */
    public function rules(): array
    {
        return [
            'username'    => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'       => ['required', 'email', 'max:100', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:8'],
            'company_id'  => ['required', 'integer', 'exists:companies,id'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            // Dodajte ostala polja po potrebi
        ];
    }
}
