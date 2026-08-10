import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { EndRecord } from '../models/MatchPlay'

export interface PlayerEndFormSeries {
  labels: string[]
  totals: number[]
  pointAverage: number | null
  tirAverage: number | null
  hasData: boolean
}

function getCompletedEnds(ends: EndRecord[], currentEndIndex: number): EndRecord[] {
  return ends.slice(0, currentEndIndex).filter((end) => {
    if (end.canceled) {
      return end.balls.some((entry) => entry.notes.length > 0)
    }

    return end.winner !== undefined && end.points !== undefined
  })
}

function sumAllNotes(end: EndRecord, playerId: number): number | null {
  const entry = end.balls.find((b) => b.playerId === playerId)
  if (!entry || entry.notes.length === 0) {
    return null
  }

  return entry.notes.reduce((acc, note) => acc + note, 0)
}

function shotTypeAverage(
  completedEnds: EndRecord[],
  playerId: number,
  shotType: 'point' | 'tir',
): number | null {
  let sum = 0
  let count = 0

  for (const end of completedEnds) {
    const entry = end.balls.find((b) => b.playerId === playerId)
    if (!entry) {
      continue
    }
    for (let i = 0; i < entry.notes.length; i++) {
      if (entry.shotTypes[i] === shotType) {
        sum += entry.notes[i]
        count++
      }
    }
  }

  return count > 0 ? sum / count : null
}

export function buildPlayerEndFormSeries(
  ends: EndRecord[],
  currentEndIndex: number,
  playerId: number,
  t: ComposerTranslation,
): PlayerEndFormSeries {
  const completedEnds = getCompletedEnds(ends, currentEndIndex)

  const labels: string[] = []
  const totals: number[] = []

  for (const end of completedEnds) {
    const total = sumAllNotes(end, playerId)
    if (total === null) {
      continue
    }
    labels.push(t('play.formChart.endLabel', { n: end.index }))
    totals.push(total)
  }

  return {
    labels,
    totals,
    pointAverage: shotTypeAverage(completedEnds, playerId, 'point'),
    tirAverage: shotTypeAverage(completedEnds, playerId, 'tir'),
    hasData: totals.length > 0,
  }
}

interface ChartBundle {
  data: object
  options: object
}

export function formatFormAvg(value: number): string {
  return value.toFixed(2)
}

export function usePlayerEndFormChart(
  ends: Ref<EndRecord[]>,
  currentEndIndex: Ref<number>,
  selectedPlayerId: Ref<number | null>,
  t: ComposerTranslation,
): {
  series: ComputedRef<PlayerEndFormSeries | null>
  chart: ComputedRef<ChartBundle | null>
} {
  const series = computed(() => {
    const playerId = selectedPlayerId.value
    if (playerId === null) {
      return null
    }

    return buildPlayerEndFormSeries(ends.value, currentEndIndex.value, playerId, t)
  })

  const chart = computed<ChartBundle | null>(() => {
    const data = series.value
    if (!data || !data.hasData || data.labels.length === 0) {
      return null
    }

    return {
      data: {
        labels: data.labels,
        datasets: [
          {
            label: t('play.formChart.total'),
            data: data.totals,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.12)',
            fill: true,
            tension: 0.25,
            pointRadius: 4,
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
            grid: { color: 'rgba(0,0,0,0.06)' },
          },
          x: {
            grid: { display: false },
          },
        },
      },
    }
  })

  return { series, chart }
}
