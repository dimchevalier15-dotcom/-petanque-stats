export type TeamSide = 'A' | 'B'

// Allowed ball notes by mode: standard: -2,-1,0,1,2; simple: -1,1
export type BallNote = -2 | -1 | 0 | 1 | 2

export interface EndBallEntry {
  playerId: number
  notes: BallNote[]
}

export interface EndRecord {
  index: number // 1-based
  balls: EndBallEntry[]
  winner?: TeamSide
  points?: number
}
