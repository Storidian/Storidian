<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadSession extends Model
{
    use HasFactory, HasUuids;

    /**
     * Upload status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'upload_id',
        'user_id',
        'folder_id',
        'filename',
        'filesize',
        'filetype',
        'status',
        'file_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filesize' => 'integer',
        ];
    }

    /**
     * Get the user that initiated this upload.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the target folder.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the created file record.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Check if the upload is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the upload is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    /**
     * Check if the upload failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark the upload as complete with the associated file.
     */
    public function markComplete(File $file): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETE,
            'file_id' => $file->id,
        ]);
    }

    /**
     * Mark the upload as failed.
     */
    public function markFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    /**
     * Scope a query to only include pending uploads.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include complete uploads.
     */
    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETE);
    }

    /**
     * Scope a query to only include stale uploads (pending for more than given hours).
     */
    public function scopeStale(Builder $query, int $hours = 24): Builder
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Scope a query to only include uploads for a specific user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}

