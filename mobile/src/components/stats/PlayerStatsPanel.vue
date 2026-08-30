<template>
  <StatsCollapsibleFilters v-if="showDateFilter" :active-count="activeFilterCount">
    <div class="filter-group">
      <span class="filter-group-label">{{ t('stats.filters.nature') }}</span>
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
        @change="onCompetitionFilterChange"
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
          @click="setFormatFilter(opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <div class="filter-group">
      <span class="filter-group-label">{{ t('stats.filters.distance') }}</span>
      <div class="distance-filter">
        <button
          v-for="opt in distanceFilterOptions"
          :key="opt.value"
          type="button"
          class="filter-btn"
          :class="{ active: distanceFilter === opt.value }"
          @click="setDistanceFilter(opt.value)"
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
      show-all-button
      @change="onDateRangeChange"
    />
  </StatsCollapsibleFilters>

  <div v-if="loading && !stats" class="loading">
    <ProgressSpinner stroke-width="4" />
  </div>

  <div v-else-if="loadError" class="empty-state">
    <i class="pi pi-exclamation-circle empty-icon" aria-hidden="true" />
    <p class="empty-title">{{ t('stats.empty.loadErrorTitle') }}</p>
    <p class="empty-hint">{{ t('stats.empty.loadErrorHint') }}</p>
    <Button :label="t('stats.empty.retry')" @click="load" />
  </div>

  <template v-else-if="stats">
    <div v-if="refreshing" class="refreshing">
      <ProgressSpinner stroke-width="4" />
    </div>

    <div
      v-if="stats.status === 'no_player' || stats.status === 'no_matches' || stats.status === 'no_data_in_period'"
      class="empty-state"
      :class="{ dimmed: refreshing }"
    >
      <i class="pi pi-chart-line empty-icon" aria-hidden="true" />
      <p class="empty-title">{{ t(emptyTitleKey) }}</p>
      <p class="empty-hint">{{ t(emptyHintKey) }}</p>
      <Button
        v-if="emptyActionRoute"
        :label="t(emptyActionKey)"
        @click="router.push(emptyActionRoute)"
      />
    </div>

    <div v-else-if="stats.status === 'no_tracked_data'" class="panel app-card notice" :class="{ dimmed: refreshing }">
      <p class="notice-title">{{ t('stats.empty.noTrackedDataTitle') }}</p>
      <p class="panel-hint">{{ t('stats.empty.noTrackedData') }}</p>
      <div class="kpi-grid compact">
        <div class="kpi-card app-card">
          <span class="kpi-label">{{ t('stats.kpi.matches') }}</span>
          <span class="kpi-value">{{ stats.summary.matchesPlayed }}</span>
        </div>
        <div class="kpi-card win">
          <span class="kpi-label">{{ t('stats.kpi.victories') }}</span>
          <span class="kpi-value">{{ stats.summary.victories }}</span>
        </div>
        <div class="kpi-card loss">
          <span class="kpi-label">{{ t('stats.kpi.defeats') }}</span>
          <span class="kpi-value">{{ stats.summary.defeats }}</span>
        </div>
      </div>
      <div v-if="stats.summary.winRate !== null" class="win-rate-card">
        <span>{{ t('stats.kpi.winRate') }}</span>
        <strong>{{ stats.summary.winRate }}%</strong>
      </div>
    </div>

    <template v-else>
      <div :class="{ dimmed: refreshing }">
        <div class="kpi-grid">
          <div class="kpi-card app-card">
            <span class="kpi-label">{{ t('stats.kpi.matches') }}</span>
            <span class="kpi-value">{{ stats.summary.matchesPlayed }}</span>
          </div>
          <div class="kpi-card win">
            <span class="kpi-label">{{ t('stats.kpi.victories') }}</span>
            <span class="kpi-value">{{ stats.summary.victories }}</span>
          </div>
          <div class="kpi-card loss">
            <span class="kpi-label">{{ t('stats.kpi.defeats') }}</span>
            <span class="kpi-value">{{ stats.summary.defeats }}</span>
          </div>
        </div>

        <div v-if="stats.summary.winRate !== null" class="win-rate-card">
          <span>{{ t('stats.kpi.winRate') }}</span>
          <strong>{{ stats.summary.winRate }}%</strong>
        </div>

        <div v-if="stats.overall" class="hero-card app-card">
          <span class="hero-label">{{ t('stats.overallAverage') }}</span>
          <Tag
            class="hero-avg"
            :value="formatAvg(stats.overall.average)"
            :severity="avgSeverity(stats.overall.average)"
          />
          <span class="hero-meta">{{ t('stats.ballsTracked', { n: stats.summary.totalBalls }) }}</span>
          <ShotSuccessRate class="hero-success" :breakdown="stats.overall" />

          <details v-if="showAverageDetails" class="avg-details">
            <summary>{{ t('stats.details.title') }}</summary>
            <div class="avg-details-body">
              <div v-if="stats.point" class="avg-detail-row">
                <span class="avg-detail-label">{{ t('play.shots.point') }}</span>
                <div class="avg-detail-values">
                  <Tag
                    :value="formatAvg(stats.point.average)"
                    :severity="avgSeverity(stats.point.average)"
                  />
                  <span v-if="successWithMasters(stats.point)" class="avg-detail-meta">
                    {{ successWithMasters(stats.point) }}
                  </span>
                  <span class="avg-detail-meta">
                    {{ t('stats.details.balls', { n: breakdownBallCount(stats.point) }) }}
                  </span>
                </div>
              </div>
              <div v-if="stats.tir" class="avg-detail-row">
                <span class="avg-detail-label">{{ t('play.shots.tir') }}</span>
                <div class="avg-detail-values">
                  <Tag
                    :value="formatAvg(stats.tir.average)"
                    :severity="avgSeverity(stats.tir.average)"
                  />
                  <span v-if="successWithMasters(stats.tir)" class="avg-detail-meta">
                    {{ successWithMasters(stats.tir) }}
                  </span>
                  <span class="avg-detail-meta">
                    {{ t('stats.details.balls', { n: breakdownBallCount(stats.tir) }) }}
                  </span>
                </div>
              </div>
              <div v-if="stats.cochonnet" class="avg-detail-row">
                <span class="avg-detail-label">{{ t('play.shots.cochonnet') }}</span>
                <div class="avg-detail-values">
                  <Tag
                    :value="formatAvg(stats.cochonnet.average)"
                    :severity="avgSeverity(stats.cochonnet.average)"
                  />
                  <span v-if="successWithMasters(stats.cochonnet)" class="avg-detail-meta">
                    {{ successWithMasters(stats.cochonnet) }}
                  </span>
                  <span class="avg-detail-meta">
                    {{ t('stats.details.balls', { n: breakdownBallCount(stats.cochonnet) }) }}
                  </span>
                </div>
              </div>
            </div>
          </details>
        </div>

        <section v-if="showEvolution && evolutionChart" class="panel app-card">
          <h3>{{ t('stats.sections.evolution') }}</h3>
          <p class="panel-hint">{{ t('stats.evolution.hint') }}</p>
          <div class="chart-box chart-line">
            <Chart type="line" :data="evolutionChart.data" :options="evolutionChart.options" />
          </div>
        </section>

        <section v-if="showDistribution && distributionChart" class="panel app-card">
          <h3>{{ t('stats.sections.distribution') }}</h3>
          <div class="chart-box chart-bar">
            <Chart type="bar" :data="distributionChart.data" :options="distributionChart.options" />
          </div>
        </section>

        <section class="panel app-card shots">
          <h3>{{ t('stats.sections.shots') }}</h3>
          <div class="shot-grid">
            <div v-if="stats.point" class="shot-card app-card">
              <div class="shot-head">
                <span>{{ t('play.shots.point') }}</span>
                <Tag :value="formatAvg(stats.point.average)" :severity="avgSeverity(stats.point.average)" />
              </div>
              <ShotSuccessRate :breakdown="stats.point" />
              <div v-if="pointDistributionChart" class="chart-box chart-bar-sm">
                <Chart type="bar" :data="pointDistributionChart.data" :options="pointDistributionChart.options" />
              </div>
              <p v-else class="no-data">{{ t('stats.empty.noPointData') }}</p>
            </div>

            <div v-if="stats.tir" class="shot-card app-card">
              <div class="shot-head">
                <span>{{ t('play.shots.tir') }}</span>
                <Tag :value="formatAvg(stats.tir.average)" :severity="avgSeverity(stats.tir.average)" />
              </div>
              <ShotSuccessRate :breakdown="stats.tir" />
              <div v-if="tirDistributionChart" class="chart-box chart-bar-sm">
                <Chart type="bar" :data="tirDistributionChart.data" :options="tirDistributionChart.options" />
              </div>
              <p v-else class="no-data">{{ t('stats.empty.noTirData') }}</p>
            </div>

            <div v-if="stats.cochonnet" class="shot-card app-card">
              <div class="shot-head">
                <span>{{ t('play.shots.cochonnet') }}</span>
                <Tag :value="formatAvg(stats.cochonnet.average)" :severity="avgSeverity(stats.cochonnet.average)" />
              </div>
              <ShotSuccessRate :breakdown="stats.cochonnet" />
              <div v-if="cochonnetDistributionChart" class="chart-box chart-bar-sm">
                <Chart type="bar" :data="cochonnetDistributionChart.data" :options="cochonnetDistributionChart.options" />
              </div>
            </div>

            <p v-if="!stats.point && !stats.tir && !stats.cochonnet" class="no-data">{{ t('stats.empty.noShotBreakdown') }}</p>
          </div>
        </section>

        <section v-if="stats.byFormat.length > 0 && formatFilter === 'all'" class="panel app-card">
          <h3>{{ t('stats.sections.byFormat') }}</h3>
          <p class="panel-hint">{{ t('stats.byFormat.hint') }}</p>
          <ul class="breakdown-list">
            <li v-for="item in stats.byFormat" :key="item.type" class="breakdown-item">
              <div class="breakdown-head">
                <span>{{ formatLabel(t, item.type) }}</span>
                <Tag :value="formatAvg(item.average)" :severity="avgSeverity(item.average)" />
              </div>
              <span class="breakdown-meta">
                {{ t('stats.byFormat.meta', {
                  matches: item.matchCount,
                  victories: item.victories,
                  balls: item.ballCount,
                }) }}
              </span>
            </li>
          </ul>
        </section>

        <section v-if="stats.byDistance.length > 0 && distanceFilter === 'all'" class="panel app-card">
          <h3>{{ t('stats.sections.byDistance') }}</h3>
          <p class="panel-hint">{{ t('stats.byDistance.hint') }}</p>
          <ul class="breakdown-list">
            <li v-for="item in stats.byDistance" :key="item.bucket" class="breakdown-item">
              <div class="breakdown-head">
                <span>{{ distanceBucketLabel(t, item.bucket) }}</span>
                <Tag :value="formatAvg(item.average)" :severity="avgSeverity(item.average)" />
              </div>
              <span class="breakdown-meta">
                {{ t('stats.byDistance.meta', { balls: item.ballCount }) }}
              </span>
              <ShotSuccessRate :breakdown="distanceBreakdown(item)" />
            </li>
          </ul>
        </section>

        <section v-if="stats.byNature.length > 0" class="panel app-card">
          <h3>{{ t('stats.sections.byNature') }}</h3>
          <p class="panel-hint">{{ t('stats.byNature.hint') }}</p>
          <ul class="nature-list">
            <li v-for="item in stats.byNature" :key="item.nature" class="nature-item">
              <div class="nature-head">
                <span>{{ natureLabel(t, item.nature) }}</span>
                <Tag :value="formatAvg(item.average)" :severity="avgSeverity(item.average)" />
              </div>
              <span class="nature-meta">
                {{ t('stats.byNature.meta', { matches: item.matchCount, balls: item.ballCount }) }}
              </span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import Dropdown from 'primevue/dropdown'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import ShotSuccessRate from './ShotSuccessRate.vue'
