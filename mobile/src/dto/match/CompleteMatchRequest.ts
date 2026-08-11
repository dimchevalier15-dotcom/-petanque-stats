import type { MatchType, StatisticsMode } from '../../models/Match'
import type { BallNote, TeamSide } from '../../models/MatchPlay'

export interface EndBallDto {
  playerId: number
  notes: BallNote[]
  shotTypes: ('point' | 'tir')[]
  // Optional distance in meters per ball, aligned with notes by index.
  distances?: (number | null)[]
}

export interface EndDto {
  index: number
  balls: EndBallDto[]
  winner: TeamSide
  points: number
  canceled?: boolean
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
