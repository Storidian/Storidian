<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const route = useRoute()
const { handleCallback, error: authError } = useAuth()

const status = ref('processing')
const errorMessage = ref('')
const logoUrl = '/storidian-icon.png'

onMounted(async () => {
    const code = route.query.code
    const state = route.query.state
    const error = route.query.error
    const errorDescription = route.query.error_description

    // Check for OAuth error
    if (error) {
        status.value = 'error'
        errorMessage.value = errorDescription || error
        return
    }

    // Check for authorization code
    if (!code) {
        status.value = 'error'
        errorMessage.value = 'No authorization code received'
        return
    }

    // Handle the callback
    const success = await handleCallback(code, state)

    if (success) {
        status.value = 'success'
        // Redirect to home after short delay
        setTimeout(() => {
            router.push('/')
        }, 1000)
    } else {
        status.value = 'error'
        errorMessage.value = authError.value || 'Authentication failed'
    }
})

function goHome() {
    router.push('/')
}
</script>

<template>
    <div class="callback">
        <div class="callback-card">
            <img :src="logoUrl" alt="Storidian" class="logo">

            <div v-if="status === 'processing'" class="status processing">
                <div class="spinner"></div>
                <h2>Signing you in...</h2>
                <p>Please wait while we complete the authentication.</p>
            </div>

            <div v-else-if="status === 'success'" class="status success">
                <div class="icon success-icon">✓</div>
                <h2>Success!</h2>
                <p>You have been signed in. Redirecting...</p>
            </div>

            <div v-else class="status error">
                <div class="icon error-icon">✕</div>
                <h2>Authentication Failed</h2>
                <p class="error-message">{{ errorMessage }}</p>
                <button @click="goHome" class="btn btn-primary">
                    Go Back
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.callback {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.callback-card {
    background-color: #f6f6f6;
    border-radius: 18px;
    filter: drop-shadow(0 0 20px rgba(0, 0, 0, 0.1));
    padding: 40px;
    text-align: center;
    max-width: 400px;
    width: 100%;
}

.logo {
    width: 64px;
    height: 64px;
    margin-bottom: 24px;
}

.status {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

h2 {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 8px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

.success-icon {
    background: #10b981;
    color: white;
}

.error-icon {
    background: #ef4444;
    color: white;
}

.error-message {
    background: #fef2f2;
    color: #ef4444;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 8px 0;
}

.btn {
    margin-top: 16px;
    padding: 12px 24px;
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
</style>

