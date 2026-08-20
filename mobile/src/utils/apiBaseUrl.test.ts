/**
 * @vitest-environment node
 */
import { Capacitor } from '@capacitor/core'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { getApiBaseUrl } from './apiBaseUrl'

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn(() => false),
  },
}))

describe('getApiBaseUrl', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(false)
  })

  it('uses the Vite proxy path in the browser when VITE_API_URL is empty', () => {
    vi.stubEnv('VITE_API_URL', '')
    expect(getApiBaseUrl()).toBe('/api')
  })

  it('uses VITE_API_URL in the browser when set', () => {
    vi.stubEnv('VITE_API_URL', 'https://api.petanque-analytics.com/api/')
    expect(getApiBaseUrl()).toBe('https://api.petanque-analytics.com/api')
  })

  it('uses VITE_API_URL on native platforms', () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true)
    vi.stubEnv('VITE_API_URL', 'https://api.petanque-analytics.com/api')
    expect(getApiBaseUrl()).toBe('https://api.petanque-analytics.com/api')
  })

  it('rejects localhost on native platforms', () => {
    vi.mocked(Capacitor.isNativePlatform).mockReturnValue(true)
    vi.stubEnv('VITE_API_URL', 'http://localhost:8080/api')
    expect(() => getApiBaseUrl()).toThrow(/production API URL/)
  })
})
