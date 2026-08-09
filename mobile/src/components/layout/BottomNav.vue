<template>
  <nav class="bottom-nav" :aria-label="t('nav.aria')">
    <RouterLink
      v-for="item in regularItems"
      :key="item.name"
      :to="{ name: item.name }"
      class="nav-item"
      :class="{ active: isActive(item.name) }"
    >
      <i :class="item.icon" aria-hidden="true" />
      <span>{{ t(item.labelKey) }}</span>
    </RouterLink>

    <RouterLink
      :to="{ name: 'newMatch' }"
      class="nav-item nav-item--primary"
      :class="{ active: isActive('newMatch') }"
      :aria-label="t('nav.newMatch')"
    >
      <span class="primary-icon" aria-hidden="true">
        <i class="pi pi-plus" />
      </span>
      <span class="primary-label">{{ t('nav.newMatch') }}</span>
    </RouterLink>
  </nav>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

const { t } = useI18n()
const route = useRoute()

const regularItems = [
  { name: 'home', icon: 'pi pi-home', labelKey: 'nav.home' },
  { name: 'matchHistory', icon: 'pi pi-history', labelKey: 'nav.history' },
  { name: 'myStats', icon: 'pi pi-chart-line', labelKey: 'nav.stats' },
]

function isActive(name: string): boolean {
  return route.name === name
}
</script>

<style scoped>
.bottom-nav {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  min-height: calc(var(--app-nav-h) + env(safe-area-inset-bottom, 0px));
  padding: var(--app-space-xs) var(--app-space-sm) calc(var(--app-space-xs) + env(safe-area-inset-bottom, 0px));
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  align-items: stretch;
  gap: var(--app-space-xs);
  background: rgba(255, 255, 255, 0.98);
  border-top: 1px solid var(--app-border);
  backdrop-filter: blur(10px);
}

.nav-item {
  display: grid;
  justify-items: center;
  align-content: center;
  gap: 0.2rem;
  padding: 0.375rem 0.2rem;
  min-height: var(--app-touch-min);
  text-decoration: none;
  color: var(--app-text-subtle);
  font-size: 0.625rem;
  font-weight: 600;
  line-height: 1.1;
  text-align: center;
  border-radius: var(--app-radius-sm);
  transition: color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.nav-item i {
  font-size: 1.125rem;
}

.nav-item.active {
  color: var(--app-primary);
  background: var(--app-primary-soft);
}

.nav-item--primary {
  color: #fff;
  background: linear-gradient(160deg, var(--app-primary), var(--app-primary-dark));
  box-shadow: var(--app-shadow-sm);
  gap: 0.15rem;
}

.nav-item--primary.active {
  color: #fff;
  background: linear-gradient(160deg, var(--app-primary-dark), #124536);
  box-shadow: var(--app-shadow-md);
}

.primary-icon {
  width: 1.75rem;
  height: 1.75rem;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
}

.primary-icon i {
  font-size: 1rem;
  font-weight: 700;
}

.primary-label {
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.nav-item--primary:active {
  transform: scale(0.98);
}
</style>
