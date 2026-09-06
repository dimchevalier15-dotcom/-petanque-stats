import type { BallNote, EndRecord, EndShot, TeamSide } from '../models/MatchPlay'
import { teamForPlayer } from './matchRoles'

export interface LiveShotFeedItem {
  sequenceOrder: number
  team: TeamSide
  playerId: number
  messageKey: string
  playerName: string
}

function noteToKey(note: BallNote): string {
  switch (note) {
    case 2:
      return 'plus2'
    case 1:
      return 'plus1'
    case 0:
      return 'zero'
    case -1:
      return 'minus1'
    case -2:
      return 'minus2'
  }
}

export function liveShotMessageKey(shot: EndShot): string {
  const category = shot.isCochonnet ? 'cochonnet' : shot.shotType
  return `live.view.feed.${category}.${noteToKey(shot.note)}`
}

export function buildLiveShotFeed(
  end: EndRecord,
  teamA: number[],
  teamB: number[],
  shortPlayerNames: Record<number, string>,
  playerNames: Record<number, string>,
): LiveShotFeedItem[] {
  return [...end.shots]
    .sort((a, b) => a.sequenceOrder - b.sequenceOrder)
    .flatMap((shot) => {
      const team = teamForPlayer(shot.playerId, teamA, teamB)
      if (!team) {
        return []
      }

      const playerName =
        shortPlayerNames[shot.playerId] ?? playerNames[shot.playerId] ?? `#${shot.playerId}`

      return [
        {
          sequenceOrder: shot.sequenceOrder,
          team,
          playerId: shot.playerId,
          messageKey: liveShotMessageKey(shot),
          playerName,
        },
      ]
    })
}
