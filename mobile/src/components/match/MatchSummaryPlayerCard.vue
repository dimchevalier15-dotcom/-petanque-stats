<template>
  <article class="player-card app-card">
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
      <div class="chart-box">
        <Chart type="bar" :data="overallChart.data" :options="overallChart.options" />
      </div>
    </div>

    <div v-if="pointChart || tirChart" class="shots-grid">
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
} from '../../composables/useMatchSummaryCharts'
import { avgSeverity, formatAvg } from '../../composables/usePlayerStatsCharts'
import ShotSuccessRate from '../stats/ShotSuccessRate.vue'

const props = defineProps<{
  player: MatchSummaryPlayer
}>()

const { t } = useI18n()

const displayName = computed(() => playerDisplayName(props.player))
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

.player-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
}

.player-name {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
  line-height: 1.3;
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
  position: relative;
  height: 150px;
}

.chart-box-sm {
  height: 120px;
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
