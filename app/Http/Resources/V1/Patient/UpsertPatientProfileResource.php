<?php

namespace App\Http\Resources\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpsertPatientProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $documents = [];
        if (!empty($this->medical_documents)) {
            foreach ($this->medical_documents as $index => $path) {
                $documents[] = [
                    'index'    => $index,
                    'filename' => basename($path),
                    'url'      => url("/api/v1/patient/profile/documents/{$index}/download"),
                ];
            }
        }

        return [
            'user_id'            => $this->user_id,
            'birth_date'         => $this->birth_date?->toDateString(),
            'gender'             => $this->gender,
            'city'               => $this->city,
            'address'            => $this->address,
            'lat'                => $this->lat,
            'lng'                => $this->lng,
            'medical_notes'      => $this->medical_notes,
            'medical_documents'  => $documents,
            'documents_count'    => count($documents),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
