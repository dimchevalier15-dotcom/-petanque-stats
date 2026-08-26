import { defineStore } from 'pinia'
import { authService, AuthValidationError } from '../services/auth'
import type { AuthSession } from '../models/AuthSession'
import type { User } from '../models/User'
import type { RegisterRequest } from '../dto/auth/RegisterRequest'

const TOKEN_KEY = 'auth_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null as string | null,
    user: null as User | null,
    loading: false,
    lastError: null as string | null,
    lastFieldErrors: {} as Record<string, string>,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    async initFromStorage() {
      this.lastError = null
      this.lastFieldErrors = {}
      const token = localStorage.getItem(TOKEN_KEY)
      if (token) {
        this.token = token
        try {
          const me = await authService.me()
          this.user = me
        } catch {
          this.logout()
        }
      }
    },
    async register(payload: RegisterRequest) {
      this.loading = true
      this.lastError = null
      this.lastFieldErrors = {}
      try {
        const res: AuthSession = await authService.register(payload)
        this.token = res.token
        this.user = res.user
        localStorage.setItem(TOKEN_KEY, res.token)
      } catch (error) {
        if (error instanceof AuthValidationError) {
          this.lastFieldErrors = error.fields
          this.lastError = error.message
        } else {
          this.lastError = error instanceof Error ? error.message : 'auth.errors.generic'
        }
      } finally {
        this.loading = false
      }
    },
    async login(email: string, password: string) {
      this.loading = true
      this.lastError = null
      try {
        const res: AuthSession = await authService.login(email, password)
        this.token = res.token
        localStorage.setItem(TOKEN_KEY, res.token)
        this.user = await authService.me()
      } catch {
        this.lastError = 'auth.errors.invalidCredentials'
      } finally {
        this.loading = false
      }
    },
    async forgotPassword(email: string): Promise<boolean> {
      this.loading = true
      this.lastError = null
      try {
        await authService.forgotPassword(email)
        return true
      } catch (error) {
        const message = error instanceof Error ? error.message : 'auth.errors.generic'
        this.lastError = message === 'auth.errors.tooManyRequests' ? message : 'auth.errors.generic'
        return false
      } finally {
        this.loading = false
      }
    },
    async resetPassword(token: string, password: string): Promise<boolean> {
      this.loading = true
      this.lastError = null
      try {
        await authService.resetPassword(token, password)
        return true
      } catch (error) {
        this.lastError = error instanceof Error ? error.message : 'auth.errors.generic'
        return false
      } finally {
        this.loading = false
      }
    },
    async resendVerification(): Promise<boolean> {
      this.loading = true
      this.lastError = null
      try {
        const result = await authService.resendVerification()
        if (result.alreadyVerified && this.user) {
          this.user = { ...this.user, emailVerified: true }
        }
        return true
      } catch (error) {
        this.lastError = error instanceof Error ? error.message : 'auth.errors.generic'
        return false
      } finally {
        this.loading = false
      }
    },
    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem(TOKEN_KEY)
      import('./impersonation').then(({ useImpersonationStore }) => {
        useImpersonationStore().clear()
      })
    },
  },
})
