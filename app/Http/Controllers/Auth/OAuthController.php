<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OAuthController extends Controller
{
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Handle the OAuth authorization request.
     * GET /oauth/authorize
     */
    public function authorize(Request $request): RedirectResponse
    {
        // Validate required parameters
        $request->validate([
            'client_id' => 'required|string',
            'redirect_uri' => 'required|url',
            'response_type' => 'required|in:code',
            'state' => 'nullable|string',
            'scope' => 'nullable|string',
            'code_challenge' => 'nullable|string',
            'code_challenge_method' => 'nullable|in:plain,S256',
        ]);

        $clientId = $request->input('client_id');
        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state');
        $scope = $request->input('scope', '');
        $codeChallenge = $request->input('code_challenge');
        $codeChallengeMethod = $request->input('code_challenge_method', 'S256');

        // Validate client and redirect URI
        $validation = $this->oauthService->validateClient($clientId, $redirectUri);

        if (! $validation['valid']) {
            // For security, don't redirect to unvalidated URIs
            // Show error page instead
            abort(400, $validation['error_description'] ?? 'Invalid client or redirect URI.');
        }

        $client = $validation['client'];

        // Check if PKCE is required for public clients
        if ($client->is_public && ! $codeChallenge) {
            return $this->redirectWithError(
                $redirectUri,
                'invalid_request',
                'PKCE code_challenge is required for public clients.',
                $state
            );
        }

        // Validate requested scopes
        $scopes = $this->oauthService->validateScopes($scope, $client);

        // Store OAuth parameters in session for after login
        session([
            'oauth' => [
                'client_id' => $clientId,
                'client' => $client,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scopes' => $scopes,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ],
        ]);

        // Check if user is already authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // For first-party clients, skip consent and issue code directly
            if ($client->is_first_party) {
                return $this->issueAuthorizationCode($user, $redirectUri, $state);
            }

            // For third-party clients, show consent screen
            return redirect()->route('oauth.consent');
        }

        // Redirect to login
        return redirect()->route('login');
    }

    /**
     * Handle the OAuth token request.
     * POST /oauth/token
     */
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'grant_type' => 'required|in:authorization_code,refresh_token',
            'client_id' => 'required|string',
            'client_secret' => 'nullable|string',
            'code' => 'required_if:grant_type,authorization_code|string',
            'code_verifier' => 'nullable|string',
            'redirect_uri' => 'required_if:grant_type,authorization_code|url',
            'refresh_token' => 'required_if:grant_type,refresh_token|string',
        ]);

        $grantType = $request->input('grant_type');
        $clientId = $request->input('client_id');

        if ($grantType === 'authorization_code') {
            $result = $this->oauthService->exchangeCodeForTokens(
                $request->input('code'),
                $request->input('code_verifier', ''),
                $clientId,
                $request->input('redirect_uri')
            );
        } else {
            // refresh_token grant
            $result = $this->oauthService->refreshTokens(
                $request->input('refresh_token'),
                $clientId
            );
        }

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Revoke a token.
     * POST /oauth/revoke
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'token_type_hint' => 'nullable|in:refresh_token,access_token',
        ]);

        $token = $request->input('token');
        $tokenTypeHint = $request->input('token_type_hint', 'refresh_token');

        if ($tokenTypeHint === 'refresh_token') {
            $this->oauthService->revokeRefreshToken($token);
        }

        // For access tokens (JWTs), we can't truly revoke them without a blacklist
        // The jwt-auth package has blacklist functionality that could be used here
        // For now, we just acknowledge the request

        // OAuth 2.0 spec says to always return 200 for revoke
        return response()->json(['revoked' => true]);
    }

    /**
     * Issue an authorization code and redirect back to the client.
     */
    protected function issueAuthorizationCode($user, string $redirectUri, ?string $state): RedirectResponse
    {
        $oauth = session('oauth');

        if (! $oauth) {
            abort(400, 'OAuth session expired. Please try again.');
        }

        // Create authorization code
        $authCode = $this->oauthService->createAuthorizationCode(
            $user,
            $oauth['client'],
            $oauth['scopes'] ?? [],
            $oauth['redirect_uri'],
            $oauth['code_challenge'] ?? null,
            $oauth['code_challenge_method'] ?? 'S256'
        );

        // Clear OAuth session data
        session()->forget('oauth');

        // Build redirect URL with code and state
        $params = ['code' => $authCode->id];
        if ($state) {
            $params['state'] = $state;
        }

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect($redirectUri.$separator.http_build_query($params));
    }

    /**
     * Redirect with an OAuth error.
     */
    protected function redirectWithError(
        string $redirectUri,
        string $error,
        string $description,
        ?string $state
    ): RedirectResponse {
        $params = [
            'error' => $error,
            'error_description' => $description,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect($redirectUri.$separator.http_build_query($params));
    }
}

