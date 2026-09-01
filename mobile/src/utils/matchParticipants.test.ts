// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { CompleteMatchRequestDto } from '../dto/match/CompleteMatchRequest'
import type { MatchSetup } from '../models/MatchDraft'
import {
  assignPlaceholderPlayers,
  containsProvisionalParticipant,
  emptySlotsToFill,
  excludePlayersFromTracked,
  isProvisionalParticipant,
  nextProvisionalId,
  participantFromPlayer,
  provisionalParticipant,
  remapSetup,
  remapSubmission,
  unresolvedParticipants,
} from './matchParticipants'

const setup: MatchSetup = {
  id: 1,
  type: 'doublette',
  targetScore: 13,
  statisticsMode: 'standard',
  teamA: [5, -1],
  teamB: [7, -2],
  teamAName: null,
  teamBName: null,
  trackedPlayers: [5, -1, 7, -2],
  defaultShotTypes: { 5: 'point', '-1': 'tir' },
  startingRoles: { 5: 'pointeur', '-1': 'tireur', 7: 'pointeur', '-2': 'tireur' },
  participants: [
    { id: 5, label: 'Dim', shortLabel: 'Dim' },
    { id: -1, label: 'Marco', shortLabel: 'Marco' },
    { id: 7, label: 'Luc', shortLabel: 'Luc' },
    { id: -2, label: 'Jo', shortLabel: 'Jo' },
  ],
  startedAt: '2026-08-29T18:00:00.000Z',
}

describe('provisional participant ids', () => {
  it('tells a provisional participant from a persisted player', () => {
    expect(isProvisionalParticipant(-1)).toBe(true)
    expect(isProvisionalParticipant(12)).toBe(false)
  })

  it('never reuses an allocated id', () => {
    expect(nextProvisionalId([])).toBe(-1)
    expect(nextProvisionalId(setup.participants)).toBe(-3)
  })

  it('labels a persisted player with its nickname', () => {
    const participant = participantFromPlayer({
      id: 4,
      firstName: 'Dimitri',
      lastName: 'Chevalier',
      nickname: 'Dim',
      clubId: null,
      clubName: null,
    })
    expect(participant.label).toBe('Dim (Dimitri Chevalier)')
    expect(participant.shortLabel).toBe('Dim')
  })

  it('falls back to the first name when there is no nickname', () => {
    const participant = participantFromPlayer({
      id: 4,
      firstName: 'Dimitri',
      lastName: 'Chevalier',
      nickname: '',
      clubId: null,
      clubName: null,
    })
    expect(participant.label).toBe('Dimitri Chevalier')
    expect(participant.shortLabel).toBe('Dimitri')
  })
})

describe('unresolvedParticipants', () => {
  it('lists the participants still to be linked, substitutes included', () => {
    const substitutions = [{ team: 'A' as const, outPlayerId: 5, inPlayerId: -3, fromEndIndex: 2 }]
    const pending = unresolvedParticipants(setup, substitutions)

    expect(pending.map((participant) => participant.id)).toEqual([-1, -2, -3])
  })

  it('ignores participants already resolved by a previous attempt', () => {
    const pending = unresolvedParticipants(setup, [], { '-1': 30 })
    expect(pending.map((participant) => participant.id)).toEqual([-2])
  })

  it('returns nothing when every participant is a persisted player', () => {
    expect(unresolvedParticipants({ ...setup, teamA: [5, 6], teamB: [7, 8] })).toEqual([])
  })
})

describe('remapSetup', () => {
  it('replaces provisional ids everywhere in the setup', () => {
    const remapped = remapSetup(setup, { '-1': 30, '-2': 31 })

    expect(remapped.teamA).toEqual([5, 30])
    expect(remapped.teamB).toEqual([7, 31])
    expect(remapped.trackedPlayers).toEqual([5, 30, 7, 31])
    expect(remapped.defaultShotTypes).toEqual({ 5: 'point', 30: 'tir' })
    expect(remapped.startingRoles[30]).toBe('tireur')
    expect(remapped.participants.map((participant) => participant.id)).toEqual([5, 30, 7, 31])
  })
})

const payload: CompleteMatchRequestDto = {
  type: 'doublette',
  targetScore: 13,
  statisticsMode: 'standard',
  teamA: [5, -1],
  teamB: [7, -2],
  trackedPlayers: [5, -1, 7, -2],
  substitutions: [{ team: 'A', outPlayerId: 5, inPlayerId: -3, fromEndIndex: 2 }],
  ends: [
    {
      index: 1,
      winner: 'A',
      points: 3,
      canceled: false,
      shots: [{ sequenceOrder: 1, playerId: -1, note: 1, shotType: 'tir', distance: null }],
      roles: [{ playerId: -1, role: 'tireur' }],
    },
  ],
}

describe('remapSubmission', () => {
  it('replaces provisional ids in every ball, role and substitution', () => {
    const remapped = remapSubmission(payload, { '-1': 30, '-2': 31, '-3': 32 })

    expect(remapped.teamA).toEqual([5, 30])
    expect(remapped.substitutions?.[0]?.inPlayerId).toBe(32)
    expect(remapped.ends[0]?.shots[0]?.playerId).toBe(30)
    expect(remapped.ends[0]?.roles?.[0]?.playerId).toBe(30)
    expect(containsProvisionalParticipant(remapped)).toBe(false)
  })

  it('reports a partially remapped payload, whose balls the backend would drop', () => {
    const remapped = remapSubmission(payload, { '-1': 30, '-2': 31 })
    expect(containsProvisionalParticipant(remapped)).toBe(true)
  })
})

describe('emptySlotsToFill', () => {
  it('lists empty slots in team A then team B order', () => {
    expect(
      emptySlotsToFill([1, 2], [1, 2], (team, slot) => team === 'A' && slot === 1),
    ).toEqual([
      { team: 'A', slot: 2, letter: 'A' },
      { team: 'B', slot: 1, letter: 'B' },
      { team: 'B', slot: 2, letter: 'C' },
    ])
  })

  it('assigns up to six letters for a full triplette roster', () => {
    expect(emptySlotsToFill([1, 2, 3], [1, 2, 3], () => false)).toHaveLength(6)
  })
})

describe('provisionalParticipant', () => {
  it('trims the typed name', () => {
    expect(provisionalParticipant(-1, '  Marco  ')).toEqual({
      id: -1,
      label: 'Marco',
      shortLabel: 'Marco',
    })
  })
})

describe('assignPlaceholderPlayers', () => {
  it('maps each provisional id to a distinct placeholder', () => {
    const mapping: Record<number, number> = { 5: 5 }
    assignPlaceholderPlayers([-1, -2], [101, 102, 103], mapping)
    expect(mapping).toEqual({ 5: 5, '-1': 101, '-2': 102 })
  })

  it('rejects more provisionals than placeholders', () => {
    expect(() => assignPlaceholderPlayers([-1, -2, -3], [101, 102], {})).toThrow()
  })
})

describe('excludePlayersFromTracked', () => {
  it('removes placeholder ids from tracked players', () => {
    expect(excludePlayersFromTracked([5, 101, 7], new Set([101, 102]))).toEqual([5, 7])
  })
})
