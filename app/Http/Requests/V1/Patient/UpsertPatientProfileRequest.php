<?php

namespace App\Http\Requests\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPatientProfileRequest extends FormRequest
{
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
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:M,F,OTHER'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'medical_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
