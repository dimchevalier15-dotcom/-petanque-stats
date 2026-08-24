/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import type { EndRecord } from './MatchPlay'
import {
  clampEndPoints,
  maxPointsForWinner,
  maxPointsPerEnd,
  suggestEndScore,
  sumTeamBallResults,
} from './EndScoreSuggestion'

function endWithBalls(entries: Array<{ playerId: number; notes: number[] }>): EndRecord {
  return {
    index: 1,
    balls: entries.map((entry) => ({
      playerId: entry.playerId,
      notes: entry.notes as EndRecord['balls'][number]['notes'],
      shotTypes: entry.notes.map(() => 'point' as const),
      distances: entry.notes.map(() => null),
    })),
  }
}

describe('sumTeamBallResults', () => {
  it('sums ball note values for team players', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [1, 0] },
      { playerId: 2, notes: [1] },
      { playerId: 3, notes: [] },
    ])

    expect(sumTeamBallResults(end, [1, 2])).toBe(2)
    expect(sumTeamBallResults(end, [3])).toBe(0)
  })

  it('includes negative notes in the sum', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [2, -1, 0] },
      { playerId: 2, notes: [1, -2] },
    ])

    expect(sumTeamBallResults(end, [1])).toBe(1)
    expect(sumTeamBallResults(end, [2])).toBe(-1)
  })
})

describe('suggestEndScore', () => {
  it('preselects team A when its ball results sum is higher', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [2, 1, 1] },
      { playerId: 2, notes: [0] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1],
        teamB: [2],
        scoreA: 0,
        scoreB: 0,
        targetScore: 13,
        type: 'tete_a_tete',
      }),
    ).toEqual({ winner: 'A', points: 3 })
  })

  it('preselects team B when its ball results sum is higher', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [0] },
      { playerId: 2, notes: [2, 1, 1] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1],
        teamB: [2],
        scoreA: 0,
        scoreB: 0,
        targetScore: 13,
        type: 'tete_a_tete',
      }).winner,
    ).toBe('B')
  })

  it('does not preselect a winner when sums are equal', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [1, 1] },
      { playerId: 2, notes: [1, 1] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1],
        teamB: [2],
        scoreA: 0,
        scoreB: 0,
        targetScore: 13,
        type: 'tete_a_tete',
      }),
    ).toEqual({ winner: null, points: 1 })
  })

  it('caps suggested points to remaining score before target', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [2, 2, 2, 2, 2] },
      { playerId: 2, notes: [] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1],
        teamB: [2],
        scoreA: 12,
        scoreB: 3,
        targetScore: 13,
        type: 'tete_a_tete',
      }),
    ).toEqual({ winner: 'A', points: 1 })
  })

  it('caps suggested points to 3 in tête-à-tête', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [2, 2, 2] },
      { playerId: 2, notes: [-2] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1],
        teamB: [2],
        scoreA: 0,
        scoreB: 0,
        targetScore: 13,
        type: 'tete_a_tete',
      }),
    ).toEqual({ winner: 'A', points: 3 })
  })

  it('caps suggested points to 6 in doublette', () => {
    const end = endWithBalls([
      { playerId: 1, notes: [2, 2, 2] },
      { playerId: 2, notes: [2, 2, 2] },
      { playerId: 3, notes: [-2] },
      { playerId: 4, notes: [] },
    ])

    expect(
      suggestEndScore({
        end,
        teamA: [1, 2],
        teamB: [3, 4],
        scoreA: 0,
        scoreB: 0,
        targetScore: 13,
        type: 'doublette',
      }),
    ).toEqual({ winner: 'A', points: 6 })
  })
})

describe('maxPointsPerEnd', () => {
  it('returns 3 in tête-à-tête and 6 in doublette/triplette', () => {
    expect(maxPointsPerEnd('tete_a_tete')).toBe(3)
    expect(maxPointsPerEnd('doublette')).toBe(6)
    expect(maxPointsPerEnd('triplette')).toBe(6)
  })
})

describe('maxPointsForWinner', () => {
  it('returns remaining points to target score', () => {
    expect(maxPointsForWinner('A', 12, 5, 13, 'doublette')).toBe(1)
    expect(maxPointsForWinner('B', 5, 8, 13, 'doublette')).toBe(5)
  })

  it('never exceeds format maximum', () => {
    expect(maxPointsForWinner('A', 0, 0, 13, 'tete_a_tete')).toBe(3)
    expect(maxPointsForWinner('A', 0, 0, 13, 'doublette')).toBe(6)
    expect(maxPointsForWinner('A', 0, 0, 13, 'triplette')).toBe(6)
  })
})

describe('clampEndPoints', () => {
  it('clamps between 1 and max allowed', () => {
    expect(clampEndPoints('A', 6, 12, 4, 13, 'doublette')).toBe(1)
    expect(clampEndPoints('B', 0, 8, 4, 13, 'doublette')).toBe(1)
    expect(clampEndPoints('B', 9, 4, 8, 13, 'doublette')).toBe(5)
  })

  it('cannot exceed 3 points in tête-à-tête', () => {
    expect(clampEndPoints('A', 9, 0, 0, 13, 'tete_a_tete')).toBe(3)
  })

  it('cannot exceed 6 points in doublette or triplette', () => {
    expect(clampEndPoints('A', 9, 0, 0, 13, 'doublette')).toBe(6)
    expect(clampEndPoints('B', 8, 0, 0, 13, 'triplette')).toBe(6)
  })

  it('allows 0 when the end is complete', () => {
    expect(clampEndPoints('A', 0, 0, 0, 13, 'doublette', 0)).toBe(0)
  })

  it('rejects 0 when the minimum is 1', () => {
    expect(clampEndPoints('A', 0, 0, 0, 13, 'doublette', 1)).toBe(1)
  })
})
