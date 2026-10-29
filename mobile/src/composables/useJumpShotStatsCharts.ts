import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { JumpShotStats } from '../models/JumpShot'
import { JUMP_SHOT_DISTANCES, JUMP_SHOT_THROW_RESULTS } from '../models/JumpShot'

interface ChartBundle {
  data: object
  options: object
}

const RESULT_COLORS: Record<string, string> = {
  missed: '#EF4444',
  chique: '#F59E0B',
  hit: '#EAB308',
  carreau: '#22C55E',
}

export function successRateSeverity(rate: number): 'secondary' | 'danger' | 'warn' | 'success' {
  if (rate >= 75) return 'success'
  if (rate >= 50) return 'warn'
  return 'danger'
}

export function useJumpShotStatsCharts(stats: Ref<JumpShotStats | null>, t: ComposerTranslation) {
  const showEvolution = computed(() => (stats.value?.evolution.length ?? 0) >= 2)

  const totalResults = computed(() =>
    stats.value?.byResult.reduce((acc, row) => acc + row.count, 0) ?? 0,
  )

  const strongestDistance = computed(() => {
    const rows = stats.value?.byDistance ?? []
    if (rows.length === 0) return null
    return [...rows].sort((a, b) => b.successRate - a.successRate)[0]
  })

  const weakestDistance = computed(() => {
    const rows = stats.value?.byDistance ?? []
    if (rows.length === 0) return null
    return [...rows].sort((a, b) => a.successRate - b.successRate)[0]
  })

  const evolutionChart: ComputedRef<ChartBundle | null> = computed(() => {
    const points = stats.value?.evolution ?? []
    if (points.length < 2) return null
    const maxSeries = Math.max(...points.map((p) => p.seriesCount), 4)

    return {
      data: {
        labels: points.map((p) =>
          new Date(p.date).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }),
        ),
        datasets: [
          {
            data: points.map((p) => p.seriesCount),
            borderColor: '#1F6B88',
            backgroundColor: 'rgba(31, 107, 136, 0.15)',
            fill: true,
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: maxSeries + 1, ticks: { stepSize: 1 } },
          x: { grid: { display: false } },
        },
      },
    }
  })

  const distanceChart: ComputedRef<ChartBundle | null> = computed(() => {
    const rows = stats.value?.byDistance ?? []
    if (rows.length === 0) return null

    const ordered = JUMP_SHOT_DISTANCES.map((d) => rows.find((r) => r.distance === d)).filter(Boolean) as NonNullable<
      (typeof rows)[number]
    >[]

    return {
      data: {
        labels: ordered.map((r) => t('jumpShot.stats.distanceMeters', { n: r.distance })),
        datasets: [
          {
            data: ordered.map((r) => r.successRate),
            backgroundColor: '#2D8B6F',
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, max: 100, ticks: { callback: (v: number) => `${v}%` } },
          x: { grid: { display: false } },
        },
      },
    }
  })

  const resultChart: ComputedRef<ChartBundle | null> = computed(() => {
    const rows = stats.value?.byResult ?? []
    if (rows.length === 0) return null

    const ordered = JUMP_SHOT_THROW_RESULTS.map((key) => rows.find((r) => r.result === key)).filter(Boolean) as NonNullable<
      (typeof rows)[number]
    >[]

    return {
      data: {
        labels: ordered.map((r) => t(`jumpShot.results.${r.result}`)),
        datasets: [
          {
            data: ordered.map((r) => r.count),
            backgroundColor: ordered.map((r) => RESULT_COLORS[r.result] ?? '#94A3B8'),
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' as const } },
      },
    }
  })

  return {
    showEvolution,
    evolutionChart,
    distanceChart,
    resultChart,
    strongestDistance,
    weakestDistance,
    totalResults,
  }
}
