import type { PlayerRole } from './Match'

export type TeamSide = 'A' | 'B'

export interface TeamSubstitution {
  team: TeamSide
  outPlayerId: number
  inPlayerId: number
  /** 1-based end index when the substitution takes effect */
  fromEndIndex: number
}

// Allowed ball notes by mode: standard: -2,-1,0,1,2; simple: -1,1
export type BallNote = -2 | -1 | 0 | 1 | 2

export interface EndBallEntry {
  playerId: number
  notes: BallNote[]
  shotTypes: ('point' | 'tir')[]
  // Optional distance in meters for each ball, aligned with notes by index. null = not set.
  distances: (number | null)[]
  // Cochonnet shots are tracked separately and excluded from tir statistics.
  isCochonnet?: boolean[]
}

export interface EndRecord {
  index: number // 1-based
  balls: EndBallEntry[]
  winner?: TeamSide
  points?: number
  canceled?: boolean
  roles?: Record<number, PlayerRole>
}
