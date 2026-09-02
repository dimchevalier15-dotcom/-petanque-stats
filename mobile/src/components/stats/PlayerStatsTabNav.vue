<template>
  <nav class="summary-segments" role="tablist" :aria-label="t('stats.tabs.label')">
    <div class="summary-segments-track">
      <button
        type="button"
        role="tab"
        class="summary-segment"
        :class="{ 'summary-segment--active': modelValue === 'overview' }"
        :aria-selected="modelValue === 'overview'"
        @click="$emit('update:modelValue', 'overview')"
      >
        <i class="pi pi-list" aria-hidden="true" />
        <span>{{ t('stats.tabs.overview') }}</span>
      </button>
      <button
        type="button"
        role="tab"
        class="summary-segment"
        :class="{ 'summary-segment--active': modelValue === 'tactics' }"
        :aria-selected="modelValue === 'tactics'"
        @click="$emit('update:modelValue', 'tactics')"
      >
        <i class="pi pi-chart-line" aria-hidden="true" />
        <span>{{ t('stats.tabs.tactics') }}</span>
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

export type PlayerStatsTab = 'overview' | 'tactics'

defineProps<{
  modelValue: PlayerStatsTab
}>()

defineEmits<{
  'update:modelValue': [value: PlayerStatsTab]
}>()

const { t } = useI18n()
</script>

<style scoped>
.summary-segments {
  margin: 0;
}

.summary-segments-track {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.25rem;
  padding: 0.3125rem;
  background: var(--app-surface-muted);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-lg);
  box-shadow: inset 0 1px 2px rgba(28, 36, 48, 0.04);
}

.summary-segment {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4375rem;
  min-height: var(--app-touch-min);
  padding: 0.625rem 0.75rem;
  border: none;
  border-radius: calc(var(--app-radius-lg) - 0.3125rem);
  background: transparent;
  color: var(--app-text-muted);
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.15s ease;
}

.summary-segment .pi {
  font-size: 0.9375rem;
  opacity: 0.85;
}

.summary-segment--active {
  background: var(--app-surface);
  color: var(--app-primary);
  box-shadow: var(--app-shadow-sm);
}

.summary-segment--active .pi {
  opacity: 1;
}

.summary-segment:active:not(.summary-segment--active) {
  transform: scale(0.98);
}

.summary-segment:focus-visible {
  outline: 2px solid var(--app-primary);
  outline-offset: 2px;
}
</style>
