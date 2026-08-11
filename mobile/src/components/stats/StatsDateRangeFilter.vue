<template>
  <section class="date-range-filter app-card" :aria-label="t('dateRange.label')">
    <div class="date-range-head">
      <i class="pi pi-calendar" aria-hidden="true" />
      <span class="date-range-title">{{ t('dateRange.label') }}</span>
    </div>
    <div class="date-range-fields">
      <label class="date-field">
        <span class="date-field-label">{{ t('dateRange.from') }}</span>
        <input
          v-model="dateFrom"
          type="date"
          class="date-input"
          :max="dateTo"
          @change="emitChange"
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
          @change="emitChange"
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

defineProps<{
  maxDate: string
}>()

const emit = defineEmits<{
  change: []
}>()

function emitChange(): void {
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
  gap: 0.5rem;
  color: var(--app-text-muted);
  font-size: 0.8125rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
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
