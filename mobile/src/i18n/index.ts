import { createI18n } from 'vue-i18n'
import fr from './locales/fr.json'
import en from './locales/en.json'
import sk from './locales/sk.json'

const SUPPORTED_LOCALES = ['fr', 'en', 'sk'] as const

function resolveInitialLocale(): string {
  try {
    const saved = localStorage.getItem('locale')
    if (saved && SUPPORTED_LOCALES.includes(saved as (typeof SUPPORTED_LOCALES)[number])) {
      return saved
    }
  } catch {
    // Private mode or unavailable storage: fall back to default.
  }
  return 'fr'
}

export const i18n = createI18n({
  legacy: false,
  locale: resolveInitialLocale(),
  fallbackLocale: 'en',
  messages: { fr, en, sk },
})
