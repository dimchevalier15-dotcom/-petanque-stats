<template>
  <AppPage>
    <PageHeader
      :title="playerTitle"
      :subtitle="t('coach.player.subtitle')"
      :back-to="{ name: 'coachPlayers' }"
    />

    <div class="tabs">
      <SelectButton v-model="activeTab" :options="tabOptions" option-label="label" option-value="value" />
    </div>

    <template v-if="activeTab === 'stats'">
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
              @click="setNatureFilter(opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
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

        <StatsDateRangeFilter
          v-model:date-from="dateFrom"
          v-model:date-to="dateTo"
          :max-date="maxDate"
          @change="onStatsFiltersChange"
        />
      </StatsCollapsibleFilters>

      <div v-if="statsLoading && !stats" class="loading">
        <ProgressSpinner stroke-width="4" />
      </div>

      <div v-else-if="stats && stats.status === 'ok'" class="stats-body">
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

        <div v-if="stats.overall" class="hero-card app-card">
          <span class="hero-label">{{ t('stats.overallAverage') }}</span>
          <Tag class="hero-avg" :value="formatAvg(stats.overall.average)" :severity="avgSeverity(stats.overall.average)" />
          <span class="hero-meta">{{ t('stats.ballsTracked', { n: stats.summary.totalBalls }) }}</span>
        </div>

        <section class="panel app-card shots">
          <h3>{{ t('stats.sections.shots') }}</h3>
          <div class="shot-grid">
            <div v-if="stats.point" class="shot-card app-card">
              <div class="shot-head">
                <span>{{ t('play.shots.point') }}</span>
                <Tag :value="formatAvg(stats.point.average)" :severity="avgSeverity(stats.point.average)" />
              </div>
              <p v-if="successWithMasters(stats.point)" class="shot-meta">{{ successWithMasters(stats.point) }}</p>
              <ShotSuccessRate :breakdown="stats.point" />
            </div>
            <div v-if="stats.tir" class="shot-card app-card">
              <div class="shot-head">
                <span>{{ t('play.shots.tir') }}</span>
                <Tag :value="formatAvg(stats.tir.average)" :severity="avgSeverity(stats.tir.average)" />
              </div>
              <p v-if="successWithMasters(stats.tir)" class="shot-meta">{{ successWithMasters(stats.tir) }}</p>
              <ShotSuccessRate :breakdown="stats.tir" />
            </div>
          </div>
        </section>

        <section v-if="evolutionChart" class="panel app-card">
          <h3>{{ t('stats.sections.evolution') }}</h3>
          <div class="chart-box">
            <Chart type="line" :data="evolutionChart.data" :options="evolutionChart.options" />
          </div>
        </section>
      </div>

      <EmptyState
        v-else
        :title="t('coach.player.noStats')"
        icon="pi pi-chart-line"
      />
    </template>

    <template v-else>
      <EmptyState
        v-if="historyItems.length === 0 && !historyLoading"
        :title="t('history.empty')"
        icon="pi pi-inbox"
      />

      <ul v-else class="history-list">
        <li v-for="m in historyItems" :key="m.id">
          <button type="button" class="match-card app-card" @click="openMatch(m.id)">
            <div class="head">
              <span class="date">{{ formatDate(m.date) }}</span>
              <Tag
                v-if="m.victory !== null"
                :value="m.victory ? t('history.victory') : t('history.defeat')"
                :severity="m.victory ? 'success' : 'danger'"
              />
            </div>
            <div class="type">{{ typeLabel(m.type) }}</div>
            <div class="score">{{ m.scoreA }} - {{ m.scoreB }}</div>
          </button>
        </li>
      </ul>

      <div v-if="canLoadMoreHistory" class="app-actions">
        <Button :label="t('history.loadMore')" :loading="historyLoading" outlined @click="loadMoreHistory" />
      </div>
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import ProgressSpinner from 'primevue/progressspinner'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'
import ShotSuccessRate from '../components/stats/ShotSuccessRate.vue'
import StatsCollapsibleFilters from '../components/stats/StatsCollapsibleFilters.vue'
import StatsDateRangeFilter from '../components/stats/StatsDateRangeFilter.vue'
import { formatMasters, shotMasters, shotSuccessRate } from '../composables/matchSuccessRate'
import {
  avgSeverity,
  formatAvg,
  usePlayerStatsCharts,
} from '../composables/usePlayerStatsCharts'
import { useStatsDateRange } from '../composables/useStatsDateRange'
import type { MatchNature } from '../models/MatchContext'
import type { MatchType } from '../models/Match'
import type { MatchSummaryShotBreakdown } from '../models/MatchSummary'
import type { MatchHistoryItem } from '../models/MatchHistory'
import type { PlayerStats } from '../models/PlayerStats'
import { coachService } from '../services/coach'

type CoachTab = 'stats' | 'history'

const { t, d } = useI18n()
const route = useRoute()
const router = useRouter()

const playerId = Number(route.params.id)
const playerTitle = ref(String(route.query.name ?? t('coach.player.title')))

const activeTab = ref<CoachTab>('stats')
const tabOptions = computed(() => [
  { label: t('coach.player.tabs.stats'), value: 'stats' as CoachTab },
  { label: t('coach.player.tabs.history'), value: 'history' as CoachTab },
])

const stats = ref<PlayerStats | null>(null)
const statsLoading = ref(false)
const natureFilter = ref<MatchNature | 'all'>('all')
const formatFilter = ref<MatchType | 'all'>('all')

const { dateFrom, dateTo, maxDate, queryParams } = useStatsDateRange()
if (typeof route.query.from === 'string') dateFrom.value = route.query.from
if (typeof route.query.to === 'string') dateTo.value = route.query.to

