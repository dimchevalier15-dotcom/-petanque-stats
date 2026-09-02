// @vitest-environment node
import { describe, expect, it } from 'vitest'
import { buildLocalMatchInsights } from './buildLocalMatchInsights'
import type { EndRecord, EndShot } from '../models/MatchPlay'

function shot(order: number, playerId: number, note: number, shotType: 'point' | 'tir' = 'point'): EndShot {
  return { sequenceOrder: order, playerId, note, shotType, distance: null }
}

function endWithRajoutPrefix(extraShots: EndShot[]): EndRecord {
  const shots: EndShot[] = []
  let order = 1

  for (let i = 0; i < 3; i++) {
    shots.push(shot(order++, 1, 0, 'point'))
  }
  for (let i = 0; i < 3; i++) {
    shots.push(shot(order++, 2, 0, 'point'))
  }
  for (let i = 0; i < 3; i++) {
    shots.push(shot(order++, 3, 0, 'point'))
  }

  for (const extra of extraShots) {
    shots.push({ ...extra, sequenceOrder: order++ })
  }

  return {
    index: 1,
    winner: 'B',
    points: 2,
    canceled: false,
    shots,
  }
}

describe('buildLocalMatchInsights', () => {
  it('is unavailable when not all players are tracked', () => {
    const res = buildLocalMatchInsights({
      type: 'tete_a_tete',
      teamA: [1],
      teamB: [2],
      trackedPlayers: [1],
      ends: [],
    })

    expect(res.status).toBe('unavailable')
    expect(res.reason).toBe('not_all_tracked')
  })

  it('computes opening team from first shot', () => {
    const end: EndRecord = {
      index: 1,
      winner: 'A',
      points: 2,
      canceled: false,
      shots: [shot(1, 1, 1, 'point'), shot(2, 2, -1, 'tir')],
    }

    const res = buildLocalMatchInsights({
      type: 'tete_a_tete',
      teamA: [1],
      teamB: [2],
      trackedPlayers: [1, 2],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.teamA?.endsOpened).toBe(1)
    expect(res.pointDominanceTeamA).toEqual({
      endsWonWhenOpened: 1,
      endsOpened: 1,
      endsOpenedWellAndWon: 1,
      endsOpenedWell: 1,
    })
    expect(res.markingTeamA?.point.attempts).toBe(0)
    expect(res.markingTeamB?.tir.attempts).toBe(0)
  })

  it('tracks well-started ends separately from won-when-opened', () => {
    const end: EndRecord = {
      index: 1,
      winner: 'B',
      points: 2,
      canceled: false,
      shots: [shot(1, 1, 1, 'point'), shot(2, 2, 2, 'point')],
    }

    const res = buildLocalMatchInsights({
      type: 'tete_a_tete',
      teamA: [1],
      teamB: [2],
      trackedPlayers: [1, 2],
      ends: [end],
    })

    expect(res.pointDominanceTeamA).toEqual({
      endsWonWhenOpened: 0,
      endsOpened: 1,
      endsOpenedWellAndWon: 0,
      endsOpenedWell: 1,
    })
  })

  it('counts marking balls when opponent is out and stops after success', () => {
    const shots: EndShot[] = []
    let order = 1

    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 1, 0, 'point'))
    }
    for (let i = 0; i < 2; i++) {
      shots.push(shot(order++, 2, 0, 'point'))
    }
    shots.push(shot(order++, 2, 1, 'point'))
    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 3, 0, 'point'))
    }
    shots.push(shot(order++, 4, -1, 'tir'))
    shots.push(shot(order++, 4, 1, 'tir'))
    shots.push(shot(order++, 4, 2, 'tir'))

    const end: EndRecord = {
      index: 1,
      winner: 'B',
      points: 2,
      canceled: false,
      shots,
    }

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.markingTeamB?.tir).toEqual({ made: 1, attempts: 2, rate: 50 })
    expect(res.rajoutTeamB?.tir).toEqual({ made: 1, attempts: 1, rate: 100 })
    expect(res.markingTeamA?.point.attempts).toBe(0)
    expect(res.markingTeamA?.tir.attempts).toBe(0)
  })

  it('counts rajout balls when opponent last ball is not positive', () => {
    const shots: EndShot[] = []
    let order = 1

    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 1, 0, 'point'))
    }
    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 2, 0, 'point'))
    }
    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 3, 0, 'point'))
    }
    shots.push(shot(order++, 4, -1, 'tir'))
    shots.push(shot(order++, 4, 1, 'tir'))
    shots.push(shot(order++, 4, 2, 'tir'))

    const end: EndRecord = {
      index: 1,
      winner: 'B',
      points: 2,
      canceled: false,
      shots,
    }

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.markingTeamB?.tir.attempts).toBe(0)
    expect(res.rajoutTeamB?.point).toEqual({ made: 0, attempts: 3, rate: 0 })
    expect(res.rajoutTeamB?.tir).toEqual({ made: 2, attempts: 3, rate: 66.7 })
  })

  it('stops rajout sequence after minus two', () => {
    const end = endWithRajoutPrefix([
      shot(0, 4, -1, 'tir'),
      shot(0, 4, -2, 'tir'),
      shot(0, 4, 2, 'tir'),
    ])

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.rajoutTeamB?.tir).toEqual({ made: 0, attempts: 2, rate: 0 })
  })

  it('treats zero and minus one as failed rajout and positive as success', () => {
    const end = endWithRajoutPrefix([
      shot(0, 4, 0, 'point'),
      shot(0, 4, -1, 'tir'),
      shot(0, 4, 2, 'tir'),
    ])

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.rajoutTeamB?.point).toEqual({ made: 0, attempts: 4, rate: 0 })
    expect(res.rajoutTeamB?.tir).toEqual({ made: 1, attempts: 2, rate: 50 })
  })

  it('stops rajout sequence when minus two is on point', () => {
    const end = endWithRajoutPrefix([
      shot(0, 4, -2, 'point'),
      shot(0, 4, 1, 'tir'),
    ])

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.rajoutTeamB?.point).toEqual({ made: 0, attempts: 4, rate: 0 })
    expect(res.rajoutTeamB?.tir).toEqual({ made: 0, attempts: 0, rate: null })
  })

  it('counts held end minus two errors when opponent has no balls left', () => {
    const end = endWithRajoutPrefix([
      shot(0, 4, -2, 'point'),
      shot(0, 4, 1, 'tir'),
      shot(0, 4, 0, 'point'),
    ])

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.heldEndErrorTeamB).toEqual({ minusTwoCount: 1, ballsPlayed: 6, rate: 16.7 })
    expect(res.heldEndErrorTeamA).toEqual({ minusTwoCount: 0, ballsPlayed: 0, rate: null })
  })

  it('counts end sequence dominance when opponent plays three consecutive shots', () => {
    const shots: EndShot[] = []
    let order = 1

    shots.push(shot(order++, 1, 0, 'point'))
    shots.push(shot(order++, 2, 0, 'point'))
    shots.push(shot(order++, 1, 0, 'point'))
    shots.push(shot(order++, 2, 0, 'point'))
    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 3, 0, 'point'))
    }
    for (let i = 0; i < 3; i++) {
      shots.push(shot(order++, 4, 0, 'point'))
    }
    shots.push(shot(order++, 1, 0, 'point'))
    shots.push(shot(order++, 2, 0, 'point'))

    const end: EndRecord = {
      index: 1,
      winner: 'B',
      points: 2,
      canceled: false,
      shots,
    }

    const res = buildLocalMatchInsights({
      type: 'doublette',
      teamA: [1, 2],
      teamB: [3, 4],
      trackedPlayers: [1, 2, 3, 4],
      ends: [end],
    })

    expect(res.status).toBe('ok')
    expect(res.endSequenceDominanceTeamB).toEqual({
      endsDominated: 1,
      endsWonWhileDominating: 1,
      pointsOnDominatedEnds: 2,
      totalPointsScored: 2,
    })
    expect(res.endSequenceDominanceTeamA).toEqual({
      endsDominated: 0,
      endsWonWhileDominating: 0,
      pointsOnDominatedEnds: 0,
      totalPointsScored: 0,
    })
  })
})
