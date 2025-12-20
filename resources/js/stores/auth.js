import { defineStore } from 'pinia'

// OAuth configuration
const OAUTH_CONFIG = {
  clientId: 'storidian-web',
  redirectUri: `${window.location.origin}/callback`,
  authorizeUrl: '/oauth/authorize',
  tokenUrl: '/api/oauth/token'
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    accessToken: null,
    refreshToken: null,
    user: null,
    isLoading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,

    /**
     * Get the user's full name
     */
    fullName: (state) => {
      return state.user?.name || ''
    },

    /**
     * Get the user's first name
     */
    firstName: (state) => {
      if (!state.user?.name) return ''
      const nameParts = state.user.name.trim().split(/\s+/)
      return nameParts[0] || ''
    },

    /**
     * Get the user's last name
     */
    lastName: (state) => {
      if (!state.user?.name) return ''
      const nameParts = state.user.name.trim().split(/\s+/)
      if (nameParts.length <= 1) return ''
      // Return everything after the first name as last name
      return nameParts.slice(1).join(' ') || ''
    },

    /**
     * Get the user's initials (first letter of first name and last name)
     */
    initials: (state) => {
      if (!state.user?.name) return ''
      const nameParts = state.user.name.trim().split(/\s+/).filter(part => part.length > 0)
      
      if (nameParts.length === 0) return ''
      if (nameParts.length === 1) {
        // Single name: use first two letters if available, otherwise just first letter
        const name = nameParts[0]
        return name.length >= 2 ? name.substring(0, 2).toUpperCase() : name.charAt(0).toUpperCase()
      }
      
      // Multiple names: use first letter of first name and first letter of last name
      const firstInitial = nameParts[0].charAt(0).toUpperCase()
      const lastInitial = nameParts[nameParts.length - 1].charAt(0).toUpperCase()
      return firstInitial + lastInitial
    }
  },

  actions: {
    /**
     * Generate a random string for PKCE and state
     */
    generateRandomString(length = 64) {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~'
      let result = ''
      const randomValues = crypto.getRandomValues(new Uint8Array(length))
      for (let i = 0; i < length; i++) {
        result += chars[randomValues[i] % chars.length]
      }
      return result
    },

    /**
     * Generate PKCE code challenge from verifier
     * Uses crypto.subtle when available (HTTPS), falls back to plain method for HTTP dev
     */
    async generateCodeChallenge(codeVerifier) {
      // crypto.subtle is only available in secure contexts (HTTPS)
      // For local development over HTTP, we fall back to 'plain' method
      if (typeof crypto !== 'undefined' && crypto.subtle) {
        const encoder = new TextEncoder()
        const data = encoder.encode(codeVerifier)
        const digest = await crypto.subtle.digest('SHA-256', data)
        const base64 = btoa(String.fromCharCode(...new Uint8Array(digest)))
        return {
          challenge: base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''),
          method: 'S256'
        }
      }

      // Fallback: use plain method (verifier = challenge)
      // This is less secure but works for development
      console.warn('crypto.subtle not available (HTTP context). Using plain PKCE method.')
      return {
        challenge: codeVerifier,
        method: 'plain'
      }
    },

    /**
     * Store tokens
     */
    setTokens(access, refresh) {
      this.accessToken = access
      this.refreshToken = refresh
    },

    /**
     * Clear tokens and user
     */
    clearAuth() {
      this.accessToken = null
      this.refreshToken = null
      this.user = null
      this.error = null
    },

    /**
     * Initiate OAuth login flow
     */
    async login() {
      // Generate PKCE code verifier and challenge
      const codeVerifier = this.generateRandomString(64)
      const { challenge, method } = await this.generateCodeChallenge(codeVerifier)
      const state = this.generateRandomString(32)

      // Store verifier and state for callback
      sessionStorage.setItem('oauth_code_verifier', codeVerifier)
      sessionStorage.setItem('oauth_state', state)

      // Build authorization URL
      const params = new URLSearchParams({
        client_id: OAUTH_CONFIG.clientId,
        redirect_uri: OAUTH_CONFIG.redirectUri,
        response_type: 'code',
        scope: 'profile files:read files:write',
        code_challenge: challenge,
        code_challenge_method: method,
        state: state
      })

      // Redirect to authorization endpoint
      window.location.href = `${OAUTH_CONFIG.authorizeUrl}?${params.toString()}`
    },

    /**
     * Handle OAuth callback
     */
    async handleCallback(code, state) {
      this.isLoading = true
      this.error = null

      try {
        // Verify state
        const storedState = sessionStorage.getItem('oauth_state')
        if (state !== storedState) {
          throw new Error('Invalid state parameter')
        }

        // Get code verifier
        const codeVerifier = sessionStorage.getItem('oauth_code_verifier')
        if (!codeVerifier) {
          throw new Error('Missing code verifier')
        }

        // Exchange code for tokens
        const response = await fetch(OAUTH_CONFIG.tokenUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
          },
          body: JSON.stringify({
            grant_type: 'authorization_code',
            client_id: OAUTH_CONFIG.clientId,
            code: code,
            code_verifier: codeVerifier,
            redirect_uri: OAUTH_CONFIG.redirectUri
          })
        })

        const data = await response.json()

        if (!response.ok) {
          throw new Error(data.error_description || data.error || 'Token exchange failed')
        }

        // Store tokens
        this.setTokens(data.access_token, data.refresh_token)

        // Clean up session storage
        sessionStorage.removeItem('oauth_code_verifier')
        sessionStorage.removeItem('oauth_state')

        // Fetch user info
        await this.fetchUser()

        return true
      } catch (err) {
        this.error = err.message
        console.error('OAuth callback error:', err)
        return false
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Fetch current user
     */
    async fetchUser() {
      if (!this.accessToken) {
        return null
      }

      this.isLoading = true
      this.error = null

      try {
        const response = await fetch('/api/v1/auth/me', {
          headers: {
            Authorization: `Bearer ${this.accessToken}`,
            Accept: 'application/json'
          }
        })

        if (response.status === 401) {
          // Try to refresh token
          const refreshed = await this.refreshAccessToken()
          if (refreshed) {
            return this.fetchUser()
          }
          this.clearAuth()
          return null
        }

        if (!response.ok) {
          throw new Error('Failed to fetch user')
        }

        const data = await response.json()
        this.user = data.data
        return this.user
      } catch (err) {
        this.error = err.message
        console.error('Fetch user error:', err)
        return null
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Refresh access token
     */
    async refreshAccessToken() {
      if (!this.refreshToken) {
        return false
      }

      try {
        const response = await fetch(OAUTH_CONFIG.tokenUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json'
          },
          body: JSON.stringify({
            grant_type: 'refresh_token',
            client_id: OAUTH_CONFIG.clientId,
            refresh_token: this.refreshToken
          })
        })

        if (!response.ok) {
          return false
        }

        const data = await response.json()
        this.setTokens(data.access_token, data.refresh_token)
        return true
      } catch (err) {
        console.error('Token refresh error:', err)
        return false
      }
    },

    /**
     * Logout
     */
    async logout() {
      try {
        if (this.accessToken) {
          await fetch('/api/v1/auth/logout', {
            method: 'POST',
            headers: {
              Authorization: `Bearer ${this.accessToken}`,
              Accept: 'application/json'
            }
          })
        }
      } catch (err) {
        console.error('Logout error:', err)
      } finally {
        this.clearAuth()
      }
    },

    /**
     * Make authenticated API request
     */
    async apiFetch(url, options = {}) {
      if (!this.accessToken) {
        throw new Error('Not authenticated')
      }

      const response = await fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          Authorization: `Bearer ${this.accessToken}`,
          Accept: 'application/json'
        }
      })

      if (response.status === 401) {
        const refreshed = await this.refreshAccessToken()
        if (refreshed) {
          return this.apiFetch(url, options)
        }
        this.clearAuth()
        throw new Error('Session expired')
      }

      return response
    }
  },

  persist: {
    key: 'auth',
    storage: localStorage,
    paths: ['accessToken', 'refreshToken', 'user']
  }
})

