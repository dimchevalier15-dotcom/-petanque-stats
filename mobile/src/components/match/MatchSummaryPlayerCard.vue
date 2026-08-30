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
      <ShotSuccessRate :breakdown="overallBreakdown" />
      <div class="chart-box">
        <Chart type="bar" :data="overallChart.data" :options="overallChart.options" />
      </div>
    </div>

    <MatchSummaryShotDrawer :point="player.point" :tir="player.tir" :cochonnet="player.cochonnet" />

    <p v-if="!overallChart && !player.point && !player.tir" class="no-data">
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
import {
  buildPlayerDistributionChart,
  playerDisplayName,
  playerToOverallBreakdown,
} from '../../composables/useMatchSummaryCharts'
import { avgSeverity, formatAvg } from '../../composables/usePlayerStatsCharts'
import ShotSuccessRate from '../stats/ShotSuccessRate.vue'
import MatchSummaryShotDrawer from './MatchSummaryShotDrawer.vue'

const props = defineProps<{
  player: MatchSummaryPlayer
}>()

const { t } = useI18n()

const displayName = computed(() => playerDisplayName(props.player))
const overallBreakdown = computed(() => playerToOverallBreakdown(props.player))
const overallChart = computed(() => buildPlayerDistributionChart(props.player, t))
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

.no-data {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
  text-align: center;
}
</style>
