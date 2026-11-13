import { describe, expect, it } from 'vitest'
import type { TournamentDay } from '../models/TournamentDay'
import { currentChampionshipDayId } from './tournamentDays'

function day(id: number, status: TournamentDay['status'], phase: TournamentDay['phase'] = 'championship'): TournamentDay {
  return {
    id,
    number: id,
    phase,
    status,
    playoffRound: null,
    closedAt: null,
    excludedLaneNumbers: [],
    usableLaneNumbers: [],
    readyToClose: false,
    deletable: false,
    matches: [],
  }
}

describe('currentChampionshipDayId', () => {
  it('opens the first day when none is closed', () => {
    expect(currentChampionshipDayId([day(1, 'generated'), day(2, 'generated')])).toBe(1)
  })

  it('opens the first day that is not closed', () => {
    expect(currentChampionshipDayId([day(1, 'closed'), day(2, 'in_progress'), day(3, 'generated')])).toBe(2)
  })

  it('opens the last day when every championship day is closed', () => {
    expect(currentChampionshipDayId([day(1, 'closed'), day(2, 'closed'), day(9, 'generated', 'playoff')])).toBe(2)
  })
})
