import type { EndRecord } from '../models/MatchPlay'
import type { MatchDraft, MatchPlayState, MatchSetup } from '../models/MatchDraft'

const STORAGE_KEY = 'match_draft'

function normalizeEnd(end: EndRecord): EndRecord {
  return {
    ...end,
    balls: end.balls.map((ball) => ({
      ...ball,
      distances: ball.distances ?? ball.notes.map(() => null),
    })),
  }
}

function normalizeDraft(raw: MatchDraft): MatchDraft {
  return {
    ...raw,
    ends: raw.ends.map(normalizeEnd),
  }
}

function isValidDraft(value: unknown): value is MatchDraft {
  if (!value || typeof value !== 'object') return false
  const d = value as MatchDraft
  return (
    d.version === 1 &&
    typeof d.id === 'number' &&
    typeof d.type === 'string' &&
    Array.isArray(d.teamA) &&
    Array.isArray(d.teamB) &&
    Array.isArray(d.ends) &&
    typeof d.currentEndIndex === 'number'
  )
}

/**
 * Persists the in-progress match in localStorage.
 * Works in Capacitor WebView (Play Store builds): same API as in the browser.
 */
export function loadMatchDraft(expectedUserId: number | null): MatchDraft | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed: unknown = JSON.parse(raw)
    if (!isValidDraft(parsed)) {
      clearMatchDraft()
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

export function saveMatchDraft(
  setup: MatchSetup,
  playState: MatchPlayState,
  userId: number | null,
): void {
  try {
    const draft: MatchDraft = {
      version: 1,
      userId,
      savedAt: new Date().toISOString(),
      ...setup,
      currentEndIndex: playState.currentEndIndex,
      ends: playState.ends.map(normalizeEnd),
      distanceEstimate: playState.distanceEstimate,
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
  } catch {
    // Private mode or quota exceeded: play continues, but won't survive a reload.
  }
}

export function clearMatchDraft(): void {
  try {
    localStorage.removeItem(STORAGE_KEY)
  } catch {
    // ignore
  }
}

export function hasMatchDraft(expectedUserId: number | null): boolean {
  return loadMatchDraft(expectedUserId) !== null
}
