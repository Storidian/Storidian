# Tusd Integration Guide

This document provides a comprehensive breakdown of how to integrate [tusd](https://github.com/tus/tusd) (a resumable file upload server implementing the tus protocol) into a web application. This guide is based on the implementation in the Erugo project.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Infrastructure Setup](#infrastructure-setup)
3. [Backend Integration](#backend-integration)
4. [Frontend Integration](#frontend-integration)
5. [Database Schema](#database-schema)
6. [Upload Flow](#upload-flow)
7. [Chunking and Resumability](#chunking-and-resumability)
8. [Bundle Uploads for Small Files](#bundle-uploads-for-small-files)
9. [Security Considerations](#security-considerations)
10. [Error Handling](#error-handling)

---

## Architecture Overview

The tusd integration follows this high-level architecture:

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│                 │      │                 │      │                 │
│   Frontend      │─────▶│  Reverse Proxy  │─────▶│    tusd         │
│   (tus-js)      │      │   (Caddy)       │      │   Server        │
│                 │      │                 │      │                 │
└─────────────────┘      └─────────────────┘      └─────────────────┘
                                │                        │
                                │                        │ HTTP Hooks
                                ▼                        ▼
                         ┌─────────────────┐      ┌─────────────────┐
                         │                 │      │                 │
                         │  Laravel API    │◀─────│  Webhook        │
                         │   Server        │      │  Handler        │
                         │                 │      │                 │
                         └─────────────────┘      └─────────────────┘
                                │
                                ▼
                         ┌─────────────────┐
                         │                 │
                         │   Database      │
                         │                 │
                         └─────────────────┘
```

### Key Components

1. **tusd Server** - Handles the actual file upload using the tus protocol
2. **Reverse Proxy (Caddy)** - Routes `/files/` requests to tusd, all other requests to Laravel
3. **Laravel Backend** - Receives webhook hooks from tusd, manages upload sessions and files
4. **Frontend (tus-js-client)** - JavaScript client that implements the tus protocol

---

## Infrastructure Setup

### Docker Configuration

#### Installing tusd in Dockerfile

```dockerfile
# Download and install tusd for the correct architecture
ENV TUSD_VERSION=2.6.0
RUN TUSD_ARCH="${TARGETARCH}" && \
    if [ "$TUSD_ARCH" = "amd64" ]; then TUSD_ARCH="amd64"; \
    elif [ "$TUSD_ARCH" = "arm64" ]; then TUSD_ARCH="arm64"; \
    else echo "Unsupported architecture: $TARGETARCH" && exit 1; fi && \
    curl -L -o /tmp/tusd.tar.gz "https://github.com/tus/tusd/releases/download/v${TUSD_VERSION}/tusd_linux_${TUSD_ARCH}.tar.gz" && \
    tar -xzf /tmp/tusd.tar.gz -C /tmp && \
    mv /tmp/tusd_linux_${TUSD_ARCH}/tusd /usr/local/bin/tusd && \
    chmod +x /usr/local/bin/tusd && \
    rm -rf /tmp/tusd.tar.gz /tmp/tusd_linux_${TUSD_ARCH}
```

#### Supervisord Configuration

tusd runs as a daemon managed by supervisord:

```ini
[program:tusd]
command=/usr/local/bin/tusd -behind-proxy -hooks-http http://localhost/api/tusd-hooks -upload-dir /var/www/html/storage/app/uploads -base-path /files/
user=www-data
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autostart=true
autorestart=true
priority=15
```

**Key tusd flags:**
- `-behind-proxy` - Indicates tusd is running behind a reverse proxy (required for proper URL handling)
- `-hooks-http http://localhost/api/tusd-hooks` - URL where tusd sends HTTP hook notifications
- `-upload-dir /var/www/html/storage/app/uploads` - Directory where uploaded files are stored
- `-base-path /files/` - Base URL path for tus uploads (must match reverse proxy config)

### Reverse Proxy Configuration (Caddy)

The reverse proxy routes upload requests to tusd and all other requests to your application:

```caddyfile
:80 {
    root * /var/www/html/public

    # Allow large file uploads (500MB)
    request_body {
        max_size 500MB
    }

    # Proxy tusd uploads - must be handled BEFORE Laravel catches it
    handle /files/* {
        reverse_proxy localhost:8080 {
            header_up X-Forwarded-Proto {header.X-Forwarded-Proto}
            header_up X-Forwarded-Host {header.X-Forwarded-Host}
        }
    }

    # Handle everything else with Laravel
    handle {
        file_server {
            index index.php
        }
        try_files {path} {path}/ /index.php?{query}
        php_fastcgi unix//run/php/php-fpm.sock
    }
}
```

**Important:** The `/files/*` route MUST be defined before the catch-all Laravel handler.

---

## Backend Integration

### Webhook Handler Controller

tusd sends HTTP webhooks at various lifecycle points. Create a controller to handle these:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\File;
use App\Models\UploadSession;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TusdHooksController extends Controller
{
    /**
     * Handle incoming tusd hook requests
     * tusd sends different hook types: pre-create, post-create, post-receive, post-finish, post-terminate
     */
    public function handleHook(Request $request)
    {
        // Security: Verify request is from internal network (tusd process)
        $clientIp = $request->ip();
        $allowedNetworks = [
            '172.',      // Docker bridge networks
            '10.',       // Private network
            '192.168.',  // Private network
            '127.0.0.1', // Localhost IPv4
            '::1',       // Localhost IPv6
        ];
        
        $isAllowed = false;
        foreach ($allowedNetworks as $network) {
            if (str_starts_with($clientIp, $network)) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            Log::warning('tusd hook rejected: unauthorized source IP', ['ip' => $clientIp]);
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $payload = $request->all();
        $hookName = $payload['Type'] ?? null;

        switch ($hookName) {
            case 'pre-create':
                return $this->preCreate($request, $payload);
            case 'post-create':
                return $this->postCreate($request, $payload);
            case 'post-finish':
                return $this->postFinish($request, $payload);
            case 'post-terminate':
                return $this->postTerminate($request, $payload);
            default:
                return response()->json(['ok' => true]);
        }
    }
}
```

### Hook Types and Handlers

#### 1. pre-create Hook

Called before tusd accepts an upload. Use this for **authentication and validation**:

```php
protected function preCreate(Request $request, array $payload)
{
    try {
        // Extract authorization header from the upload request
        $authHeader = $payload['Event']['HTTPRequest']['Header']['Authorization'][0] ?? null;

        if (!$authHeader) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized: No authorization header'
            ], 401);
        }

        // Extract and validate JWT token
        $token = str_replace('Bearer ', '', $authHeader);
        $user = JWTAuth::setToken($token)->authenticate();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized: Invalid token'
            ], 401);
        }

        // Validate file size
        $fileSize = $payload['Event']['Upload']['Size'] ?? 0;
        $maxUploadSize = config('app.max_upload_size');

        if ($maxUploadSize && $fileSize > $maxUploadSize) {
            return response()->json([
                'ok' => false,
                'message' => 'File size exceeds maximum allowed'
            ], 413);
        }

        return response()->json(['ok' => true]);

    } catch (\Exception $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Unauthorized: ' . $e->getMessage()
        ], 401);
    }
}
```

#### 2. post-create Hook

Called after tusd creates the upload and assigns an ID. Use this to **create an upload session record**:

```php
protected function postCreate(Request $request, array $payload)
{
    try {
        // Get user from auth header
        $authHeader = $payload['Event']['HTTPRequest']['Header']['Authorization'][0] ?? null;
        $token = str_replace('Bearer ', '', $authHeader);
        $user = JWTAuth::setToken($token)->authenticate();

        // Extract metadata
        $metadata = $payload['Event']['Upload']['MetaData'] ?? [];
        $filename = $metadata['filename'] ?? 'unknown';
        $filesize = $payload['Event']['Upload']['Size'] ?? 0;
        $filetype = $metadata['filetype'] ?? 'application/octet-stream';
        $uploadId = $payload['Event']['Upload']['ID'] ?? null;
        
        // Validate upload ID format (tusd generates 32-char hex IDs)
        if (!$uploadId || !preg_match('/^[a-f0-9]+$/i', $uploadId)) {
            return response()->json(['ok' => true]);
        }

        // Create upload session record
        UploadSession::create([
            'upload_id' => $uploadId,
            'user_id' => $user->id,
            'filename' => $filename,
            'filesize' => $filesize,
            'filetype' => $filetype,
            'total_chunks' => 1, // tusd handles chunking internally
            'chunks_received' => 0,
            'status' => 'pending'
        ]);

        return response()->json(['ok' => true]);

    } catch (\Exception $e) {
        return response()->json(['ok' => true]); // Don't fail the upload
    }
}
```

#### 3. post-finish Hook

Called when the upload completes successfully. Use this to **create the file record**:

```php
protected function postFinish(Request $request, array $payload)
{
    try {
        $uploadId = $payload['Event']['Upload']['ID'] ?? null;
        
        // Validate upload ID
        if (!$uploadId || !preg_match('/^[a-f0-9]+$/i', $uploadId)) {
            return response()->json(['ok' => true]);
        }
        
        $metadata = $payload['Event']['Upload']['MetaData'] ?? [];
        $filename = $metadata['filename'] ?? 'unknown';
        $filesize = $payload['Event']['Upload']['Size'] ?? 0;
        $filetype = $metadata['filetype'] ?? 'application/octet-stream';

        // Find the upload session (with retry for race conditions)
        $session = null;
        $maxRetries = 10;
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $session = UploadSession::where('upload_id', $uploadId)->first();
            if ($session) break;
            if ($attempt < $maxRetries - 1) usleep(100000); // 100ms
        }

        if (!$session) {
            return response()->json(['ok' => true]);
        }

        // Create file record
        $file = File::create([
            'name' => $filename,
            'original_name' => $filename,
            'type' => $filetype,
            'size' => $filesize,
            'temp_path' => 'uploads/' . $uploadId
        ]);

        // Update session
        $session->status = 'complete';
        $session->chunks_received = 1;
        $session->file_id = $file->id;
        $session->save();

        return response()->json(['ok' => true]);

    } catch (\Exception $e) {
        return response()->json(['ok' => true]);
    }
}
```

#### 4. post-terminate Hook

Called when an upload is cancelled. Use this for **cleanup**:

```php
protected function postTerminate(Request $request, array $payload)
{
    try {
        $uploadId = $payload['Event']['Upload']['ID'] ?? null;
        
        if (!$uploadId || !preg_match('/^[a-f0-9]+$/i', $uploadId)) {
            return response()->json(['ok' => true]);
        }

        $session = UploadSession::where('upload_id', $uploadId)->first();

        if ($session) {
            // Delete associated file record if exists
            if ($session->file_id) {
                $file = File::find($session->file_id);
                if ($file) {
                    $file->delete();
                }
            }
            $session->delete();
        }

        return response()->json(['ok' => true]);

    } catch (\Exception $e) {
        return response()->json(['ok' => true]);
    }
}
```

### API Routes

Register the webhook handler route (not authenticated via middleware since tusd calls it):

```php
// routes/api.php

// tusd webhook handler (called by tusd server, not authenticated via middleware)
Route::post('/tusd-hooks', [TusdHooksController::class, 'handleHook'])->name('tusd.hooks');

// Upload routes (authenticated)
Route::group(['prefix' => 'uploads', 'middleware' => ['auth']], function ($router) {
    // Verify if an upload session is still valid (for tus resume functionality)
    Route::get('/verify/{uploadId}', [UploadsController::class, 'verifyUpload']);
    
    // Create a share from uploaded files (after tusd uploads complete)
    Route::post('/create-share-from-uploads', [UploadsController::class, 'createShareFromUploads']);
});
```

### Upload Verification Endpoint

Endpoint to verify if a previous upload can be resumed:

```php
public function verifyUpload(Request $request, string $uploadId)
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    // Check if the upload session exists and belongs to this user
    $session = UploadSession::where('upload_id', $uploadId)
        ->where('user_id', $user->id)
        ->whereIn('status', ['pending', 'complete'])
        ->first();

    if (!$session) {
        return response()->json(['status' => 'error', 'message' => 'Upload session not found'], 404);
    }

    // Verify the file still exists on disk
    $uploadPath = storage_path('app/uploads/' . $uploadId);
    if (!file_exists($uploadPath)) {
        $session->delete();
        return response()->json(['status' => 'error', 'message' => 'Upload file not found'], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Upload session valid',
        'data' => [
            'upload_id' => $uploadId,
            'status' => $session->status,
            'filename' => $session->filename,
            'filesize' => $session->filesize
        ]
    ]);
}
```

---

## Frontend Integration

### Dependencies

Install the tus-js-client:

```bash
npm install tus-js-client
```

### Getting the Tusd URL

```javascript
const getTusdUrl = () => {
    // tusd is proxied through the reverse proxy at /files/
    const protocol = window.location.protocol
    const host = window.location.host
    return `${protocol}//${host}/files/`
}
```

### Upload Function

```javascript
import * as tus from 'tus-js-client'

