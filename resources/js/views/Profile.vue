<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, fetchUser, logout, isLoading, accessToken } = useAuth()

onMounted(async () => {
    if (!user.value) {
        await fetchUser()
    }
})

async function handleLogout() {
    await logout()
    router.push('/')
}

function goHome() {
    router.push('/')
}

function formatDate(dateString) {
    if (!dateString) return 'Never'
    return new Date(dateString).toLocaleString()
}

function formatBytes(bytes) {
    if (bytes === null || bytes === undefined) return 'Unlimited'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let unitIndex = 0
    let size = bytes
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024
        unitIndex++
    }
    return `${size.toFixed(1)} ${units[unitIndex]}`
}
</script>

<template>
    <div class="profile">
        <div class="profile-card">
            <div class="header">
                <button @click="goHome" class="back-btn">
                    ← Back
                </button>
                <h1>Your Profile</h1>
            </div>

            <div v-if="isLoading" class="loading">
                Loading...
            </div>

            <div v-else-if="user" class="content">
                <div class="avatar-section">
                    <div class="avatar">{{ user.name?.charAt(0)?.toUpperCase() || '?' }}</div>
                    <div class="user-name">{{ user.name }}</div>
                    <div class="user-role" :class="user.role">{{ user.role }}</div>
                </div>

                <div class="info-section">
                    <div class="info-group">
                        <label>Email</label>
                        <div class="info-value">{{ user.email }}</div>
                    </div>

                    <div class="info-group">
                        <label>Email Verified</label>
                        <div class="info-value">
                            <span v-if="user.email_verified_at" class="badge success">
                                Verified {{ formatDate(user.email_verified_at) }}
                            </span>
                            <span v-else class="badge warning">Not verified</span>
                        </div>
                    </div>

                    <div class="info-group">
                        <label>Storage Quota</label>
                        <div class="info-value">{{ formatBytes(user.quota_bytes) }}</div>
                    </div>

                    <div class="info-group">
                        <label>Two-Factor Auth</label>
                        <div class="info-value">
                            <span v-if="user.has_two_factor" class="badge success">Enabled</span>
                            <span v-else class="badge neutral">Not enabled</span>
                        </div>
                    </div>

                    <div class="info-group">
                        <label>Account Status</label>
                        <div class="info-value">
                            <span v-if="user.is_active" class="badge success">Active</span>
                            <span v-else class="badge error">Inactive</span>
                        </div>
                    </div>

                    <div class="info-group">
                        <label>User ID</label>
                        <div class="info-value mono">{{ user.id }}</div>
                    </div>

                    <div class="info-group">
                        <label>Account Created</label>
                        <div class="info-value">{{ formatDate(user.created_at) }}</div>
                    </div>
                </div>

                <div class="token-section">
                    <h3>Current Access Token</h3>
                    <div class="token-display">
                        <code>{{ accessToken?.substring(0, 50) }}...</code>
                    </div>
                </div>

                <button @click="handleLogout" class="btn btn-danger">
                    Sign Out
                </button>
            </div>

            <div v-else class="error">
                <p>Unable to load user profile.</p>
                <button @click="goHome" class="btn btn-primary">
                    Go Home
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.profile {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.profile-card {
    background-color: #f6f6f6;
    border-radius: 18px;
    filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
    padding: 32px;
    max-width: 500px;
    width: 100%;
}

.header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.back-btn {
    background: none;
    border: none;
    color: #3b82f6;
    cursor: pointer;
    font-size: 14px;
    font-family: inherit;
    padding: 8px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}

.back-btn:hover {
    background: rgba(59, 130, 246, 0.1);
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.loading {
    text-align: center;
    color: #6b7280;
    padding: 40px;
}

.avatar-section {
    text-align: center;
    margin-bottom: 32px;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 600;
    margin: 0 auto 12px;
}

.user-name {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.user-role {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.user-role.admin {
    background: #fef3c7;
    color: #92400e;
}

.user-role.user {
    background: #e0e7ff;
    color: #3730a3;
}

.info-section {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.info-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.info-group:last-child {
    border-bottom: none;
}

.info-group label {
    font-size: 14px;
    color: #6b7280;
}

.info-value {
    font-size: 14px;
    color: #1f2937;
    text-align: right;
}

.info-value.mono {
    font-family: monospace;
    font-size: 12px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

.badge.success {
    background: #d1fae5;
    color: #065f46;
}

.badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.badge.error {
    background: #fee2e2;
    color: #991b1b;
}

.badge.neutral {
    background: #f3f4f6;
    color: #6b7280;
}

.token-section {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.token-section h3 {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
}

.token-display {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 12px;
    overflow-x: auto;
}

.token-display code {
    font-family: monospace;
    font-size: 11px;
    color: #6b7280;
    word-break: break-all;
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
    transition: background-color 0.2s;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.error {
    text-align: center;
    padding: 20px;
}

.error p {
    color: #6b7280;
    margin-bottom: 16px;
}
</style>

