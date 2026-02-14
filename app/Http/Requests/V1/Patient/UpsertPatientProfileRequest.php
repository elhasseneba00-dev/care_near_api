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
            'medical_files'     => ['nullable', 'array', 'max:5'],                                // max 5 fichiers à la fois
            'medical_files.*'   => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],                // 5 Mo chacun
        ];
    }

    public function messages(): array
    {
        return [
            'medical_files.max'      => 'Vous pouvez uploader maximum 5 fichiers à la fois.',
            'medical_files.*.mimes'  => 'Les documents doivent être des fichiers PDF, JPG ou PNG.',
            'medical_files.*.max'    => 'Chaque document ne doit pas dépasser 5 Mo.',
        ];
    }
}
