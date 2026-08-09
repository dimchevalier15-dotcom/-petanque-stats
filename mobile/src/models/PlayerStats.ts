import type { MatchSummaryShotBreakdown } from './MatchSummary'

export type PlayerStatsStatus = 'ok' | 'no_player' | 'no_matches' | 'no_tracked_data'

export interface PlayerStatsSummary {
  matchesPlayed: number
  victories: number
  defeats: number
  winRate: number | null
  trackedMatches: number
  totalBalls: number
}

export interface PlayerStatsEvolutionPoint {
  matchId: number
  date: string
  average: number
  victory: boolean
}

export interface PlayerStatsByNature {
  nature: string
  matchCount: number
  ballCount: number
  average: number
}

export interface PlayerStats {
  status: PlayerStatsStatus
  playerId: number | null
  displayName: string | null
  summary: PlayerStatsSummary
  overall: MatchSummaryShotBreakdown | null
  point: MatchSummaryShotBreakdown | null
  tir: MatchSummaryShotBreakdown | null
  evolution: PlayerStatsEvolutionPoint[]
  byNature: PlayerStatsByNature[]
}
