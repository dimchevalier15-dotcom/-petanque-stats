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

export interface EndShot {
  /** 1-based chronological order within the end (global across all players). */
  sequenceOrder: number
  playerId: number
  note: BallNote
  shotType: 'point' | 'tir'
  distance: number | null
  isCochonnet?: boolean
}

/** @deprecated Legacy per-player ball arrays. Use EndShot on EndRecord instead. */
export interface EndBallEntry {
  playerId: number
  notes: BallNote[]
  shotTypes: ('point' | 'tir')[]
  distances: (number | null)[]
  isCochonnet?: boolean[]
}

export interface EndRecord {
  index: number // 1-based
  shots: EndShot[]
  winner?: TeamSide
  points?: number
  canceled?: boolean
  roles?: Record<number, PlayerRole>
}
