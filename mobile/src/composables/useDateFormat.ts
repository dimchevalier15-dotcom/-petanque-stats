import { useI18n } from 'vue-i18n'

/**
 * Formats ISO date strings using the app's current locale.
 *
 * `vue-i18n`'s own `d()` helper requires `datetimeFormats` to be configured,
 * which this project does not do, so it silently renders nothing. This
 * composable formats dates directly via `Intl.DateTimeFormat` instead.
 */
export function useDateFormat() {
  const { locale } = useI18n()

  function formatShortDate(iso: string): string {
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) {
      return ''
    }
    return new Intl.DateTimeFormat(locale.value, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(date)
  }

  return { formatShortDate }
}
