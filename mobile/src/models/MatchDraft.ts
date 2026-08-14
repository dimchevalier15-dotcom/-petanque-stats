import type { MatchType, PlayerRole, ShotType, StatisticsMode } from './Match'
import type { EndRecord } from './MatchPlay'

export interface MatchSetup {
  id: number
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, ShotType>
  startingRoles: Record<number, PlayerRole>
}

export interface MatchPlayState {
  currentEndIndex: number
  ends: EndRecord[]
  distanceEstimate: number | null
  currentRoles: Record<number, PlayerRole>
}

export interface MatchDraft extends MatchSetup, MatchPlayState {
  version: 1
  userId: number | null
  savedAt: string
}
