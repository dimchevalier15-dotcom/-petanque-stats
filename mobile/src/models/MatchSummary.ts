export interface MatchSummaryShotBreakdown {
  average: number
  p2: number
  p1: number
  p0: number
  m1: number
  m2: number
  successRate: number | null
}

export interface MatchSummaryEndTotal {
  endIndex: number
  total: number
}

export interface MatchSummaryPlayer {
  playerId: number
  firstName: string
  lastName: string
  nickname: string
  team: 'A' | 'B'
  average: number
  p2: number
  p1: number
  p0: number
  m1: number
  m2: number
  point?: MatchSummaryShotBreakdown | null
  tir?: MatchSummaryShotBreakdown | null
  cochonnet?: MatchSummaryShotBreakdown | null
  endTotals?: MatchSummaryEndTotal[]
}

export interface MatchSummary {
  matchId: number
  scoreA: number
  scoreB: number
  winner: 'A' | 'B'
  ends: number
  type?: 'tete_a_tete' | 'doublette' | 'triplette'
  endIndexes?: number[]
  canceledEndIndexes?: number[]
  players: MatchSummaryPlayer[]
  myMatchPlayerId?: number | null
  myHasValidatedMatch?: boolean | null
}
