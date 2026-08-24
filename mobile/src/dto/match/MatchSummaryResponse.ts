export interface MatchSummaryShotBreakdownDto {
  average: number
  p2: number
  p1: number
  p0: number
  m1: number
  m2: number
  successRate: number | null
}

export interface MatchSummaryEndTotalDto {
  endIndex: number
  total: number
}

export interface MatchSummaryPlayerDto {
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
  point?: MatchSummaryShotBreakdownDto | null
  tir?: MatchSummaryShotBreakdownDto | null
  endTotals?: MatchSummaryEndTotalDto[]
}

export interface MatchSummaryResponseDto {
  matchId: number
  scoreA: number
  scoreB: number
  winner: 'A' | 'B'
  ends: number
  type?: 'tete_a_tete' | 'doublette' | 'triplette'
  endIndexes?: number[]
  players: MatchSummaryPlayerDto[]
}
