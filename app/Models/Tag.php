<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
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
        'color',
    ];

    /**
     * Get the user that owns this tag.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the files with this tag.
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_tag')
            ->withTimestamps();
    }

    /**
     * Get the folders with this tag.
     */
    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_tag')
            ->withTimestamps();
    }
}

