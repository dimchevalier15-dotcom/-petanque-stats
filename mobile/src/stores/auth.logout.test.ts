/**
 * @vitest-environment node
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('../services/http', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
  },
}))

import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from './auth'

const storage = new Map<string, string>()

beforeEach(() => {
  storage.clear()
  globalThis.localStorage = {
    getItem: (key) => storage.get(key) ?? null,
    setItem: (key, value) => {
      storage.set(key, value)
    },
    removeItem: (key) => {
      storage.delete(key)
    },
    clear: () => storage.clear(),
    key: () => null,
    length: 0,
  }
  setActivePinia(createPinia())
})

describe('auth store logout after account deletion', () => {
  it('clears the session token so the user is signed out', () => {
    const auth = useAuthStore()
    auth.token = 'jwt-to-revoke'
    auth.user = {
      id: 1,
      email: 'user@test.local',
      playerId: null,
      emailVerified: true,
    }
    localStorage.setItem('auth_token', 'jwt-to-revoke')

    auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(auth.isAuthenticated).toBe(false)
    expect(localStorage.getItem('auth_token')).toBeNull()
  })
})
