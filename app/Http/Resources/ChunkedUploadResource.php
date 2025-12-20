<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChunkedUploadResource extends JsonResource
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
            'folder_id' => $this->folder_id,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'total_size' => $this->total_size,
            'total_chunks' => $this->total_chunks,
            'uploaded_chunks' => $this->uploaded_chunks,
            'progress' => $this->progress,
            'is_complete' => $this->isComplete(),
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at,
            'folder' => new FolderResource($this->whenLoaded('folder')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

