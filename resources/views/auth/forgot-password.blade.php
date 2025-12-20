<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - Storidian</title>
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
            --success-color: #10b981;
            --success-bg: #ecfdf5;
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

        .container {
            width: 100%;
            max-width: 420px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 18px;
            filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-color-muted);
            line-height: 1.5;
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

        .error-message {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .success-message {
            background: var(--success-bg);
            color: var(--success-color);
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

        .footer {
            text-align: center;
            margin-top: 24px;
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <img src="/storidian-icon.png" alt="Storidian" class="logo">
                <h1 class="title">Forgot your password?</h1>
                <p class="subtitle">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
            </div>

            @if($status)
                <div class="success-message">
                    {{ $status }}
                </div>
            @endif

            @if($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
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

                <button type="submit" class="btn btn-primary">
                    Send reset link
                </button>
            </form>

            <div class="footer">
                <a href="{{ route('login') }}">Back to sign in</a>
            </div>
        </div>
    </div>
</body>
</html>

