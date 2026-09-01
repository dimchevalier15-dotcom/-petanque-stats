import type { EndRecord } from '../models/MatchPlay'
import {
  emptyDraftProgress,
  type MatchDraft,
  type MatchDraftProgress,
  type MatchDraftV1,
  type MatchPlayState,
  type MatchSetup,
} from '../models/MatchDraft'
import { inferStartingRoles } from '../utils/matchRoles'
import { ensureEndHasShotStructure, migrateLegacyBallsToShots } from '../utils/matchEndShots'

const STORAGE_KEY = 'match_draft'

type LegacyEndRecord = EndRecord & { balls?: import('../models/MatchPlay').EndBallEntry[] }

function normalizeEnd(end: LegacyEndRecord): EndRecord {
  if (!Array.isArray(end.shots)) {
    end.shots = end.balls?.length ? migrateLegacyBallsToShots(end.balls) : []
  }
  ensureEndHasShotStructure(end)
  return end
}

function normalizeDraft(raw: MatchDraft): MatchDraft {
  const startingRoles = inferStartingRoles(
    raw.type,
    raw.teamA,
    raw.teamB,
    raw.defaultShotTypes,
    raw.startingRoles,
  )

  const ends = raw.ends.map(normalizeEnd)
  const rolesOfCurrentEnd = ends[raw.currentEndIndex]?.roles
  const currentRoles =
    raw.currentRoles && Object.keys(raw.currentRoles).length > 0
      ? { ...raw.currentRoles }
      : { ...(rolesOfCurrentEnd ?? startingRoles) }

  return {
    ...raw,
    startingRoles,
    currentRoles,
    ends,
  }
}

/**
 * Before ADR-001 the match was created on the server up-front, so the stored id was the
 * server id and no participant label was kept. An ongoing match must stay playable and
 * savable across the update.
 */
function migrateFromV1(draft: MatchDraftV1): MatchDraft {
  return {
    version: 3,
    userId: draft.userId,
    savedAt: draft.savedAt,
    id: draft.id,
    serverId: draft.id,
    resolvedPlayers: {},
    type: draft.type,
    targetScore: draft.targetScore,
    statisticsMode: draft.statisticsMode,
    teamA: draft.teamA,
    teamB: draft.teamB,
    teamAName: null,
    teamBName: null,
    trackedPlayers: draft.trackedPlayers,
    defaultShotTypes: draft.defaultShotTypes ?? {},
    startingRoles: draft.startingRoles ?? {},
    participants: [],
    startedAt: draft.savedAt,
    currentEndIndex: draft.currentEndIndex,
    ends: draft.ends,
    distanceEstimate: draft.distanceEstimate,
    currentRoles: draft.currentRoles,
    substitutions: draft.substitutions ?? [],
    openingScoreA: 0,
    openingScoreB: 0,
  }
}

function hasDraftShape(value: unknown): value is MatchDraft | MatchDraftV1 {
  if (!value || typeof value !== 'object') return false
  const draft = value as MatchDraft
  return (
    typeof draft.id === 'number' &&
    typeof draft.type === 'string' &&
    Array.isArray(draft.teamA) &&
    Array.isArray(draft.teamB) &&
    Array.isArray(draft.ends) &&
    typeof draft.currentEndIndex === 'number'
  )
}

function parseDraft(value: unknown): MatchDraft | null {
  if (!hasDraftShape(value)) {
    return null
  }
  if (value.version === 1) {
    return migrateFromV1(value)
  }
  if (value.version === 2 || value.version === 3) {
    return normalizeDraft({ ...value, version: 3, resolvedPlayers: value.resolvedPlayers ?? {} })
  }
  return null
}

/**
 * Persists the in-progress match in localStorage.
 * Works in Capacitor WebView (Play Store builds): same API as in the browser.
 */
export interface LoadMatchDraftOptions {
  guest?: boolean
}

function isGuestDraft(draft: MatchDraft): boolean {
  return draft.draftOwner === 'guest'
}

export function loadMatchDraft(
  expectedUserId: number | null,
  options?: LoadMatchDraftOptions,
): MatchDraft | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = parseDraft(JSON.parse(raw) as unknown)
    if (parsed === null) {
      clearMatchDraft()
      return null
    }
    if (options?.guest) {
      return isGuestDraft(parsed) ? normalizeDraft(parsed) : null
    }
    if (isGuestDraft(parsed)) {
      return null
    }
    if (parsed.userId !== expectedUserId) {
      return null
    }
    return normalizeDraft(parsed)
  } catch {
    return null
  }
}

function loadStoredDraft(): MatchDraft | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    return parseDraft(JSON.parse(raw) as unknown)
  } catch {
    return null
  }
}

function readProgress(setupId: number): MatchDraftProgress {
  const stored = loadStoredDraft()
  if (stored === null || stored.id !== setupId) {
    return emptyDraftProgress()
  }
  return { serverId: stored.serverId, resolvedPlayers: stored.resolvedPlayers }
}

export interface SaveMatchDraftOptions {
  guest?: boolean
}

export function saveMatchDraft(
  setup: MatchSetup,
  playState: MatchPlayState,
  userId: number | null,
  options?: SaveMatchDraftOptions,
): void {
  try {
    const progress = readProgress(setup.id)
    const isGuest = options?.guest === true
    const draft: MatchDraft = {
      version: 3,
      userId: isGuest ? null : userId,
      draftOwner: isGuest ? 'guest' : 'user',
      savedAt: new Date().toISOString(),
      ...progress,
      ...setup,
      currentEndIndex: playState.currentEndIndex,
      ends: playState.ends.map(normalizeEnd),
      distanceEstimate: playState.distanceEstimate,
      currentRoles: { ...playState.currentRoles },
      substitutions: playState.substitutions?.map((sub) => ({ ...sub })) ?? [],
      openingScoreA: playState.openingScoreA ?? 0,
      openingScoreB: playState.openingScoreB ?? 0,
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
  } catch {
    // Private mode or quota exceeded: play continues, but won't survive a reload.
  }
}

/** Records how far the deferred save went, so a retry never duplicates anything. */
export function saveMatchDraftProgress(progress: Partial<MatchDraftProgress>): void {
  try {
    const stored = loadStoredDraft()
    if (stored === null) return
    const draft: MatchDraft = {
      ...stored,
      serverId: progress.serverId ?? stored.serverId,
      resolvedPlayers: { ...stored.resolvedPlayers, ...progress.resolvedPlayers },
      savedAt: new Date().toISOString(),
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
  } catch {
    // Nothing else to do: the save flow reports the failure to the user.
  }
}

export function clearMatchDraft(options?: LoadMatchDraftOptions): void {
  try {
    const stored = loadStoredDraft()
    if (stored && options?.guest && !isGuestDraft(stored)) {
      return
    }
    if (stored && !options?.guest && isGuestDraft(stored)) {
      return
    }
    localStorage.removeItem(STORAGE_KEY)
  } catch {
    // ignore
  }
}

export function hasMatchDraft(
  expectedUserId: number | null,
  options?: LoadMatchDraftOptions,
): boolean {
  return loadMatchDraft(expectedUserId, options) !== null
}

/** Converts a finished guest draft into a user-owned draft ready for server save. */
export function transferGuestDraftToUser(userId: number, draft: MatchDraft): void {
  try {
    const transferred: MatchDraft = {
      ...draft,
      userId,
      draftOwner: 'user',
      savedAt: new Date().toISOString(),
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(transferred))
  } catch {
    // Save flow will surface failures to the user.
  }
}
