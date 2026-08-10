import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { MatchSummaryShotBreakdown } from '../models/MatchSummary'
import type { PlayerStats } from '../models/PlayerStats'

interface ChartBundle {
  data: object
  options: object
}

const NOTE_COLORS = {
  p2: '#3B82F6',
  p1: '#22C55E',
  p0: '#94A3B8',
  m1: '#F59E0B',
  m2: '#EF4444',
} as const

function breakdownToCounts(b: MatchSummaryShotBreakdown): number[] {
  return [b.m2, b.m1, b.p0, b.p1, b.p2]
}

export function breakdownBallCount(b: MatchSummaryShotBreakdown): number {
  return b.p2 + b.p1 + b.p0 + b.m1 + b.m2
}

export function buildNoteDistributionChart(
  breakdown: MatchSummaryShotBreakdown | null,
  t: ComposerTranslation,
): ChartBundle | null {
  if (!breakdown) {
    return null
  }
  const total = breakdownBallCount(breakdown)
  if (total === 0) {
    return null
  }

  const noteLabels = [
    t('stats.notes.m2'),
    t('stats.notes.m1'),
    t('stats.notes.p0'),
    t('stats.notes.p1'),
    t('stats.notes.p2'),
  ]

  return {
    data: {
      labels: noteLabels,
      datasets: [
        {
          data: breakdownToCounts(breakdown),
          backgroundColor: [NOTE_COLORS.m2, NOTE_COLORS.m1, NOTE_COLORS.p0, NOTE_COLORS.p1, NOTE_COLORS.p2],
          borderRadius: 6,
          barThickness: 14,
        },
      ],
    },
    options: {
      indexAxis: 'y' as const,
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { precision: 0 },
          grid: { display: false },
        },
        y: {
          grid: { display: false },
        },
      },
    },
  }
}

export function usePlayerStatsCharts(
  stats: Ref<PlayerStats | null>,
  t: ComposerTranslation,
): {
  showEvolution: ComputedRef<boolean>
  showDistribution: ComputedRef<boolean>
  evolutionChart: ComputedRef<ChartBundle | null>
  distributionChart: ComputedRef<ChartBundle | null>
  pointDistributionChart: ComputedRef<ChartBundle | null>
  tirDistributionChart: ComputedRef<ChartBundle | null>
} {
  const showEvolution = computed(() => (stats.value?.evolution.length ?? 0) >= 2)
  const showDistribution = computed(() => (stats.value?.overall?.p2 ?? 0) + (stats.value?.overall?.p1 ?? 0) + (stats.value?.overall?.p0 ?? 0) + (stats.value?.overall?.m1 ?? 0) + (stats.value?.overall?.m2 ?? 0) > 0)

  function buildDistributionChart(breakdown: MatchSummaryShotBreakdown | null): ChartBundle | null {
    return buildNoteDistributionChart(breakdown, t)
  }

  const evolutionChart = computed<ChartBundle | null>(() => {
    const points = stats.value?.evolution ?? []
    if (points.length < 2) return null

    const recent = points.slice(-10)
    return {
      data: {
        labels: recent.map((_, i) => t('stats.evolution.matchLabel', { n: i + 1 })),
        datasets: [
          {
            label: t('stats.evolution.average'),
            data: recent.map((p) => p.average),
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.12)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: recent.map((p) => (p.victory ? '#22C55E' : '#EF4444')),
            pointRadius: 5,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          y: {
            suggestedMin: -2,
            suggestedMax: 2,
            grid: { color: 'rgba(0,0,0,0.06)' },
          },
          x: {
            grid: { display: false },
          },
        },
      },
    }
  })

  const distributionChart = computed(() => buildDistributionChart(stats.value?.overall ?? null))
  const pointDistributionChart = computed(() => buildDistributionChart(stats.value?.point ?? null))
  const tirDistributionChart = computed(() => buildDistributionChart(stats.value?.tir ?? null))

  return {
    showEvolution,
    showDistribution,
    evolutionChart,
    distributionChart,
    pointDistributionChart,
    tirDistributionChart,
  }
}

export function avgSeverity(n?: number): 'danger' | 'warn' | 'secondary' | 'success' | 'help' | undefined {
  const v = n ?? 0
  if (v >= 1) return 'help'
  if (v > 0) return 'success'
  if (v === 0) return 'secondary'
  if (v > -1) return 'warn'
  return 'danger'
}

export function formatAvg(n: number): string {
  return n.toFixed(2)
}

export function natureLabel(t: ComposerTranslation, nature: string): string {
  const key = `context.nature.${nature}`
  const translated = t(key)
  return translated === key ? nature : translated
}
