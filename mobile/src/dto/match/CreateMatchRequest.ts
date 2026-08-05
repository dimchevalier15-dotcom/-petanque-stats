import type { MatchType, StatisticsMode } from '../../models/Match'

export interface CreateMatchRequestDto {
  type: MatchType
  targetScore: number
  teamA: number[]
  teamB: number[]
  statisticsMode: StatisticsMode
  trackedPlayers: number[]
}