import StatsCollapsibleFilters from './StatsCollapsibleFilters.vue'
import StatsDateRangeFilter from './StatsDateRangeFilter.vue'
import { usePlayerStatsPanel, type PlayerStatsFetcher } from '../../composables/usePlayerStatsPanel'

const props = withDefaults(
  defineProps<{
    fetchStats: PlayerStatsFetcher
    showEmptyActions?: boolean
    initialNature?: import('../../models/MatchContext').MatchNature | 'all'
    initialFrom?: string
    initialTo?: string
    reloadKey?: unknown
  }>(),
  { showEmptyActions: true },
)

const emit = defineEmits<{
  statsLoaded: [stats: import('../../models/PlayerStats').PlayerStats]
}>()

const {
  router,
  t,
  loading,
  refreshing,
  loadError,
  stats,
  natureFilter,
  competitionFilter,
  formatFilter,
  distanceFilter,
  dateFrom,
  dateTo,
  maxDate,
  dateFilterEnabled,
  natureFilterOptions,
  competitionFilterOptions,
  formatFilterOptions,
  distanceFilterOptions,
  activeFilterCount,
  showDateFilter,
  showEvolution,
  showDistribution,
  evolutionChart,
  distributionChart,
  pointDistributionChart,
  tirDistributionChart,
  cochonnetDistributionChart,
  successWithMasters,
  distanceBreakdown,
  emptyTitleKey,
  emptyHintKey,
  emptyActionKey,
  emptyActionRoute,
  showAverageDetails,
  setNatureFilter,
  onCompetitionFilterChange,
  setFormatFilter,
  setDistanceFilter,
  load,
  onDateRangeChange,
  avgSeverity,
  breakdownBallCount,
  distanceBucketLabel,
  formatAvg,
  formatLabel,
  natureLabel,
} = usePlayerStatsPanel({
  fetchStats: (range, nature, type, distance, competitionId) =>
    props.fetchStats(range, nature, type, distance, competitionId),
  showEmptyActions: props.showEmptyActions,
  initialNature: props.initialNature,
  initialFrom: props.initialFrom,
  initialTo: props.initialTo,
  onStatsLoaded: (loadedStats) => emit('statsLoaded', loadedStats),
  ...(props.reloadKey !== undefined ? { reloadKey: computed(() => props.reloadKey) } : {}),
})
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

