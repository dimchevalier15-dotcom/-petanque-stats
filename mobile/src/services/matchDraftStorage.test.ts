// @vitest-environment node
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import {
  clearMatchDraft,
  hasMatchDraft,
  loadMatchDraft,
  saveMatchDraft,
} from '../services/matchDraftStorage'
import type { MatchSetup } from '../models/MatchDraft'

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
  teamB: [3, 4],
  trackedPlayers: [1, 2, 3, 4],
  defaultShotTypes: { 1: 'point', 2: 'tir' },
}

const playState = {
  currentEndIndex: 1,
  ends: [
    {
      index: 1,
      winner: 'A' as const,
      points: 3,
      canceled: false,
      balls: [
        {
          playerId: 1,
          notes: [1, 0] as (-2 | -1 | 0 | 1 | 2)[],
          shotTypes: ['point', 'tir'] as ('point' | 'tir')[],
          distances: [7.5, null],
        },
      ],
    },
    { index: 2, balls: [], canceled: false },
  ],
  distanceEstimate: 8,
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
    expect(loaded?.ends[0].balls[0].distances).toEqual([7.5, null])
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
})
