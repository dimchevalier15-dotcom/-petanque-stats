<template>
  <div class="jump-shot-stats-panel">
    <StatsDateRangeFilter
      v-if="showDateFilter"
      v-model:date-from="dateFrom"
      v-model:date-to="dateTo"
      v-model:date-filter-enabled="dateFilterEnabled"
      :max-date="maxDate"
      show-all-button
      @change="onDateRangeChange"
    />

    <div v-if="loading && !stats" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <div v-else-if="loadError" class="empty-state">
      <i class="pi pi-exclamation-circle empty-icon" aria-hidden="true" />
      <p class="empty-title">{{ t('jumpShot.stats.empty.loadErrorTitle') }}</p>
      <Button :label="t('jumpShot.stats.empty.retry')" @click="load" />
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
          v-if="showEmptyStartAction && stats.status === 'no_sessions'"
          :label="t('jumpShot.stats.empty.startSession')"
          @click="emit('start-session')"
        />
      </div>

      <template v-else>
        <div :class="{ dimmed: refreshing }" class="panels-grouper">
          <div class="kpi-grid">
            <div class="kpi-card app-card">
              <span class="kpi-label">{{ t('jumpShot.stats.kpi.sessions') }}</span>
              <span class="kpi-value">{{ stats.summary.sessionsCount }}</span>
            </div>
            <div class="kpi-card best app-card">
              <span class="kpi-label">{{ t('jumpShot.stats.kpi.fewestSeries') }}</span>
              <span class="kpi-value">{{ stats.summary.fewestSeriesToWin ?? '—' }}</span>
            </div>
            <div class="kpi-card avg app-card">
              <span class="kpi-label">{{ t('jumpShot.stats.kpi.avgSeries') }}</span>
              <span class="kpi-value">{{ stats.summary.averageSeriesPerSession ?? '—' }}</span>
            </div>
          </div>

          <div v-if="stats.summary.overallSuccessRate !== null" class="hero-card app-card">
            <span class="hero-label">{{ t('jumpShot.stats.hero.successRate') }}</span>
            <Tag
              class="hero-score"
              :value="`${stats.summary.overallSuccessRate}%`"
              :severity="successRateSeverity(stats.summary.overallSuccessRate)"
            />
            <span class="hero-meta">
              {{ t('jumpShot.stats.hero.throwsTracked', { n: stats.summary.totalThrows }) }}
            </span>
          </div>

          <section v-if="strongestDistance && weakestDistance" class="insight-grid">
            <article class="insight-card strength app-card">
              <span class="insight-badge">{{ t('jumpShot.stats.insights.strength') }}</span>
              <strong>{{ t('jumpShot.stats.distanceMeters', { n: strongestDistance.distance }) }}</strong>
              <Tag
                :value="`${strongestDistance.successRate}%`"
                :severity="successRateSeverity(strongestDistance.successRate)"
              />
            </article>
            <article class="insight-card weakness app-card">
              <span class="insight-badge">{{ t('jumpShot.stats.insights.toWork') }}</span>
              <strong>{{ t('jumpShot.stats.distanceMeters', { n: weakestDistance.distance }) }}</strong>
              <Tag
                :value="`${weakestDistance.successRate}%`"
                :severity="successRateSeverity(weakestDistance.successRate)"
              />
            </article>
          </section>

          <section v-if="showEvolution && evolutionChart" class="panel app-card">
            <h3>{{ t('jumpShot.stats.sections.evolution') }}</h3>
            <p class="panel-hint">{{ t('jumpShot.stats.evolution.hint') }}</p>
            <div class="chart-box chart-line">
              <Chart type="line" :data="evolutionChart.data" :options="evolutionChart.options" />
            </div>
          </section>

          <section v-if="distanceChart" class="panel app-card">
            <h3>{{ t('jumpShot.stats.sections.distances') }}</h3>
            <p class="panel-hint">{{ t('jumpShot.stats.distances.hint') }}</p>
            <div class="chart-box chart-bar">
              <Chart type="bar" :data="distanceChart.data" :options="distanceChart.options" />
            </div>
          </section>

          <section v-if="resultChart" class="panel app-card">
            <h3>{{ t('jumpShot.stats.sections.results') }}</h3>
            <p class="panel-hint">{{ t('jumpShot.stats.results.hint') }}</p>
            <div class="chart-box chart-doughnut">
              <Chart type="doughnut" :data="resultChart.data" :options="resultChart.options" />
            </div>
            <ul class="result-breakdown">
              <li v-for="row in stats.byResult" :key="row.result">
                <span>{{ t(`jumpShot.results.${row.result}`) }}</span>
                <span class="result-pct">
                  {{ row.count }}
                  <small>({{ resultPercent(row.count) }}%)</small>
                </span>
              </li>
            </ul>
          </section>

          <section v-if="stats.summary.specialSeriesCount > 0" class="panel app-card notice">
            <p class="notice-title">{{ t('jumpShot.stats.special.title') }}</p>
            <p class="panel-hint">
              {{ t('jumpShot.stats.special.hint', { n: stats.summary.specialSeriesCount }) }}
            </p>
          </section>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import StatsDateRangeFilter from '../stats/StatsDateRangeFilter.vue'
