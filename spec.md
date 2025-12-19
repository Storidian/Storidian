# Storidian Technical Specification

> **Version:** 0.1.0-draft  
> **Last Updated:** 2024-12-18  
> **Status:** Planning Phase

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Core Concepts](#core-concepts)
4. [Authentication & Authorization](#authentication--authorization)
5. [API Design](#api-design)
6. [Database Schema](#database-schema)
7. [Storage Layer](#storage-layer)
8. [File Operations](#file-operations)
9. [Background Jobs](#background-jobs)
10. [Web UI](#web-ui)
11. [Mobile Apps](#mobile-apps)
12. [Future: Sync](#future-sync)
13. [Development Phases](#development-phases)

---

## Overview

Storidian is a self-hosted file storage platform that prioritizes user control, security, and extensibility. The system follows an **API-first architecture** where all clients (web UI, iOS, Android, third-party integrations) interact through a unified REST API.

### Design Principles

- **API-First:** The web UI consumes the same API available to external clients
- **Storage Agnostic:** Support multiple storage backends via Laravel Flysystem
- **Multi-Tenant:** Full multi-user support with admin controls
- **Extensible Auth:** Native auth + SSO providers (Google, Microsoft, OIDC, etc.)
- **Progressive Enhancement:** Core features first, sync and collaboration later

### Platform Priority

1. **Web UI** (Vue.js 3) - Primary development focus
2. **iOS App** - Native Swift/SwiftUI
3. **Android App** - Native Kotlin

---

## Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         Clients                                 │
├─────────────┬─────────────┬─────────────┬─────────────┬─────────┤
│   Web UI    │   iOS App   │ Android App │  API Keys   │  OAuth  │
│  (Vue.js)   │   (Swift)   │  (Kotlin)   │ (System)    │ Clients │
└──────┬──────┴──────┬──────┴──────┬──────┴──────┬──────┴────┬────┘
       │             │             │             │           │
       └─────────────┴─────────────┴─────────────┴───────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │         REST API            │
                    │     (Laravel + JWT)         │
                    └──────────────┬──────────────┘
                                   │
       ┌───────────────────────────┼───────────────────────────┐
       │                           │                           │
┌──────▼──────┐          ┌─────────▼─────────┐        ┌────────▼────────┐
│   Storage   │          │     Database      │        │  Queue Workers  │
│  (Flysystem)│          │   (PostgreSQL)    │        │    (Redis)      │
└──────┬──────┘          └───────────────────┘        └─────────────────┘
       │
       ├── Local Disk
       ├── S3 / MinIO
       ├── SFTP
       └── SMB/CIFS
```

### Component Breakdown

| Component | Technology | Purpose |
|-----------|------------|---------|
| API | Laravel 12 | REST endpoints, business logic |
| Database | PostgreSQL (default) | Metadata, user data, relationships |
| Cache | Redis | Session, queue, cache |
| Storage | Flysystem | File storage abstraction |
| Queue | Laravel Queues | Background job processing |
| Web UI | Vue.js 3 + Vite | Single-page application |
| Auth | php-open-source-saver/jwt-auth | JWT token management |

---

## Core Concepts

### Files

A file represents a stored object with metadata.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `user_id` | FK | Owner of the file |
| `folder_id` | FK (nullable) | Parent folder (null = root) |
| `name` | string | Display name |
| `original_name` | string | Name at upload time |
| `mime_type` | string | MIME type |
| `size` | bigint | Size in bytes |
| `storage_path` | string | Path in storage backend |
| `storage_disk` | string | Flysystem disk identifier |
| `checksum` | string | SHA-256 hash |
| `metadata` | json | Additional metadata (EXIF, etc.) |
| `created_at` | timestamp | Upload timestamp |
| `updated_at` | timestamp | Last modification |
| `deleted_at` | timestamp | Soft delete timestamp |

### Folders

Virtual containers for organizing files.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `user_id` | FK | Owner |
| `parent_id` | FK (nullable) | Parent folder (null = root) |
| `name` | string | Display name |
| `color` | string (nullable) | UI color hint |
| `icon` | string (nullable) | UI icon hint |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

### Tags

User-defined labels for files and folders.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `user_id` | FK | Owner |
| `name` | string | Tag name |
| `color` | string | Display color |
| `created_at` | timestamp | |

### Virtual Folders

Dynamic views based on tag queries.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `user_id` | FK | Owner |
| `name` | string | Display name |
| `tag_query` | json | Tag filter expression |
| `sort_order` | string | Default sort |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Tag Query Examples:**

```json
// All files with tag "work"
{ "include": ["work"] }

// Files with "photos" AND "2024"
{ "include": ["photos", "2024"], "operator": "AND" }

// Files with "documents" but NOT "archived"
{ "include": ["documents"], "exclude": ["archived"] }
```

---

## Authentication & Authorization

### Authentication Architecture

Storidian implements **OAuth 2.0 as its primary authentication mechanism**. All clients - including first-party apps (web UI, iOS, Android) - authenticate via OAuth. This ensures:

- First-party and third-party apps use identical auth flows
- The OAuth implementation is battle-tested by daily use
- Clean separation between the API and its consumers
- Consistent token-based authentication across all platforms

#### OAuth 2.0 Provider

Storidian is an OAuth 2.0 Authorization Server supporting:

- **Authorization Code Flow** (with PKCE) - For web and mobile apps
- **Refresh Token Flow** - For maintaining sessions

##### OAuth Clients (Applications)

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `name` | string | Application name |
| `client_id` | string | Public client identifier |
| `client_secret` | string (nullable) | Secret for confidential clients |
| `redirect_uris` | json | Allowed redirect URIs |
| `scopes` | json | Allowed scopes for this client |
| `is_first_party` | boolean | Skip consent screen |
| `is_public` | boolean | Public client (no secret, PKCE required) |
| `created_by` | FK (nullable) | Admin who created (null = system) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

##### First-Party Clients (Pre-registered)

These are created during installation:

| Client | Type | Notes |
|--------|------|-------|
| Storidian Web | Confidential | Same-origin, session-based |
| Storidian iOS | Public | PKCE required |
| Storidian Android | Public | PKCE required |

First-party clients (`is_first_party = true`) skip the consent screen since the user implicitly trusts them.

##### OAuth Scopes

| Scope | Description |
|-------|-------------|
| `profile` | Read user profile |
| `profile:write` | Update user profile |
| `files:read` | Read files and folders |
| `files:write` | Create, update, move files |
| `files:delete` | Delete files (soft delete) |
| `tags:read` | Read tags |
| `tags:write` | Manage tags |
| `shares:read` | View shares (Erugo integration) |
| `shares:write` | Create/manage shares |
| `admin` | Admin operations (admin users only) |

##### OAuth Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /oauth/authorize` | Authorization endpoint |
| `POST /oauth/token` | Token endpoint |
| `POST /oauth/revoke` | Revoke token |
| `GET /oauth/userinfo` | OpenID Connect userinfo |
| `GET /.well-known/openid-configuration` | OIDC discovery |
| `GET /.well-known/jwks.json` | JSON Web Key Set |

##### Third-Party App Registration

Admins can register third-party OAuth clients via:
- Admin UI → Settings → OAuth Applications
- API: `POST /admin/oauth-clients`

When a third-party app initiates auth, users see a consent screen showing:
- App name and developer
- Requested scopes with descriptions
- Approve/Deny buttons

#### SSO Providers (External Identity)

Users can also authenticate via external identity providers. Following the Erugo pattern, admins can configure:

- **Google** (via Laravel Socialite)
- **Microsoft/Azure AD** (via Socialite)
- **Authentik** (via Socialite)
- **Generic OIDC** (via jumbojett/OpenIDConnectClient)

Provider configuration stored in `auth_providers` table with:
- `allow_registration` - Create users on first SSO login
- `trust_email` - Auto-link accounts with matching email

**Flow:** External SSO → Storidian account link → Storidian OAuth token issued

This means external SSO is used to *identify* the user, but the app still receives a Storidian OAuth token (not the external provider's token).

#### Native Authentication (Email/Password)

For users without SSO, native authentication is supported:

- Email + password with optional 2FA (TOTP)
- Password requirements configurable by admin
- Account lockout after failed attempts

**Important:** The login form is hosted by the Storidian API itself, not by client applications. This is the standard OAuth security model:

1. Client apps (Web UI, iOS, Android) **never** handle user credentials directly
2. When authentication is needed, the client redirects to `https://storidian.example/oauth/authorize`
3. The API presents its own login form (rendered server-side via Blade)
4. User submits credentials directly to the API
5. On success, the API redirects back to the client with an authorization code
6. Client exchanges the code for tokens

This approach ensures:
- Credentials are only ever entered on the trusted API domain
- Client applications cannot intercept or store passwords
- SSO and native login use the same flow from the client's perspective
- Consistent security model across all platforms

#### API Keys (Machine-to-Machine)

Storidian supports two types of API keys for programmatic access:

##### User API Keys

Created by users for their own integrations. Scoped to the user's own files.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `user_id` | FK | Owner |
| `name` | string | User-provided name |
| `key_hash` | string | Hashed API key |
| `key_prefix` | string | First 8 chars (e.g., `strd_u_abc`) |
| `scopes` | json | Permission scopes |
| `folder_scope` | FK (nullable) | Limit to folder subtree |
| `expires_at` | timestamp (nullable) | Optional expiration |
| `last_used_at` | timestamp | Last API call |
| `created_at` | timestamp | |

**Use cases:**
- Personal backup scripts
- CI/CD pipelines for a user's projects
- Custom integrations with user's own files

##### System API Keys

Created by admins for service-to-service integrations. Can access files across users within defined scopes.

| Property | Type | Description |
|----------|------|-------------|
| `id` | UUID | Unique identifier |
| `created_by` | FK | Admin who created |
| `name` | string | Integration name |
| `key_hash` | string | Hashed API key |
| `key_prefix` | string | First 8 chars (e.g., `strd_s_xyz`) |
| `scopes` | json | Permission scopes |
| `allowed_users` | json (nullable) | Limit to specific users (null = all) |
| `expires_at` | timestamp (nullable) | Optional expiration |
| `last_used_at` | timestamp | Last API call |
| `created_at` | timestamp | |

**Use cases:**
- Erugo fetching shared files on behalf of any user
- Backup systems that need access to all user data
- Admin monitoring/reporting tools
- Migration scripts

**Security considerations:**
- System keys are powerful - admin-only creation
- Audit log all system key usage
- Require explicit scope grants (no implicit "all access")
- Consider IP allowlisting for sensitive integrations

##### Scope Examples

```json
// User key: Full access to own files
{ "files": ["read", "write", "delete"] }

// User key: Read-only, specific folder
// (combined with folder_scope field)
{ "files": ["read"] }

// System key: Read files for any user (Erugo integration)
{ "files": ["read"], "system": true }

// System key: Full admin access (backup system)
{ "files": ["read", "write", "delete"], "users": ["read"], "system": true }
```

##### Key Prefixes

Keys use prefixes for easy identification:
- `strd_u_` - User API key
- `strd_s_` - System API key

### OAuth 2.0 Flows

#### Authorization Code Flow (Web & Mobile Apps)

```
┌─────────┐                    ┌─────────────┐                 ┌──────────┐
│  Client │                    │  Storidian  │                 │   User   │
│  (App)  │                    │   (OAuth)   │                 │          │
└────┬────┘                    └──────┬──────┘                 └────┬─────┘
     │                                │                              │
     │  GET /oauth/authorize          │                              │
     │  ?client_id=...                │                              │
     │  &redirect_uri=...             │                              │
     │  &scope=files:read+files:write │                              │
     │  &code_challenge=...           │                              │
     │  &state=...                    │                              │
     │───────────────────────────────>│                              │
     │                                │                              │
     │                                │  Show login (if not authed)  │
     │                                │  Show consent (if 3rd party) │
     │                                │─────────────────────────────>│
     │                                │                              │
     │                                │  User approves               │
     │                                │<─────────────────────────────│
     │                                │                              │
     │  302 Redirect to redirect_uri  │                              │
     │  ?code=AUTH_CODE&state=...     │                              │
     │<───────────────────────────────│                              │
     │                                │                              │
     │  POST /oauth/token             │                              │
     │  { code, code_verifier,        │                              │
     │    client_id, redirect_uri }   │                              │
     │───────────────────────────────>│                              │
     │                                │                              │
     │  { access_token,               │                              │
     │    refresh_token,              │                              │
     │    expires_in, scope }         │                              │
     │<───────────────────────────────│                              │
     │                                │                              │
     │  GET /api/v1/files             │                              │
     │  Authorization: Bearer TOKEN   │                              │
     │───────────────────────────────>│                              │
     │                                │                              │
     │  { data: [...] }               │                              │
     │<───────────────────────────────│                              │
```

#### Token Refresh Flow

```
┌─────────┐                           ┌─────────────┐
│ Client  │                           │  Storidian  │
└────┬────┘                           └──────┬──────┘
     │                                       │
     │  POST /oauth/token                    │
     │  { grant_type: "refresh_token",       │
     │    refresh_token: "...",              │
     │    client_id: "..." }                 │
     │──────────────────────────────────────>│
     │                                       │
     │  { access_token, refresh_token,       │
     │    expires_in }                       │
     │<──────────────────────────────────────│
```

#### Web UI Login (First-Party)

The web UI uses the standard OAuth Authorization Code flow. Being first-party, it skips the consent screen but otherwise follows the same pattern as any other client:

```
User                    Web UI (SPA)              Storidian API
  │                         │                          │
  │  Navigate to /          │                          │
  │────────────────────────>│                          │
  │                         │                          │
  │                         │  Check for valid token   │
  │                         │  (none found)            │
  │                         │                          │
  │  Redirect to API login  │                          │
  │  /oauth/authorize?...   │                          │
  │<────────────────────────│                          │
  │                         │                          │
  │  GET /oauth/authorize   │                          │
  │  (lands on API domain)  │                          │
  │─────────────────────────────────────────────────────>
  │                         │                          │
  │                         │    Show login form       │
  │                         │    (Blade template)      │
  │<─────────────────────────────────────────────────────
  │                         │                          │
  │  POST credentials       │                          │
  │  (directly to API)      │                          │
  │─────────────────────────────────────────────────────>
  │                         │                          │
  │  302 Redirect to SPA    │                          │
  │  ?code=AUTH_CODE        │                          │
  │<─────────────────────────────────────────────────────
  │                         │                          │
  │  Follow redirect        │                          │
  │────────────────────────>│                          │
  │                         │                          │
  │                         │  POST /oauth/token       │
  │                         │  { code, code_verifier } │
  │                         │─────────────────────────>│
  │                         │                          │
  │                         │  { access_token, ... }   │
  │                         │<─────────────────────────│
  │                         │                          │
  │  Render app             │  Store token in memory   │
  │  (authenticated)        │                          │
  │<────────────────────────│                          │
```

Key points:
- User credentials are entered on the **API domain**, not the SPA
- The SPA never sees or handles the user's password
- First-party client skips consent (user already trusts it)
- Same flow works identically for mobile apps

**Token Lifetimes:**
- Access token: 15 minutes
- Refresh token: 7 days (configurable)
- Authorization code: 60 seconds

### Authorization

Role-based access control:

| Role | Permissions |
|------|-------------|
| `user` | Manage own files, folders, tags, API keys |
| `admin` | All user permissions + user management, system settings, storage config |

**Resource-level permissions:**
- Users can only access their own files/folders
- API keys inherit user permissions, optionally scoped to folder subtree
- Sharing permissions handled via Erugo integration (see [Sharing](#sharing-erugo-integration))

---

## API Design

### Base URL

```
https://storidian.example.com/api/v1
```

### Authentication Headers

```http
# JWT Token
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...

# API Key
Authorization: ApiKey strd_abc123...
```

### Response Format

All responses follow a consistent structure:

```json
// Success
{
  "data": { ... },
  "meta": {
    "pagination": { ... }
  }
}

// Error
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The name field is required",
    "details": {
      "name": ["The name field is required"]
    }
  }
}
```

### Endpoints Overview

#### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Create account (if enabled) |
| POST | `/auth/login` | Email/password login |
| POST | `/auth/refresh` | Refresh access token |
| POST | `/auth/logout` | Invalidate tokens |
| GET | `/auth/me` | Current user info |
| PUT | `/auth/me` | Update profile |
| POST | `/auth/me/password` | Change password |
| GET | `/auth/providers` | List enabled SSO providers |
| GET | `/auth/providers/{id}/redirect` | Initiate SSO flow |

#### Files

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/files` | List files (with filters) |
| POST | `/files` | Upload file |
| GET | `/files/{id}` | Get file metadata |
| PUT | `/files/{id}` | Update file metadata |
| DELETE | `/files/{id}` | Soft delete file |
| GET | `/files/{id}/download` | Download file |
| GET | `/files/{id}/thumbnail` | Get thumbnail |
| POST | `/files/{id}/move` | Move to folder |
| POST | `/files/{id}/copy` | Copy file |
| POST | `/files/upload/init` | Initialize chunked upload |
| POST | `/files/upload/{upload_id}/chunk` | Upload chunk |
| POST | `/files/upload/{upload_id}/complete` | Complete chunked upload |

#### Folders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/folders` | List folders (tree or flat) |
| POST | `/folders` | Create folder |
| GET | `/folders/{id}` | Get folder details |
| PUT | `/folders/{id}` | Update folder |
| DELETE | `/folders/{id}` | Soft delete folder |
| GET | `/folders/{id}/contents` | List folder contents |
| POST | `/folders/{id}/move` | Move folder |

#### Tags

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tags` | List user's tags |
| POST | `/tags` | Create tag |
| PUT | `/tags/{id}` | Update tag |
| DELETE | `/tags/{id}` | Delete tag |
| POST | `/files/{id}/tags` | Add tags to file |
| DELETE | `/files/{id}/tags/{tag_id}` | Remove tag from file |
| POST | `/folders/{id}/tags` | Add tags to folder |

#### Virtual Folders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/virtual-folders` | List virtual folders |
| POST | `/virtual-folders` | Create virtual folder |
| PUT | `/virtual-folders/{id}` | Update virtual folder |
| DELETE | `/virtual-folders/{id}` | Delete virtual folder |
| GET | `/virtual-folders/{id}/contents` | Get matching files |

#### Trash

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/trash` | List deleted items |
| POST | `/trash/{id}/restore` | Restore item |
| DELETE | `/trash/{id}` | Permanently delete |
| DELETE | `/trash` | Empty trash |

#### API Keys

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api-keys` | List user's API keys |
| POST | `/api-keys` | Create API key |
| PUT | `/api-keys/{id}` | Update key (name, scopes) |
| DELETE | `/api-keys/{id}` | Revoke key |

#### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/users` | List users |
| POST | `/admin/users` | Create user |
| PUT | `/admin/users/{id}` | Update user |
| DELETE | `/admin/users/{id}` | Disable/delete user |
| GET | `/admin/settings` | Get system settings |
| PUT | `/admin/settings` | Update settings |
| GET | `/admin/storage` | Get storage usage stats |
| GET | `/admin/auth-providers` | List SSO providers |
| POST | `/admin/auth-providers` | Create provider |
| PUT | `/admin/auth-providers/{id}` | Update provider |
| DELETE | `/admin/auth-providers/{id}` | Delete provider |
| GET | `/admin/oauth-clients` | List OAuth applications |
| POST | `/admin/oauth-clients` | Register OAuth app |
| PUT | `/admin/oauth-clients/{id}` | Update OAuth app |
| DELETE | `/admin/oauth-clients/{id}` | Revoke OAuth app |
| GET | `/admin/api-keys` | List system API keys |
| POST | `/admin/api-keys` | Create system API key |
| PUT | `/admin/api-keys/{id}` | Update system API key |
| DELETE | `/admin/api-keys/{id}` | Revoke system API key |

### Pagination

List endpoints support cursor-based pagination:

```http
GET /api/v1/files?limit=50&cursor=eyJpZCI6MTIzfQ
```

Response includes pagination metadata:

```json
{
  "data": [...],
  "meta": {
    "pagination": {
      "limit": 50,
      "next_cursor": "eyJpZCI6MTczfQ",
      "prev_cursor": null,
      "has_more": true
    }
  }
}
```

### Filtering & Sorting

```http
GET /api/v1/files?folder_id=abc123&mime_type=image/*&sort=-created_at
```

| Parameter | Description |
|-----------|-------------|
| `folder_id` | Filter by folder (use `root` for root) |
| `mime_type` | Filter by MIME (supports wildcards) |
| `tag` | Filter by tag(s) |
| `search` | Full-text search in name |
| `sort` | Sort field (prefix `-` for desc) |
| `trashed` | Include soft-deleted items |

---

## Database Schema

### Entity Relationship Diagram

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    users     │       │   folders    │       │    files     │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id (PK)      │──┐    │ id (PK)      │──┐    │ id (PK)      │
│ email        │  │    │ user_id (FK) │◄─┤    │ user_id (FK) │◄─┐
│ name         │  │    │ parent_id(FK)│──┘    │ folder_id(FK)│──┘
│ password     │  │    │ name         │       │ name         │
│ role         │  │    │ ...          │       │ mime_type    │
│ quota        │  │    └──────────────┘       │ size         │
│ ...          │  │                           │ ...          │
└──────────────┘  │    ┌──────────────┐       └──────────────┘
                  │    │    tags      │              │
┌──────────────┐  │    ├──────────────┤              │
│  api_keys    │  │    │ id (PK)      │              │
├──────────────┤  │    │ user_id (FK) │◄─────────────┤
│ id (PK)      │  │    │ name         │              │
│ user_id (FK) │◄─┤    │ color        │    ┌─────────┴─────────┐
│ key_hash     │  │    └──────────────┘    │                   │
│ scopes       │  │           │            │  file_tag (pivot) │
│ ...          │  │           │            │  folder_tag(pivot)│
└──────────────┘  │           ▼            └───────────────────┘
                  │    ┌──────────────┐
┌──────────────┐  │    │virtual_folder│
│auth_providers│  │    ├──────────────┤
├──────────────┤  │    │ id (PK)      │
│ id (PK)      │  │    │ user_id (FK) │◄─┘
│ name         │  │    │ name         │
│provider_class│  │    │ tag_query    │
│ config       │  │    └──────────────┘
│ enabled      │  │
└──────────────┘  │    ┌─────────────────┐
                  │    │user_auth_provider│
                  │    ├─────────────────┤
                  │    │ user_id (FK)    │◄─┘
                  │    │ provider_id(FK) │
                  │    │ provider_user_id│
                  │    └─────────────────┘
```

### Key Tables

See [Core Concepts](#core-concepts) for detailed column definitions.

Additional tables:

#### users

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `email` | string | Unique email |
| `name` | string | Display name |
| `password` | string | Hashed password |
| `role` | enum | `user`, `admin` |
| `quota_bytes` | bigint (nullable) | Storage quota (null = unlimited) |
| `is_active` | boolean | Account enabled |
| `email_verified_at` | timestamp | |
| `two_factor_secret` | string (nullable) | TOTP secret |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### system_settings

| Column | Type | Description |
|--------|------|-------------|
| `key` | string | Setting key |
| `value` | json | Setting value |
| `updated_at` | timestamp | |

**Default Settings:**

```json
{
  "registration_enabled": false,
  "default_quota_bytes": null,
  "max_file_size_bytes": null,
  "trash_purge_days": 30,
  "allowed_mime_types": ["*/*"],
  "storage_disk": "local"
}
```

---

## Storage Layer

### Flysystem Integration

Storidian uses Laravel's Flysystem abstraction to support multiple storage backends. Each user's files are stored with a path structure:

```
{user_uuid}/{year}/{month}/{file_uuid}.{extension}
```

### Supported Backends

| Backend | Driver | Package |
|---------|--------|---------|
| Local Disk | `local` | Built-in |
| Amazon S3 | `s3` | `league/flysystem-aws-s3-v3` |
| S3-Compatible (MinIO, etc.) | `s3` | Same as above |
| SFTP | `sftp` | `league/flysystem-sftp-v3` |
| SMB/CIFS | `smb` | Community driver |

### Storage Configuration

Admins configure storage via the admin UI. Configuration stored in `system_settings`:

```json
{
  "storage": {
    "default_disk": "s3",
    "disks": {
      "local": {
        "driver": "local",
        "root": "/var/storidian/files"
      },
      "s3": {
        "driver": "s3",
        "bucket": "storidian-files",
        "region": "us-east-1",
        "key": "...",
        "secret": "...",
        "endpoint": null
      }
    }
  }
}
```

### Chunked Uploads

For large files, the API supports chunked uploads:

1. **Initialize:** `POST /files/upload/init` with file metadata → returns `upload_id`
2. **Upload chunks:** `POST /files/upload/{upload_id}/chunk` with chunk data and index
3. **Complete:** `POST /files/upload/{upload_id}/complete` → assembles chunks, creates file record

Chunk size: 5MB default (configurable)

---

## File Operations

### Upload Flow

```
Client                          API                         Storage
  │                              │                             │
  │  POST /files                 │                             │
  │  multipart/form-data         │                             │
  │─────────────────────────────>│                             │
  │                              │  Validate user quota        │
  │                              │  Validate file size/type    │
  │                              │                             │
  │                              │  PUT file                   │
  │                              │────────────────────────────>│
  │                              │                             │
  │                              │  Create file record         │
  │                              │  Queue thumbnail job        │
  │                              │                             │
  │  { file: {...} }             │                             │
  │<─────────────────────────────│                             │
```

### Download Flow

```
Client                          API                         Storage
  │                              │                             │
  │  GET /files/{id}/download    │                             │
  │─────────────────────────────>│                             │
  │                              │  Validate ownership/scope   │
  │                              │                             │
  │                              │  Get presigned URL (S3)     │
  │                              │  OR stream file (local)     │
  │                              │<────────────────────────────│
  │                              │                             │
  │  302 Redirect to presigned   │                             │
  │  OR streamed file data       │                             │
  │<─────────────────────────────│                             │
```

### Thumbnails & Previews

Generated asynchronously via queue jobs:

| Type | Formats | Sizes |
|------|---------|-------|
| Thumbnail | JPEG | 150x150, 300x300 |
| Preview | JPEG, PDF preview | 800x800 max |

Supported source formats:
- Images: JPEG, PNG, GIF, WebP, HEIC
- Documents: PDF (first page)
- Videos: First frame (future)

Thumbnails stored alongside originals:
```
{user_uuid}/{year}/{month}/{file_uuid}_thumb_150.jpg
{user_uuid}/{year}/{month}/{file_uuid}_thumb_300.jpg
```

---

## Background Jobs

### Queue Configuration

Using Laravel Horizon with Redis:

| Queue | Purpose | Workers |
|-------|---------|---------|
| `default` | General tasks | 2 |
| `thumbnails` | Image/preview generation | 2 |
| `uploads` | Chunked upload processing | 1 |
| `trash` | Trash purging | 1 |

### Scheduled Jobs

| Job | Schedule | Description |
|-----|----------|-------------|
| `PurgeTrash` | Daily | Permanently delete items older than `trash_purge_days` |
| `CleanOrphanedChunks` | Hourly | Delete incomplete chunked uploads older than 24h |
| `CalculateUserQuotas` | Hourly | Update cached quota usage |
| `PruneExpiredTokens` | Daily | Clean expired JWT refresh tokens |

---

## Web UI

### Technology Stack

- **Framework:** Vue.js 3 (Composition API)
- **Build:** Vite
- **Routing:** Vue Router
- **State:** Pinia
- **HTTP:** Axios
- **Styling:** Bootstrap 5 + SCSS
- **Icons:** Lucide

### Key Views

| Route | Component | Description |
|-------|-----------|-------------|
| `/` | Dashboard | Overview, recent files, storage usage |
| `/files` | FileExplorer | Main file browser |
| `/files/:folderId` | FileExplorer | Folder contents |
| `/virtual/:id` | VirtualFolder | Virtual folder contents |
| `/trash` | Trash | Deleted items |
| `/tags` | TagManager | Manage tags |
| `/settings` | UserSettings | Profile, password, 2FA |
| `/settings/api-keys` | ApiKeys | Manage API keys |
| `/admin` | AdminDashboard | System overview (admin) |
| `/admin/users` | UserManagement | Manage users (admin) |
| `/admin/settings` | SystemSettings | System config (admin) |
| `/admin/storage` | StorageSettings | Storage config (admin) |
| `/admin/auth` | AuthProviders | SSO providers (admin) |
| `/login` | Login | Authentication |
| `/register` | Register | Registration (if enabled) |

### UI Features

- **Drag & drop upload** with progress
- **Folder tree** sidebar navigation
- **Grid & list view** toggle
- **Bulk operations** (select multiple, move, delete, tag)
- **Context menus** for quick actions
- **Keyboard shortcuts** (Ctrl+A, Delete, etc.)
- **File preview** modal (images, PDFs)
- **Search** with filters

---

## Mobile Apps

### iOS App

**Technology:** Swift + SwiftUI

**Features (v1):**
- Browse files and folders
- Upload photos/files from device
- Download files for offline access
- Basic file management (move, rename, delete)
- Share sheet integration

**Authentication:**
- Native login form
- SSO via ASWebAuthenticationSession
- Biometric unlock for stored credentials

### Android App

**Technology:** Kotlin + Jetpack Compose

**Features (v1):**
- Same feature set as iOS
- Share intent integration
- Background upload service

---

## Sharing (Erugo Integration)

> **Note:** Storidian integrates with [Erugo](https://github.com/ErugoOSS/Erugo) for file sharing functionality rather than implementing its own share link system.

**Erugo** is a self-hosted file-sharing platform (WeTransfer-style) that provides:
- Human-readable share URLs (`quiet-cloud-shrill-thunder`)
- Password-protected shares
- Configurable expiration and download limits
- Folder structure preservation
- Reverse shares (guest uploads)

By integrating with Erugo, Storidian users get full-featured sharing without duplicating functionality.

### Integration Architecture

Two integration approaches to consider:

#### Option A: Erugo as External Service

Storidian and Erugo run as separate services. When a user wants to share a file:

1. Storidian copies/streams the file to Erugo
2. Erugo creates the share and manages the link
3. Files exist in both systems (duplication)

**Pros:** Clean separation, Erugo works standalone  
**Cons:** File duplication, sync complexity

#### Option B: Erugo with Storidian Storage Backend

Erugo gains the ability to create shares that reference Storidian files directly:

1. User initiates share from Storidian UI
2. Storidian calls Erugo API to create share, passing file reference
3. When share is accessed, Erugo fetches file from Storidian API
4. Single source of truth for files

```
Storidian                      Erugo
    │                            │
    │  POST /api/shares          │
    │  {                         │
    │    source: "storidian",    │
    │    storidian_file_id,      │
    │    storidian_api_key,      │
    │    password, expiry, etc   │
    │  }                         │
    │───────────────────────────>│
    │                            │
    │  {                         │
    │    share_url,              │
    │    share_id,               │
    │    settings                │
    │  }                         │
    │<───────────────────────────│
    │                            │
    │                            │  Recipient accesses share
    │                            │<────────────────────────────
    │                            │
    │  GET /api/files/{id}/raw   │
    │  Authorization: ApiKey     │
    │<───────────────────────────│
    │                            │
    │  File stream               │
    │───────────────────────────>│
    │                            │
    │                            │  Stream to recipient
    │                            │─────────────────────────────>
```

**Pros:** No duplication, single source of truth  
**Cons:** Requires Erugo enhancement, Storidian must be available for share access

### Recommended: Option B

This approach treats Storidian as the authoritative file store while Erugo handles the sharing UX. Requires:

1. **Erugo enhancement:** Add "external storage" plugin system
2. **Storidian API endpoint:** `GET /api/files/{id}/raw` for streaming file content
3. **Auth mechanism:** Storidian **system API key** configured in Erugo (allows Erugo to fetch any user's shared files)
4. **Storidian UI:** "Share via Erugo" button that calls Erugo API

### Storidian API Endpoints for Erugo

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/files/{id}/raw` | Stream raw file content |
| GET | `/files/{id}/metadata` | Get file metadata (name, size, mime) |
| GET | `/folders/{id}/contents/raw` | Stream folder as ZIP |

These endpoints authenticate via API key, allowing Erugo to fetch files on behalf of the share.

### UI Integration

In Storidian's file context menu:
- **Share via Erugo** → Opens modal to configure share settings
- Settings passed to Erugo API, share URL returned
- User can manage shares from either Storidian or Erugo UI

### Configuration

Storidian admin settings:

```json
{
  "erugo_integration": {
    "enabled": true,
    "erugo_url": "https://share.example.com",
    "api_key": "erugo_service_key_here"
  }
}
```

---

## Future: Sync

> **Planned for future phases.** Not in scope for initial release.

### Sync Concepts

- **Sync folders:** Users designate folders for sync
- **Desktop client:** Background sync daemon
- **Conflict resolution:** Last-write-wins with conflict copies
- **Delta sync:** Only transfer changed chunks

---

## Development Phases

### Phase 1: Foundation ✅ (Current)

- [x] Docker development environment
- [x] Laravel + Vue.js setup
- [x] Hot module replacement

### Phase 2: Authentication & OAuth

- [ ] User model with roles
- [ ] OAuth 2.0 Authorization Server
  - [ ] OAuth client (application) model
  - [ ] Authorization endpoint
  - [ ] Token endpoint (auth code, refresh)
  - [ ] PKCE support
  - [ ] Scope system
- [ ] First-party OAuth clients (Web, iOS, Android)
- [ ] Admin UI for OAuth client management
- [ ] Consent screen for third-party apps
- [ ] Native email/password login (issues OAuth token)
- [ ] Password reset flow
- [ ] SSO provider framework (port from Erugo)
- [ ] Google SSO provider
- [ ] OIDC provider
- [ ] 2FA (TOTP)
- [ ] OIDC discovery endpoints (`.well-known`)

### Phase 3: Core File Management

- [ ] File model and migrations
- [ ] Folder model with hierarchy
- [ ] Storage abstraction (Flysystem)
- [ ] Upload endpoint (single file)
- [ ] Chunked uploads
- [ ] Download endpoint
- [ ] File metadata API
- [ ] Basic CRUD operations

### Phase 4: Organization

- [ ] Tags system
- [ ] File/folder tagging
- [ ] Virtual folders
- [ ] Search functionality
- [ ] Trash and restore
- [ ] Scheduled trash purge

### Phase 5: Thumbnails & Previews

- [ ] Queue job infrastructure
- [ ] Image thumbnail generation
- [ ] PDF preview generation
- [ ] Preview API endpoint

### Phase 6: API Keys

- [ ] API key model
- [ ] Key generation endpoint
- [ ] Key authentication middleware
- [ ] Folder scoping
- [ ] Rate limiting

### Phase 7: Web UI

- [ ] Auth views (login, register)
- [ ] File explorer component
- [ ] Upload interface
- [ ] Folder navigation
- [ ] Tag management
- [ ] User settings
- [ ] Admin dashboard
- [ ] User management (admin)
- [ ] System settings (admin)
- [ ] Storage configuration (admin)
- [ ] SSO provider management (admin)

### Phase 8: Admin Controls

- [ ] Per-user quotas
- [ ] Per-group quotas
- [ ] File size limits
- [ ] Storage usage reports
- [ ] Audit logging (optional)

### Phase 9: Mobile - iOS

- [ ] Project setup
- [ ] Authentication flow
- [ ] File browsing
- [ ] Upload/download
- [ ] Share extension

### Phase 10: Mobile - Android

- [ ] Project setup
- [ ] Authentication flow
- [ ] File browsing
- [ ] Upload/download
- [ ] Share intent

### Future Phases

- Erugo sharing integration
- Storidian as OAuth provider
- Desktop sync client
- Real-time collaboration
- Version history
- End-to-end encryption option

---

## Open Questions

1. **Erugo Integration Approach:** Option A (file duplication) or Option B (Storidian as storage backend)? Option B is recommended but requires Erugo enhancements. Which direction do you want to go?

2. **Groups/Teams:** You mentioned per-group quotas. Should we add a groups/teams model for organizing users? This would enable:
   - Shared folders between team members
   - Group-level quotas and permissions
   - Admin can manage groups

3. **Preview Generation:** Should we use a specific library (Intervention Image, Imagick) or make it pluggable?

4. **Audit Logging:** What level of audit logging is needed? Just admin actions, or all file operations?

5. **Erugo Auth via Storidian:** Once Storidian's OAuth provider is built, should Erugo use it? This would give users a single account across both platforms. Erugo would become an OAuth client of Storidian.

6. **OAuth Library:** Laravel Passport vs. custom implementation? Passport provides OAuth2 server out of the box but adds complexity. Custom gives more control but more work.

---

*This specification is a living document and will be updated as the project evolves.*

