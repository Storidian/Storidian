import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

// Views
import Home from '../views/Home.vue'
import Callback from '../views/Callback.vue'
import Profile from '../views/Profile.vue'
import Commander from '../views/Commander.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
    },
    {
        path: '/callback',
        name: 'callback',
        component: Callback,
    },
    {
        path: '/profile',
        name: 'profile',
        component: Profile,
        meta: { requiresAuth: true },
    },
    {
        path: '/commander',
        name: 'commander',
        component: Commander,
        meta: { requiresAuth: true },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

// Navigation guard for protected routes
router.beforeEach(async (to, from, next) => {
    const { isAuthenticated, fetchUser, user } = useAuth()

    // If route requires auth and user is not authenticated
    if (to.meta.requiresAuth && !isAuthenticated.value) {
        // Try to fetch user (in case we have a stored token)
        if (!user.value) {
            await fetchUser()
        }

        if (!isAuthenticated.value) {
            // Redirect to home (which has login button)
            return next({ name: 'home' })
        }
    }

    next()
})

export default router

