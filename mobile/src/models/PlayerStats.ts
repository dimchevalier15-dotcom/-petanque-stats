import type { MatchSummaryShotBreakdown } from './MatchSummary'
import type { MatchNature } from './MatchContext'

export type PlayerStatsStatus = 'ok' | 'no_player' | 'no_matches' | 'no_tracked_data' | 'no_data_in_period'

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
  nature: MatchNature
  matchCount: number
  ballCount: number
  average: number
}

export interface PlayerStatsByFormat {
  type: 'tete_a_tete' | 'doublette' | 'triplette'
  matchCount: number
  victories: number
  ballCount: number
  average: number
}

export type DistanceBucketKey = 'under_6' | '6_7' | '7_8' | '8_9' | '9_10' | '10_plus'

export const DISTANCE_BUCKET_KEYS: DistanceBucketKey[] = [
  'under_6',
  '6_7',
  '7_8',
  '8_9',
  '9_10',
  '10_plus',
]

export interface PlayerStatsByDistance {
  bucket: DistanceBucketKey
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
  byFormat: PlayerStatsByFormat[]
  byDistance: PlayerStatsByDistance[]
}
