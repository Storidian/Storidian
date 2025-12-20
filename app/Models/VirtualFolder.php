<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualFolder extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'tag_query',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tag_query' => 'array',
        ];
    }

    /**
     * Get the user that owns this virtual folder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the files matching this virtual folder's tag query.
     * This returns a query builder, not a relationship.
     */
    public function getFilesQuery()
    {
        $query = $this->tag_query;
        $includeTags = $query['include'] ?? [];
        $excludeTags = $query['exclude'] ?? [];
        $operator = $query['operator'] ?? 'AND';

        $filesQuery = File::where('user_id', $this->user_id);

        if (! empty($includeTags)) {
            if ($operator === 'AND') {
                foreach ($includeTags as $tagName) {
                    $filesQuery->whereHas('tags', function ($q) use ($tagName) {
                        $q->where('name', $tagName);
                    });
                }
            } else {
                // OR operator
                $filesQuery->whereHas('tags', function ($q) use ($includeTags) {
                    $q->whereIn('name', $includeTags);
                });
            }
        }

        if (! empty($excludeTags)) {
            $filesQuery->whereDoesntHave('tags', function ($q) use ($excludeTags) {
                $q->whereIn('name', $excludeTags);
            });
        }

        return $filesQuery->orderBy($this->sort_order);
    }
}

