<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:buyer,supplier'],
            'phone' => ['required', 'string', 'max:30'],
            'contact_person' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'sppg_id' => ['required_if:role,buyer', 'nullable', 'string', 'max:50', Rule::unique('users', 'sppg_id')],
            'nib_number' => ['required_if:role,supplier', 'nullable', 'digits:13', Rule::unique('supplier_profiles', 'nib_number')],
            'nib_document' => ['required_if:role,supplier', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
