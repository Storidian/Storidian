import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable wrapper for the auth store
 * This maintains backward compatibility with existing code
 * while using Pinia under the hood
 */
export function useAuth() {
  const store = useAuthStore()

  return {
    // State (as refs for backward compatibility)
    accessToken: computed(() => store.accessToken),
    refreshToken: computed(() => store.refreshToken),
    user: computed(() => store.user),
    isLoading: computed(() => store.isLoading),
    error: computed(() => store.error),
    isAuthenticated: computed(() => store.isAuthenticated),

    // User name helpers
    fullName: computed(() => store.fullName),
    firstName: computed(() => store.firstName),
    lastName: computed(() => store.lastName),
    initials: computed(() => store.initials),

    // Methods
    login: () => store.login(),
    logout: () => store.logout(),
    handleCallback: (code, state) => store.handleCallback(code, state),
    fetchUser: () => store.fetchUser(),
    refreshAccessToken: () => store.refreshAccessToken(),
    apiFetch: (url, options) => store.apiFetch(url, options)
  }
}