const natureFilterOptions = computed(() => [
  { label: t('stats.filters.all'), value: 'all' as const },
  { label: t('context.nature.training'), value: 'training' as MatchNature },
  { label: t('context.nature.friendly'), value: 'friendly' as MatchNature },
  { label: t('context.nature.competition'), value: 'competition' as MatchNature },
])

const formatFilterOptions = computed(() => [
  { label: t('stats.filters.all'), value: 'all' as const },
  { label: t('matches.types.teteATete'), value: 'tete_a_tete' as MatchType },
  { label: t('matches.types.doublette'), value: 'doublette' as MatchType },
  { label: t('matches.types.triplette'), value: 'triplette' as MatchType },
])

const activeFilterCount = computed(() => {
  let count = 0
  if (natureFilter.value !== 'all') count++
  if (formatFilter.value !== 'all') count++
  return count
})

const { evolutionChart } = usePlayerStatsCharts(stats, t)

const historyItems = ref<MatchHistoryItem[]>([])
const historyPage = ref(1)
const historyTotal = ref(0)
const historyLoading = ref(false)
const canLoadMoreHistory = computed(() => historyItems.value.length < historyTotal.value)

function setNatureFilter(value: MatchNature | 'all'): void {
  natureFilter.value = value
  void loadStats()
}

function setFormatFilter(value: MatchType | 'all'): void {
  formatFilter.value = value
  void loadStats()
}

function onStatsFiltersChange(): void {
  void loadStats()
}

function successWithMasters(breakdown: MatchSummaryShotBreakdown | null | undefined): string | null {
  const masters = shotMasters(breakdown)
  const pct = shotSuccessRate(breakdown)
  if (pct === null) return null
  const pctLabel = `${pct}%`
  return masters ? `${pctLabel} (${formatMasters(masters)})` : pctLabel
}

async function loadStats(): Promise<void> {
  statsLoading.value = true
  try {
    stats.value = await coachService.getPlayerStats(
      playerId,
      queryParams(),
      natureFilter.value,
      formatFilter.value,
    )
    if (stats.value.displayName) {
      playerTitle.value = stats.value.displayName
    }
  } finally {
    statsLoading.value = false
  }
}

async function loadHistory(reset = false): Promise<void> {
  if (reset) {
    historyPage.value = 1
    historyItems.value = []
  }
  historyLoading.value = true
  try {
    const res = await coachService.getPlayerHistory(playerId, historyPage.value)
    historyTotal.value = res.total
    historyItems.value = reset ? res.items : [...historyItems.value, ...res.items]
  } finally {
    historyLoading.value = false
  }
}

function loadMoreHistory(): void {
  historyPage.value += 1
  void loadHistory()
}

function typeLabel(type: MatchHistoryItem['type']): string {
  switch (type) {
    case 'tete_a_tete':
      return t('matches.types.teteATete')
    case 'doublette':
      return t('matches.types.doublette')
    case 'triplette':
      return t('matches.types.triplette')
    default:
      return String(type)
  }
}

function formatDate(iso: string): string {
  try {
    return d(new Date(iso), 'short') as string
  } catch {
    return iso
  }
}

function openMatch(id: number): void {
  router.push({ name: 'matchSummary', params: { id } })
}

watch(activeTab, (tab) => {
  if (tab === 'history' && historyItems.value.length === 0) {
    void loadHistory(true)
  }
})

onMounted(() => {
  void loadStats()
})
</script>

<style scoped>
.tabs {
  margin-bottom: var(--app-space-md);
}

.filter-group {
  display: grid;
  gap: 0.5rem;
}

.filter-group-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--app-text-muted);
}

.nature-filter,
.format-filter {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
}

.filter-btn {
  border: 1px solid var(--app-border);
  background: var(--app-surface);
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
  font-size: 0.8125rem;
  cursor: pointer;
}

.filter-btn.active {
  background: var(--app-primary);
  border-color: var(--app-primary);
  color: #fff;
}

.loading {
  display: flex;
  justify-content: center;
  padding: var(--app-space-xl);
}

.stats-body {
  display: grid;
  gap: var(--app-space-md);
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--app-space-sm);
}

.kpi-card {
  padding: var(--app-space-sm);
  display: grid;
  gap: 0.25rem;
  text-align: center;
}

.kpi-card.win {
  background: color-mix(in srgb, var(--app-success) 12%, var(--app-surface));
}

.kpi-card.loss {
  background: color-mix(in srgb, var(--app-danger) 10%, var(--app-surface));
}

.kpi-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  color: var(--app-text-muted);
}

.kpi-value {
  font-size: 1.25rem;
  font-weight: 700;
}

.hero-card {
  padding: var(--app-space-md);
  display: grid;
  gap: 0.5rem;
  justify-items: center;
}

.hero-label {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.hero-meta {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.panel {
  padding: var(--app-space-md);
}

.panel h3 {
  margin: 0 0 var(--app-space-sm);
  font-size: 1rem;
}

.shot-grid {
  display: grid;
  gap: var(--app-space-sm);
}

.shot-card {
  padding: var(--app-space-sm);
}

.shot-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.shot-meta {
  margin: 0 0 0.5rem;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.chart-box {
  min-height: 12rem;
}

.history-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--app-space-sm);
}

.match-card {
  width: 100%;
  border: none;
  cursor: pointer;
  text-align: left;
  padding: var(--app-space-md);
  font: inherit;
  color: inherit;
}

.head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.date {
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.type {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.score {
  font-size: 1.125rem;
  font-weight: 700;
}

.app-actions {
  margin-top: var(--app-space-md);
  display: flex;
  justify-content: center;
}
</style>
