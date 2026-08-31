// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { MatchDraft } from '../models/MatchDraft'
import { buildLocalMatchSummary } from '../utils/buildLocalMatchSummary'

const baseDraft: MatchDraft = {
  version: 2,
  userId: null,
  draftOwner: 'guest',
  savedAt: '2026-08-29T18:00:00.000Z',
  id: 42,
  serverId: null,
  resolvedPlayers: {},
  type: 'doublette',
  targetScore: 13,
  statisticsMode: 'standard',
  teamA: [1, 2],
  teamB: [3, -1],
  teamAName: 'Team A',
  teamBName: null,
  trackedPlayers: [1, 2, 3],
  defaultShotTypes: { 1: 'point', 2: 'tir', 3: 'point' },
  startingRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur' },
  participants: [
    { id: 1, label: 'Alice Martin', shortLabel: 'Alice' },
    { id: 2, label: 'Bob Petit', shortLabel: 'Bob' },
    { id: 3, label: 'Luc', shortLabel: 'Luc' },
    { id: -1, label: 'Marco', shortLabel: 'Marco' },
  ],
  startedAt: '2026-08-29T18:00:00.000Z',
  currentEndIndex: 0,
  ends: [
    {
      index: 1,
      winner: 'A',
      points: 3,
      canceled: false,
      balls: [
        {
          playerId: 1,
          notes: [2, 1],
          shotTypes: ['point', 'point'],
          distances: [7.5, 8],
        },
      ],
    },
  ],
  distanceEstimate: 8,
  currentRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur' },
  substitutions: [],
  openingScoreA: 0,
  openingScoreB: 0,
}

describe('buildLocalMatchSummary', () => {
  it('builds score and player stats from a local draft', () => {
    const summary = buildLocalMatchSummary(baseDraft)

    expect(summary.scoreA).toBe(3)
    expect(summary.scoreB).toBe(0)
    expect(summary.winner).toBe('A')
    expect(summary.players).toHaveLength(3)

    const alice = summary.players.find((player) => player.playerId === 1)
    expect(alice?.p2).toBe(1)
    expect(alice?.p1).toBe(1)
    expect(alice?.average).toBe(1.5)
    expect(alice?.team).toBe('A')
  })
})
