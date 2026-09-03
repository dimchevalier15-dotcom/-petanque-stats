<template>
  <StatsCollapsibleFilters :active-count="activeFilterCount">
    <div class="filter-group">
      <span class="filter-group-label">{{ t('stats.filters.nature') }}</span>
      <div class="nature-filter">
        <button
          v-for="opt in natureFilterOptions"
          :key="opt.value"
          type="button"
          class="filter-btn"
          :class="{ active: natureFilter === opt.value }"
          @click="onNatureChange(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <div v-if="natureFilter === 'competition'" class="filter-group">
      <span class="filter-group-label">{{ t('stats.filters.competition') }}</span>
      <Dropdown
        v-model="competitionFilter"
        :options="competitionFilterOptions"
        option-label="label"
        option-value="value"
        :placeholder="t('stats.filters.all')"
        show-clear
        fluid
        @change="emit('change')"
      />
    </div>

    <div class="filter-group">
      <span class="filter-group-label">{{ t('stats.filters.format') }}</span>
      <div class="format-filter">
        <button
          v-for="opt in formatFilterOptions"
          :key="opt.value"
          type="button"
          class="filter-btn"
          :class="{ active: formatFilter === opt.value }"
          @click="onFormatChange(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>
    <StatsDateRangeFilter
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      v-model:date-filter-enabled="dateFilterEnabled"
      :max-date="maxDate"
      embedded
      show-all-button
      @change="onDateRangeChange"
    />

    <label class="refused-toggle">
      <input
        v-model="includeRefused"
        type="checkbox"
        class="refused-checkbox"
        @change="emit('change')"
      />
      <span>{{ t('history.filters.showRefused') }}</span>
    </label>
  </StatsCollapsibleFilters>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Dropdown from 'primevue/dropdown'
import StatsCollapsibleFilters from '../stats/StatsCollapsibleFilters.vue'
import StatsDateRangeFilter from '../stats/StatsDateRangeFilter.vue'
import type { MatchNature } from '../../models/MatchContext'
import type { MatchType } from '../../models/Match'

defineProps<{
  activeFilterCount: number
  maxDate: string
  natureFilterOptions: Array<{ value: MatchNature | 'all'; label: string }>
  competitionFilterOptions: Array<{ value: number; label: string }>
  formatFilterOptions: Array<{ value: MatchType | 'all'; label: string }>
}>()

const natureFilter = defineModel<MatchNature | 'all'>('natureFilter', { required: true })
const competitionFilter = defineModel<number | null>('competitionFilter', { required: true })
const formatFilter = defineModel<MatchType | 'all'>('formatFilter', { required: true })
const dateFrom = defineModel<string>('dateFrom', { required: true })
const dateTo = defineModel<string>('dateTo', { required: true })
const dateFilterEnabled = defineModel<boolean>('dateFilterEnabled', { required: true })
const includeRefused = defineModel<boolean>('includeRefused', { required: true })

const emit = defineEmits<{
  change: []
}>()

const { t } = useI18n()

function onNatureChange(value: MatchNature | 'all'): void {
  natureFilter.value = value
  if (value !== 'competition') {
    competitionFilter.value = null
  }
  emit('change')
}

function onFormatChange(value: MatchType | 'all'): void {
  formatFilter.value = value
  emit('change')
}

function onDateRangeChange(): void {
  emit('change')
}
</script>

<style scoped>
.filter-group {
  display: grid;
  gap: var(--app-space-xs);
}

.filter-group-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.nature-filter,
.format-filter {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--app-space-xs);
}

@media (min-width: 420px) {
  .nature-filter,
  .format-filter {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.filter-btn {
  min-width: 0;
  min-height: 2.25rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: #fff;
  font: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
  cursor: pointer;
  line-height: 1.2;
  white-space: normal;
}

.filter-btn.active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary);
}

.refused-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: var(--app-text-muted);
  cursor: pointer;
  user-select: none;
}

.refused-checkbox {
  width: 0.875rem;
  height: 0.875rem;
  margin: 0;
  accent-color: var(--app-primary, #6366f1);
}
</style>