import { successRateSeverity, useJumpShotStatsCharts } from '../../composables/useJumpShotStatsCharts'
import { useStatsDateRange, type StatsDateRangeParams } from '../../composables/useStatsDateRange'
import type { JumpShotStats } from '../../models/JumpShot'

const props = withDefaults(
  defineProps<{
    fetchStats: (range: StatsDateRangeParams) => Promise<JumpShotStats>
    showEmptyStartAction?: boolean
  }>(),
  {
    showEmptyStartAction: false,
  },
)

const emit = defineEmits<{
  'start-session': []
}>()

const { t } = useI18n()

const loading = ref(true)
const refreshing = ref(false)
const loadError = ref(false)
const stats = ref<JumpShotStats | null>(null)
const { dateFrom, dateTo, maxDate, dateFilterEnabled, normalizeRange, queryParams } = useStatsDateRange()

const showDateFilter = computed(() => stats.value !== null)

const emptyTitleKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'jumpShot.stats.empty.noDataInPeriodTitle'
    : 'jumpShot.stats.empty.noSessionsTitle',
)

const emptyHintKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'jumpShot.stats.empty.noDataInPeriodHint'
    : 'jumpShot.stats.empty.noSessionsHint',
)

const {
  showEvolution,
  evolutionChart,
  distanceChart,
  resultChart,
  strongestDistance,
  weakestDistance,
  totalResults,
} = useJumpShotStatsCharts(stats, t)

function resultPercent(count: number): string {
  if (totalResults.value === 0) return '0'
  return ((count / totalResults.value) * 100).toFixed(0)
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
    stats.value = await props.fetchStats(queryParams())
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
    void load({ refresh: true })
  }
}

onMounted(() => {
  void load()
})

defineExpose({ reload: load })
</script>

<style scoped>
.panels-grouper {
  display: flex;
  flex-direction: column;
  gap: 8px;
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
  font-size: 0.9rem;
  max-width: 280px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.kpi-card {
  padding: var(--app-space-md) var(--app-space-sm);
  display: grid;
  gap: 0.125rem;
  text-align: center;
  border-radius: 8px;
}

.kpi-card.best {
  background: #fffbeb;
  border-color: #fde68a;
}

.kpi-card.avg {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.kpi-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  opacity: 0.7;
  letter-spacing: 0.02em;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1.1;
}

.hero-card {
  border: 1px solid #c7e8dc;
  background: linear-gradient(135deg, #f0fdf9 0%, #ecfdf5 100%);
  border-radius: 12px;
  padding: 1rem;
  display: grid;
  gap: 0.375rem;
  justify-items: center;
  text-align: center;
}

.hero-label {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.7;
}

.hero-score :deep(.p-tag) {
  font-size: 1.75rem;
  font-weight: 800;
  padding: 0.35rem 0.75rem;
}

.hero-meta {
  font-size: 0.85rem;
  opacity: 0.7;
}

.insight-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--app-space-sm);
}

.insight-card {
  padding: var(--app-space-md);
  display: grid;
  gap: 0.375rem;
  align-content: start;
}

.insight-card.strength {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.insight-card.weakness {
  background: #fff7ed;
  border-color: #fed7aa;
}

.insight-badge {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.75;
}

.insight-card strong {
  font-size: 0.875rem;
  line-height: 1.3;
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h3 {
  margin: 0;
  font-size: 0.9375rem;
}

.panel-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.chart-box {
  width: 100%;
}

.chart-line {
  height: 200px;
}

.chart-bar {
  height: 180px;
}

.chart-doughnut {
  height: 200px;
  max-width: 240px;
  margin: 0 auto;
}

.result-breakdown {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.375rem;
}

.result-breakdown li {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.result-pct {
  font-weight: 700;
}

.result-pct small {
  font-weight: 500;
  opacity: 0.7;
}

.notice {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.notice-title {
  margin: 0;
  font-weight: 700;
}
</style>
