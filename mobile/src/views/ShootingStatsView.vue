<template>
  <AppPage>
    <PageHeader
      :title="t('shooting.stats.title')"
      :subtitle="t('shooting.stats.subtitle')"
      :back-to="{ name: 'shootingHome' }"
    />

    <div class="nature-filter">
      <button
        v-for="opt in natureFilterOptions"
        :key="opt.value"
        type="button"
        class="filter-btn"
        :class="{ active: natureFilter === opt.value }"
        @click="setNatureFilter(opt.value)"
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
      <p class="empty-title">{{ t('shooting.stats.empty.loadErrorTitle') }}</p>
      <Button :label="t('shooting.stats.empty.retry')" @click="load" />
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
          :label="t('shooting.stats.empty.startSession')"
          @click="goHome"
        />
      </div>

      <template v-else>
        <div :class="{ dimmed: refreshing }">
        <div class="kpi-grid">
          <div class="kpi-card app-card">
            <span class="kpi-label">{{ t('shooting.stats.kpi.sessions') }}</span>
            <span class="kpi-value">{{ stats.summary.sessionsCount }}</span>
          </div>
          <div class="kpi-card best app-card">
            <span class="kpi-label">{{ t('shooting.stats.kpi.best') }}</span>
            <span class="kpi-value">{{ stats.summary.bestSessionScore ?? '—' }}</span>
          </div>
          <div class="kpi-card avg app-card">
            <span class="kpi-label">{{ t('shooting.stats.kpi.average') }}</span>
            <span class="kpi-value">{{ stats.summary.averageSessionScore ?? '—' }}</span>
          </div>
        </div>

        <div v-if="stats.summary.averageSessionScore !== null" class="hero-card app-card">
          <span class="hero-label">{{ t('shooting.stats.hero.averageSession') }}</span>
          <Tag
            class="hero-score"
            :value="`${stats.summary.averageSessionScore}/100`"
            :severity="sessionScoreSeverity(stats.summary.averageSessionScore)"
          />
          <span class="hero-meta">
            {{ t('shooting.stats.hero.shotsTracked', { n: stats.summary.totalShots }) }}
          </span>
        </div>

        <section v-if="strongestWorkshop && weakestWorkshop" class="insight-grid">
          <article class="insight-card strength app-card">
            <span class="insight-badge">{{ t('shooting.stats.insights.strength') }}</span>
            <strong>{{ workshopLabel(t, strongestWorkshop.workshop) }}</strong>
            <Tag
              :value="formatShotScore(strongestWorkshop.averageScore)"
              :severity="shotScoreSeverity(strongestWorkshop.averageScore)"
            />
          </article>
          <article class="insight-card weakness app-card">
            <span class="insight-badge">{{ t('shooting.stats.insights.toWork') }}</span>
            <strong>{{ workshopLabel(t, weakestWorkshop.workshop) }}</strong>
            <Tag
              :value="formatShotScore(weakestWorkshop.averageScore)"
              :severity="shotScoreSeverity(weakestWorkshop.averageScore)"
            />
          </article>
        </section>

        <section v-if="showEvolution && evolutionChart" class="panel app-card">
          <h3>{{ t('shooting.stats.sections.evolution') }}</h3>
          <p class="panel-hint">{{ t('shooting.stats.evolution.hint') }}</p>
          <div class="chart-box chart-line">
            <Chart type="line" :data="evolutionChart.data" :options="evolutionChart.options" />
          </div>
        </section>

        <section v-if="workshopChart" class="panel app-card">
          <h3>{{ t('shooting.stats.sections.workshops') }}</h3>
          <p class="panel-hint">{{ t('shooting.stats.workshops.hint') }}</p>
          <div class="chart-box chart-bar">
            <Chart type="bar" :data="workshopChart.data" :options="workshopChart.options" />
          </div>
        </section>

        <section v-if="distanceChart" class="panel app-card">
          <h3>{{ t('shooting.stats.sections.distances') }}</h3>
          <p class="panel-hint">{{ t('shooting.stats.distances.hint') }}</p>
          <div v-if="strongestDistance && weakestDistance" class="distance-pills">
            <span class="pill best">
              {{ t('shooting.stats.distances.best', { n: strongestDistance.distance }) }}
            </span>
            <span class="pill weak">
              {{ t('shooting.stats.distances.weakest', { n: weakestDistance.distance }) }}
            </span>
          </div>
          <div class="chart-box chart-bar">
            <Chart type="bar" :data="distanceChart.data" :options="distanceChart.options" />
          </div>
        </section>

        <section v-if="resultChart" class="panel app-card">
          <h3>{{ t('shooting.stats.sections.results') }}</h3>
          <p class="panel-hint">{{ t('shooting.stats.results.hint') }}</p>
          <div class="chart-box chart-bar">
            <Chart type="bar" :data="resultChart.data" :options="resultChart.options" />
          </div>
          <ul class="result-breakdown">
            <li v-for="item in stats.byResult" :key="item.result">
              <span>{{ t(`shooting.results.${item.result}`) }}</span>
              <span class="result-pct">
                {{ resultPercent(item.count) }}%
                <small>({{ item.count }})</small>
              </span>
            </li>
          </ul>
        </section>

        <section v-if="heatmapGrid.length > 0" class="panel app-card heatmap-panel">
          <h3>{{ t('shooting.stats.sections.heatmap') }}</h3>
          <p class="panel-hint">{{ t('shooting.stats.heatmap.hint') }}</p>
          <div class="heatmap">
            <div class="heatmap-corner" />
            <div v-for="distance in distances" :key="distance" class="heatmap-col-label">
              {{ t('shooting.distanceMeters', { n: distance }) }}
            </div>
            <template v-for="workshop in workshops" :key="workshop">
              <div class="heatmap-row-label">{{ workshopLabel(t, workshop) }}</div>
              <div
                v-for="distance in distances"
                :key="`${workshop}-${distance}`"
                class="heatmap-cell"
                :style="{ backgroundColor: heatmapCellColor(cellFor(workshop, distance).averageScore) }"
              >
                <span class="cell-score">{{ formatShotScore(cellFor(workshop, distance).averageScore) }}</span>
                <span class="cell-count">{{ cellFor(workshop, distance).shotCount }}</span>
              </div>
            </template>
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
import {
  formatShotScore,
  heatmapCellColor,
  sessionScoreSeverity,
  shotScoreSeverity,
  useShootingStatsCharts,
  workshopLabel,
} from '../composables/useShootingStatsCharts'
import { useStatsDateRange } from '../composables/useStatsDateRange'
import { SHOOTING_DISTANCES, SHOOTING_WORKSHOPS, type ShootingContextNature, type ShootingStats } from '../models/Shooting'
import { shootingSessionsService } from '../services/shootingSessions'

