/**
 * @vitest-environment node
 */
import { Capacitor } from '@capacitor/core'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { getPublicAppBaseUrl } from './getPublicAppBaseUrl'

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn(() => false),
  },
}))

describe('getPublicAppBaseUrl', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false)
  })

  it('uses VITE_FRONTEND_BASE_URL when set', () => {
    vi.stubEnv('VITE_FRONTEND_BASE_URL', 'https://petanque-analytics.com/')
    expect(getPublicAppBaseUrl()).toBe('https://petanque-analytics.com')
  })

  it('derives the frontend host from VITE_API_URL on native', () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true)
    vi.stubEnv('VITE_API_URL', 'https://api.petanque-analytics.com/api')
    expect(getPublicAppBaseUrl()).toBe('https://petanque-analytics.com')
  })

  it('falls back to window.location.origin in the browser', () => {
    vi.stubGlobal('window', { location: { origin: 'http://localhost:5173' } })
    expect(getPublicAppBaseUrl()).toBe('http://localhost:5173')
  })
})
