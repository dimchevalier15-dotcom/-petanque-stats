<template>
  <article class="player-card app-card" :class="`player-card--${player.team}`">
    <div class="player-head">
      <h4 class="player-name">{{ displayName }}</h4>
      <Tag
        class="player-avg"
        :value="formatAvg(player.average)"
        :severity="avgSeverity(player.average)"
      />
    </div>

    <div v-if="overallChart" class="chart-block">
      <span class="chart-label">{{ t('summary.sections.distribution') }}</span>
      <ShotSuccessRate :rate="shotSuccessRate(overallBreakdown)" />
      <div class="chart-box">
        <Chart type="bar" :data="overallChart.data" :options="overallChart.options" />
      </div>
    </div>

    <details v-if="pointChart || tirChart" class="shots-drawer">
      <summary class="shots-drawer-summary">{{ t('summary.sections.shotDetails') }}</summary>
      <div class="shots-grid">
        <div v-if="pointChart" class="shot-block">
          <div class="shot-head">
            <span>{{ t('play.shots.point') }}</span>
            <Tag
              :value="formatAvg(player.point!.average)"
              :severity="avgSeverity(player.point!.average)"
            />
          </div>
          <ShotSuccessRate :rate="shotSuccessRate(player.point)" />
          <div class="chart-box chart-box-sm">
            <Chart type="bar" :data="pointChart.data" :options="pointChart.options" />
          </div>
        </div>

        <div v-if="tirChart" class="shot-block">
          <div class="shot-head">
            <span>{{ t('play.shots.tir') }}</span>
            <Tag
              :value="formatAvg(player.tir!.average)"
              :severity="avgSeverity(player.tir!.average)"
            />
          </div>
          <ShotSuccessRate :rate="shotSuccessRate(player.tir)" />
          <div class="chart-box chart-box-sm">
            <Chart type="bar" :data="tirChart.data" :options="tirChart.options" />
          </div>
        </div>
      </div>
    </details>

    <p v-if="!overallChart && !pointChart && !tirChart" class="no-data">
      {{ t('summary.empty.noPlayerData') }}
    </p>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Chart from 'primevue/chart'
import Tag from 'primevue/tag'
import type { MatchSummaryPlayer } from '../../models/MatchSummary'
import { shotSuccessRate } from '../../composables/matchSuccessRate'
import {
  buildPlayerDistributionChart,
  buildPlayerShotChart,
  playerDisplayName,
  playerToOverallBreakdown,
} from '../../composables/useMatchSummaryCharts'
import { avgSeverity, formatAvg } from '../../composables/usePlayerStatsCharts'
import ShotSuccessRate from '../stats/ShotSuccessRate.vue'

const props = defineProps<{
  player: MatchSummaryPlayer
}>()

const { t } = useI18n()

const displayName = computed(() => playerDisplayName(props.player))
const overallBreakdown = computed(() => playerToOverallBreakdown(props.player))
const overallChart = computed(() => buildPlayerDistributionChart(props.player, t))
const pointChart = computed(() => buildPlayerShotChart(props.player.point, t))
const tirChart = computed(() => buildPlayerShotChart(props.player.tir, t))
</script>

<style scoped>
.player-card {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.player-card--A {
  border-left: 4px solid #22c55e;
}

.player-card--B {
  border-left: 4px solid #3b82f6;
}

.player-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
}

.player-name {
  margin: 0;
  min-width: 0;
  font-size: 0.9375rem;
  font-weight: 700;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.player-avg :deep(.p-tag) {
  font-size: 1rem;
  font-weight: 800;
}

.chart-block {
  display: grid;
  gap: 0.375rem;
}

.chart-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--app-text-subtle);
}

.chart-box {
  height: 150px;
}

.chart-box-sm {
  height: 120px;
}

.shots-drawer {
  overflow: hidden;
}

.shots-drawer-summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.375rem;
  padding: 0.375rem 0;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--app-text-subtle);
  user-select: none;
}

.shots-drawer-summary::-webkit-details-marker {
  display: none;
}

.shots-drawer-summary::after {
  content: '';
  width: 0.35rem;
  height: 0.35rem;
  border-right: 2px solid currentColor;
  border-bottom: 2px solid currentColor;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  opacity: 0.6;
  flex-shrink: 0;
}

.shots-drawer[open] .shots-drawer-summary::after {
  transform: rotate(-135deg);
}

.shots-grid {
  display: grid;
  gap: 0.75rem;
}

.shot-block {
  display: grid;
  gap: 0.375rem;
  padding-top: 0.25rem;
  border-top: 1px solid var(--app-border);
}

.shot-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8125rem;
  font-weight: 600;
}

.no-data {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  text-align: center;
}
</style>
