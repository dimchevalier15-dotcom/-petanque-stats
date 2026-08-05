import type { MatchType, StatisticsMode } from '../../models/Match'
import type { BallNote, TeamSide } from '../../models/MatchPlay'

export interface EndBallDto {
  playerId: number
  notes: BallNote[]
}

export interface EndDto {
  index: number
  balls: EndBallDto[]
  winner: TeamSide
  points: number
}

export interface CompleteMatchRequestDto {
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  ends: EndDto[]
}