export const uploadFileWithTus = (file, onProgress, onComplete, onError, extraMetadata = {}) => {
    const startUpload = (skipResume = false) => {
        const tusdEndpoint = getTusdUrl()
        
        const upload = new tus.Upload(file, {
            endpoint: tusdEndpoint,
            retryDelays: [0, 1000, 3000, 5000],
            chunkSize: 20 * 1024 * 1024, // 20MB chunks
            removeFingerprintOnSuccess: true,
            metadata: {
                filename: file.name,
                filetype: file.type || 'application/octet-stream',
                ...extraMetadata
            },
            headers: {
                Authorization: `Bearer ${store.jwt}`
            },
            // Refresh token before each request if it's about to expire
            onBeforeRequest: function (req) {
                const now = new Date()
                const fiveMinutesFromNow = new Date(now.getTime() + 5 * 60 * 1000)

                if (store.jwtExpires && store.jwtExpires < fiveMinutesFromNow) {
                    return refreshToken()
                        .then(refreshData => {
                            store.authSuccess(refreshData)
                            req.setHeader('Authorization', `Bearer ${store.jwt}`)
                        })
                        .catch(e => {
                            console.error('Failed to refresh token:', e)
                        })
                }
            },
            onError: (error) => {
                console.error('tus upload error:', error)
                onError(error)
            },
            onProgress: (bytesUploaded, bytesTotal) => {
                const percentage = Math.round((bytesUploaded / bytesTotal) * 100)
                onProgress({
                    percentage,
                    uploadedBytes: bytesUploaded,
                    totalBytes: bytesTotal
                })
            },
            onSuccess: () => {
                // Extract upload ID from the URL (last part of the path)
                const uploadUrl = upload.url
                const uploadId = uploadUrl.split('/').pop()
                onComplete({
                    uploadId,
                    uploadUrl,
                    filename: file.name,
                    filesize: file.size,
                    filetype: file.type
                })
            }
        })

        if (skipResume) {
            upload.start()
        } else {
            // Check for previous uploads to resume
            upload.findPreviousUploads().then(async (previousUploads) => {
                // Filter out uploads with mismatched protocol
                const currentProtocol = window.location.protocol
                const validPreviousUploads = previousUploads.filter(u => {
                    const uploadProtocol = new URL(u.uploadUrl).protocol
                    return uploadProtocol === currentProtocol
                })
                
                if (validPreviousUploads.length > 0) {
                    const previousUpload = validPreviousUploads[0]
                    const previousUploadId = previousUpload.uploadUrl.split('/').pop()

                    // Verify with backend that this upload session still exists
                    try {
                        const response = await fetch(`/api/uploads/verify/${previousUploadId}`, {
                            method: 'GET',
                            headers: { Authorization: `Bearer ${store.jwt}` }
                        })

                        if (response.ok) {
                            // Safe to resume
                            upload.resumeFromPreviousUpload(previousUpload)
                        } else {
                            // Session doesn't exist, clear fingerprint
                            clearTusFingerprint(previousUpload.uploadUrl)
                        }
                    } catch (e) {
                        clearTusFingerprint(previousUpload.uploadUrl)
                    }
                }
                upload.start()
            })
        }

        return upload
    }

    return startUpload(false)
}

