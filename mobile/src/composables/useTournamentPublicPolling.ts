import axios from 'axios'
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { TournamentDetail } from '../models/TournamentDetail'
import { tournamentsService } from '../services/tournaments'
import { tournamentErrorMessage } from '../utils/tournamentError'

const DEFAULT_POLL_INTERVAL_MS = 120_000

export function useTournamentPublicPolling(publicUuid: string, pollIntervalMs = DEFAULT_POLL_INTERVAL_MS) {
  const { t, te } = useI18n()
  const detail = ref<TournamentDetail | null>(null)
  const loading = ref(true)
  const notFound = ref(false)
  const error = ref('')
  let timer: ReturnType<typeof setInterval> | null = null

  async function fetchDetail(): Promise<void> {
    try {
      detail.value = await tournamentsService.getPublic(publicUuid)
      notFound.value = false
      error.value = ''
    } catch (caught) {
      if (axios.isAxiosError(caught) && caught.response?.status === 404) {
        notFound.value = true
        error.value = ''
        return
      }
      error.value = tournamentErrorMessage(caught, t, te)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    void fetchDetail()
    timer = setInterval(() => {
      void fetchDetail()
    }, pollIntervalMs)
  })

  onUnmounted(() => {
    if (timer !== null) {
      clearInterval(timer)
    }
  })

  return { detail, loading, notFound, error, refresh: fetchDetail }
}
