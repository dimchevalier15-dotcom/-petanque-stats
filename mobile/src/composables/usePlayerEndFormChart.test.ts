/**
 * @vitest-environment node
 */
import { describe, expect, it } from 'vitest'
import type { ComposerTranslation } from 'vue-i18n'
import type { BallNote, EndRecord } from '../models/MatchPlay'
import { buildPlayerEndFormSeries, formatMasters } from './usePlayerEndFormChart'

const t = ((key: string, params?: { n: number }) => {
  if (key === 'play.formChart.endLabel') {
    return `M${params?.n}`
  }
  return key
}) as ComposerTranslation

function completedEnd(
  index: number,
  notes: BallNote[],
  shotTypes: Array<'point' | 'tir'>,
  playerId = 1,
): EndRecord {
  return {
    index,
    winner: 'A',
    points: 1,
    balls: [
      {
        playerId,
        notes,
        shotTypes,
        distances: notes.map(() => null),
      },
    ],
  }
}

describe('formatMasters', () => {
  it('formats success over total', () => {
    expect(formatMasters({ success: 6, total: 8 })).toBe('6/8')
  })
})

describe('buildPlayerEndFormSeries masters', () => {
  it('counts +1 and +2 as successes and uses balls played as total', () => {
    const series = buildPlayerEndFormSeries(
      [
        completedEnd(1, [2, 1, 0, -1], ['point', 'point', 'point', 'point']),
        completedEnd(2, [1, -2, 2, 0], ['point', 'point', 'point', 'point']),
      ],
      2,
      1,
      t,
    )

    expect(series.pointMasters).toEqual({ success: 4, total: 8 })
    expect(formatMasters(series.pointMasters!)).toBe('4/8')
    expect(series.tirMasters).toBeNull()
  })

  it('splits point and tir independently', () => {
    const series = buildPlayerEndFormSeries(
      [
        completedEnd(1, [1, 0, 2, -1], ['point', 'point', 'tir', 'tir']),
        completedEnd(2, [2, 1, 0, 1], ['point', 'tir', 'tir', 'point']),
      ],
      2,
      1,
      t,
    )

    expect(series.pointMasters).toEqual({ success: 3, total: 4 })
    expect(series.tirMasters).toEqual({ success: 2, total: 4 })
  })

  it('treats 0, -1 and -2 as misses', () => {
    const series = buildPlayerEndFormSeries(
      [completedEnd(1, [0, -1, -2], ['tir', 'tir', 'tir'])],
      1,
      1,
      t,
    )

    expect(series.tirMasters).toEqual({ success: 0, total: 3 })
  })

  it('ignores the current unfinished end', () => {
    const series = buildPlayerEndFormSeries(
      [
        completedEnd(1, [1, 1], ['point', 'point']),
        {
          index: 2,
          balls: [
            {
              playerId: 1,
              notes: [2, 2],
              shotTypes: ['point', 'point'],
              distances: [null, null],
            },
          ],
        },
      ],
      1,
      1,
      t,
    )

    expect(series.pointMasters).toEqual({ success: 2, total: 2 })
  })
})