// Clear stale tus fingerprints from localStorage
const clearTusFingerprint = (uploadUrl) => {
    try {
        for (let i = localStorage.length - 1; i >= 0; i--) {
            const key = localStorage.key(i)
            if (key && key.startsWith('tus::')) {
                const value = localStorage.getItem(key)
                if (value && value.includes(uploadUrl)) {
                    localStorage.removeItem(key)
                }
            }
        }
    } catch (e) {
        console.warn('Could not clear stale tus fingerprint:', e)
    }
}
```

### Uploading Multiple Files

```javascript
export const uploadFilesInChunks = async (
    files,
    onProgress,
    onComplete,
    onError
) => {
    const totalSize = files.reduce((total, file) => total + file.size, 0)
    let uploadedSize = 0
    const results = []

    // Process each file sequentially
    for (let i = 0; i < files.length; i++) {
        const file = files[i]

        try {
            const result = await new Promise((resolve, reject) => {
                const upload = uploadFileWithTus(
                    file,
                    (progress) => {
                        // Calculate overall progress
                        const fileTotalUploaded = (progress.percentage / 100) * file.size
                        const overallPercentage = Math.round(
                            ((uploadedSize + fileTotalUploaded) / totalSize) * 100
                        )

                        onProgress({
                            percentage: overallPercentage,
                            uploadedBytes: uploadedSize + progress.uploadedBytes,
                            totalBytes: totalSize,
                            currentFile: i + 1,
                            totalFiles: files.length,
                            currentFileName: file.name
                        })
                    },
                    (uploadResult) => {
                        resolve(uploadResult)
                    },
                    (error) => {
                        reject(error)
                    }
                )
            })

            results.push(result)
            uploadedSize += file.size
        } catch (error) {
            onError(error)
            return
        }
    }

    // All files uploaded, now create the share/process them
    const uploadIds = results.map((r) => r.uploadId)
    
    const response = await fetch('/api/uploads/create-share-from-uploads', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${store.jwt}`
        },
        body: JSON.stringify({
            uploadIds: uploadIds
            // ... other data
        })
    })

    if (response.ok) {
        const data = await response.json()
        onComplete(data)
    } else {
        const data = await response.json()
        onError(new Error(data.message))
    }
}
```

---

## Database Schema

### upload_sessions Table

Tracks each upload session:

```php
Schema::create('upload_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('upload_id')->unique();  // tusd-generated ID
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('filename');
    $table->bigInteger('filesize');
    $table->string('filetype');
    $table->integer('total_chunks');
    $table->integer('chunks_received')->default(0);
    $table->string('status')->default('pending');  // pending, complete, failed
    $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
    $table->boolean('is_bundle')->default(false);  // For bundle uploads
    $table->text('bundle_file_ids')->nullable();   // JSON array of file IDs
    $table->timestamps();

    $table->index(['upload_id', 'user_id']);
});
```

### UploadSession Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadSession extends Model
{
    protected $fillable = [
        'upload_id',
        'user_id',
        'filename',
        'filesize',
        'filetype',
        'total_chunks',
        'chunks_received',
        'status',
        'file_id',
        'is_bundle',
        'bundle_file_ids'
    ];

    protected $casts = [
        'is_bundle' => 'boolean'
    ];

    public function getBundleFileIdsArray(): array
    {
        if (!$this->bundle_file_ids) {
            return [];
        }
        return json_decode($this->bundle_file_ids, true) ?? [];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
```

---

## Upload Flow

### Complete Upload Lifecycle

```
1. User selects files
         │
         ▼
2. Frontend creates tus.Upload with JWT in headers
         │
         ▼
3. tus-js-client sends POST to /files/ (tusd endpoint)
         │
         ▼
4. tusd receives request, triggers pre-create hook
         │
         ▼
5. Backend validates JWT, checks file size limits
         │
         ├──── If invalid: Return error, tusd rejects upload
         │
         ▼
6. tusd creates upload, assigns ID, triggers post-create hook
         │
         ▼
7. Backend creates UploadSession record with status='pending'
         │
         ▼
8. tus-js-client sends PATCH requests with file chunks
         │
         ▼
9. tusd writes chunks to disk (handles resumability internally)
         │
         ▼
10. When complete, tusd triggers post-finish hook
         │
         ▼
11. Backend creates File record, updates UploadSession status='complete'
         │
         ▼
12. Frontend receives success, calls /api/uploads/create-share-from-uploads
         │
         ▼
13. Backend moves files from uploads/ to shares/ directory
         │
         ▼
14. Backend cleans up UploadSession and tusd .info files
```

---

## Chunking and Resumability

### How Chunking Works

1. **Client-side chunking**: The tus-js-client automatically breaks files into chunks (default 20MB)
2. **Server-side reassembly**: tusd handles reassembling chunks on the server
3. **Fingerprinting**: tus-js-client creates a fingerprint based on file name, size, and other properties
4. **LocalStorage**: The fingerprint and upload URL are stored in localStorage for resumability

### Resume Flow

```
1. User selects a file that was previously partially uploaded
         │
         ▼
2. tus-js-client checks localStorage for matching fingerprint
         │
         ▼
3. If found, call backend /api/uploads/verify/{uploadId}
         │
         ├──── If session exists: Resume from previous offset
         │
         └──── If not: Clear fingerprint, start fresh
```

### Handling Token Expiration During Long Uploads

For large files that take longer than the JWT expiration time:

```javascript
onBeforeRequest: function (req) {
    const now = new Date()
    const fiveMinutesFromNow = new Date(now.getTime() + 5 * 60 * 1000)

    // Proactively refresh if token expires within 5 minutes
    if (store.jwtExpires && store.jwtExpires < fiveMinutesFromNow) {
        return refreshToken()
            .then(refreshData => {
                req.setHeader('Authorization', `Bearer ${store.jwt}`)
            })
    }
}
```

---

## Bundle Uploads for Small Files

When uploading many small files (e.g., 50+ files where 70%+ are under 100KB), it's more efficient to bundle them into a single zip upload to avoid webhook overhead.

### Frontend Bundle Logic

```javascript
const BUNDLE_CONFIG = {
    minFileCount: 50,        // Minimum files to trigger bundling
    maxFileSizeBytes: 102400, // Files under 100KB are "small"
    minSmallFileRatio: 0.7   // At least 70% must be small
}

const shouldBundleFiles = (files) => {
    if (files.length < BUNDLE_CONFIG.minFileCount) return false

    const smallFileCount = files.filter(
        f => f.size <= BUNDLE_CONFIG.maxFileSizeBytes
    ).length
    const smallFileRatio = smallFileCount / files.length

    return smallFileRatio >= BUNDLE_CONFIG.minSmallFileRatio
}

const bundleFilesIntoZip = async (files, onProgress) => {
    const zip = new JSZip()
    const manifest = { version: 1, files: [] }

    for (const file of files) {
        const filePath = file.fullPath || file.name
        zip.file(filePath, file)
        manifest.files.push({
            path: filePath,
            originalName: file.name,
            size: file.size,
            type: file.type
        })
    }

    // Include manifest for server-side extraction
    zip.file('__erugo_manifest__.json', JSON.stringify(manifest))

    return await zip.generateAsync({ type: 'blob', compression: 'DEFLATE' })
}
```

### Backend Bundle Extraction

In the post-finish hook, detect and handle bundle uploads:

```php
$isBundle = ($metadata['isBundle'] ?? 'false') === 'true';

if ($isBundle) {
    return $this->handleBundleUpload($uploadId, $session, $metadata);
}

protected function handleBundleUpload(string $uploadId, UploadSession $session, array $metadata)
{
    $bundlePath = storage_path('app/uploads/' . $uploadId);
    $extractDir = storage_path('app/uploads/' . $uploadId . '_extracted');

    $zip = new \ZipArchive();
    $zip->open($bundlePath);

    // Read manifest
    $manifestContent = $zip->getFromName('__erugo_manifest__.json');
    $manifest = json_decode($manifestContent, true);

    // Extract all files
    $zip->extractTo($extractDir);
    $zip->close();

    // Create File records for each extracted file
    $fileIds = [];
    foreach ($manifest['files'] as $fileInfo) {
        $file = File::create([
            'name' => $fileInfo['originalName'],
            'type' => $fileInfo['type'],
            'size' => $fileInfo['size'],
            'temp_path' => 'uploads/' . $uploadId . '_extracted/' . $fileInfo['path']
        ]);
        $fileIds[] = $file->id;
    }

    $session->is_bundle = true;
    $session->bundle_file_ids = json_encode($fileIds);
    $session->status = 'complete';
    $session->save();

    // Clean up the original zip
    unlink($bundlePath);
}
```

---

## Security Considerations

### 1. Webhook Authentication

Only allow webhook requests from internal IPs:

```php
$allowedNetworks = [
    '172.',      // Docker bridge
    '10.',       // Private
    '192.168.',  // Private
    '127.0.0.1', // Localhost
    '::1',       // Localhost IPv6
];
```

### 2. Upload ID Validation

Always validate upload IDs (tusd generates 32-char hex strings):

```php
if (!$uploadId || !preg_match('/^[a-f0-9]+$/i', $uploadId)) {
    // Invalid upload ID
}
```

### 3. User Ownership Verification

When verifying or processing uploads, always check the user owns the session:

```php
$session = UploadSession::where('upload_id', $uploadId)
    ->where('user_id', $user->id)
    ->first();
```

### 4. File Size Limits

Enforce limits in both pre-create hook and frontend:

```php
if ($fileSize > $maxUploadSize) {
    return response()->json([
        'ok' => false,
        'message' => 'File size exceeds maximum allowed'
    ], 413);
}
```

### 5. Cumulative Size Limits

Prevent users from bypassing limits by uploading many files:

```php
$pendingUploadsSize = UploadSession::where('user_id', $user->id)
    ->whereIn('status', ['pending', 'complete'])
    ->sum('filesize');

if ($pendingUploadsSize + $fileSize > $maxUploadSize) {
    // Reject and clean up
}
```

---

## Error Handling

### Frontend Retry Logic

```javascript
onError: (error) => {
    console.error('tus upload error:', error)
    onError(error)
},
retryDelays: [0, 1000, 3000, 5000], // Retry after 0s, 1s, 3s, 5s
```

### Backend Race Condition Handling

Sometimes post-finish arrives before post-create completes:

```php
$maxRetries = 10;
for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
    $session = UploadSession::where('upload_id', $uploadId)->first();
    if ($session) break;
    usleep(100000); // 100ms
}
```

### Creating Share with Retry

Frontend should retry share creation if sessions aren't ready:

```javascript
for (let attempt = 0; attempt < maxRetries; attempt++) {
    const response = await fetch('/api/uploads/create-share-from-uploads', { ... })
    
    if (response.ok) {
        return await response.json()
    }

    const data = await response.json()
    if (data.message.includes('not found or not completed')) {
        // Wait and retry with exponential backoff
        await new Promise(r => setTimeout(r, 500 * Math.pow(2, attempt)))
        continue
    }
    
    throw new Error(data.message)
}
```

---

## Cleanup Jobs

### Maintenance Job for Orphaned Uploads

Run periodically to clean up failed/abandoned uploads:

```php
// Delete upload sessions older than 7 days
UploadSession::where('created_at', '<', now()->subDays(7))
    ->where('status', 'pending')
    ->each(function ($session) {
        $uploadPath = storage_path('app/uploads/' . $session->upload_id);
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        // Also clean up .info file
        $infoPath = $uploadPath . '.info';
        if (file_exists($infoPath)) {
            unlink($infoPath);
        }
        $session->delete();
    });
```

---

## Summary

The tusd integration provides:

1. **Resumable uploads** - Users can resume interrupted uploads
2. **Chunked transfers** - Large files are split into manageable chunks
3. **Progress tracking** - Real-time upload progress updates
4. **Token refresh** - Long uploads won't fail due to token expiration
5. **Bundle optimization** - Many small files are bundled for efficiency
6. **Security** - JWT validation, IP restrictions, size limits

Key files to implement:
- `TusdHooksController.php` - Webhook handler
- `UploadsController.php` - Upload verification and share creation
- `UploadSession.php` - Model for tracking uploads
- `api.js` - Frontend upload functions
- `Caddyfile` - Reverse proxy configuration
- `supervisord.conf` - tusd daemon configuration

