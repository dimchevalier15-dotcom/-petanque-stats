<template>
  <nav class="bottom-nav" :style="navStyle" :aria-label="t('nav.aria')">
    <RouterLink
      v-for="item in navItems"
      :key="item.name"
      :to="{ name: item.name }"
      class="nav-item"
      :class="{ active: isActive(item.name) }"
    >
      <i :class="item.icon" aria-hidden="true" />
      <span>{{ t(item.labelKey) }}</span>
    </RouterLink>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useIsCoach } from '../../composables/useIsCoach'

const { t } = useI18n()
const route = useRoute()
const isCoach = useIsCoach()

const navItems = computed(() => {
  const items = [
    { name: 'home', icon: 'pi pi-home', labelKey: 'nav.home' },
    { name: 'matchHistory', icon: 'pi pi-history', labelKey: 'nav.history' },
    { name: 'myStats', icon: 'pi pi-chart-line', labelKey: 'nav.stats' },
  ]

  if (isCoach.value) {
    items.push({ name: 'coachPlayers', icon: 'pi pi-users', labelKey: 'nav.coach' })
  }

  return items
})

const navStyle = computed(() => ({
  gridTemplateColumns: `repeat(${navItems.value.length}, 1fr)`,
}))

function isActive(name: string): boolean {
  if (name === 'coachPlayers') {
    return typeof route.name === 'string' && route.name.startsWith('coach')
  }

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

.nav-item:active {
  transform: scale(0.98);
}
</style>
