<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Authorize Application - Storidian</title>
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
            --danger-color: #ef4444;
            --danger-hover: #dc2626;
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

        .consent-container {
            width: 100%;
            max-width: 480px;
        }

        .consent-card {
            background-color: var(--card-bg);
            border-radius: 18px;
            filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
            padding: 40px;
        }

        .consent-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .consent-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
        }

        .consent-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .consent-subtitle {
            font-size: 14px;
            color: var(--text-color-muted);
        }

        .app-info {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: center;
        }

        .app-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 4px;
        }

        .app-request {
            font-size: 14px;
            color: var(--text-color-muted);
        }

        .scopes-section {
            margin-bottom: 24px;
        }

        .scopes-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 12px;
        }

        .scopes-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .scope-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .scope-item:last-child {
            border-bottom: none;
        }

        .scope-icon {
            width: 20px;
            height: 20px;
            color: var(--primary-color);
        }

        .scope-text {
            font-size: 14px;
            color: var(--text-color);
        }

        .user-info {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-label {
            font-size: 13px;
            color: var(--text-color-muted);
        }

        .user-email {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-color);
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
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

        .btn-secondary {
            background: white;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-color);
        }
    </style>
</head>
<body>
    <div class="consent-container">
        <div class="consent-card">
            <div class="consent-header">
                <img src="/storidian-icon.png" alt="Storidian" class="consent-logo">
                <h1 class="consent-title">Authorize Application</h1>
                <p class="consent-subtitle">Review the permissions being requested</p>
            </div>

            <div class="app-info">
                <div class="app-name">{{ $client->name }}</div>
                <div class="app-request">wants to access your Storidian account</div>
            </div>

            @if(count($scopes) > 0)
                <div class="scopes-section">
                    <div class="scopes-title">This application will be able to:</div>
                    <div class="scopes-list">
                        @foreach($scopes as $scope)
                            <div class="scope-item">
                                <svg class="scope-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span class="scope-text">{{ ucfirst(str_replace([':', '_'], [' ', ' '], $scope)) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="user-info">
                <span class="user-label">Signed in as</span>
                <span class="user-email">{{ auth()->user()->email }}</span>
            </div>

            <form method="POST" action="{{ route('oauth.consent') }}">
                @csrf
                <div class="button-group">
                    <button type="submit" name="decision" value="deny" class="btn btn-secondary">
                        Deny
                    </button>
                    <button type="submit" name="decision" value="approve" class="btn btn-primary">
                        Authorize
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

