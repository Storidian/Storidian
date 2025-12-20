<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Show the login form.
     * GET /login
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        // If user is already logged in, handle OAuth flow or redirect home
        if (Auth::check()) {
            if (session()->has('oauth')) {
                return $this->handleAuthenticatedOAuthFlow();
            }

            return redirect('/');
        }

        // Get OAuth context if present
        $oauth = session('oauth');

        return view('auth.login', [
            'oauth' => $oauth,
            'client_name' => $oauth['client']->name ?? null,
        ]);
    }

    /**
     * Handle login form submission.
     * POST /login
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting
        $throttleKey = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [__('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        // Check if user is active
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => [__('Your account has been deactivated.')],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        // Check if this is an OAuth flow
        if (session()->has('oauth')) {
            return $this->handleAuthenticatedOAuthFlow();
        }

        // Regular login redirect
        return redirect()->intended('/');
    }

    /**
     * Handle the OAuth flow for an authenticated user.
     */
    protected function handleAuthenticatedOAuthFlow(): RedirectResponse
    {
        $oauth = session('oauth');
        $user = Auth::user();
        $client = $oauth['client'];

        // For first-party clients, issue code directly
        if ($client->is_first_party) {
            return $this->issueAuthorizationCode($user, $oauth);
        }

        // For third-party clients, redirect to consent
        return redirect()->route('oauth.consent');
    }

    /**
     * Show the OAuth consent screen.
     * GET /oauth/consent
     */
    public function showConsent(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $oauth = session('oauth');
        if (! $oauth) {
            return redirect('/');
        }

        $client = $oauth['client'];

        // First-party clients skip consent
        if ($client->is_first_party) {
            return $this->issueAuthorizationCode(Auth::user(), $oauth);
        }

        return view('auth.consent', [
            'client' => $client,
            'scopes' => $oauth['scopes'] ?? [],
        ]);
    }

    /**
     * Handle consent form approval.
     * POST /oauth/consent
     */
    public function handleConsent(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $oauth = session('oauth');
        if (! $oauth) {
            return redirect('/');
        }

        if ($request->input('decision') === 'deny') {
            // User denied - redirect with error
            $redirectUri = $oauth['redirect_uri'];
            $params = [
                'error' => 'access_denied',
                'error_description' => 'The user denied the request.',
            ];
            if ($oauth['state']) {
                $params['state'] = $oauth['state'];
            }

            session()->forget('oauth');
            $separator = str_contains($redirectUri, '?') ? '&' : '?';

            return redirect($redirectUri.$separator.http_build_query($params));
        }

        // User approved
        return $this->issueAuthorizationCode(Auth::user(), $oauth);
    }

    /**
     * Issue an authorization code and redirect to the client.
     */
    protected function issueAuthorizationCode($user, array $oauth): RedirectResponse
    {
        $authCode = $this->oauthService->createAuthorizationCode(
            $user,
            $oauth['client'],
            $oauth['scopes'] ?? [],
            $oauth['redirect_uri'],
            $oauth['code_challenge'] ?? null,
            $oauth['code_challenge_method'] ?? 'S256'
        );

        // Clear OAuth session
        session()->forget('oauth');

        // Build redirect URL
        $redirectUri = $oauth['redirect_uri'];
        $params = ['code' => $authCode->id];
        if ($oauth['state']) {
            $params['state'] = $oauth['state'];
        }

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect($redirectUri.$separator.http_build_query($params));
    }

    /**
     * Log the user out.
     * POST /logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

