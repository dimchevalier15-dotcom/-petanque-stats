import type { MatchType, PlayerRole, ShotType, StatisticsMode } from './Match'
import type { EndRecord, TeamSubstitution } from './MatchPlay'

export interface LiveMatchData {
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, ShotType>
  startingRoles: Record<number, PlayerRole>
  currentEndIndex: number
  ends: EndRecord[]
  distanceEstimate: number | null
  currentRoles: Record<number, PlayerRole>
  substitutions?: TeamSubstitution[]
  playerNames: Record<number, string>
  shortPlayerNames: Record<number, string>
  teamALabel?: string
  teamBLabel?: string
}
