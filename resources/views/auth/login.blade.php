<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Storidian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-color-hover: #2563eb;
            --text-color: #1f2937;
            --text-color-muted: #6b7280;
            --bg-color: #f9fafb;
            --card-bg: #f6f6f6;
            --border-color: #e5e7eb;
            --error-color: #ef4444;
            --error-bg: #fef2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Outfit", sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            background-image: url('/images/default-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background-color: var(--card-bg);
            border-radius: 18px;
            filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 14px;
            color: var(--text-color-muted);
        }

        .oauth-info {
            background: white;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--text-color-muted);
            text-align: center;
        }

        .oauth-info strong {
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            font-family: inherit;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--text-color);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error {
            border-color: var(--error-color);
        }

        .form-checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .form-checkbox-label {
            font-size: 14px;
            color: var(--text-color-muted);
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn {
            width: 100%;
            padding: 14px 24px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-color-hover);
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="/storidian-icon.png" alt="Storidian" class="login-logo">
                <h1 class="login-title">Welcome back</h1>
                <p class="login-subtitle">Sign in to your account to continue</p>
            </div>

            @if($client_name && !str_starts_with($client_name, 'Storidian'))
                <div class="oauth-info">
                    Signing in to <strong>{{ $client_name }}</strong>
                </div>
            @endif

            @if($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input @error('email') error @enderror"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input @error('password') error @enderror"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <div class="form-group">
                    <div class="form-checkbox-group">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="form-checkbox"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label for="remember" class="form-checkbox-label">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Sign in
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            </div>
        </div>
    </div>
</body>
</html>

