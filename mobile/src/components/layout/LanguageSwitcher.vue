<template>
  <div class="lang-switcher" role="group" :aria-label="t('home.language')">
    <button
      v-for="item in locales"
      :key="item.code"
      type="button"
      class="lang-btn"
      :class="{ 'lang-btn--active': locale === item.code }"
      :aria-pressed="locale === item.code"
      @click="changeLanguage(item.code)"
    >
      {{ item.short }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useLocaleSwitcher } from '../../composables/useLocaleSwitcher'

const { t } = useI18n()
const { locale, locales, changeLanguage } = useLocaleSwitcher()
</script>

<style scoped>
.lang-switcher {
  display: inline-flex;
  gap: 0.25rem;
  padding: 0.2rem;
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: var(--app-surface);
  box-shadow: var(--app-shadow-sm);
}

.lang-btn {
  margin: 0;
  min-width: 2.25rem;
  min-height: 1.85rem;
  padding: 0 0.5rem;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: var(--app-text-muted);
  font: inherit;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  cursor: pointer;
}

.lang-btn--active {
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
}
</style>
