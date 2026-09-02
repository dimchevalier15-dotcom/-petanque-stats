import type { MatchInsightsHeldEndError, MatchInsightsMarkingRate, MatchInsightsMarkingTeam } from './MatchInsights'

export type PlayerTacticalInsightsStatus = 'ok' | 'no_player' | 'no_matches' | 'no_data_in_period' | 'no_eligible_matches'

export interface PlayerTacticalInsightsByDistance {
  bucket: string
  point: MatchInsightsMarkingRate
  tir: MatchInsightsMarkingRate
}

export interface PlayerTacticalInsightsCoverage {
  matchesEligible: number
  matchesAnalyzed: number
  endsAnalyzed: number
  distanceSampleRate: number
}

export interface PlayerTacticalInsights {
  status: PlayerTacticalInsightsStatus
  reason?: PlayerTacticalInsightsStatus
  markingOverall?: MatchInsightsMarkingTeam
  rajoutOverall?: MatchInsightsMarkingTeam
  heldEndError?: MatchInsightsHeldEndError
  markingByDistance: PlayerTacticalInsightsByDistance[]
  rajoutByDistance: PlayerTacticalInsightsByDistance[]
  coverage?: PlayerTacticalInsightsCoverage
}
