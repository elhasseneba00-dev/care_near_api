<?php

namespace App\Http\Resources\V1\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'care_request_id' => $this->care_request_id,
            'patient_user_id' => $this->patient_user_id,
            'nurse_user_id' => $this->nurse_user_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
