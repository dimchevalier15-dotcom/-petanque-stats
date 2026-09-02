import { Capacitor } from '@capacitor/core'

function normalizeBaseUrl(url: string): string {
  return url.replace(/\/+$/, '')
}

function deriveFrontendFromApiUrl(apiUrl: string): string | null {
  try {
    const parsed = new URL(apiUrl, 'https://capacitor.local')
    if (parsed.hostname.startsWith('api.')) {
      parsed.hostname = parsed.hostname.slice(4)
    }
    parsed.pathname = ''
    parsed.search = ''
    parsed.hash = ''
    return normalizeBaseUrl(parsed.origin)
  } catch {
    return null
  }
}

/**
 * Public web origin for shareable links (recap, live).
 * Native app: derived from VITE_API_URL or VITE_FRONTEND_BASE_URL (never capacitor://localhost).
 * Browser: current origin (dev proxy or production host).
 */
export function getPublicAppBaseUrl(): string {
  const frontendUrl = import.meta.env.VITE_FRONTEND_BASE_URL?.trim() ?? ''
  if (frontendUrl !== '') {
    return normalizeBaseUrl(frontendUrl)
  }

  if (Capacitor.isNativePlatform()) {
    const apiUrl = import.meta.env.VITE_API_URL?.trim() ?? ''
    const derived = apiUrl !== '' ? deriveFrontendFromApiUrl(apiUrl) : null
    if (derived) {
      return derived
    }
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin
  }

  return 'https://petanque-analytics.com'
}
