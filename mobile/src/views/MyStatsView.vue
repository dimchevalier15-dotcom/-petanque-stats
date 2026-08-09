<template>
  <AppPage :title="t('stats.title')" :subtitle="stats?.displayName ?? undefined">
    <div v-if="loading" class="loading">
      <ProgressSpinner stroke-width="4" />
    </div>

    <div v-else-if="loadError" class="empty-state">
      <i class="pi pi-exclamation-circle empty-icon" aria-hidden="true" />
      <p class="empty-title">{{ t('stats.empty.loadErrorTitle') }}</p>
      <p class="empty-hint">{{ t('stats.empty.loadErrorHint') }}</p>
      <Button :label="t('stats.empty.retry')" @click="load" />
    </div>

    <template v-else-if="stats">
      <div
        v-if="stats.status === 'no_player' || stats.status === 'no_matches'"
        class="empty-state"
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

        <div v-else-if="stats.status === 'no_tracked_data'" class="panel app-card notice">
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
              <div v-if="tirDistributionChart" class="chart-box chart-bar-sm">
                <Chart type="bar" :data="tirDistributionChart.data" :options="tirDistributionChart.options" />
              </div>
              <p v-else class="no-data">{{ t('stats.empty.noTirData') }}</p>
            </div>

            <p v-if="!stats.point && !stats.tir" class="no-data">{{ t('stats.empty.noShotBreakdown') }}</p>
          </div>
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
      </template>
    </template>
  </AppPage>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, type RouteLocationRaw } from 'vue-router'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import ProgressSpinner from 'primevue/progressspinner'
import Tag from 'primevue/tag'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import {
  avgSeverity,
  formatAvg,
  natureLabel,
  usePlayerStatsCharts,
} from '../composables/usePlayerStatsCharts'
import type { PlayerStats } from '../models/PlayerStats'
import { statsService } from '../services/stats'

const { t } = useI18n()
const router = useRouter()

const loading = ref(true)
const loadError = ref(false)
const stats = ref<PlayerStats | null>(null)

const {
  showEvolution,
  showDistribution,
  evolutionChart,
  distributionChart,
  pointDistributionChart,
  tirDistributionChart,
} = usePlayerStatsCharts(stats, t)

const emptyTitleKey = computed(() => {
  switch (stats.value?.status) {
    case 'no_player':
      return 'stats.empty.noPlayerTitle'
    case 'no_matches':
      return 'stats.empty.noMatchesTitle'
    default:
      return 'stats.empty.noDataTitle'
  }
})

const emptyHintKey = computed(() => {
  switch (stats.value?.status) {
    case 'no_player':
      return 'stats.empty.noPlayerHint'
    case 'no_matches':
      return 'stats.empty.noMatchesHint'
    default:
      return 'stats.empty.noDataHint'
  }
})

const emptyActionKey = computed(() => {
  switch (stats.value?.status) {
    case 'no_matches':
      return 'stats.empty.startMatch'
    default:
      return 'stats.empty.goHome'
  }
})

const emptyActionRoute = computed<RouteLocationRaw | null>(() => {
  switch (stats.value?.status) {
    case 'no_matches':
      return { name: 'newMatch' }
    case 'no_player':
    case 'no_tracked_data':
      return { name: 'home' }
    default:
      return null
  }
})

async function load() {
  loading.value = true
  loadError.value = false
  try {
    stats.value = await statsService.getMyStats()
  } catch {
    loadError.value = true
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.loading {
  display: grid;
  place-items: center;
  min-height: 12rem;
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
  width: 100%;
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

.nature-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.nature-item {
  border: 1px solid #f0f0f0;
  border-radius: 10px;
  padding: 0.625rem 0.75rem;
  display: grid;
  gap: 0.25rem;
}

.nature-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.nature-meta {
  font-size: 0.8rem;
  opacity: 0.7;
}
</style>
