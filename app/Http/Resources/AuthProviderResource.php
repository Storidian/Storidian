<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthProviderResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'provider_class' => $this->provider_class,
            'enabled' => $this->enabled,
            'allow_registration' => $this->allow_registration,
            'trust_email' => $this->trust_email,
            'sort_order' => $this->sort_order,
            // Config is intentionally excluded for security
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

