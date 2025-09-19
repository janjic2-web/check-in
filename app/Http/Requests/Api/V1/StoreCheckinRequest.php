<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckinRequest extends FormRequest
{
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ]
        ], 422);
        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
    public function authorize(): bool
    {
        // Dozvoli svim autentifikovanim korisnicima
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            // Dodaj dodatna pravila po potrebi (method, action, lat, lng...)
        ];
    }
}
