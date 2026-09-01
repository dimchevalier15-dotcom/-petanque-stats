/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { formatMasters, playerCochonnetMastersFromEnds, playerMastersFromEnds, playerShotMastersFromEnds, shotMasters, shotSuccessRate } from './matchSuccessRate'
import type { EndRecord } from '../models/MatchPlay'

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

describe('playerMastersFromEnds', () => {
  it('counts successes across all ends for a player', () => {
    const ends: EndRecord[] = [
      {
        index: 1,
        shots: [
          { sequenceOrder: 1, playerId: 1, note: 1, shotType: 'point', distance: null },
          { sequenceOrder: 2, playerId: 1, note: -1, shotType: 'point', distance: null },
          { sequenceOrder: 3, playerId: 1, note: 2, shotType: 'point', distance: null },
        ],
      },
      {
        index: 2,
        shots: [
          { sequenceOrder: 1, playerId: 1, note: 0, shotType: 'point', distance: null },
          { sequenceOrder: 2, playerId: 1, note: 1, shotType: 'tir', distance: null },
        ],
      },
    ]

    expect(playerMastersFromEnds(ends, 1)).toEqual({ success: 3, total: 5 })
    expect(formatMasters(playerMastersFromEnds(ends, 1)!)).toBe('3/5')
  })

  it('returns null when the player has no balls', () => {
    expect(playerMastersFromEnds([], 1)).toBeNull()
    expect(playerMastersFromEnds([{ index: 1, shots: [] }], 1)).toBeNull()
  })
})

describe('playerShotMastersFromEnds', () => {
  it('counts point and tir masters separately', () => {
    const ends: EndRecord[] = [
      {
        index: 1,
        shots: [
          { sequenceOrder: 1, playerId: 1, note: 1, shotType: 'point', distance: null },
          { sequenceOrder: 2, playerId: 1, note: -1, shotType: 'point', distance: null },
          { sequenceOrder: 3, playerId: 1, note: 2, shotType: 'tir', distance: null },
          { sequenceOrder: 4, playerId: 1, note: 0, shotType: 'tir', distance: null },
        ],
      },
    ]

    expect(playerShotMastersFromEnds(ends, 1, 'point')).toEqual({ success: 1, total: 2 })
    expect(playerShotMastersFromEnds(ends, 1, 'tir')).toEqual({ success: 1, total: 2 })
  })

  it('excludes cochonnet shots from tir masters', () => {
    const ends: EndRecord[] = [
      {
        index: 1,
        shots: [
          { sequenceOrder: 1, playerId: 1, note: 1, shotType: 'tir', distance: null },
          { sequenceOrder: 2, playerId: 1, note: 2, shotType: 'tir', distance: null, isCochonnet: true },
          { sequenceOrder: 3, playerId: 1, note: -1, shotType: 'tir', distance: null },
        ],
      },
    ]

    expect(playerShotMastersFromEnds(ends, 1, 'tir')).toEqual({ success: 1, total: 2 })
  })

  it('counts cochonnet masters separately', () => {
    const ends: EndRecord[] = [
      {
        index: 1,
        shots: [
          { sequenceOrder: 1, playerId: 1, note: 2, shotType: 'tir', distance: null, isCochonnet: true },
          { sequenceOrder: 2, playerId: 1, note: 1, shotType: 'tir', distance: null, isCochonnet: true },
        ],
      },
    ]

    expect(playerCochonnetMastersFromEnds(ends, 1)).toEqual({ success: 2, total: 2 })
    expect(playerShotMastersFromEnds(ends, 1, 'tir')).toBeNull()
  })
})
