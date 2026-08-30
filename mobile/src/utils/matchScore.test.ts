// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { EndRecord } from '../models/MatchPlay'
import { matchScore, normalizeOpeningScore, scoreFromEnds } from './matchScore'

const ends: EndRecord[] = [
  { index: 1, balls: [], winner: 'A', points: 3, canceled: false },
  { index: 2, balls: [], winner: 'B', points: 2, canceled: false },
]

describe('matchScore', () => {
  it('sums only recorded ends by default', () => {
    expect(scoreFromEnds(ends)).toEqual({ scoreA: 3, scoreB: 2 })
    expect(matchScore(ends)).toEqual({ scoreA: 3, scoreB: 2 })
  })

  it('adds the opening score configured at match start', () => {
    expect(matchScore(ends, 5, 1)).toEqual({ scoreA: 8, scoreB: 3 })
  })

  it('ignores canceled ends', () => {
    const withCanceled: EndRecord[] = [
      ...ends,
      { index: 3, balls: [], winner: 'A', points: 4, canceled: true },
    ]
    expect(matchScore(withCanceled, 2, 0)).toEqual({ scoreA: 5, scoreB: 2 })
  })
})

describe('normalizeOpeningScore', () => {
  it('clamps invalid values', () => {
    expect(normalizeOpeningScore(-3, 13)).toBe(0)
    expect(normalizeOpeningScore(99, 13)).toBe(13)
    expect(normalizeOpeningScore(4.8, 13)).toBe(4)
  })
})
