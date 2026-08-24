/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { endTotalByIndex, endTotalTone, formatSignedTotal, playerEndTotalsSum } from './MatchEndGrid'
import type { MatchSummaryPlayer } from './MatchSummary'

function player(endTotals: Array<{ endIndex: number; total: number }>): MatchSummaryPlayer {
  return {
    playerId: 1,
    firstName: 'A',
    lastName: 'A',
    nickname: '',
    team: 'A',
    average: 0,
    p2: 0,
    p1: 0,
    p0: 0,
    m1: 0,
    m2: 0,
    endTotals,
  }
}

describe('formatSignedTotal', () => {
  it('prefixes positive values', () => {
    expect(formatSignedTotal(3)).toBe('+3')
  })

  it('keeps zero and negatives as-is', () => {
    expect(formatSignedTotal(0)).toBe('0')
    expect(formatSignedTotal(-1)).toBe('-1')
  })
})

describe('endTotalTone', () => {
  it('maps totals to note-like tones', () => {
    expect(endTotalTone(4)).toBe('p2')
    expect(endTotalTone(1)).toBe('p1')
    expect(endTotalTone(0)).toBe('p0')
    expect(endTotalTone(-1)).toBe('m1')
    expect(endTotalTone(-3)).toBe('m2')
  })
})

describe('endTotalByIndex', () => {
  it('returns the total of an end or null when the player did not play', () => {
    const row = player([{ endIndex: 1, total: -1 }])
    expect(endTotalByIndex(row, 1)).toBe(-1)
    expect(endTotalByIndex(row, 2)).toBeNull()
  })
})

describe('playerEndTotalsSum', () => {
  it('sums recorded ends only', () => {
    expect(playerEndTotalsSum(player([]))).toBeNull()
    expect(playerEndTotalsSum(player([{ endIndex: 1, total: -1 }, { endIndex: 2, total: 3 }]))).toBe(2)
  })
})
