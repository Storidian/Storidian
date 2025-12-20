<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { isAuthenticated, user, login, logout, fetchUser, isLoading } = useAuth()

// Public folder assets need to be referenced this way for Vite
const logoUrl = '/storidian-icon.png'

onMounted(async () => {
    if (isAuthenticated.value && !user.value) {
        await fetchUser()
    }
})

function handleLogin() {
    login()
}

async function handleLogout() {
    await logout()
}

function goToProfile() {
    router.push('/profile')
}

function goToCommander() {
    router.push('/commander')
}
</script>

<template>
    <div class="home">
        <div class="home-card">
            <img :src="logoUrl" alt="Storidian" class="logo">
            <h1>Storidian</h1>
            <p class="subtitle">Self-hosted file storage</p>

            <div v-if="isLoading" class="loading">
                Loading...
            </div>

            <div v-else-if="isAuthenticated && user" class="authenticated">
                <div class="user-info">
                    <div class="user-avatar">{{ user.name?.charAt(0)?.toUpperCase() || '?' }}</div>
                    <div class="user-details">
                        <div class="user-name">{{ user.name }}</div>
                        <div class="user-email">{{ user.email }}</div>
                    </div>
                </div>

                <div class="button-group">
                    <button @click="goToCommander" class="btn btn-primary">
                        Open Commander
                    </button>
                    <button @click="goToProfile" class="btn btn-primary">
                        View Profile
                    </button>
                    <button @click="handleLogout" class="btn btn-secondary">
                        Sign Out
                    </button>
                </div>
            </div>

            <div v-else class="unauthenticated">
                <p class="description">
                    Sign in to access your files
                </p>
                <button @click="handleLogin" class="btn btn-primary">
                    Sign In
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.home {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.home-card {
    background-color: #f6f6f6;
    border-radius: 18px;
    filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
    padding: 40px;
    text-align: center;
    max-width: 400px;
    width: 100%;
}

.logo {
    width: 80px;
    height: 80px;
    margin-bottom: 16px;
}

h1 {
    font-size: 28px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 32px 0;
}

.loading {
    color: #6b7280;
    padding: 20px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 16px;
    background: white;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
}

.user-details {
    text-align: left;
}

.user-name {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.user-email {
    font-size: 14px;
    color: #6b7280;
}

.description {
    color: #6b7280;
    margin-bottom: 24px;
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
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
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #f9fafb;
}
</style>

