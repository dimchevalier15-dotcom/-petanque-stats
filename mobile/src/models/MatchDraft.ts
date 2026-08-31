import type { MatchType, PlayerRole, ShotType, StatisticsMode } from './Match'
import type { EndRecord, TeamSubstitution } from './MatchPlay'

/**
 * A match participant, identified across the whole play engine by its id.
 *
 * A positive id references a persisted Player. A negative id references a provisional
 * participant, known only inside the local draft until it is resolved at the end of the
 * match. See ADR-001.
 */
export interface MatchParticipant {
  id: number
  label: string
  shortLabel: string
}

export interface MatchSetup {
  /** Local draft id, also used as the play route parameter. */
  id: number
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  teamAName: string | null
  teamBName: string | null
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, ShotType>
  startingRoles: Record<number, PlayerRole>
  participants: MatchParticipant[]
  /** ISO date-time the match was started, sent as the match date. */
  startedAt: string
}

export interface MatchPlayState {
  currentEndIndex: number
  ends: EndRecord[]
  distanceEstimate: number | null
  currentRoles: Record<number, PlayerRole>
  substitutions?: TeamSubstitution[]
  /** Score before the first recorded end (joining a match already in progress). */
  openingScoreA?: number
  openingScoreB?: number
}

/**
 * Progress of the deferred save, persisted so that a failed attempt can resume without
 * creating duplicates. See ADR-001.
 */
export interface MatchDraftProgress {
  /** Server match id, null until the match has been created. */
  serverId: number | null
  /** Provisional participant id -> persisted Player id. */
  resolvedPlayers: Record<number, number>
}

export type MatchDraftOwner = 'guest' | 'user'

export interface MatchDraft extends MatchSetup, MatchPlayState, MatchDraftProgress {
  version: 2
  userId: number | null
  savedAt: string
  /** Distinguishes guest-only drafts from logged-out user drafts (both may use userId null). */
  draftOwner?: MatchDraftOwner
}

/** Draft format used before ADR-001, still readable so an ongoing match survives an update. */
export interface MatchDraftV1 extends MatchPlayState {
  version: 1
  userId: number | null
  savedAt: string
  /** Server match id: the match was created up-front in this format. */
  id: number
  type: MatchType
  targetScore: number
  statisticsMode: StatisticsMode
  teamA: number[]
  teamB: number[]
  trackedPlayers: number[]
  defaultShotTypes?: Record<number, ShotType>
  startingRoles?: Record<number, PlayerRole>
}

export function emptyDraftProgress(): MatchDraftProgress {
  return { serverId: null, resolvedPlayers: {} }
}
