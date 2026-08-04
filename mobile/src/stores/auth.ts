import { defineStore } from 'pinia'
import { authService, type AuthResponse, type AuthUser } from '../services/auth'

const TOKEN_KEY = 'auth_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null as string | null,
    user: null as AuthUser | null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    async initFromStorage() {
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
    async register(email: string, password: string) {
      this.loading = true
      try {
        const res: AuthResponse = await authService.register(email, password)
        this.token = res.token
        this.user = res.user
        localStorage.setItem(TOKEN_KEY, res.token)
      }
      catch (e) {
          console.log(e)
      } finally {
        this.loading = false
      }
    },
    async login(email: string, password: string) {
      this.loading = true
      try {
        const res: AuthResponse = await authService.login(email, password)
        this.token = res.token
        this.user = res.user
        localStorage.setItem(TOKEN_KEY, res.token)
      } finally {
        this.loading = false
      }
    },
    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem(TOKEN_KEY)
    },
  },
})
