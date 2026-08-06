export interface MatchSummaryShotBreakdown {
  average: number
  p2: number
  p1: number
  p0: number
  m1: number
  m2: number
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
}

export interface MatchSummary {
  matchId: number
  scoreA: number
  scoreB: number
  winner: 'A' | 'B'
  ends: number
  players: MatchSummaryPlayer[]
}
