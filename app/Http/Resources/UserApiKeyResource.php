<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserApiKeyResource extends JsonResource
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
            'name' => $this->name,
            'key_prefix' => $this->key_prefix,
            // key_hash is intentionally excluded
            'scopes' => $this->scopes,
            'folder_scope' => $this->folder_scope,
            'scoped_folder' => new FolderResource($this->whenLoaded('scopedFolder')),
            'expires_at' => $this->expires_at,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

