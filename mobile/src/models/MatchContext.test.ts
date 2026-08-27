/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import {
  formatPlayedAt,
  matchContextToForm,
  playedAtToInputDate,
  todayInputDate,
  type MatchContext,
} from './MatchContext'

function context(partial: Partial<MatchContext> = {}): MatchContext {
  return {
    matchId: 1,
    comment: null,
    teamAName: null,
    teamBName: null,
    nature: null,
    competitionId: null,
    competitionName: null,
    competitionStage: null,
    terrainType: null,
    playedAt: '2026-08-20',
    ...partial,
  }
}

describe('playedAt dates', () => {
  it('keeps YYYY-MM-DD for the date input', () => {
    expect(playedAtToInputDate('2026-08-20')).toBe('2026-08-20')
    expect(playedAtToInputDate('2026-08-20T18:30:00+02:00')).toBe('2026-08-20')
  })

  it('formats without timezone shift', () => {
    expect(formatPlayedAt('2026-08-20', 'fr-FR')).toBe(
      new Date(2026, 7, 20).toLocaleDateString('fr-FR'),
    )
  })

  it('maps context playedAt into the form', () => {
    expect(matchContextToForm(context()).playedAt).toBe('2026-08-20')
  })

  it('formats local today as YYYY-MM-DD', () => {
    expect(todayInputDate(new Date(2026, 7, 27))).toBe('2026-08-27')
  })
})
