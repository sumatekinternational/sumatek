import { createI18n } from 'vue-i18n'
import ar from './locales/ar.json'
import en from './locales/en.json'

// Arabic is the default locale (§7); persisted across reloads.
const saved = localStorage.getItem('locale') || 'ar'

export const i18n = createI18n({
  legacy: false,
  locale: saved,
  fallbackLocale: 'en',
  messages: { ar, en },
})

export function applyDirection(locale) {
  const dir = locale === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.setAttribute('dir', dir)
  document.documentElement.setAttribute('lang', locale)
}

export function setLocale(locale) {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  applyDirection(locale)
}

applyDirection(saved)
