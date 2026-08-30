import type { MatchType, ShotType, StatisticsMode } from '../../models/Match'
import type { PlayerRole } from '../../models/Match'

export interface DefaultShotTypeDto {
  playerId: number
  defaultShotType: ShotType
}

export interface StartingRoleDto {
  playerId: number
  role: PlayerRole
}

export interface CreateMatchRequestDto {
  type: MatchType
  targetScore: number
  teamA: number[]
  teamB: number[]
  teamAName?: string | null
  teamBName?: string | null
  statisticsMode: StatisticsMode
  trackedPlayers: number[]
  defaultShotTypes?: DefaultShotTypeDto[]
  startingRoles?: StartingRoleDto[]
  /** ISO-8601 date-time the match started, since the match is sent once finished. */
  playedAt?: string | null
}
