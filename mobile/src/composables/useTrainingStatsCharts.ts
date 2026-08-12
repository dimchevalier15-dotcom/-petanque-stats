import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { TrainingStats, TrainingType } from '../models/Training'

interface ChartBundle {
  data: object
  options: object
}

const TYPE_COLORS: Record<TrainingType, string> = {
  point: '#2D8B6F',
  tir: '#1F6B88',
}

export function trainingSuccessSeverity(rate: number): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  if (rate >= 80) return 'help'
  if (rate >= 60) return 'success'
  if (rate >= 40) return 'warn'
  return 'danger'
}

function buildLineChart(labels: string[], values: number[], color: string, maxValue = 100): ChartBundle {
  return {
    data: {
      labels,
      datasets: [
        {
          data: values,
          borderColor: color,
          backgroundColor: `${color}33`,
          fill: true,
          tension: 0.3,
          pointRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          max: maxValue,
          ticks: { stepSize: maxValue > 20 ? 20 : 1 },
          grid: { display: false },
        },
        x: { grid: { display: false } },
      },
    },
  }
}

function buildHorizontalBarChart(labels: string[], values: number[], colors: string[], maxValue = 100): ChartBundle {
  return {
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: colors,
          borderRadius: 6,
          barThickness: 18,
        },
      ],
    },
    options: {
      indexAxis: 'y' as const,
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: {
          beginAtZero: true,
          max: maxValue,
          ticks: { stepSize: 20 },
          grid: { display: false },
        },
        y: { grid: { display: false } },
      },
    },
  }
}

export function useTrainingStatsCharts(
  stats: Ref<TrainingStats | null>,
  t: ComposerTranslation,
  typeFilter: Ref<TrainingType | 'all'>,
): {
  evolutionChart: ComputedRef<ChartBundle | null>
  typeChart: ComputedRef<ChartBundle | null>
  distanceChart: ComputedRef<ChartBundle | null>
} {
  const evolutionChart = computed(() => {
    if (!stats.value || stats.value.evolution.length < 2) return null
    const labels = stats.value.evolution.map((_, i) => String(i + 1))
    const values = stats.value.evolution.map((p) => p.successRate)
    return buildLineChart(labels, values, '#2D8B6F')
  })

  const typeChart = computed(() => {
    if (!stats.value || stats.value.byType.length === 0 || typeFilter.value !== 'all') return null
    const labels = stats.value.byType.map((row) => t(`training.types.${row.type}`))
    const values = stats.value.byType.map((row) => row.successRate)
    const colors = stats.value.byType.map((row) => TYPE_COLORS[row.type])
    return buildHorizontalBarChart(labels, values, colors)
  })

  const distanceChart = computed(() => {
    if (!stats.value || stats.value.byDistance.length === 0) return null
    const labels = stats.value.byDistance.map((d) => t('training.distanceMeters', { n: d.distance }))
    const values = stats.value.byDistance.map((d) => d.successRate)
    const colors = stats.value.byDistance.map((_, i) => `hsl(${160 + i * 12}, 45%, 42%)`)
    return buildHorizontalBarChart(labels, values, colors)
  })

  return { evolutionChart, typeChart, distanceChart }
}
