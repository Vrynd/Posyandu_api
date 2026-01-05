<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identifier: email format OR 16-digit NIK (alphanumeric only)
            'identifier' => [
                'required',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}|[0-9]{16})$/'
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:128',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.regex' => 'Format email atau NIK tidak valid.',
            'identifier.required' => 'Email atau NIK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
