import axios from 'axios'
import router from '../router'

// Use absolute API URL to target backend on port 8080 (Docker exposed)
const api = axios.create({
  baseURL: '/api',
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Login/register can legitimately fail with 401 (wrong credentials): this must
// never be treated as an expired/invalid session.
const authEndpoints = ['/auth/login', '/auth/register']

function isAuthEndpointRequest(url: string | undefined): boolean {
  return url !== undefined && authEndpoints.some((endpoint) => url.endsWith(endpoint))
}

let isHandlingSessionExpiration = false

async function handleSessionExpiration(): Promise<void> {
  if (isHandlingSessionExpiration) {
    return
  }
  isHandlingSessionExpiration = true
  try {
    const { useAuthStore } = await import('../stores/auth')
    const authStore = useAuthStore()
    if (!authStore.isAuthenticated) {
      return
    }
    authStore.logout()
    if (router.currentRoute.value.name !== 'login') {
      await router.push({ name: 'login' })
    }
  } finally {
    isHandlingSessionExpiration = false
  }
}

api.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (axios.isAxiosError(error) && error.response?.status === 401 && !isAuthEndpointRequest(error.config?.url)) {
      await handleSessionExpiration()
    }
    return Promise.reject(error)
  },
)

// Default export for existing imports
export default api
// Named export for modules importing { http }
export const http = api
