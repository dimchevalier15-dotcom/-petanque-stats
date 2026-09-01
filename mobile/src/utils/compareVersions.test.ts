/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { compareVersions, isVersionLowerThan } from './compareVersions'

describe('compareVersions', () => {
  it('compares numeric segments correctly', () => {
    expect(compareVersions('1.9.0', '1.10.0')).toBeLessThan(0)
    expect(compareVersions('1.10.0', '1.9.0')).toBeGreaterThan(0)
    expect(compareVersions('2.0.0', '1.99.99')).toBeGreaterThan(0)
  })

  it('treats missing segments as zero', () => {
    expect(compareVersions('1.0', '1.0.0')).toBe(0)
    expect(compareVersions('1', '1.0.1')).toBeLessThan(0)
  })

  it('detects lower versions', () => {
    expect(isVersionLowerThan('1.3.0', '1.4.0')).toBe(true)
    expect(isVersionLowerThan('1.4.0', '1.4.0')).toBe(false)
    expect(isVersionLowerThan('1.5.0', '1.4.0')).toBe(false)
  })
})
