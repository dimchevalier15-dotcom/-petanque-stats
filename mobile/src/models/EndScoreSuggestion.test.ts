/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import type { EndRecord } from './MatchPlay'
import {
  clampEndPoints,
  maxPointsForWinner,
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
      }),
    ).toEqual({ winner: 'A', points: 4 })
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
      }),
    ).toEqual({ winner: 'A', points: 1 })
  })
})

describe('maxPointsForWinner', () => {
  it('returns remaining points to target score', () => {
    expect(maxPointsForWinner('A', 12, 5, 13)).toBe(1)
    expect(maxPointsForWinner('B', 5, 8, 13)).toBe(5)
  })
})

describe('clampEndPoints', () => {
  it('clamps between 1 and max allowed', () => {
    expect(clampEndPoints('A', 6, 12, 4, 13)).toBe(1)
    expect(clampEndPoints('B', 0, 8, 4, 13)).toBe(1)
    expect(clampEndPoints('B', 9, 4, 8, 13)).toBe(5)
  })
})
