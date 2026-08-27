/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import type { MatchSummaryPlayer, MatchSummaryShotBreakdown } from '../models/MatchSummary'
import { mergeTeamBreakdown, mergeTeamShotBreakdown } from './useMatchSummaryCharts'

function breakdown(
  partial: Partial<MatchSummaryShotBreakdown> & { average: number },
): MatchSummaryShotBreakdown {
  return {
    p2: 0,
    p1: 0,
    p0: 0,
    m1: 0,
    m2: 0,
    successRate: null,
    ...partial,
  }
}

function player(
  overrides: Partial<MatchSummaryPlayer> & Pick<MatchSummaryPlayer, 'playerId' | 'team'>,
): MatchSummaryPlayer {
  return {
    firstName: 'A',
    lastName: 'B',
    nickname: '',
    average: 0,
    p2: 0,
    p1: 0,
    p0: 0,
    m1: 0,
    m2: 0,
    ...overrides,
  }
}

describe('mergeTeamShotBreakdown', () => {
  it('aggregates point notes across teammates', () => {
    const players = [
      player({
        playerId: 1,
        team: 'A',
        point: breakdown({ average: 1, p1: 2, p0: 1 }),
      }),
      player({
        playerId: 2,
        team: 'A',
        point: breakdown({ average: 2, p2: 2 }),
      }),
    ]

    expect(mergeTeamShotBreakdown(players, 'point')).toEqual({
      average: 1.4,
      p2: 2,
      p1: 2,
      p0: 1,
      m1: 0,
      m2: 0,
      successRate: 80,
    })
  })

  it('keeps point and tir independent', () => {
    const players = [
      player({
        playerId: 1,
        team: 'A',
        point: breakdown({ average: 1, p1: 1 }),
        tir: breakdown({ average: 0, p0: 1, m1: 1 }),
      }),
    ]

    expect(mergeTeamShotBreakdown(players, 'point')).toEqual({
      average: 1,
      p2: 0,
      p1: 1,
      p0: 0,
      m1: 0,
      m2: 0,
      successRate: 100,
    })
    expect(mergeTeamShotBreakdown(players, 'tir')).toEqual({
      average: 0,
      p2: 0,
      p1: 0,
      p0: 1,
      m1: 1,
      m2: 0,
      successRate: 0,
    })
  })

  it('returns null when the team has no balls of that type', () => {
    const players = [
      player({
        playerId: 1,
        team: 'A',
        point: breakdown({ average: 1, p1: 1 }),
      }),
    ]

    expect(mergeTeamShotBreakdown(players, 'tir')).toBeNull()
    expect(mergeTeamBreakdown([])).toBeNull()
  })
})
