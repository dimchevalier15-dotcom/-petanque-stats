// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { EndRecord } from '../models/MatchPlay'
import {
  addShot,
  migrateLegacyBallsToShots,
  undoLastShot,
  hasValidShotSequence,
} from './matchEndShots'

describe('matchEndShots', () => {
  it('assigns global sequence order when adding shots', () => {
    const end: EndRecord = { index: 1, shots: [] }
    addShot(end, { playerId: 1, note: 1, shotType: 'point', distance: 7 })
    addShot(end, { playerId: 2, note: -1, shotType: 'tir', distance: 7 })

    expect(end.shots.map((shot) => shot.sequenceOrder)).toEqual([1, 2])
  })

  it('undoes the last shot by sequence order', () => {
    const end: EndRecord = { index: 1, shots: [] }
    addShot(end, { playerId: 1, note: 1, shotType: 'point', distance: null })
    addShot(end, { playerId: 2, note: 0, shotType: 'point', distance: null })

    undoLastShot(end)

    expect(end.shots).toHaveLength(1)
    expect(end.shots[0]?.playerId).toBe(1)
  })

  it('migrates legacy per-player balls into a global sequence', () => {
    const shots = migrateLegacyBallsToShots([
      { playerId: 1, notes: [1], shotTypes: ['point'], distances: [7] },
      { playerId: 2, notes: [-1], shotTypes: ['tir'], distances: [8] },
    ])

    expect(shots.map((shot) => shot.sequenceOrder)).toEqual([1, 2])
  })

  it('validates contiguous sequence order', () => {
    const end: EndRecord = {
      index: 1,
      shots: [
        { sequenceOrder: 1, playerId: 1, note: 1, shotType: 'point', distance: null },
        { sequenceOrder: 2, playerId: 2, note: 0, shotType: 'tir', distance: null },
      ],
    }

    expect(hasValidShotSequence(end, 3)).toBe(true)
  })
})
