/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import { sanitizeTournamentDayPdfFileName } from './exportTournamentDayPdf'

describe('sanitizeTournamentDayPdfFileName', () => {
  it('strips unsafe characters', () => {
    expect(sanitizeTournamentDayPdfFileName('Coupe Club / Journée 2')).toBe(
      'Coupe-Club-Journée-2',
    )
  })

  it('falls back when empty', () => {
    expect(sanitizeTournamentDayPdfFileName('   /// ')).toBe('journee')
  })
})
