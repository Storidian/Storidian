<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Check that the authenticated user's account is active.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_DISABLED',
                    'message' => 'Your account has been deactivated. Please contact an administrator.',
                ],
            ], 403);
        }

        return $next($request);
    }
}

