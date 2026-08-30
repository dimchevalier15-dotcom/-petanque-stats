// @vitest-environment node
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import {
  clearMatchDraft,
  hasMatchDraft,
  loadMatchDraft,
  saveMatchDraft,
  saveMatchDraftProgress,
} from '../services/matchDraftStorage'
import type { MatchDraftV1, MatchPlayState, MatchSetup } from '../models/MatchDraft'

const storage = new Map<string, string>()

beforeEach(() => {
  storage.clear()
  globalThis.localStorage = {
    getItem: (key) => storage.get(key) ?? null,
    setItem: (key, value) => {
      storage.set(key, value)
    },
    removeItem: (key) => {
      storage.delete(key)
    },
    clear: () => storage.clear(),
    key: () => null,
    length: 0,
  }
})

const setup: MatchSetup = {
  id: 42,
  type: 'doublette',
  targetScore: 13,
  statisticsMode: 'standard',
  teamA: [1, 2],
  teamB: [3, -1],
  teamAName: 'Les rouges',
  teamBName: null,
  trackedPlayers: [1, 2, 3, -1],
  defaultShotTypes: { 1: 'point', 2: 'tir' },
  startingRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur', '-1': 'tireur' },
  participants: [
    { id: 1, label: 'Dim (Dimitri Chevalier)', shortLabel: 'Dim' },
    { id: 2, label: 'Marc Petit', shortLabel: 'Marc' },
    { id: 3, label: 'Luc Grand', shortLabel: 'Luc' },
    { id: -1, label: 'Marco', shortLabel: 'Marco' },
  ],
  startedAt: '2026-08-29T18:00:00.000Z',
}

const playState: MatchPlayState = {
  currentEndIndex: 1,
  currentRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur', '-1': 'tireur' },
  ends: [
    {
      index: 1,
      winner: 'A',
      points: 3,
      canceled: false,
      balls: [
        {
          playerId: 1,
          notes: [1, 0],
          shotTypes: ['point', 'tir'],
          distances: [7.5, null],
        },
      ],
    },
    { index: 2, balls: [], canceled: false },
  ],
  distanceEstimate: 8,
  substitutions: [],
}

describe('matchDraftStorage', () => {
  afterEach(() => {
    clearMatchDraft()
  })

  it('saves and loads a draft for the same user', () => {
    saveMatchDraft(setup, playState, 7)
    const loaded = loadMatchDraft(7)

    expect(loaded).not.toBeNull()
    expect(loaded?.id).toBe(42)
    expect(loaded?.currentEndIndex).toBe(1)
    expect(loaded?.ends[0]?.balls[0]?.distances).toEqual([7.5, null])
    expect(loaded?.distanceEstimate).toBe(8)
    expect(hasMatchDraft(7)).toBe(true)
  })

  it('does not load a draft saved for another user', () => {
    saveMatchDraft(setup, playState, 7)
    expect(loadMatchDraft(8)).toBeNull()
  })

  it('clears the stored draft', () => {
    saveMatchDraft(setup, playState, null)
    clearMatchDraft()
    expect(loadMatchDraft(null)).toBeNull()
  })

  it('keeps the participant labels needed to play offline', () => {
    saveMatchDraft(setup, playState, 7)
    const loaded = loadMatchDraft(7)

    expect(loaded?.participants).toHaveLength(4)
    expect(loaded?.participants.find((p) => p.id === -1)?.label).toBe('Marco')
    expect(loaded?.teamAName).toBe('Les rouges')
    expect(loaded?.startedAt).toBe('2026-08-29T18:00:00.000Z')
  })

  it('starts with no save progress', () => {
    saveMatchDraft(setup, playState, 7)
    const loaded = loadMatchDraft(7)

    expect(loaded?.serverId).toBeNull()
    expect(loaded?.resolvedPlayers).toEqual({})
  })

  it('preserves the save progress across later plays', () => {
    saveMatchDraft(setup, playState, 7)
    saveMatchDraftProgress({ serverId: 99, resolvedPlayers: { '-1': 12 } })
    saveMatchDraft(setup, playState, 7)

    const loaded = loadMatchDraft(7)
    expect(loaded?.serverId).toBe(99)
    expect(loaded?.resolvedPlayers).toEqual({ '-1': 12 })
  })

  it('drops the progress of a different draft', () => {
    saveMatchDraft(setup, playState, 7)
    saveMatchDraftProgress({ serverId: 99 })
    saveMatchDraft({ ...setup, id: 43 }, playState, 7)

    expect(loadMatchDraft(7)?.serverId).toBeNull()
  })

  it('migrates a draft saved before the deferred save', () => {
    const legacy: MatchDraftV1 = {
      version: 1,
      userId: 7,
      savedAt: '2026-08-20T10:00:00.000Z',
      id: 77,
      type: 'doublette',
      targetScore: 13,
      statisticsMode: 'standard',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      defaultShotTypes: { 1: 'point', 2: 'tir' },
      startingRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur', 4: 'tireur' },
      currentEndIndex: 0,
      ends: [{ index: 1, balls: [], canceled: false }],
      distanceEstimate: null,
      currentRoles: { 1: 'pointeur', 2: 'tireur', 3: 'pointeur', 4: 'tireur' },
    }
    storage.set('match_draft', JSON.stringify(legacy))

    const loaded = loadMatchDraft(7)
    expect(loaded?.version).toBe(2)
    expect(loaded?.id).toBe(77)
    // The match already existed on the server, so creating it again must be skipped.
    expect(loaded?.serverId).toBe(77)
    expect(loaded?.participants).toEqual([])
    expect(loaded?.startedAt).toBe('2026-08-20T10:00:00.000Z')
  })

  it('discards a draft written by an unknown version', () => {
    storage.set('match_draft', JSON.stringify({ ...setup, version: 99, ends: [], currentEndIndex: 0 }))
    expect(loadMatchDraft(7)).toBeNull()
  })
})
