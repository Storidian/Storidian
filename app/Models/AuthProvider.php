<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthProvider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'provider_class',
        'config',
        'enabled',
        'allow_registration',
        'trust_email',
        'sort_order',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'config', // Contains sensitive credentials
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'enabled' => 'boolean',
            'allow_registration' => 'boolean',
            'trust_email' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the user auth provider links for this provider.
     */
    public function userAuthProviders(): HasMany
    {
        return $this->hasMany(UserAuthProvider::class, 'provider_id');
    }
}

