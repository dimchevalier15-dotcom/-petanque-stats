import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { ShootingStats, ShootingStatsWorkshop, ShootingStatsDistance } from '../models/Shooting'
import { SHOOTING_DISTANCES, SHOOTING_WORKSHOPS } from '../models/Shooting'

interface ChartBundle {
  data: object
  options: object
}

const RESULT_COLORS: Record<string, string> = {
  missed: '#EF4444',
  touched: '#F59E0B',
  successful: '#22C55E',
  carreau: '#6366F1',
}

const WORKSHOP_COLORS = ['#1F6B88', '#2D8B6F', '#B8923A', '#7C5C3C', '#4F46E5']

export function sessionScoreSeverity(score: number): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  if (score >= 80) return 'help'
  if (score >= 60) return 'success'
  if (score >= 40) return 'warn'
  return 'danger'
}

export function shotScoreSeverity(score: number): 'secondary' | 'danger' | 'warn' | 'success' | 'help' {
  if (score >= 4) return 'help'
  if (score >= 3) return 'success'
  if (score >= 1) return 'warn'
  return 'danger'
}

export function formatShotScore(n: number): string {
  return n.toFixed(2)
}

export function workshopLabel(t: ComposerTranslation, workshop: number): string {
  const keys = ['ballAlone', 'ballBehindJack', 'betweenTwoBalls', 'jumpedBall', 'jack']
  return t(`shooting.workshops.${keys[workshop - 1]}`)
}

export function heatmapCellColor(averageScore: number): string {
  const ratio = Math.min(1, Math.max(0, averageScore / 5))
  const r = Math.round(239 - ratio * (239 - 34))
  const g = Math.round(68 + ratio * (197 - 68))
  const b = Math.round(68 + ratio * (94 - 68))
  return `rgb(${r}, ${g}, ${b})`
}

function buildHorizontalBarChart(
  labels: string[],
  values: number[],
  colors: string[],
  maxValue = 5,
): ChartBundle {
  return {
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: colors,
          borderRadius: 6,
          barThickness: 16,
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
          ticks: { stepSize: 1 },
          grid: { display: false },
        },
        y: { grid: { display: false } },
      },
    },
  }
}

export function useShootingStatsCharts(
  stats: Ref<ShootingStats | null>,
  t: ComposerTranslation,
): {
  showEvolution: ComputedRef<boolean>
  evolutionChart: ComputedRef<ChartBundle | null>
  workshopChart: ComputedRef<ChartBundle | null>
  distanceChart: ComputedRef<ChartBundle | null>
  resultChart: ComputedRef<ChartBundle | null>
  strongestWorkshop: ComputedRef<ShootingStatsWorkshop | null>
  weakestWorkshop: ComputedRef<ShootingStatsWorkshop | null>
  strongestDistance: ComputedRef<ShootingStatsDistance | null>
  weakestDistance: ComputedRef<ShootingStatsDistance | null>
  heatmapGrid: ComputedRef<Array<{ workshop: number; distance: number; averageScore: number; shotCount: number }>>
  totalResults: ComputedRef<number>
} {
  const showEvolution = computed(() => (stats.value?.evolution.length ?? 0) >= 2)

  const strongestWorkshop = computed(() => {
    const items = stats.value?.byWorkshop ?? []
    if (items.length === 0) return null
    return items.reduce((best, item) => (item.averageScore > best.averageScore ? item : best))
  })

  const weakestWorkshop = computed(() => {
    const items = stats.value?.byWorkshop ?? []
    if (items.length === 0) return null
    return items.reduce((worst, item) => (item.averageScore < worst.averageScore ? item : worst))
  })

  const strongestDistance = computed(() => {
    const items = stats.value?.byDistance ?? []
    if (items.length === 0) return null
    return items.reduce((best, item) => (item.averageScore > best.averageScore ? item : best))
  })

  const weakestDistance = computed(() => {
    const items = stats.value?.byDistance ?? []
    if (items.length === 0) return null
    return items.reduce((worst, item) => (item.averageScore < worst.averageScore ? item : worst))
  })

  const totalResults = computed(() =>
    (stats.value?.byResult ?? []).reduce((sum, r) => sum + r.count, 0),
  )

  const heatmapGrid = computed(() => {
    const cells = stats.value?.heatmap ?? []
    const map = new Map(cells.map((c) => [`${c.workshop}-${c.distance}`, c]))
    const grid: Array<{ workshop: number; distance: number; averageScore: number; shotCount: number }> = []
    for (const workshop of SHOOTING_WORKSHOPS) {
      for (const distance of SHOOTING_DISTANCES) {
        const cell = map.get(`${workshop}-${distance}`)
        grid.push({
          workshop,
          distance,
          averageScore: cell?.averageScore ?? 0,
          shotCount: cell?.shotCount ?? 0,
        })
      }
    }
    return grid
  })

  const evolutionChart = computed<ChartBundle | null>(() => {
    const points = stats.value?.evolution ?? []
    if (points.length < 2) return null
    const slice = points.slice(-12)
    return {
      data: {
        labels: slice.map((_, i) => String(i + 1)),
        datasets: [
          {
            data: slice.map((p) => p.totalScore),
            borderColor: '#1F6B88',
            backgroundColor: 'rgba(31, 107, 88, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: slice.map((p) =>
              p.totalScore >= 80 ? '#6366F1' : p.totalScore >= 60 ? '#22C55E' : '#F59E0B',
            ),
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
            max: 100,
            ticks: { stepSize: 20 },
            grid: { color: 'rgba(0,0,0,0.06)' },
          },
          x: { grid: { display: false } },
        },
      },
    }
  })

  const workshopChart = computed<ChartBundle | null>(() => {
    const items = stats.value?.byWorkshop ?? []
    if (items.length === 0) return null
    const labels = items.map((w) => workshopLabel(t, w.workshop))
    const values = items.map((w) => w.averageScore)
    const colors = items.map((w) => WORKSHOP_COLORS[w.workshop - 1] ?? '#94A3B8')
    return buildHorizontalBarChart(labels, values, colors)
  })

  const distanceChart = computed<ChartBundle | null>(() => {
    const items = stats.value?.byDistance ?? []
    if (items.length === 0) return null
    const labels = items.map((d) => t('shooting.distanceMeters', { n: d.distance }))
    const values = items.map((d) => d.averageScore)
    const colors = ['#0EA5E9', '#0284C7', '#0369A1', '#075985']
    return buildHorizontalBarChart(labels, values, colors)
  })

  const resultChart = computed<ChartBundle | null>(() => {
    const items = stats.value?.byResult ?? []
    if (items.length === 0) return null
    const order: Array<'missed' | 'touched' | 'successful' | 'carreau'> = [
      'missed',
      'touched',
      'successful',
      'carreau',
    ]
    const sorted = order
      .map((result) => items.find((r) => r.result === result))
      .filter((r): r is NonNullable<typeof r> => r !== undefined)
    const labels = sorted.map((r) => t(`shooting.results.${r.result}`))
    const values = sorted.map((r) => r.count)
    const colors = sorted.map((r) => RESULT_COLORS[r.result] ?? '#94A3B8')
    const max = Math.max(...values, 1)
    return buildHorizontalBarChart(labels, values, colors, max)
  })

  return {
    showEvolution,
    evolutionChart,
    workshopChart,
    distanceChart,
    resultChart,
    strongestWorkshop,
    weakestWorkshop,
    strongestDistance,
    weakestDistance,
    heatmapGrid,
    totalResults,
  }
}
