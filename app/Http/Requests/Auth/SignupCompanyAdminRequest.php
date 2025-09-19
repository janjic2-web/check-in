<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignupCompanyAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_display_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'token' => ['required', 'string', 'max:255', function($attribute, $value, $fail) {
                if (!isValidInviteToken($value)) {
                    $fail('Invite token nije validan ili je istekao.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'company_display_name.required' => 'Naziv kompanije je obavezan.',
            'company_display_name.max' => 'Naziv kompanije može imati najviše 255 karaktera.',
            'name.required' => 'Ime je obavezno.',
            'surname.required' => 'Prezime je obavezno.',
            'email.required' => 'Email je obavezan.',
            'email.email' => 'Email format nije validan.',
            'email.unique' => 'Email je već registrovan.',
            'password.required' => 'Lozinka je obavezna.',
            'password.min' => 'Lozinka mora imati najmanje 8 karaktera.',
            'password.confirmed' => 'Potvrda lozinke se ne poklapa.',
            'token.required' => 'Invite token je obavezan.',
        ];
    }
}
