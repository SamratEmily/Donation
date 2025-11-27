<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'creator_name' => $this->creator_name,
            'creator_email' => $this->creator_email,
            'creator_phone' => $this->creator_phone,
            'target_amount' => (float) $this->target_amount,
            'current_amount' => (float) $this->current_amount,
            'payment_type' => $this->payment_type,
            'is_active' => (bool) $this->is_active,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'progress_percentage' => $this->progress_percentage,
            'is_completed' => $this->is_completed,
            'status' => $this->status,
            'user' => $this->whenLoaded('user'),
            'donations' => $this->whenLoaded('donations'),
        ];
    }
}
