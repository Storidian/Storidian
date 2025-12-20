<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group which applies stateless handling.
|
| API routes are prefixed with /api by default.
|
*/

// OAuth Token Endpoints (stateless)
Route::post('/oauth/token', [OAuthController::class, 'token']);
Route::post('/oauth/revoke', [OAuthController::class, 'revoke']);

// API v1 Routes
Route::prefix('v1')->group(function () {
    // Protected routes requiring JWT authentication
    Route::middleware(['auth:api', 'active'])->group(function () {
        // Auth endpoints
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/me', [AuthController::class, 'update']);
        Route::post('/auth/me/password', [AuthController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);

        // File endpoints (placeholder - will be implemented later)
        Route::middleware('scopes:files:read')->group(function () {
            // Route::get('/files', [FileController::class, 'index']);
            // Route::get('/files/{id}', [FileController::class, 'show']);
        });

        Route::middleware('scopes:files:write')->group(function () {
            // Route::post('/files', [FileController::class, 'store']);
            // Route::put('/files/{id}', [FileController::class, 'update']);
        });

        Route::middleware('scopes:files:delete')->group(function () {
            // Route::delete('/files/{id}', [FileController::class, 'destroy']);
        });
    });
});

