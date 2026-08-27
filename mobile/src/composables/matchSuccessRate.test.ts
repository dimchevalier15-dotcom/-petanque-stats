/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { formatMasters, shotMasters, shotSuccessRate } from './matchSuccessRate'

describe('shotSuccessRate', () => {
  it('counts +2 as success', () => {
    expect(shotSuccessRate({ p2: 1, p1: 0, p0: 0, m1: 0, m2: 0, average: 2, successRate: null })).toBe(100)
  })

  it('counts +1 as success', () => {
    expect(shotSuccessRate({ p2: 0, p1: 1, p0: 0, m1: 0, m2: 0, average: 1, successRate: null })).toBe(100)
  })

  it('counts 0 as failure', () => {
    expect(shotSuccessRate({ p2: 0, p1: 0, p0: 1, m1: 0, m2: 0, average: 0, successRate: null })).toBe(0)
  })

  it('counts -1 as failure', () => {
    expect(shotSuccessRate({ p2: 0, p1: 0, p0: 0, m1: 1, m2: 0, average: -1, successRate: null })).toBe(0)
  })

  it('counts -2 as failure', () => {
    expect(shotSuccessRate({ p2: 0, p1: 0, p0: 0, m1: 0, m2: 1, average: -2, successRate: null })).toBe(0)
  })

  it('returns 60% for 6 positive balls out of 10', () => {
    expect(shotSuccessRate({ p2: 3, p1: 3, p0: 2, m1: 1, m2: 1, average: 0, successRate: null })).toBe(60)
  })

  it('returns null when there is no data', () => {
    expect(shotSuccessRate(null)).toBeNull()
    expect(shotSuccessRate({ p2: 0, p1: 0, p0: 0, m1: 0, m2: 0, average: 0, successRate: null })).toBeNull()
  })

  it('prefers the API successRate when present', () => {
    expect(shotSuccessRate({ p2: 1, p1: 0, p0: 1, m1: 0, m2: 0, average: 1, successRate: 50 })).toBe(50)
  })
})

describe('shotMasters', () => {
  it('counts +1 and +2 as successes', () => {
    expect(shotMasters({ p2: 3, p1: 3, p0: 2, m1: 1, m2: 1, average: 0, successRate: null })).toEqual({
      success: 6,
      total: 10,
    })
    expect(formatMasters({ success: 6, total: 10 })).toBe('6/10')
  })

  it('returns null when there is no data', () => {
    expect(shotMasters(null)).toBeNull()
    expect(shotMasters({ p2: 0, p1: 0, p0: 0, m1: 0, m2: 0, average: 0, successRate: null })).toBeNull()
  })
})
