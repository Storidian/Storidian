<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes handle session-based authentication for the OAuth flow.
| The login form and consent screen are served here.
|
*/

// OAuth Authorization Endpoint (initiates the flow)
Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// OAuth Consent Routes (for third-party apps)
Route::get('/oauth/consent', [LoginController::class, 'showConsent'])->name('oauth.consent');
Route::post('/oauth/consent', [LoginController::class, 'handleConsent']);

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

// Main App (SPA) - catch-all for Vue router
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|oauth|login|logout|forgot-password|reset-password).*$');
