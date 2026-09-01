/**
 * @vitest-environment node
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAppUpdateStore } from './appUpdate'

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    getPlatform: vi.fn(() => 'android'),
  },
}))

vi.mock('../utils/appVersion', () => ({
  getInstalledAppVersion: vi.fn(async () => '1.3.0'),
}))

vi.mock('../services/appConfig', () => ({
  appConfigService: {
    getVersionConfig: vi.fn(async () => ({
      latestVersion: '1.4.0',
      minimumVersion: '1.2.0',
      androidStoreUrl: 'https://play.google.com/store/apps/details?id=com.petanquestats.app',
    })),
  },
}))

describe('useAppUpdateStore', () => {
  beforeEach(() => {
    const storage = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: (key: string) => storage.get(key) ?? null,
      setItem: (key: string, value: string) => {
        storage.set(key, value)
      },
      removeItem: (key: string) => {
        storage.delete(key)
      },
      clear: () => {
        storage.clear()
      },
    })
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('shows a recommended banner when installed version is behind latest', async () => {
    const store = useAppUpdateStore()
    await store.checkOnce()

    expect(store.status).toBe('recommended')
    expect(store.showRecommendedBanner).toBe(true)
    expect(store.showRequiredBlock).toBe(false)
  })

  it('hides the recommended banner after dismissal until latest changes', async () => {
    const store = useAppUpdateStore()
    await store.checkOnce()

    store.dismissRecommended()
    expect(store.showRecommendedBanner).toBe(false)
  })

  it('does not fail when the config request errors', async () => {
    const { appConfigService } = await import('../services/appConfig')
    vi.mocked(appConfigService.getVersionConfig).mockRejectedValueOnce(new Error('network'))

    const store = useAppUpdateStore()
    await store.checkOnce()

    expect(store.status).toBe('upToDate')
    expect(store.showRecommendedBanner).toBe(false)
    expect(store.showRequiredBlock).toBe(false)
  })
})
