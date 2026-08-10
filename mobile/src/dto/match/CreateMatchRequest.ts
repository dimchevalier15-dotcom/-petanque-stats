import type { MatchType, ShotType, StatisticsMode } from '../../models/Match'

export interface DefaultShotTypeDto {
  playerId: number
  defaultShotType: ShotType
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
}
