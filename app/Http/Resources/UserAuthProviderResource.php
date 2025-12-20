<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAuthProviderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id,
            'provider_user_id' => $this->provider_user_id,
            'provider' => new AuthProviderResource($this->whenLoaded('provider')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

