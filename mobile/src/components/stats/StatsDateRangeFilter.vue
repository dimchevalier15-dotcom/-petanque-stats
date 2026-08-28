<template>
  <section class="date-range-filter app-card" :aria-label="t('dateRange.label')">
    <div class="date-range-head">
      <div class="date-range-head-main">
        <i class="pi pi-calendar" aria-hidden="true" />
        <span class="date-range-title">{{ t('dateRange.label') }}</span>
      </div>
      <button
        v-if="showAllButton"
        type="button"
        class="show-all-btn"
        :class="{ active: !dateFilterEnabled }"
        @click="onShowAll"
      >
        {{ t('dateRange.showAll') }}
      </button>
    </div>
    <div class="date-range-fields" :class="{ 'date-range-fields--inactive': !dateFilterEnabled }">
      <label class="date-field">
        <span class="date-field-label">{{ t('dateRange.from') }}</span>
        <input
          v-model="dateFrom"
          type="date"
          class="date-input"
          :max="dateTo"
          :disabled="!dateFilterEnabled"
          @change="onDateChange"
        />
      </label>
      <span class="date-range-separator" aria-hidden="true">→</span>
      <label class="date-field">
        <span class="date-field-label">{{ t('dateRange.to') }}</span>
        <input
          v-model="dateTo"
          type="date"
          class="date-input"
          :min="dateFrom"
          :max="maxDate"
          :disabled="!dateFilterEnabled"
          @change="onDateChange"
        />
      </label>
    </div>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const dateFrom = defineModel<string>('dateFrom', { required: true })
const dateTo = defineModel<string>('dateTo', { required: true })
const dateFilterEnabled = defineModel<boolean>('dateFilterEnabled', { default: true })

withDefaults(
  defineProps<{
    maxDate: string
    showAllButton?: boolean
  }>(),
  {
    showAllButton: false,
  },
)

const emit = defineEmits<{
  change: []
}>()

function onDateChange(): void {
  dateFilterEnabled.value = true
  emit('change')
}

function onShowAll(): void {
  dateFilterEnabled.value = !dateFilterEnabled.value
  emit('change')
}
</script>

<style scoped>
.date-range-filter {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.date-range-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.date-range-head-main {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--app-text-muted);
  font-size: 0.8125rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.show-all-btn {
  border: 1px solid var(--app-border);
  border-radius: 999px;
  background: #fff;
  padding: 0.3125rem 0.625rem;
  font: inherit;
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--app-text-muted);
  cursor: pointer;
  white-space: nowrap;
}

.show-all-btn.active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary-dark);
}

.date-range-fields--inactive {
  opacity: 0.55;
}

.date-range-title {
  color: var(--app-text, #111827);
}

.date-range-fields {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 0.5rem;
  align-items: end;
}

.date-field {
  display: grid;
  gap: 0.25rem;
  min-width: 0;
}

.date-field-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.date-input {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.5rem 0.625rem;
  font: inherit;
  font-size: 0.875rem;
  background: #fff;
  color: inherit;
  min-height: 2.5rem;
}

.date-input:disabled {
  cursor: not-allowed;
  background: var(--app-surface-muted, #f4f4f5);
}

.date-input:focus {
  outline: 2px solid rgba(99, 102, 241, 0.35);
  border-color: #a5b4fc;
}

.date-range-separator {
  padding-bottom: 0.625rem;
  color: var(--app-text-muted);
  font-weight: 700;
}
</style>
