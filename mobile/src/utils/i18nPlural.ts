import type { ComposerTranslation } from 'vue-i18n'

/**
 * vue-i18n does not resolve object-shaped plural messages via `t(key, count)`.
 * Pick the locale category sub-key explicitly (e.g. `key.one`, `key.other`).
 */
export function formatPluralMessage(
  t: ComposerTranslation,
  locale: string,
  key: string,
  count: number,
  params: Record<string, unknown> = {},
): string {
  if (count === 0) {
    const zeroKey = `${key}.zero`
    const zeroMessage = t(zeroKey, params)
    if (zeroMessage !== zeroKey) {
      return zeroMessage
    }
  }

  const category = new Intl.PluralRules(locale).select(count)
  const categoryKey = `${key}.${category}`
  const categoryMessage = t(categoryKey, params)
  if (categoryMessage !== categoryKey) {
    return categoryMessage
  }

  const fallbackKey = `${key}.other`
  return t(fallbackKey, params)
}
