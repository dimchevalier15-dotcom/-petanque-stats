import { computed, type ComputedRef, type Ref } from 'vue'
import type { ComposerTranslation } from 'vue-i18n'
import type { BallNote, EndRecord } from '../models/MatchPlay'
import { isCochonnetShot } from '../utils/matchBallFlags'
import { hasAnyPlayedShot, shotsForPlayer } from '../utils/matchEndShots'
import { formatMasters, type MastersScore } from './matchSuccessRate'

export type { MastersScore }
export { formatMasters }

export interface PlayerEndFormSeries {
  labels: string[]
  totals: number[]
  pointAverage: number | null
  tirAverage: number | null
  pointMasters: MastersScore | null
  tirMasters: MastersScore | null
  cochonnetMasters: MastersScore | null
  hasData: boolean
}

function getCompletedEnds(ends: EndRecord[], currentEndIndex: number): EndRecord[] {
  return ends.slice(0, currentEndIndex).filter((end) => {
    if (end.canceled) {
      return hasAnyPlayedShot(end)
    }

    return end.winner !== undefined && end.points !== undefined
  })
}

function sumAllNotes(end: EndRecord, playerId: number): number | null {
  const playerShots = shotsForPlayer(end, playerId)
  if (playerShots.length === 0) {
    return null
  }

  return playerShots.reduce((acc, shot) => acc + shot.note, 0)
}

function forEachShotNote(
  completedEnds: EndRecord[],
  playerId: number,
  shotType: 'point' | 'tir',
  fn: (note: BallNote) => void,
): void {
  for (const end of completedEnds) {
    const playerShots = shotsForPlayer(end, playerId)
    for (let i = 0; i < playerShots.length; i++) {
      const shot = playerShots[i]!
      if (shot.shotType !== shotType || isCochonnetShot(end, playerId, i)) {
        continue
      }
      fn(shot.note)
    }
  }
}

function forEachCochonnetNote(
  completedEnds: EndRecord[],
  playerId: number,
  fn: (note: BallNote) => void,
): void {
  for (const end of completedEnds) {
    const playerShots = shotsForPlayer(end, playerId)
    for (let i = 0; i < playerShots.length; i++) {
      if (!isCochonnetShot(end, playerId, i)) {
        continue
      }
      fn(playerShots[i]!.note)
    }
  }
}

function shotTypeAverage(
  completedEnds: EndRecord[],
  playerId: number,
  shotType: 'point' | 'tir',
): number | null {
  let sum = 0
  let count = 0

  forEachShotNote(completedEnds, playerId, shotType, (note) => {
    sum += note
    count++
  })

  return count > 0 ? sum / count : null
}

function shotTypeMasters(
  completedEnds: EndRecord[],
  playerId: number,
  shotType: 'point' | 'tir',
): MastersScore | null {
  let success = 0
  let total = 0

  forEachShotNote(completedEnds, playerId, shotType, (note) => {
    total++
    if (note >= 1) {
      success++
    }
  })

  return total > 0 ? { success, total } : null
}

function cochonnetMasters(completedEnds: EndRecord[], playerId: number): MastersScore | null {
  let success = 0
  let total = 0

  forEachCochonnetNote(completedEnds, playerId, (note) => {
    total++
    if (note >= 1) {
      success++
    }
  })

  return total > 0 ? { success, total } : null
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
    pointMasters: shotTypeMasters(completedEnds, playerId, 'point'),
    tirMasters: shotTypeMasters(completedEnds, playerId, 'tir'),
    cochonnetMasters: cochonnetMasters(completedEnds, playerId),
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
