<template>
  <AppPage>
    <PageHeader
      :title="t('training.stats.title')"
      :subtitle="t('training.stats.subtitle')"
      :back-to="{ name: 'trainingHome' }"
    />

    <div class="type-filter">
      <button
        v-for="opt in typeFilterOptions"
        :key="opt.value"
        type="button"
        class="filter-btn"
        :class="{ active: typeFilter === opt.value }"
        @click="setTypeFilter(opt.value)"
      >
        {{ opt.label }}
      </button>
    </div>

    <StatsDateRangeFilter
      v-if="showDateFilter"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      :max-date="maxDate"
      @change="onDateRangeChange"
    />

    <div v-if="loading && !stats" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <div v-else-if="loadError" class="empty-state">
      <i class="pi pi-exclamation-circle empty-icon" aria-hidden="true" />
      <p class="empty-title">{{ t('training.stats.empty.loadErrorTitle') }}</p>
      <Button :label="t('training.stats.empty.retry')" @click="load" />
    </div>

    <template v-else-if="stats">
      <div v-if="refreshing" class="refreshing">
        <ProgressSpinner stroke-width="4" />
      </div>

      <div
        v-if="stats.status === 'no_sessions' || stats.status === 'no_data_in_period'"
        class="empty-state"
        :class="{ dimmed: refreshing }"
      >
        <i class="pi pi-chart-bar empty-icon" aria-hidden="true" />
        <p class="empty-title">{{ t(emptyTitleKey) }}</p>
        <p class="empty-hint">{{ t(emptyHintKey) }}</p>
        <Button
          v-if="stats.status === 'no_sessions'"
          :label="t('training.stats.empty.startSession')"
          @click="goHome"
        />
      </div>

      <template v-else>
        <div :class="{ dimmed: refreshing }">
          <div class="kpi-grid">
            <div class="kpi-card app-card">
              <span class="kpi-label">{{ t('training.stats.kpi.sessions') }}</span>
              <span class="kpi-value">{{ stats.summary.sessionsCount }}</span>
            </div>
            <div class="kpi-card app-card">
              <span class="kpi-label">{{ t('training.stats.kpi.balls') }}</span>
              <span class="kpi-value">{{ stats.summary.totalBalls }}</span>
            </div>
            <div class="kpi-card best app-card">
              <span class="kpi-label">{{ t('training.stats.kpi.success') }}</span>
              <span class="kpi-value">{{ stats.summary.successRate ?? '—' }}%</span>
            </div>
            <div class="kpi-card avg app-card">
              <span class="kpi-label">{{ t('training.stats.kpi.best') }}</span>
              <span class="kpi-value">{{ stats.summary.bestScore ?? '—' }}</span>
            </div>
          </div>

          <div v-if="stats.summary.averageScore !== null" class="hero-card app-card">
            <span class="hero-label">{{ t('training.stats.hero.averageScore') }}</span>
            <Tag class="hero-score" :value="String(stats.summary.averageScore)" severity="info" />
          </div>

          <section v-if="evolutionChart" class="panel app-card">
            <h3>{{ t('training.stats.sections.evolution') }}</h3>
            <p class="panel-hint">{{ t('training.stats.evolution.hint') }}</p>
            <div class="chart-box chart-line">
              <Chart type="line" :data="evolutionChart.data" :options="evolutionChart.options" />
            </div>
          </section>

          <section v-if="typeChart" class="panel app-card">
            <h3>{{ t('training.stats.sections.byType') }}</h3>
            <p class="panel-hint">{{ t('training.stats.byType.hint') }}</p>
            <div class="chart-box chart-bar">
              <Chart type="bar" :data="typeChart.data" :options="typeChart.options" />
            </div>
          </section>

          <section v-if="distanceChart" class="panel app-card">
            <h3>{{ t('training.stats.sections.distances') }}</h3>
            <p class="panel-hint">{{ t('training.stats.distances.hint') }}</p>
            <div class="chart-box chart-bar">
              <Chart type="bar" :data="distanceChart.data" :options="distanceChart.options" />
            </div>
          </section>
        </div>
      </template>
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import StatsDateRangeFilter from '../components/stats/StatsDateRangeFilter.vue'
import { useTrainingStatsCharts } from '../composables/useTrainingStatsCharts'
import { useStatsDateRange } from '../composables/useStatsDateRange'
import type { TrainingStats, TrainingType } from '../models/Training'
import { trainingSessionsService } from '../services/trainingSessions'

