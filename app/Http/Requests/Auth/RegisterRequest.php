<?php

namespace App\Http\Requests\Auth;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => Phone::normalize((string) $this->input('phone'))
            ]);
        }

        if ($this->has('email') && $this->input('email') === '') {
            $this->merge(['email' => null]);
        }
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(['PATIENT', 'NURSE'])],

            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
