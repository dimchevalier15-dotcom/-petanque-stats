import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import {
  RINGS_MAX_SESSION_SCORE,
  RINGS_TARGETS,
  RINGS_THROWS_PER_SESSION,
  type RingsStats,
  type RingsStatsTargetRow,
} from '../models/Rings'

interface ChartBundle {
  data: object
  options: object
}

const TARGET_COLORS: Record<string, string> = {
  far: '#6366F1',
  middle: '#2D8B6F',
  near: '#B8923A',
}

export function sessionScoreSeverity(
  score: number,
  maxScore: number = RINGS_MAX_SESSION_SCORE,
): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  const ratio = maxScore > 0 ? score / maxScore : 0
  if (ratio >= 0.8) return 'help'
  if (ratio >= 0.6) return 'success'
  if (ratio >= 0.4) return 'warn'
  return 'danger'
}

export function successRateSeverity(rate: number): 'secondary' | 'danger' | 'warn' | 'success' {
  if (rate >= 75) return 'success'
  if (rate >= 50) return 'warn'
  return 'danger'
}

export function decileLabel(t: ComposerTranslation, min: number, max: number): string {
  return t('rings.stats.deciles.range', { min, max })
}

export function targetDistanceLabel(t: ComposerTranslation, target: string): string {
  const map: Record<string, number> = { near: 6.5, middle: 7.5, far: 8.5 }
  return t('rings.distanceMeters', { n: map[target] ?? target })
}

export function useRingsStatsCharts(stats: Ref<RingsStats | null>, t: ComposerTranslation) {
  const showEvolution = computed(() => (stats.value?.evolution.length ?? 0) >= 2)
  const maxSessionScore = computed(() => stats.value?.summary.maxSessionScore ?? RINGS_MAX_SESSION_SCORE)
  const maxThrowScore = computed(() => maxSessionScore.value / RINGS_THROWS_PER_SESSION)

  function orderedTargets(): RingsStatsTargetRow[] {
    const rows = stats.value?.byTarget ?? []
    return RINGS_TARGETS.map((key) => rows.find((r) => r.target === key)).filter(Boolean) as RingsStatsTargetRow[]
  }

  const strongestTarget = computed((): RingsStatsTargetRow | null => {
    const rows = stats.value?.byTarget.filter((r) => r.attemptCount > 0) ?? []
    if (rows.length === 0) return null
    return [...rows].sort((a, b) => b.successRate - a.successRate)[0] ?? null
  })

  const weakestTarget = computed((): RingsStatsTargetRow | null => {
    const rows = stats.value?.byTarget.filter((r) => r.attemptCount > 0) ?? []
    if (rows.length === 0) return null
    return [...rows].sort((a, b) => a.successRate - b.successRate)[0] ?? null
  })

  const evolutionChart: ComputedRef<ChartBundle | null> = computed(() => {
    const points = stats.value?.evolution ?? []
    if (points.length < 2) return null

    return {
      data: {
        labels: points.map((p) =>
          new Date(p.date).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }),
        ),
        datasets: [
          {
            data: points.map((p) => p.totalScore),
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
          y: { beginAtZero: true, max: maxSessionScore.value, ticks: { stepSize: maxSessionScore.value / 5 } },
          x: { grid: { display: false } },
        },
      },
    }
  })

  const decileChart: ComputedRef<ChartBundle | null> = computed(() => {
    const rows = stats.value?.scoreByDecile ?? []
    if (rows.length === 0) return null

    return {
      data: {
        labels: rows.map((d) => decileLabel(t, d.minScore, d.maxScore)),
        datasets: [
          {
            data: rows.map((d) => d.sessionCount),
            backgroundColor: '#1F6B88',
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } },
          x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } } },
        },
      },
    }
  })

  const targetSuccessChart: ComputedRef<ChartBundle | null> = computed(() => {
    const ordered = orderedTargets()
    if (ordered.length === 0) return null

    return {
      data: {
        labels: ordered.map((r) => targetDistanceLabel(t, r.target)),
        datasets: [
          {
            data: ordered.map((r) => r.successRate),
            backgroundColor: ordered.map((r) => TARGET_COLORS[r.target] ?? '#94A3B8'),
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

  const targetCarreauChart: ComputedRef<ChartBundle | null> = computed(() => {
    const ordered = orderedTargets()
    if (ordered.length === 0) return null

    return {
      data: {
        labels: ordered.map((r) => targetDistanceLabel(t, r.target)),
        datasets: [
          {
            data: ordered.map((r) => (r.attemptCount > 0 ? Math.round((r.carreauCount / r.attemptCount) * 1000) / 10 : 0)),
            backgroundColor: '#8B5CF6',
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

  const targetAverageChart: ComputedRef<ChartBundle | null> = computed(() => {
    const ordered = orderedTargets()
    if (ordered.length === 0) return null

    return {
      data: {
        labels: ordered.map((r) => targetDistanceLabel(t, r.target)),
        datasets: [
          {
            data: ordered.map((r) => r.averagePointsPerAttempt),
            backgroundColor: ordered.map((r) => TARGET_COLORS[r.target] ?? '#94A3B8'),
            borderRadius: 6,
          },
        ],
      },
      options: {
        indexAxis: 'y' as const,
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { beginAtZero: true, max: maxThrowScore.value, ticks: { stepSize: maxThrowScore.value > 5 ? 2 : 1 } },
          y: { grid: { display: false } },
        },
      },
    }
  })

  return {
    showEvolution,
    evolutionChart,
    decileChart,
    targetSuccessChart,
    targetCarreauChart,
    targetAverageChart,
    strongestTarget,
    weakestTarget,
  }
}
