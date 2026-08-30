<template>
  <details v-if="pointChart || tirChart || cochonnetChart" class="shots-drawer">
    <summary class="shots-drawer-summary">{{ t('summary.sections.shotDetails') }}</summary>
    <div class="shots-grid">
      <div v-if="pointChart && point" class="shot-block">
        <div class="shot-head">
          <span>{{ t('play.shots.point') }}</span>
          <Tag :value="formatAvg(point.average)" :severity="avgSeverity(point.average)" />
        </div>
        <ShotSuccessRate :breakdown="point" />
        <div class="chart-box chart-box-sm">
          <Chart type="bar" :data="pointChart.data" :options="pointChart.options" />
        </div>
      </div>

      <div v-if="tirChart && tir" class="shot-block">
        <div class="shot-head">
          <span>{{ t('play.shots.tir') }}</span>
          <Tag :value="formatAvg(tir.average)" :severity="avgSeverity(tir.average)" />
        </div>
        <ShotSuccessRate :breakdown="tir" />
        <div class="chart-box chart-box-sm">
          <Chart type="bar" :data="tirChart.data" :options="tirChart.options" />
        </div>
      </div>

      <div v-if="cochonnetChart && cochonnet" class="shot-block">
        <div class="shot-head">
          <span>{{ t('play.shots.cochonnet') }}</span>
          <Tag :value="formatAvg(cochonnet.average)" :severity="avgSeverity(cochonnet.average)" />
        </div>
        <ShotSuccessRate :breakdown="cochonnet" />
        <div class="chart-box chart-box-sm">
          <Chart type="bar" :data="cochonnetChart.data" :options="cochonnetChart.options" />
        </div>
      </div>
    </div>
  </details>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Chart from 'primevue/chart'
import Tag from 'primevue/tag'
import type { MatchSummaryShotBreakdown } from '../../models/MatchSummary'
import { buildPlayerShotChart } from '../../composables/useMatchSummaryCharts'
import { avgSeverity, formatAvg } from '../../composables/usePlayerStatsCharts'
import ShotSuccessRate from '../stats/ShotSuccessRate.vue'

const props = defineProps<{
  point?: MatchSummaryShotBreakdown | null
  tir?: MatchSummaryShotBreakdown | null
  cochonnet?: MatchSummaryShotBreakdown | null
}>()

const { t } = useI18n()

const pointChart = computed(() => buildPlayerShotChart(props.point, t))
const tirChart = computed(() => buildPlayerShotChart(props.tir, t))
const cochonnetChart = computed(() => buildPlayerShotChart(props.cochonnet, t))
</script>

<style scoped>
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

.chart-box-sm {
  height: 120px;
}
</style>