const { t } = useI18n()
const router = useRouter()

const loading = ref(true)
const refreshing = ref(false)
const loadError = ref(false)
const stats = ref<ShootingStats | null>(null)
const natureFilter = ref<ShootingContextNature | 'all'>('all')
const { dateFrom, dateTo, maxDate, normalizeRange, queryParams } = useStatsDateRange()

const natureFilterOptions = computed(() => [
  { value: 'all' as const, label: t('shooting.stats.filters.all') },
  { value: 'training' as const, label: t('shooting.context.nature.training') },
  { value: 'competition' as const, label: t('shooting.context.nature.competition') },
])

const showDateFilter = computed(() => stats.value !== null)

const emptyTitleKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'shooting.stats.empty.noDataInPeriodTitle'
    : 'shooting.stats.empty.noSessionsTitle',
)

const emptyHintKey = computed(() =>
  stats.value?.status === 'no_data_in_period'
    ? 'shooting.stats.empty.noDataInPeriodHint'
    : 'shooting.stats.empty.noSessionsHint',
)

const workshops = SHOOTING_WORKSHOPS
const distances = SHOOTING_DISTANCES

const {
  showEvolution,
  evolutionChart,
  workshopChart,
  distanceChart,
  resultChart,
  strongestWorkshop,
  weakestWorkshop,
  strongestDistance,
  weakestDistance,
  heatmapGrid,
  totalResults,
} = useShootingStatsCharts(stats, t)

function cellFor(workshop: number, distance: number) {
  return heatmapGrid.value.find((c) => c.workshop === workshop && c.distance === distance) ?? {
    workshop,
    distance,
    averageScore: 0,
    shotCount: 0,
  }
}

function resultPercent(count: number): string {
  if (totalResults.value === 0) return '0'
  return ((count / totalResults.value) * 100).toFixed(0)
}

function setNatureFilter(value: ShootingContextNature | 'all'): void {
  natureFilter.value = value
  if (stats.value) {
    load({ refresh: true })
  }
}

function goHome(): void {
  router.push({ name: 'shootingHome' })
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
    stats.value = await shootingSessionsService.getStats(queryParams(), natureFilter.value)
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
.nature-filter {
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

.distance-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.pill {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
}

.pill.best {
  background: #dcfce7;
  color: #166534;
}

.pill.weak {
  background: #ffedd5;
  color: #9a3412;
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

.heatmap {
  display: grid;
  grid-template-columns: minmax(4.5rem, 1.2fr) repeat(4, 1fr);
  gap: 0.375rem;
  align-items: stretch;
}

.heatmap-corner {
  min-height: 1.5rem;
}

.heatmap-col-label,
.heatmap-row-label {
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--app-text-muted);
  display: flex;
  align-items: center;
}

.heatmap-col-label {
  justify-content: center;
  text-align: center;
}

.heatmap-cell {
  border-radius: 8px;
  min-height: 3rem;
  display: grid;
  place-items: center;
  gap: 0.125rem;
  color: #fff;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

.cell-score {
  font-size: 0.875rem;
  font-weight: 800;
}

.cell-count {
  font-size: 0.625rem;
  opacity: 0.9;
}
</style>
