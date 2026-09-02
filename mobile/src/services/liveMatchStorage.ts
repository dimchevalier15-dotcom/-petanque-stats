const STORAGE_KEY = 'live_match_uuid'

interface StoredLiveMatch {
  matchId: number
  uuid: string
}

function readStored(): StoredLiveMatch | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }
    const parsed = JSON.parse(raw) as StoredLiveMatch
    if (typeof parsed.matchId !== 'number' || typeof parsed.uuid !== 'string' || parsed.uuid === '') {
      return null
    }
    return parsed
  } catch {
    return null
  }
}

export function loadLiveMatchUuid(matchId: number): string | null {
  const stored = readStored()
  if (stored === null || stored.matchId !== matchId) {
    return null
  }
  return stored.uuid
}

export function saveLiveMatchUuid(matchId: number, uuid: string): void {
  const payload: StoredLiveMatch = { matchId, uuid }
  localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
}

export function clearLiveMatchUuid(): void {
  localStorage.removeItem(STORAGE_KEY)
}

import { getPublicAppBaseUrl } from '../utils/getPublicAppBaseUrl'

export function buildLiveMatchUrl(uuid: string): string {
  return `${getPublicAppBaseUrl()}/live/${uuid}`
}

export function buildLiveMatchOverlayUrl(uuid: string): string {
  return `${getPublicAppBaseUrl()}/live/${uuid}/overlay`
}
