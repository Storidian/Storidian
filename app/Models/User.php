<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'quota_bytes',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'quota_bytes' => 'integer',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ==========================================
    // File Management Relationships
    // ==========================================

    /**
     * Get the folders owned by the user.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * Get the files owned by the user.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Get the tags owned by the user.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the virtual folders owned by the user.
     */
    public function virtualFolders(): HasMany
    {
        return $this->hasMany(VirtualFolder::class);
    }

    /**
     * Get the chunked uploads for the user.
     */
    public function chunkedUploads(): HasMany
    {
        return $this->hasMany(ChunkedUpload::class);
    }

    // ==========================================
    // Authentication Relationships
    // ==========================================

    /**
     * Get the auth providers linked to the user via UserAuthProvider.
     */
    public function authProviders(): BelongsToMany
    {
        return $this->belongsToMany(AuthProvider::class, 'user_auth_providers', 'user_id', 'provider_id')
            ->using(UserAuthProvider::class)
            ->withPivot(['provider_user_id', 'provider_data'])
            ->withTimestamps();
    }

    /**
     * Get the user auth provider links.
     */
    public function userAuthProviders(): HasMany
    {
        return $this->hasMany(UserAuthProvider::class);
    }

    /**
     * Get the OAuth clients created by this user (admin).
     */
    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class, 'created_by');
    }

    /**
     * Get the OAuth authorization codes for the user.
     */
    public function oauthAuthorizationCodes(): HasMany
    {
        return $this->hasMany(OauthAuthorizationCode::class);
    }

    /**
     * Get the OAuth refresh tokens for the user.
     */
    public function oauthRefreshTokens(): HasMany
    {
        return $this->hasMany(OauthRefreshToken::class);
    }

    // ==========================================
    // API Key Relationships
    // ==========================================

    /**
     * Get the user's API keys.
     */
    public function userApiKeys(): HasMany
    {
        return $this->hasMany(UserApiKey::class);
    }

    /**
     * Get the system API keys created by this user (admin).
     */
    public function systemApiKeysCreated(): HasMany
    {
        return $this->hasMany(SystemApiKey::class, 'created_by');
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Calculate the total storage used by the user.
     */
    public function getStorageUsedAttribute(): int
    {
        return $this->files()->sum('size');
    }

    /**
     * Check if the user has exceeded their quota.
     */
    public function isOverQuota(): bool
    {
        if ($this->quota_bytes === null) {
            return false; // Unlimited quota
        }

        return $this->storage_used > $this->quota_bytes;
    }

    /**
     * Get the remaining storage quota.
     */
    public function getRemainingQuotaAttribute(): ?int
    {
        if ($this->quota_bytes === null) {
            return null; // Unlimited
        }

        return max(0, $this->quota_bytes - $this->storage_used);
    }

    // ==========================================
    // JWT Subject Implementation
    // ==========================================

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
        ];
    }
}
