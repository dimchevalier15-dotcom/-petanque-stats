export type MatchInsightsStatus = 'ok' | 'unavailable'

export interface MatchInsightsTeam {
  team: 'A' | 'B'
  endsWon: number
  endsOpened: number
  firstShotAverage: number
  capitalizedCount: number
  capitalizationOpportunities: number
  avgPointsWhenCapitalizing: number
  defendedCount: number
  defenseSituations: number
  avgPointsConcededWhenDefending: number
  reclaimsCount: number
}

export interface MatchInsightsMarkingRate {
  made: number
  attempts: number
  rate: number | null
}

export interface MatchInsightsMarkingTeam {
  point: MatchInsightsMarkingRate
  tir: MatchInsightsMarkingRate
}

export type MatchInsightsRajoutTeam = MatchInsightsMarkingTeam

export interface MatchInsightsPointDominance {
  endsWonWhenOpened: number
  endsOpened: number
  endsOpenedWellAndWon: number
  endsOpenedWell: number
}

export interface MatchInsightsEndSequenceDominance {
  endsDominated: number
  endsWonWhileDominating: number
  pointsOnDominatedEnds: number
  totalPointsScored: number
}

export interface MatchInsightsDistanceTeam {
  average: number
  balls: number
  pointSuccessRate: number | null
}

export interface MatchInsightsByDistance {
  bucket: string
  teamA: MatchInsightsDistanceTeam | null
  teamB: MatchInsightsDistanceTeam | null
  dominantTeam: 'A' | 'B' | null
}

export interface MatchInsightsDistanceOutlook {
  singleDominantTeam: 'A' | 'B' | null
  competitiveBuckets: MatchInsightsByDistance[]
}

export interface MatchInsightsHeldEndError {
  minusTwoCount: number
  ballsPlayed: number
  rate: number | null
}

export interface MatchInsightsCoverage {
  distanceSampleRate: number
  endsAnalyzed: number
}

export interface MatchInsights {
  status: MatchInsightsStatus
  reason?: 'not_all_tracked' | 'invalid_sequence' | 'no_data'
  teamA?: MatchInsightsTeam
  teamB?: MatchInsightsTeam
  markingTeamA?: MatchInsightsMarkingTeam
  markingTeamB?: MatchInsightsMarkingTeam
  rajoutTeamA?: MatchInsightsRajoutTeam
  rajoutTeamB?: MatchInsightsRajoutTeam
  heldEndErrorTeamA?: MatchInsightsHeldEndError
  heldEndErrorTeamB?: MatchInsightsHeldEndError
  pointDominanceTeamA?: MatchInsightsPointDominance
  pointDominanceTeamB?: MatchInsightsPointDominance
  endSequenceDominanceTeamA?: MatchInsightsEndSequenceDominance
  endSequenceDominanceTeamB?: MatchInsightsEndSequenceDominance
  distanceOutlook?: MatchInsightsDistanceOutlook
  coverage?: MatchInsightsCoverage
}
