<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OauthClient extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'redirect_uris',
        'scopes',
        'is_first_party',
        'is_public',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'client_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
            'scopes' => 'array',
            'is_first_party' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the user who created this OAuth client.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the authorization codes for this client.
     */
    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(OauthAuthorizationCode::class, 'client_id');
    }

    /**
     * Get the refresh tokens for this client.
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(OauthRefreshToken::class, 'client_id');
    }
}

