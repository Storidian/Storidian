<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        protected OAuthService $oauthService
    ) {}

    /**
     * Get the authenticated user's information.
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     * PUT /api/v1/auth/me
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Change the authenticated user's password.
     * POST /api/v1/auth/me/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => $request->input('password'),
        ]);

        // Optionally revoke all refresh tokens to force re-login on other devices
        // $this->oauthService->revokeAllUserTokens($user);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Log out the authenticated user (revoke current token).
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Invalidate the current JWT token
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token might already be invalid, continue anyway
        }

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Log out from all devices (revoke all refresh tokens).
     * POST /api/v1/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all refresh tokens
        $count = $this->oauthService->revokeAllUserTokens($user);

        // Invalidate current JWT
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Continue anyway
        }

        return response()->json([
            'message' => 'Successfully logged out from all devices.',
            'sessions_revoked' => $count,
        ]);
    }
}