const { t } = useI18n()
const router = useRouter()

const loading = ref(true)
const refreshing = ref(false)
const loadError = ref(false)
const stats = ref<TrainingStats | null>(null)
const typeFilter = ref<TrainingType | 'all'>('all')
const { dateFrom, dateTo, maxDate, normalizeRange, queryParams } = useStatsDateRange()

const showDateFilter = computed(() => stats.value !== null)

const typeFilterOptions = computed(() => [
  { value: 'all' as const, label: t('training.stats.filters.all') },
  { value: 'point' as const, label: t('training.types.point') },
  { value: 'tir' as const, label: t('training.types.tir') },
])

const emptyTitleKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'training.stats.empty.noDataInPeriodTitle'
    : 'training.stats.empty.noSessionsTitle',
)

const emptyHintKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'training.stats.empty.noDataInPeriodHint'
    : 'training.stats.empty.noSessionsHint',
)

const { evolutionChart, typeChart, distanceChart } = useTrainingStatsCharts(stats, t, typeFilter)

function setTypeFilter(value: TrainingType | 'all'): void {
  typeFilter.value = value
  if (stats.value) {
    load({ refresh: true })
  }
}

function goHome(): void {
  router.push({ name: 'trainingHome' })
}

async function load(options: { refresh?: boolean } = {}): Promise<void> {
  const isRefresh = options.refresh === true
  if (isRefresh) {
    refreshing.value = true
  } else {
    loading.value = true
  }
  loadError.value = false
  try {
    stats.value = await trainingSessionsService.getStats(queryParams(), typeFilter.value)
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

function onDateRangeChange(): void {
  normalizeRange()
  if (stats.value) {
    load({ refresh: true })
  }
}

onMounted(load)
</script>

<style scoped>
.type-filter {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--app-space-xs);
  margin-bottom: var(--app-space-sm);
}

.filter-btn {
  min-height: 2.25rem;
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-sm);
  background: #fff;
  font: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
  cursor: pointer;
}

.filter-btn.active {
  border-color: var(--app-primary);
  background: var(--app-primary-soft);
  color: var(--app-primary);
}

.loading {
  display: grid;
  place-items: center;
  min-height: 12rem;
}

.refreshing {
  display: grid;
  place-items: center;
  min-height: 2rem;
}

.dimmed {
  opacity: 0.55;
  pointer-events: none;
}

.empty-state {
  padding: var(--app-space-xl) var(--app-space-lg);
  text-align: center;
  display: grid;
  gap: var(--app-space-sm);
  justify-items: center;
}

.empty-icon {
  font-size: 2rem;
  opacity: 0.45;
}

.empty-title {
  margin: 0;
  font-weight: 700;
}

.empty-hint {
  margin: 0;
  opacity: 0.75;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--app-space-sm);
  margin-bottom: var(--app-space-sm);
}

.kpi-card {
  padding: var(--app-space-md);
  display: grid;
  gap: 0.25rem;
}

.kpi-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-subtle);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.1;
}

.hero-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  align-items: center;
  text-align: center;
  margin-bottom: var(--app-space-sm);
}

.hero-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.hero-score {
  font-size: 1.25rem;
  font-weight: 800;
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
  margin-bottom: var(--app-space-sm);
}

.panel h3 {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
}

.panel-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  line-height: 1.4;
}

.chart-box {
  min-height: 12rem;
}

.chart-line {
  min-height: 14rem;
}

.chart-bar {
  min-height: 10rem;
}
</style>
