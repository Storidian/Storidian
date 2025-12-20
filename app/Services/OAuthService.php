<?php

namespace App\Services;

use App\Models\OauthAuthorizationCode;
use App\Models\OauthClient;
use App\Models\OauthRefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OAuthService
{
    /**
     * Authorization code TTL in seconds.
     */
    protected const AUTH_CODE_TTL = 60;

    /**
     * Refresh token TTL in days.
     */
    protected const REFRESH_TOKEN_TTL_DAYS = 7;

    /**
     * Validate an OAuth client and redirect URI.
     *
     * @return array{valid: bool, error?: string, client?: OauthClient}
     */
    public function validateClient(string $clientId, string $redirectUri): array
    {
        $client = OauthClient::where('client_id', $clientId)->first();

        if (! $client) {
            return [
                'valid' => false,
                'error' => 'invalid_client',
                'error_description' => 'The client identifier provided is invalid.',
            ];
        }

        // First-party clients can use any redirect URI (they're trusted)
        // This allows flexible development environments
        if ($client->is_first_party) {
            return [
                'valid' => true,
                'client' => $client,
            ];
        }

        // Third-party clients must use registered redirect URIs
        $allowedRedirectUris = $client->redirect_uris ?? [];

        if (! in_array($redirectUri, $allowedRedirectUris, true)) {
            return [
                'valid' => false,
                'error' => 'invalid_redirect_uri',
                'error_description' => 'The redirect URI provided does not match any registered redirect URIs.',
            ];
        }

        return [
            'valid' => true,
            'client' => $client,
        ];
    }

    /**
     * Validate PKCE code verifier against the stored code challenge.
     */
    public function validatePKCE(string $codeVerifier, string $codeChallenge, string $method = 'S256'): bool
    {
        if ($method === 'plain') {
            return hash_equals($codeChallenge, $codeVerifier);
        }

        // S256: Base64URL(SHA256(code_verifier))
        $hash = hash('sha256', $codeVerifier, true);
        $computed = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        return hash_equals($codeChallenge, $computed);
    }

    /**
     * Generate a PKCE code challenge from a verifier (for testing purposes).
     */
    public function generateCodeChallenge(string $codeVerifier, string $method = 'S256'): string
    {
        if ($method === 'plain') {
            return $codeVerifier;
        }

        $hash = hash('sha256', $codeVerifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * Create an authorization code for the user.
     */
    public function createAuthorizationCode(
        User $user,
        OauthClient $client,
        array $scopes,
        string $redirectUri,
        ?string $codeChallenge = null,
        string $codeChallengeMethod = 'S256'
    ): OauthAuthorizationCode {
        // Generate a random authorization code
        $code = Str::random(64);

        return OauthAuthorizationCode::create([
            'id' => $code,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'scopes' => $scopes,
            'redirect_uri' => $redirectUri,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'revoked' => false,
            'expires_at' => now()->addSeconds(self::AUTH_CODE_TTL),
        ]);
    }

    /**
     * Exchange an authorization code for access and refresh tokens.
     *
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, scope: string}|array{error: string, error_description: string}
     */
    public function exchangeCodeForTokens(
        string $code,
        string $codeVerifier,
        string $clientId,
        string $redirectUri
    ): array {
        $authCode = OauthAuthorizationCode::find($code);

        if (! $authCode) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code is invalid.',
            ];
        }

        if ($authCode->revoked) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code has been revoked.',
            ];
        }

        if ($authCode->isExpired()) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The authorization code has expired.',
            ];
        }

        // Validate client
        $client = $authCode->client;
        if (! $client || $client->client_id !== $clientId) {
            return [
                'error' => 'invalid_client',
                'error_description' => 'The client identifier is invalid.',
            ];
        }

        // Validate redirect URI
        if ($authCode->redirect_uri !== $redirectUri) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The redirect URI does not match.',
            ];
        }

        // Validate PKCE (required for public clients)
        if ($authCode->code_challenge) {
            if (! $codeVerifier) {
                return [
                    'error' => 'invalid_grant',
                    'error_description' => 'The code verifier is required.',
                ];
            }

            $method = $authCode->code_challenge_method ?? 'S256';
            if (! $this->validatePKCE($codeVerifier, $authCode->code_challenge, $method)) {
                return [
                    'error' => 'invalid_grant',
                    'error_description' => 'The code verifier is invalid.',
                ];
            }
        }

        // Revoke the authorization code (single-use)
        $authCode->revoke();

        // Get the user
        $user = $authCode->user;

        if (! $user || ! $user->is_active) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The user account is inactive.',
            ];
        }

        // Generate tokens
        return $this->generateTokens($user, $client, $authCode->scopes ?? []);
    }

    /**
     * Refresh tokens using a refresh token.
     *
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, scope: string}|array{error: string, error_description: string}
     */
    public function refreshTokens(string $refreshToken, string $clientId): array
    {
        $token = OauthRefreshToken::find($refreshToken);

        if (! $token) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token is invalid.',
            ];
        }

        if ($token->revoked) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token has been revoked.',
            ];
        }

        if ($token->isExpired()) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token has expired.',
            ];
        }

        // Validate client
        $client = $token->client;
        if (! $client || $client->client_id !== $clientId) {
            return [
                'error' => 'invalid_client',
                'error_description' => 'The client identifier is invalid.',
            ];
        }

        // Get the user
        $user = $token->user;

        if (! $user || ! $user->is_active) {
            return [
                'error' => 'invalid_grant',
                'error_description' => 'The user account is inactive.',
            ];
        }

        // Revoke the old refresh token
        $token->revoke();

        // Generate new tokens
        return $this->generateTokens($user, $client, $token->scopes ?? []);
    }

    /**
     * Generate access and refresh tokens for a user.
     *
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, scope: string}
     */
    protected function generateTokens(User $user, OauthClient $client, array $scopes): array
    {
        // Generate JWT access token with scopes
        $customClaims = [
            'scopes' => $scopes,
            'client_id' => $client->client_id,
        ];

        $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

        // Get TTL from JWT config (in minutes)
        $ttlMinutes = config('jwt.ttl', 60);

        // Create refresh token
        $refreshTokenId = Str::random(64);
        OauthRefreshToken::create([
            'id' => $refreshTokenId,
            'user_id' => $user->id,
            'client_id' => $client->id,
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenId,
            'token_type' => 'Bearer',
            'expires_in' => $ttlMinutes * 60, // Convert to seconds
            'scope' => implode(' ', $scopes),
        ];
    }

    /**
     * Revoke a refresh token.
     */
    public function revokeRefreshToken(string $refreshToken): bool
    {
        $token = OauthRefreshToken::find($refreshToken);

        if (! $token) {
            return false;
        }

        $token->revoke();

        return true;
    }

    /**
     * Revoke all refresh tokens for a user.
     */
    public function revokeAllUserTokens(User $user): int
    {
        return OauthRefreshToken::where('user_id', $user->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    /**
     * Parse and validate requested scopes against client's allowed scopes.
     *
     * @return array<string>
     */
    public function validateScopes(string $requestedScopes, OauthClient $client): array
    {
        $requested = array_filter(explode(' ', $requestedScopes));
        $allowed = $client->scopes ?? [];

        // If client has wildcard scope, allow all requested
        if (in_array('*', $allowed, true)) {
            return $requested;
        }

        // Filter to only allowed scopes
        return array_values(array_intersect($requested, $allowed));
    }
}

