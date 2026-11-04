import { useI18n } from 'vue-i18n'
import { RINGS_TARGET_DISTANCE_M, RINGS_TARGET_POINTS, type RingsTarget } from '../models/Rings'
import { formatPluralMessage } from '../utils/i18nPlural'

export function useRingsTargetLabels() {
  const { t, locale } = useI18n()

  function distanceLabel(target: RingsTarget): string {
    const n = new Intl.NumberFormat(locale.value).format(RINGS_TARGET_DISTANCE_M[target])
    return t('rings.distanceMeters', { n })
  }

  function pointsText(n: number): string {
    return formatPluralMessage(t, locale.value, 'rings.points', n, { n })
  }

  function pointsLabel(target: RingsTarget): string {
    return pointsText(RINGS_TARGET_POINTS[target])
  }

  return { distanceLabel, pointsLabel, pointsText }
}
