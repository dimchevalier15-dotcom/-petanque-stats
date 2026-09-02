// @vitest-environment node
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import fr from '../i18n/locales/fr.json'
import { formatPluralMessage } from './i18nPlural'

describe('formatPluralMessage', () => {
  const i18n = createI18n({ legacy: false, locale: 'fr', messages: { fr } })
  const { t } = i18n.global

  it('resolves object plural messages via category sub-keys', () => {
    const wonPart = formatPluralMessage(
      t,
      'fr',
      'summary.insights.endSequenceDominance.endsWonPart',
      1,
      { won: 1 },
    )
    expect(wonPart).toBe('dont 1 gagnée')

    const legend = formatPluralMessage(
      t,
      'fr',
      'summary.insights.endSequenceDominance.endsLegend',
      3,
      { count: 3, wonPart },
    )
    expect(legend).toBe('3 mènes dominées (dont 1 gagnée)')
  })

  it('uses zero sub-key when present', () => {
    const wonPart = formatPluralMessage(t, 'fr', 'summary.insights.point.wonPart', 0, { won: 0 })
    expect(wonPart).toBe('aucune emportée')
  })
})
