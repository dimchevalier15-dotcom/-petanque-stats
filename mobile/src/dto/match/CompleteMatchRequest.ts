import type { MatchType, StatisticsMode } from '../../models/Match'
import type { PlayerRole } from '../../models/Match'
import type { BallNote, TeamSide, TeamSubstitution } from '../../models/MatchPlay'

export interface EndShotDto {
  sequenceOrder: number
  playerId: number
  note: BallNote
  shotType: 'point' | 'tir'
  distance?: number | null
  isCochonnet?: boolean
}

export interface EndRoleDto {
  playerId: number
  role: PlayerRole
}

export interface EndDto {
  index: number
  shots: EndShotDto[]
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
