import type { MatchType, StatisticsMode } from './Match'
import type { EndRecord } from './MatchPlay'

export interface MatchSetup {
  id: number
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, 'point' | 'tir'>
}

export interface MatchPlayState {
  currentEndIndex: number
  ends: EndRecord[]
  distanceEstimate: number | null
}

export interface MatchDraft extends MatchSetup, MatchPlayState {
  version: 1
  userId: number | null
  savedAt: string
}
