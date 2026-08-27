import axios from 'axios'
import { ref } from 'vue'
import type { LiveMatchData } from '../models/LiveMatch'
import { liveMatchesService } from '../services/liveMatches'
import {
  buildLiveMatchUrl,
  clearLiveMatchUuid,
  loadLiveMatchUuid,
  saveLiveMatchUuid,
} from '../services/liveMatchStorage'

export function useLiveMatchSync(matchId: number, buildData: () => LiveMatchData) {
  const uuid = ref<string | null>(loadLiveMatchUuid(matchId))
  const liveUrl = ref<string | null>(uuid.value ? buildLiveMatchUrl(uuid.value) : null)
  const isActive = ref(uuid.value !== null)
  let syncInFlight: Promise<void> | null = null

  function markInactive(): void {
    clearLiveMatchUuid()
    uuid.value = null
    liveUrl.value = null
    isActive.value = false
  }

  async function verifyRemoteStatus(): Promise<void> {
    if (!uuid.value) {
      return
    }

    try {
      const response = await liveMatchesService.get(uuid.value)
      if (response.status === 'finished') {
        markInactive()
      }
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.status === 404) {
        markInactive()
      }
    }
  }

  async function sync(): Promise<void> {
    if (!uuid.value) {
      return
    }

    if (syncInFlight) {
      return syncInFlight
    }

    syncInFlight = (async () => {
      try {
        await liveMatchesService.update(uuid.value!, { data: buildData() })
      } catch (error) {
        if (axios.isAxiosError(error)) {
          if (error.response?.status === 404 || error.response?.status === 409) {
            markInactive()
          }
        }
      }
    })().finally(() => {
      syncInFlight = null
    })

    return syncInFlight
  }

  async function startLive(): Promise<string | null> {
    if (uuid.value) {
      await verifyRemoteStatus()
      if (!uuid.value) {
        return null
      }
      await sync()
      return liveUrl.value
    }

    try {
      const response = await liveMatchesService.create({ data: buildData() })
      uuid.value = response.uuid
      liveUrl.value = response.url || buildLiveMatchUrl(response.uuid)
      isActive.value = true
      saveLiveMatchUuid(matchId, response.uuid)
      return liveUrl.value
    } catch {
      return null
    }
  }

  async function finishLive(): Promise<void> {
    if (!uuid.value) {
      return
    }

    const currentUuid = uuid.value

    try {
      await liveMatchesService.update(currentUuid, { data: buildData() })
      await liveMatchesService.finish(currentUuid)
    } catch {
      // Best-effort: local cleanup still happens so scoring is never blocked.
    } finally {
      markInactive()
    }
  }

  return {
    uuid,
    liveUrl,
    isActive,
    startLive,
    sync,
    finishLive,
    verifyRemoteStatus,
  }
}
