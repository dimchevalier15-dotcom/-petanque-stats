import { Capacitor } from '@capacitor/core'

const BROWSER_DEV_API_BASE_URL = '/api'

function normalizeBaseUrl(url: string): string {
  return url.replace(/\/+$/, '')
}

function isLoopbackApiUrl(url: string): boolean {
  try {
    const parsed = new URL(url, 'https://capacitor.local')
    return parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1'
  } catch {
    return false
  }
}

/**
 * Resolves the Axios base URL.
 * Dev browser: relative `/api` (Vite proxy → local API).
 * Production web and Android: `VITE_API_URL` (production API).
 * Android must never fall back to localhost: that is the device itself.
 */
export function getApiBaseUrl(): string {
  const configured = import.meta.env.VITE_API_URL?.trim() ?? ''

  if (Capacitor.isNativePlatform()) {
    if (configured === '' || isLoopbackApiUrl(configured)) {
      throw new Error(
        'Android builds require VITE_API_URL to be the production API URL (not localhost). Use npm run build then npx cap sync android.',
      )
    }
    return normalizeBaseUrl(configured)
  }

  if (configured !== '') {
    return normalizeBaseUrl(configured)
  }

  return BROWSER_DEV_API_BASE_URL
}
