<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class ValidateScopes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$scopes  Required scopes (any one must be present)
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        if (empty($scopes)) {
            return $next($request);
        }

        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenScopes = $payload->get('scopes', []);

            // Check if token has wildcard scope
            if (in_array('*', $tokenScopes, true)) {
                return $next($request);
            }

            // Check if any required scope is present
            foreach ($scopes as $scope) {
                if (in_array($scope, $tokenScopes, true)) {
                    return $next($request);
                }

                // Check for wildcard patterns (e.g., "files:*" matches "files:read")
                $scopeParts = explode(':', $scope);
                if (count($scopeParts) > 1) {
                    $wildcardScope = $scopeParts[0].':*';
                    if (in_array($wildcardScope, $tokenScopes, true)) {
                        return $next($request);
                    }
                }
            }

            return response()->json([
                'error' => [
                    'code' => 'INSUFFICIENT_SCOPE',
                    'message' => 'The access token does not have the required scopes.',
                    'required_scopes' => $scopes,
                ],
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'The access token is invalid.',
                ],
            ], 401);
        }
    }
}

