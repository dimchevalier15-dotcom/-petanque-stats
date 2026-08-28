import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

export const APP_LOCALES = [
  { code: 'fr', short: 'FR', label: 'Français' },
  { code: 'en', short: 'EN', label: 'English' },
  { code: 'sk', short: 'SK', label: 'Slovenčina' },
] as const

export type AppLocaleCode = (typeof APP_LOCALES)[number]['code']

export function useLocaleSwitcher() {
  const { locale } = useI18n()

  const currentLanguage = computed(() => {
    const match = APP_LOCALES.find((item) => item.code === locale.value)
    return match?.short ?? locale.value.toUpperCase()
  })

  function changeLanguage(lang: string): void {
    locale.value = lang
    try {
      localStorage.setItem('locale', lang)
    } catch {
      // Private mode or unavailable storage.
    }
  }

  const languageItems = APP_LOCALES.map((item) => ({
    label: item.label,
    command: () => changeLanguage(item.code),
  }))

  return {
    locale,
    currentLanguage,
    languageItems,
    locales: APP_LOCALES,
    changeLanguage,
  }
}
