/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { isSafeInternalPath } from './internalRedirect'

describe('isSafeInternalPath', () => {
  it('accepts in-app paths', () => {
    expect(isSafeInternalPath('/delete-account')).toBe(true)
    expect(isSafeInternalPath('/privacy')).toBe(true)
  })

  it('rejects open redirects', () => {
    expect(isSafeInternalPath('https://evil.example/phish')).toBe(false)
    expect(isSafeInternalPath('//evil.example')).toBe(false)
    expect(isSafeInternalPath('\\evil')).toBe(false)
    expect(isSafeInternalPath('login')).toBe(false)
    expect(isSafeInternalPath(null)).toBe(false)
  })
})
