<?php

namespace App\Http\Resources\V1\Nurse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UpsertNurseProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'diploma' => $this->diploma,
            'experience_years' => $this->experience_years,
            'bio' => $this->bio,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'coverage_km' => $this->coverage_km,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'verified' => (bool) $this->verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
