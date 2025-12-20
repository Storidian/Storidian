<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import Commander from '../components/commander/commander.vue'

const router = useRouter()
const { user, fetchUser, isLoading } = useAuth()

onMounted(async () => {
    if (!user.value) {
        await fetchUser()
    }
})

function goHome() {
    router.push('/')
}
</script>

<template>
    <div class="commander-view">
        <div v-if="isLoading" class="loading">
            Loading...
        </div>
        <div v-else-if="user" class="commander-container">
            <Commander :username="user.name || 'User'" current-folder="Home" />
        </div>
        <div v-else class="error">
            <p>Unable to load user data.</p>
            <button @click="goHome" class="btn btn-primary">
                Go Home
            </button>
        </div>
    </div>
</template>

<style scoped>
.commander-view {
    min-height: 100vh;
    padding: 20px;
    background-color: #f6f6f6;
}

.commander-container {
    height: calc(100vh - 40px);
}

.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    color: #6b7280;
}

.error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    text-align: center;
}

.error p {
    color: #6b7280;
    margin-bottom: 16px;
}

.btn {
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
</style>

