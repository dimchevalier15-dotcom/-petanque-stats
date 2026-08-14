<template>
  <details class="stats-filters app-card">
    <summary class="stats-filters-summary">
      <i class="pi pi-sliders-h" aria-hidden="true" />
      <span>{{ t('stats.filters.title') }}</span>
      <span v-if="activeCount > 0" class="active-badge">{{ activeCount }}</span>
    </summary>
    <div class="stats-filters-body">
      <slot />
    </div>
  </details>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{
  activeCount: number
}>()

const { t } = useI18n()
</script>

<style scoped>
.stats-filters {
  margin-bottom: var(--app-space-sm);
  padding: 0;
  overflow: hidden;
}

.stats-filters-summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: var(--app-space-md);
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--app-text, #111827);
  user-select: none;
}

.stats-filters-summary::-webkit-details-marker {
  display: none;
}

.stats-filters-summary::after {
  content: '';
  width: 0.4rem;
  height: 0.4rem;
  margin-left: auto;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  opacity: 0.6;
}

.stats-filters[open] .stats-filters-summary::after {
  transform: rotate(-135deg);
}

.active-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: var(--app-primary, #6366f1);
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 700;
}

.stats-filters-body {
  display: grid;
  gap: var(--app-space-sm);
  padding: 0 var(--app-space-md) var(--app-space-md);
  border-top: 1px solid var(--app-border, #e5e7eb);
}
</style>
