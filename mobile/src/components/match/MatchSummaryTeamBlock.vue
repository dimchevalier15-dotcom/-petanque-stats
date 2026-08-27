<template>
  <div class="team-block">
    <div class="team-header app-card" :class="{ 'team-a': team === 'A', 'team-b': team === 'B' }">
      <div>
        <h3>{{ label }}</h3>
        <p v-if="average !== null" class="team-meta">
          {{ t('summary.teamAverage') }}
          <Tag :value="formatAvg(average)" :severity="avgSeverity(average)" />
        </p>
      </div>
    </div>

    <div v-if="chart" class="panel app-card">
      <h4>{{ t('summary.sections.teamDistribution') }}</h4>
      <ShotSuccessRate :breakdown="overall" />
      <div class="chart-box">
        <Chart type="bar" :data="chart.data" :options="chart.options" />
      </div>
      <MatchSummaryShotDrawer :point="point" :tir="tir" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Chart from 'primevue/chart'
import Tag from 'primevue/tag'
import type { MatchSummaryPlayer } from '../../models/MatchSummary'
import {
  buildTeamDistributionChart,
  mergeTeamBreakdown,
  mergeTeamShotBreakdown,
} from '../../composables/useMatchSummaryCharts'
import { avgSeverity, formatAvg } from '../../composables/usePlayerStatsCharts'
import ShotSuccessRate from '../stats/ShotSuccessRate.vue'
import MatchSummaryShotDrawer from './MatchSummaryShotDrawer.vue'

const props = defineProps<{
  team: 'A' | 'B'
  label: string
  players: MatchSummaryPlayer[]
}>()

const { t } = useI18n()

const overall = computed(() => mergeTeamBreakdown(props.players))
const point = computed(() => mergeTeamShotBreakdown(props.players, 'point'))
const tir = computed(() => mergeTeamShotBreakdown(props.players, 'tir'))
const average = computed(() => overall.value?.average ?? null)
const chart = computed(() => buildTeamDistributionChart(props.players, t))
</script>

<style scoped>
.team-block {
  display: grid;
  gap: var(--app-space-sm);
}

.team-header {
  padding: var(--app-space-md);
}

.team-header h3 {
  margin: 0;
  font-size: 1rem;
}

.team-a {
  border-left: 4px solid #22c55e;
}

.team-b {
  border-left: 4px solid #3b82f6;
}

.team-meta {
  margin: 0.375rem 0 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--app-text-muted);
}

.panel {
  padding: var(--app-space-md);
  display: grid;
  gap: var(--app-space-sm);
}

.panel h4 {
  margin: 0;
  font-size: 1rem;
}

.chart-box {
  height: 180px;
}
</style>
