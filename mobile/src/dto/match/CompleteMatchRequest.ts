import type { MatchType, StatisticsMode } from '../../models/Match'
import type { PlayerRole } from '../../models/Match'
import type { BallNote, TeamSide, TeamSubstitution } from '../../models/MatchPlay'

export interface EndBallDto {
  playerId: number
  notes: BallNote[]
  shotTypes: ('point' | 'tir')[]
  distances?: (number | null)[]
}

export interface EndRoleDto {
  playerId: number
  role: PlayerRole
}

export interface EndDto {
  index: number
  balls: EndBallDto[]
  winner: TeamSide
  points: number
  canceled?: boolean
  roles?: EndRoleDto[]
}

export interface CompleteMatchRequestDto {
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  substitutions?: TeamSubstitution[]
  openingScoreA?: number
  openingScoreB?: number
  ends: EndDto[]
}
