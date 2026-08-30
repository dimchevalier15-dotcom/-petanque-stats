// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { MatchPlayState, MatchSetup } from '../models/MatchDraft'
import { buildCreateMatchRequest, buildMatchSubmission } from './matchSubmission'

const setup: MatchSetup = {
  id: 1,
  type: 'doublette',
  targetScore: 11,
  statisticsMode: 'standard',
  teamA: [5, 6],
  teamB: [7, 8],
  teamAName: 'Les rouges',
  teamBName: null,
  trackedPlayers: [5, 6, 7, 8],
  defaultShotTypes: { 5: 'point', 6: 'tir', 7: 'point', 8: 'tir' },
  startingRoles: { 5: 'pointeur', 6: 'tireur', 7: 'pointeur', 8: 'tireur' },
  participants: [],
  startedAt: '2026-08-29T18:00:00.000Z',
}

const state: MatchPlayState = {
  currentEndIndex: 2,
  distanceEstimate: null,
  currentRoles: { 5: 'pointeur', 6: 'tireur', 7: 'pointeur', 8: 'tireur' },
  substitutions: [{ team: 'A', outPlayerId: 6, inPlayerId: 9, fromEndIndex: 2 }],
  ends: [
    {
      index: 1,
      winner: 'A',
      points: 3,
      canceled: false,
      balls: [{ playerId: 6, notes: [1], shotTypes: ['tir'], distances: [null] }],
      roles: { 5: 'pointeur', 6: 'tireur' },
    },
    {
      index: 2,
      winner: 'A',
      points: 0,
      canceled: true,
      balls: [{ playerId: 9, notes: [-1], shotTypes: ['tir'], distances: [8] }],
    },
    // The end in progress is never sent.
    { index: 3, balls: [], canceled: false },
  ],
}

describe('buildCreateMatchRequest', () => {
  it('sends the starting roster and the real start time', () => {
    const payload = buildCreateMatchRequest(setup)

    expect(payload.teamA).toEqual([5, 6])
    expect(payload.targetScore).toBe(11)
    expect(payload.teamAName).toBe('Les rouges')
    expect(payload.playedAt).toBe('2026-08-29T18:00:00.000Z')
    expect(payload.startingRoles).toEqual([
      { playerId: 5, role: 'pointeur' },
      { playerId: 6, role: 'tireur' },
      { playerId: 7, role: 'pointeur' },
      { playerId: 8, role: 'tireur' },
    ])
  })

  it('excludes a tracked player who is not in the starting roster', () => {
    const payload = buildCreateMatchRequest({ ...setup, trackedPlayers: [5, 6, 7, 8, 9] })
    expect(payload.trackedPlayers).toEqual([5, 6, 7, 8])
  })
})

describe('buildMatchSubmission', () => {
  it('only sends scored ends', () => {
    const payload = buildMatchSubmission(setup, state)
    expect(payload.ends.map((end) => end.index)).toEqual([1, 2])
  })

  it('forces a canceled end to zero point', () => {
    const payload = buildMatchSubmission(setup, state)
    const canceled = payload.ends[1]
    expect(canceled?.canceled).toBe(true)
    expect(canceled?.points).toBe(0)
  })

  it('tracks the substitute that replaced a tracked player', () => {
    const payload = buildMatchSubmission(setup, state)
    expect(payload.trackedPlayers).toContain(9)
  })

  it('sends a role for every player of an end, substitute included', () => {
    const payload = buildMatchSubmission(setup, state)
    const playerIds = payload.ends[0]?.roles?.map((role) => role.playerId)
    expect(playerIds).toEqual([5, 6, 7, 8, 9])
  })

  it('falls back to the starting role when an end kept no snapshot', () => {
    const payload = buildMatchSubmission(setup, state)
    const role = payload.ends[1]?.roles?.find((entry) => entry.playerId === 6)
    expect(role?.role).toBe('tireur')
  })
})
