// @vitest-environment node
import { describe, expect, it } from 'vitest'
import type { EndRecord } from '../models/MatchPlay'
import { buildLiveShotFeed, liveShotMessageKey } from './liveShotFeed'

describe('liveShotFeed', () => {
  it('maps shot type and note to i18n keys', () => {
    expect(
      liveShotMessageKey({
        sequenceOrder: 1,
        playerId: 1,
        note: 1,
        shotType: 'tir',
        distance: null,
      }),
    ).toBe('live.view.feed.tir.plus1')

    expect(
      liveShotMessageKey({
        sequenceOrder: 1,
        playerId: 1,
        note: -1,
        shotType: 'point',
        distance: null,
      }),
    ).toBe('live.view.feed.point.minus1')

    expect(
      liveShotMessageKey({
        sequenceOrder: 1,
        playerId: 1,
        note: 2,
        shotType: 'tir',
        distance: null,
        isCochonnet: true,
      }),
    ).toBe('live.view.feed.cochonnet.plus2')
  })

  it('builds chronological feed items with team and player name', () => {
    const end: EndRecord = {
      index: 1,
      shots: [
        { sequenceOrder: 2, playerId: 2, note: -1, shotType: 'tir', distance: null },
        { sequenceOrder: 1, playerId: 1, note: 1, shotType: 'point', distance: null },
      ],
    }

    const feed = buildLiveShotFeed(end, [1], [2], { 1: 'Paul', 2: 'Matej' }, {})

    expect(feed).toEqual([
      {
        sequenceOrder: 1,
        team: 'A',
        playerId: 1,
        messageKey: 'live.view.feed.point.plus1',
        playerName: 'Paul',
      },
      {
        sequenceOrder: 2,
        team: 'B',
        playerId: 2,
        messageKey: 'live.view.feed.tir.minus1',
        playerName: 'Matej',
      },
    ])
  })
})
