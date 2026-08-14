import type { MatchSummaryShotBreakdownDto } from '../match/MatchSummaryResponse'
import type { PlayerStatsStatus } from '../../models/PlayerStats'

export interface PlayerStatsSummaryDto {
  matchesPlayed: number
  victories: number
  defeats: number
  winRate: number | null
  trackedMatches: number
  totalBalls: number
}

export interface PlayerStatsEvolutionPointDto {
  matchId: number
  date: string
  average: number
  victory: boolean
}

export interface PlayerStatsByNatureDto {
  nature: string
  matchCount: number
  ballCount: number
  average: number
}

export interface PlayerStatsByFormatDto {
  type: string
  matchCount: number
  victories: number
  ballCount: number
  average: number
}

export interface PlayerStatsByDistanceDto {
  bucket: string
  ballCount: number
  average: number
}

export interface PlayerStatsResponseDto {
  status: PlayerStatsStatus
  playerId: number | null
  displayName: string | null
  summary: PlayerStatsSummaryDto
  overall: MatchSummaryShotBreakdownDto | null
  point: MatchSummaryShotBreakdownDto | null
  tir: MatchSummaryShotBreakdownDto | null
  evolution: PlayerStatsEvolutionPointDto[]
  byNature: PlayerStatsByNatureDto[]
  byFormat: PlayerStatsByFormatDto[]
  byDistance: PlayerStatsByDistanceDto[]
}
