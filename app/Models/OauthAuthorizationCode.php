<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAuthorizationCode extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'client_id',
        'scopes',
        'redirect_uri',
        'code_challenge',
        'code_challenge_method',
        'revoked',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'code_challenge',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'revoked' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that authorized this code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the OAuth client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(OauthClient::class, 'client_id');
    }

    /**
     * Check if the code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the code is valid (not expired and not revoked).
     */
    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    /**
     * Revoke the authorization code.
     */
    public function revoke(): void
    {
        $this->update(['revoked' => true]);
    }
}

