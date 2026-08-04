import axios from 'axios'

// Use absolute API URL to target backend on port 8080 (Docker exposed)
const api = axios.create({
  baseURL: 'http://localhost:8080/api',
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Default export for existing imports
export default api
// Named export for modules importing { http }
export const http = api