.distance-filter {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--app-space-xs);
}

.distance-filter .filter-btn {
  font-size: 0.75rem;
  padding: 0.375rem 0.25rem;
}

:deep(.date-range-filter) {
  padding: 0;
  border: none;
  box-shadow: none;
  background: transparent;
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

.kpi-grid.compact {
  margin-top: 0.75rem;
}

.kpi-card {
  padding: var(--app-space-md) var(--app-space-sm);
  display: grid;
  gap: 0.125rem;
  text-align: center;
}

.kpi-card.win {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.kpi-card.loss {
  background: #fef2f2;
  border-color: #fecaca;
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

.win-rate-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 0.625rem 0.875rem;
  font-size: 0.95rem;
}

.win-rate-card strong {
  font-size: 1.25rem;
  color: #6366f1;
}

.hero-card {
  border: 1px solid #e0e7ff;
  background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
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

.hero-avg :deep(.p-tag) {
  font-size: 1.75rem;
  font-weight: 800;
  padding: 0.35rem 0.75rem;
}

.hero-meta {
  font-size: 0.85rem;
  opacity: 0.7;
}

.hero-success {
  width: 100%;
  margin-top: 0.25rem;
}

.avg-details {
  width: 100%;
  margin-top: 0.25rem;
  border-top: 1px solid rgba(99, 102, 241, 0.15);
  padding-top: 0.625rem;
}

.avg-details summary {
  list-style: none;
  cursor: pointer;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--app-primary, #6366f1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  user-select: none;
}

.avg-details summary::-webkit-details-marker {
  display: none;
}

.avg-details summary::after {
  content: '';
  width: 0.4rem;
  height: 0.4rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  margin-top: -0.15rem;
}

.avg-details[open] summary::after {
  transform: rotate(-135deg);
  margin-top: 0.15rem;
}

.avg-details-body {
  display: grid;
  gap: 0.625rem;
  padding-top: 0.75rem;
}

.avg-detail-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.avg-detail-label {
  font-size: 0.875rem;
  font-weight: 600;
}

.avg-detail-values {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

.avg-detail-meta {
  font-size: 0.75rem;
  opacity: 0.65;
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h3 {
  margin: 0;
  font-size: 1rem;
}

.panel-hint {
  margin: 0;
  font-size: 0.8rem;
  opacity: 0.7;
}

.panel.notice {
  border-style: dashed;
  background: #fafafa;
}

.notice-title {
  margin: 0;
  font-weight: 700;
}

.chart-box {
  position: relative;
}

.chart-line {
  height: 200px;
}

.chart-bar {
  height: 180px;
}

.chart-bar-sm {
  height: 140px;
}

.shot-grid {
  display: grid;
  gap: 0.75rem;
}

.shot-card {
  border: 1px solid #f0f0f0;
  border-radius: 10px;
  padding: 0.625rem;
  display: grid;
  gap: 0.5rem;
}

.shot-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.no-data {
  margin: 0;
  font-size: 0.85rem;
  opacity: 0.7;
  text-align: center;
  padding: 0.5rem 0;
}

.nature-list,
.breakdown-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.nature-item,
.breakdown-item {
  border: 1px solid #f0f0f0;
  border-radius: 10px;
  padding: 0.625rem 0.75rem;
  display: grid;
  gap: 0.25rem;
}

.nature-head,
.breakdown-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.nature-head > span:first-child,
.breakdown-head > span:first-child {
  min-width: 0;
  overflow-wrap: anywhere;
}

.nature-meta,
.breakdown-meta {
  font-size: 0.8rem;
  opacity: 0.7;
}
</style>
